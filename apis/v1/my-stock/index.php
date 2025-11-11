<?php
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_inventory.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_sources.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_curl.php';

$module = "my-stock";

// 
$api_user_id = $GLOBALS['api_user']['uid'] ?? null;
$dealer_id = $GLOBALS['dealership']['dealership_id'] ?? null;
$branch_id = $GLOBALS['dealership']['branch_id'] ?? null;
$role_main = $GLOBALS['dealership']['role_main'] ?? null;
$executive_id = 0; $executive_name = '';

if( $dealer_id != $api_user_id )
{
    $executive_id = $api_user_id;
    $executive_name = $GLOBALS['dealership']['name'];
}
$executive_id = ($role_main == 'y') ? 0 : $api_user_id;

// Handle ID for constructor - always decrypt
$constructor_id = 0;
if (!empty($_POST['id'])) {
    $constructor_id = (int)data_decrypt($_POST['id']);
}
$inventory = new MyStock($constructor_id);
$inventory->dealer_id = $dealer_id;
$inventory->executive_id = $executive_id;
$inventory->executive_name = $executive_name;
$inventory->login_user_id = $api_user_id;
$inventory->branch_id = $branch_id;

$files = new Files();

$action = strtolower($_REQUEST['action'] ?? '');

switch ($action) {
    case 'getconfig':
        $moduleConfig = new moduleConfig();
        api_response(200, 'ok', 'Configuration retrieved successfully.', [
            'config' => $moduleConfig->getConfig($module)
        ]);
        break;

    case 'getlead':
        try {
            if( !isset($_POST['id']) || empty($_POST['id']) )
            {
                api_response(400, 'fail', 'Validation failed.',[],[],'Lead id is required.');
            }
            $encrypted_id = $_POST['id'];

            // Decrypt the ID to get the actual numeric ID
            $lead_id = data_decrypt($encrypted_id);
            
            if(!is_numeric($lead_id) || $lead_id <= 0) {
                api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
            }
            
            // Additional safety check - explicitly cast to int
            $lead_id = (int)$lead_id;
            
            // Check ownership before retrieving data
            if(!$inventory->ownerCheck($lead_id)) {
                api_response(403, 'fail', 'Access denied.', [], []);
            }

            try {
                $lead_details = $inventory->getLead($lead_id);
                if(empty($lead_details)) {
                    api_response(404, 'fail', 'Lead not found.', [], []);
                }
            } catch (Exception $getLeadException) {
                api_response(500, 'fail', 'Error retrieving lead details.');
            }
            $moduleConfig = new moduleConfig();
            $lead_details['menu'] = $moduleConfig->getConfig($module, 'menu', $lead_details);

            api_response(200,'ok', 'Lead fetched',$lead_details);
            
        } catch (Exception $e) {
            api_response(500, 'fail', 'Internal server error. Please try again later.');
        }
        break;
     case 'updatestatus':
        try {
            $errors = [];
            $enc_inventory_id = $_POST['id'] ?? '';
            $new_status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
            
            // Additional history tracking parameters
            $action_type = $_POST['action_type'] ?? 'status_update';
            $notes = $_POST['notes'] ?? $_POST['approval_notes'] ?? $_POST['rejection_notes'] ?? ''; // Use single notes field, check multiple possible field names
            
            // New certification approval fields
            $certified_by = $_POST['certified_by'] ?? '';
            $certified_date = $_POST['certified_date'] ?? '';

            if (empty($enc_inventory_id)) { $errors['id'] = 'Inventory ID is required'; }
            if ($new_status <= 0) { $errors['status'] = 'Valid status is required'; }

            if (!empty($errors)) {
                api_response(400,'fail','Validation failed',[],[],$errors);
            }

            $inventory_id = data_decrypt($enc_inventory_id);
            if (!is_numeric($inventory_id) || $inventory_id <= 0) {
                api_response(400,'fail','Invalid inventory id',[],[],['id'=>'Invalid inventory id']);
            }
            $inventory_id = (int)$inventory_id;

            if (!$inventory->ownerCheck($inventory_id)) {
                api_response(403,'fail','Access denied',[],[]);
            }

            // Get current inventory details for history tracking
            $current_query = "SELECT status FROM inventory WHERE id = ? AND dealer = ?";
            $current_stmt = mysqli_prepare($connection, $current_query);
            mysqli_stmt_bind_param($current_stmt, 'ii', $inventory_id, $dealer_id);
            mysqli_stmt_execute($current_stmt);
            $current_result = mysqli_stmt_get_result($current_stmt);
            $current_data = mysqli_fetch_assoc($current_result);
            $old_status = $current_data['status'] ?? 0;

            // Handle file upload for certification documents
            $uploaded_documents = [];
            if (!empty($_FILES['certified_documents'])) {
                require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
                $fileHandler = new Files();
                
                $files = $_FILES['certified_documents'];
                if (is_array($files['name'])) {
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] == UPLOAD_ERR_OK) {
                            $tempFile = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];
                            
                            $uploadResult = $fileHandler->uploadFile($tempFile, 'certification_docs');
                            if ($uploadResult['success']) {
                                $uploaded_documents[] = $uploadResult['filename'];
                            }
                        }
                    }
                }
            }

            // Start transaction
            mysqli_begin_transaction($connection);

            // Update inventory status
            $update_query = "UPDATE inventory SET 
                           status = ?,
                           updated_by = ?,
                           updated_on = NOW()
                           WHERE id = ? AND dealer = ?";
            
            $stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($stmt, 'iiii', $new_status, $api_user_id, $inventory_id, $dealer_id);
            $result = mysqli_stmt_execute($stmt);

            if ($result && mysqli_stmt_affected_rows($stmt) > 0) {
                // Prepare history data
                $remarks = "Status changed from {$old_status} to {$new_status}";
                
                // Prepare metadata JSON for certification approval
                $metadata = [];
                if (!empty($certified_by)) {
                    $metadata['certified_by'] = $certified_by;
                }
                if (!empty($certified_date)) {
                    $metadata['certified_date'] = $certified_date;
                }
                if (!empty($uploaded_documents)) {
                    $metadata['certified_documents'] = $uploaded_documents;
                }
                $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
                
                // Insert history record with certification metadata (using notes field)
                $history_query = "INSERT INTO inventory_history 
                                (inventory_id, status, action_type, notes, remarks, metadata, created_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $hist_stmt = mysqli_prepare($connection, $history_query);
                mysqli_stmt_bind_param($hist_stmt, 'iissssi', 
                    $inventory_id, $new_status, $action_type, $notes, $remarks, $metadata_json, $api_user_id);
                mysqli_stmt_execute($hist_stmt);
                
                // Commit transaction
                mysqli_commit($connection);
                
                $status_name = '';
                switch($new_status) {
                    case 0: $status_name = 'Initial Status'; break;
                    case 1: $status_name = 'Refurbishment Details Pending'; break;
                    case 2: $status_name = 'Certification In Progress'; break;
                    case 3: $status_name = 'Need Certification Approval'; break;
                    case 4: $status_name = 'Ready For Sale'; break;
                    case 5: $status_name = 'Booked'; break;
                    default: $status_name = 'Unknown Status'; break;
                }
                api_response(200,'ok',"Status updated to {$status_name}");
            } else {
                mysqli_rollback($connection);
                api_response(500,'fail','Failed to update status');
            }
        } catch (Exception $e) {
            if (isset($connection)) {
                mysqli_rollback($connection);
            }
            api_response(500,'fail','Error updating status');
        }
        break;

    // case 'getleads':
    case 'getlist':
        try {
            // Input validation and sanitization
            $errors = [];
            $current_page = $_POST['current_page'] ?? 1;
            $per_page = $_POST['perPage'] ?? 10;
            
            // Validate pagination parameters
            $current_page = (int)$current_page;
            $per_page = (int)$per_page;
            if ($current_page < 1) $current_page = 1;
            if ($per_page < 1) $per_page = 10;
            if ($per_page > 100) $per_page = 100;
            
            // Validate search filters if provided
            if (!empty($_POST['reg_num']) && !validate_field_regex('reg_num', $_POST['reg_num'])) {
                $errors['reg_num'] = "Invalid registration number format.";
            }
            if (!empty($_POST['chassis']) && !validate_field_regex('chassis', $_POST['chassis'])) {
                $errors['chassis'] = "Invalid chassis number format.";
            }
            if (!empty($_POST['make']) && !validate_field_regex('id', $_POST['make'])) {
                $errors['make'] = "Invalid make format.";
            }
            if (!empty($_POST['model']) && !validate_field_regex('id', $_POST['model'])) {
                $errors['model'] = "Invalid model format.";
            }
            
            if (count($errors) > 0) {
                api_response(400, 'fail', 'Validation failed.', [], [], $errors);
            }
            
            // Collect search filters from direct POST parameters
            $filters = [];
            if (!empty($_POST['id'])) $filters['id'] = $_POST['id'];
            if (!empty($_POST['reg_num'])) $filters['reg_num'] = $_POST['reg_num'];
            if (!empty($_POST['chassis'])) $filters['chassis'] = $_POST['chassis'];
            if (!empty($_POST['make'])) $filters['make'] = $_POST['make'];
            if (!empty($_POST['model'])) $filters['model'] = $_POST['model'];
            
            // Handle status filtering from route slugs (similar to purchase-master)
            if (!empty($_POST['status']) && trim($_POST['status']) !== '' && $_POST['status'] !== 'undefined' && strtolower($_POST['status']) !== 'all') {
                $statuses = $inventory->getStatuses();
                $status_input = trim($_POST['status']);
                
                $mapped_status_id = null;
                
                // Method 1: Try direct slug lookup (e.g., "need-certification-approval")
                if (!empty($statuses[$status_input])) {
                    $mapped_status_id = $statuses[$status_input]['status_id'];
                } else {
                    // Method 2: Try converting the input to slug format and lookup
                    $status_as_slug = strtolower(str_replace(' ', '-', $status_input));
                    if (!empty($statuses[$status_as_slug])) {
                        $mapped_status_id = $statuses[$status_as_slug]['status_id'];
                    } else {
                        // Method 3: Try reverse lookup by label (case-insensitive)
                        foreach ($statuses as $slug => $data) {
                            if (strtolower($data['label']) === strtolower($status_input)) {
                                $mapped_status_id = $data['status_id'];
                                break;
                            }
                        }
                    }
                }
                
                if ($mapped_status_id !== null) {
                    $filters['status'] = $mapped_status_id;
                }
            }
            
            $leads = $inventory->getLeads($current_page, $per_page, $filters);
            
            if (!empty($leads['list'])) {
                api_response(200, 'ok', 'Inventory fetched', $leads);
            } else {
                api_response(200, 'ok', 'No inventory found', $leads);
            }
        } catch (Exception $e) {
            api_response(500, 'fail', 'Error retrieving inventory: ' . $e->getMessage());
        }
        break;
    case 'update':
        $sub_action = $_POST['sub_action'] ?? '';
        $error = [];
        if(empty($sub_action))
        {
            $error['sub_action'] = "Sub-action is required.";
        }
        if(count($error) > 0)
        {
            api_response(400,"fail","Validation failed",[],[],$error);
        }
      
        // updatelead sub-action
        if(!empty($sub_action) && $sub_action == "updatelead")
        {
            $errors = [];
            $moduleConfig = new moduleConfig();
            $config_data = $moduleConfig->getConfig($module)['addConfig'] ?? [];
            
            if (!isset($_POST['id'])) {
                $errors['id'] = "Stock ID is required";
            } else {
                $input_id = $_POST['id'];
                $lead_id = data_decrypt($input_id);            
                if( !is_numeric($lead_id) || $lead_id <= 0 )
                {
                   $errors['id'] = "Stock ID is not valid";
                }
            }
            if( !$inventory->ownerCheck($lead_id) )
            {
               api_response(403, 'fail', 'Access denied.', [], []);
            }

            $validation = validate_addconfig($config_data, $_POST);

            if (!empty($validation['errors'])) {
                api_response(400, 'fail', 'Validation Failed', [], [], $validation['errors']);
            }

            $status = $inventory->updateLead($validation['data'], $lead_id);
            if($status) api_response(200,'ok', 'Stock details updated successfully.', []);
            else api_response(400,'fail', 'Stock details not updated', []);
            break;
        }
        else if(!empty($sub_action) && $sub_action == 'updateExecutive') //upodate executive
        {
            if (!isset($_POST['branch'])) $errors['branch'] = "Branch is required";
            if (!isset($_POST['executive']) || !validate_field_regex('numeric', $_POST['executive'])) {
                $errors['executive'] = "Executive is not valid";
            }
            if (!empty($errors)) api_response(400, 'fail', 'Validation failed', [], [], $errors);

            $update_data = $_POST;
            $input_id = $_POST['id'];
            $lead_id = data_decrypt($input_id);            
            $update_data['id'] = $lead_id;
             
            $status = $inventory->updateExecutive($update_data, $lead_id);
            
            // Handle validation response format
            if (is_array($status)) {
                if ($status['status'] === false) {
                    api_response(400, 'fail', $status['message'] ?? 'Lead executive not updated.');
                }
                api_response(200, 'ok', 'Lead executive updated successfully.');
            }
            
            // Handle legacy boolean response
            $status
                ? api_response(200, 'ok', 'Lead executive updated successfully.')
                : api_response(400, 'fail', 'Lead executive not updated.');
        }
        else if(!empty($sub_action) && $sub_action == 'uploadimages') //uploadimages
        {
            $errors = [];
            $input_id = $_POST['id'] ?? '';
          
            if (empty($input_id)) {
                $errors['id'] = "Inventory ID is required";            
            } else {
                $inv_id = data_decrypt($input_id);
                if (!is_numeric($inv_id) || $inv_id < 0) {
                    $errors['id'] = "Inventory ID is not valid";
                }
            }
            
            if (!$inventory->ownerCheck($inv_id)) {
                $errors['access'] = "Access denied";
            }  
            
            if (!array_key_exists('image', $_FILES) || (empty($_FILES['image']) && !count($_FILES['image']))) {
                $errors['image'] = "Image not selected";
            }
            $image_tag = $_POST['tag'] ?? '';
            if (empty($image_tag)) $errors['tag'] = "Image tag is required.";
            if (!array_key_exists('image', $_FILES) || empty($_FILES['image'])) $errors['image'] = "Image not selected.";
            if (!empty($errors)) api_response(400, 'fail', 'Validation failed.', [], [], $errors);

            $file_rename = $inv_id . '-' . $dealer_id . '-' . round(microtime(true) * 1000) . '-' . rand(1000, 9999);
            $result = $files->uploadFiles('image', $file_rename, 'vimages');

            if ($result['status']) {
                $saveResult = $inventory->saveImage($result['file_name'], $inv_id, $image_tag);
                $saveResult['status']
                    ? api_response(200, 'ok', 'Image uploaded successfully.', $saveResult)
                    : api_response(400, 'fail', 'Image not saved.');
            } else {
                api_response(400, 'fail', 'Image upload failed.', [], [], $result['msg']);
            }
        }
        else if(!empty($sub_action) && $sub_action == 'deleteimage')
        {
            $errors = [];
            $input_id = $_POST['id'] ?? '';
            $image_id = $_POST['image_id'] ?? '';
            
            if (empty($input_id)) {
                $errors[] = "Inventory ID is required";            
            } else {
                $inv_id = data_decrypt($input_id);
                if (!is_numeric($inv_id) || $inv_id < 0) {
                    $errors[] = "Inventory ID is not valid";
                }
            }
            
            if (!$inventory->ownerCheck($inv_id)) {
                $errors[] = "Access denied";
            }
            
            if (!$inventory->findImage($image_id, $inv_id)) {
                $errors[] = "Image does not exist.";
            }
            
            if (count($errors) > 0) {
                api_response(400, 'fail', 'Validation failed.', [], $errors);
            }
            
            $res = $inventory->deleteImage($image_id, $inv_id);
            if ($res) {
                api_response(200, 'ok', 'Image deleted successfully.', []);
            } else {
                api_response(400, 'fail', 'Image not deleted', []);
            }
        }
        else if(!empty($sub_action) && $sub_action == 'addevaluation')
        {
            $errors   = $sections = [];
            $input_id = $_POST['id'] ?? 0;
            $sections = json_decode($_POST['checklist'], true); 
           
            if (empty($input_id)) {
                $errors[] = "Stock ID is required";
            } else {
                $lead_id = data_decrypt($input_id);

                if (!is_numeric($lead_id) || $lead_id <= 0) {
                    $errors[] = "Stock ID is not valid";
                } elseif (!$inventory->ownerCheck($lead_id)) {
                    $errors[] = "Access denied";
                }
            }
            if( empty($sections) ){
                $errors[] = "The selection checklist are empty.";
            }

            if (count($errors) > 0) {
                api_response(400, 'fail', $errors[0], [], [], $errors);
            }
            $files  = new Files();
            $item_docs = [];
            if( !empty($_FILES) && count($_FILES)>0 )
            {
                foreach( $_FILES as $fkey=>$file ){
                    $ids = explode("_",$fkey);
                    $section_id = $ids[1];
                    $item_id = $ids[2];
                    $file_rename = $lead_id . '-' . $section_id . '-' . round(microtime(true) * 1000) . '-' .$item_id;
                    $result = $files->uploadFiles($fkey, $file_rename, 'docs');
                    if( $result['status'] )
                    {
                        $item_docs[$section_id][$item_id] = viewdocLink($result['file_name']);
                    }
                    else{
                        api_response(400, 'fail', 'File upload failed.', [], [], $result['msg']);
                    }
                }
            }
            $item_data = [];
            if( !empty($_POST['checklist']) ){
                $sections = json_decode($_POST['checklist'], true);           
                foreach( $sections as $sid=>$items )
                {
                    foreach( $items as $k=>$item )
                    {
                        if(!empty($item_docs[$sid][$item['imgSno']])){
                            $item['imgPath'] = $item_docs[$sid][$item['imgSno']];
                        }
                        $item_data[$sid][$item['imgSno']] = $item;
                    }
                }
            }
            $request = [];
            $request['template_id'] = $_POST['template_id'];
            $request['id'] = $_POST['id'];
            $request['checklist'] = $item_data;
            //echo '<pre>'; print_r($item_data); echo '</pre>'; exit;
            
            $status = $inventory->saveEvaluation($request);

            if ($status) {
                api_response(200, 'ok', 'Checklist updated successfully.');
            } else {
                api_response(400, 'fail', 'Checklist not updated.', [], [], $errors);
            }
        }
        else if(!empty($sub_action) && $sub_action == 'savecertification')
        {
            //echo '<pre>'; print_r($_FILES); echo '</pre>';exit;
            
            //echo '<pre>'; print_r(json_encode($cert_checklist)); echo '</pre>';exit;
            try {
                $errors = [];
                $inventory_id = isset($_POST['id']) ? (int)data_decrypt($_POST['id']) : 0;
                
                if ($inventory_id <= 0) {
                    api_response(403, 'fail', 'Invalid Inventory ID', [], []);
                }
                if (!$inventory->ownerCheck($inventory_id)) {
                    api_response(403, 'fail', 'Access denied', [], []);
                }
                
                if( empty($_POST['certification_type']) ){
                    $errors[] = "Certification type is required";
                }
                else if( empty($_POST['certification_type']) && !is_numeric($_POST['certification_type'])  ){
                    $errors[] = "Certification type is not valid";
                }
                if( empty($_POST['certified_by']) ){
                    $errors[] = "Certified by is required";
                }
                elseif( empty($_POST['certified_by']) && !is_numeric($_POST['certified_by'])  ){
                    $errors[] = "Certified by is not valid";
                }

                if( empty($_POST['certified_date']) ){
                    $errors[] = "Certified date is required";
                }
                
                if (count($errors) > 0) {
                    api_response(400, 'fail', 'Validation failed.', [], [], $errors);
                }
                if( !empty($_FILES['certification_documents']) ){
                    $files  = new Files();
                    $file_rename = $inventory_id . '-certificaton-document-' . round(microtime(true) * 1000) . '-' .$inventory->login_user_id;
                    $result = $files->uploadFiles('certification_documents', $file_rename, 'docs');
                    if( $result['status'] )
                    {
                        $_POST['certification_documents'] = $result['file_name'];
                    }
                    else{
                        api_response(400, 'fail', 'certification document upload failed.', [], [], $result['msg']);
                    }
                }
               
                $result = $inventory->updateCertification($_POST,$inventory_id);
                
                if ($result) {
                    api_response(200, 'ok', 'Certification data saved successfully', ['success' => true]);
                } else {
                    api_response(500, 'fail', 'Failed to save certification data', ['success' => false]);
                }
                
            } catch (Exception $e) {
                api_response(500, 'fail', 'Error saving certification: ' . $e->getMessage(), ['success' => false]);
            }
        }
        else if(!empty($sub_action) && $sub_action == 'certificationapproval')
        {
            try{
                $errors = [];
                $inventory_id = isset($_POST['id']) ? (int)data_decrypt($_POST['id']) : 0;
                
                if ($inventory_id <= 0) {
                    api_response(403, 'fail', 'Invalid Inventory ID', [], []);
                }
                if (!$inventory->ownerCheck($inventory_id)) {
                    api_response(403, 'fail', 'Access denied', [], []);
                }
                
                if( empty($_POST['certification_status']) ){
                    $errors[] = "Certification status is required";
                }
                if( empty($_POST['certification_remarks']) ){
                    $errors[] = "Approval/Rejection Notes is required";
                }
                else if( empty($_POST['certification_status']) && !is_numeric($_POST['certification_status'])  ){
                    $errors[] = "Certification status is not valid";
                }
                if (count($errors) > 0) {
                    api_response(400, 'fail', 'Validation failed.', [], [], $errors);
                }
                else{
                    $result = $inventory->updateCertStatus($_POST,$inventory_id);
                    if ($result) {
                        api_response(200, 'ok', 'Certification status updated successfully', []);
                    } else {
                        api_response(400, 'fail', 'Failed to update the certification status', []);
                    }
                }

            }
            catch (Exception $e) {
                api_response(500, 'fail', 'Error saving certification approval: ' . $e->getMessage(), ['success' => false]);
            }
        }
        break;
        case 'defaultimage':
            $errors = [];
            $input_id = $_POST['inventory_id'] ?? '';
            $image_id = $_POST['image_id'] ?? '';
            if (empty($input_id)) {
                $errors[] = "Inventory ID is required";            
            } else {
                $inv_id = data_decrypt($input_id);
                if (!is_numeric($inv_id) || $inv_id < 0) {
                    $errors[] = "Inventory ID is not valid";
                }
            }
            if (!$inventory->ownerCheck($inv_id)) {
                $errors[] = "Access denied";
            }
            if (!$inventory->findImage($image_id,$inv_id)) {
                $errors[] = "Image not exist.";
            }
            if (count($errors) > 0) api_response(400, 'fail', 'Validation failed.', [], $errors);
            $result = $inventory->setDefalultImage($image_id,$inv_id);
            if ($result) api_response(200, 'ok', 'Default image flag updated successfully.', []);
            else api_response(400, 'fail', 'Default image flag not updated.', []);
        break;
   
        case 'downloadimages':
            $base_folder = $_POST['base_folder'] ?? '';
            $file_name = $_POST['file_name'] ?? '';
            $local_save_path = $_POST['local_save_path'] ?? '';

            if(!empty($base_folder) && !empty($file_name))
            {
                $res = $files->downloadFiles($base_folder,"/".$file_name,$local_save_path);
                if($res["status"] == 200)
                {
                    api_response(200,"ok","Image downloaded successfully.");
                }
                else
                {
                    api_response(500,"fail","Failed to download image.");
                }
            }
            else
            {
                api_response(500,"fail","Empty base folder or file name.");
            }
            break;

        // this exportdata is not used currently due to functionality not in use
        case 'exportdata':
            exit;; // temporarily disable export functionality
            // validations of search is pending
            $data = $inventory->exportInventory();

            if ($data) {
                api_response(200, 'ok', 'Inventory exported successfully', $data);
            } else {
                api_response(500, 'fail', 'Failed to export inventory', [], []);
            }
        break;
    case 'getleadevaluation':        
        if (empty($_POST['id'])) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $leadId = data_decrypt($_POST['id']);
        if (!is_numeric($leadId) || $leadId <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }        
        elseif (!$inventory->ownerCheck($leadId)) {
            api_response(403, 'fail', 'Access denied.', [], []);
        }
        $refurbish_data = $inventory->getRefurbishmentData($leadId);
        if( empty($refurbish_data) ){
             api_response(404, 'fail', 'Stock not found.', [], []);
        }
        api_response(200, 'ok', 'Refurbishment Details.', $refurbish_data);
        break;
    case 'gethistory':
        if (empty($_POST['id'])) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $leadId = data_decrypt($_POST['id']);
        if (!is_numeric($leadId) || $leadId <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }        
        elseif (!$inventory->ownerCheck($leadId)) {
            api_response(403, 'fail', 'Access denied.', [], []);
        }
        $history = $inventory->getStockHistory($leadId);
        if( empty($history) ){
             api_response(400, 'fail', 'History not found.', [], []);
        }
        api_response(200, 'ok', 'Stock History.', ["history"=>$history]);
        break;
    default:
        api_response(400, 'fail', 'Invalid action.', [], []);
}

?>
