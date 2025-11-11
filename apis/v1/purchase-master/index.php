<?php
global $auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_sellleads.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';

$common_config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';

$action             = strtolower($_POST['action'] ?? '');
$module             = 'pm';
$logged_user_id      = $GLOBALS['api_user']['uid'] ?? null;
$logged_dealer_id    = $GLOBALS['dealership']['dealership_id'] ?? null;
$logged_branch_id    = $GLOBALS['dealership']['branch_id'] ?? null;
$role_main           = $GLOBALS['dealership']['role_main'] ?? null;
$logged_executive_id = 0;

switch ($action) {

    /** --------------------------------------------------------------
     *  GET CONFIG
     *  -------------------------------------------------------------- */
    case 'getconfig':
        $moduleConfig = new moduleConfig();
        api_response(200, 'ok', 'Configuration retrieved successfully.', [
            'config' => $moduleConfig->getConfig($module)
        ]);
        break;


    /** --------------------------------------------------------------
     *  GET LEADS LIST
     *  -------------------------------------------------------------- */
    case 'getlist':
        $filters = $errors = [];
        $custom_filters = [];

        // Validation rules
        $fields = [
            'search_id'   => ['rule' => 'alphanumeric', 'error' => 'ID is not valid'],
            'mobile'      => ['rule' => 'mobile', 'error' => 'Mobile number is not valid'],
            'email'       => ['rule' => 'email', 'error' => 'Email address is not valid'],
            'seller_name' => ['rule' => 'alphanumericspecial', 'error' => 'Seller name is not valid'],
            'reg_num'     => ['rule' => 'reg_num', 'error' => 'Registration number is not valid'],
            'chassis'     => ['rule' => 'chassis', 'error' => 'Chassis number is not valid'],
            'make'        => ['rule' => 'id', 'error' => 'Make is not valid'],
            'model'       => ['rule' => 'id', 'error' => 'Model is not valid'],
            'status'      => ['rule' => 'alphanumericspecial', 'error' => 'Status is not valid'],
        ];

        // Validate input fields
        foreach ($fields as $field => $data) {
            if (!empty($_POST[$field]) && !validate_field_regex($data['rule'], $_POST[$field])) {
                $errors[$field] = $data['error'];
            }
        }
        if ($errors) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }
        

        // Status handling - SIMPLIFIED FLAT STRUCTURE
        $statusInput = strtolower(str_replace([' ', '-'], '', $_POST['status'] ?? ''));
        
        if ($statusInput && $statusInput !== 'undefined' && $statusInput !== 'all') {

            $pmStatusList = $common_config['pm_status'] ?? [];
            $normalizedStatuses = array_map(fn($s) => strtolower(str_replace(' ', '', $s)), $pmStatusList);

            // Direct flat mapping - single status value controls everything
            switch ($statusInput) {
                // Follow-up date-based buckets
                case 'followupoverdue':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['sub_status'] = 'followup-overdue';
                    $filters['exclude_evaluation'] = true;
                    break;
                    
                case 'followuptoday':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['sub_status'] = 'followup-today';
                    $filters['exclude_evaluation'] = true;
                    break;
                    
                case 'followupupcoming':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['sub_status'] = 'followup-upcoming';
                    $filters['exclude_evaluation'] = true;
                    break;
                
                // Evaluation date-based buckets
                case 'evaluationoverdue':
                    $filters['status'] = array_search('followup', $normalizedStatuses); // Evaluation uses followup status
                    $filters['evaluation_bucket'] = true;
                    $filters['sub_status'] = 'evaluation-overdue';
                    break;
                    
                case 'evaluationtoday':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['evaluation_bucket'] = true;
                    $filters['sub_status'] = 'evaluation-today';
                    break;
                    
                case 'evaluationupcoming':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['evaluation_bucket'] = true;
                    $filters['sub_status'] = 'evaluation-upcoming';
                    break;
                
                // Active leads bucket (combines Fresh + Follow up + Deal Done)
                case 'active':
                case 'activeleads':
                    $filters['active_bucket'] = true;
                    break;
                
                // Evaluation main bucket
                case 'evaluation':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['evaluation_bucket'] = true;
                    break;
                
                // Follow-up main bucket
                case 'followup':
                    $filters['status'] = array_search('followup', $normalizedStatuses);
                    $filters['exclude_evaluation'] = true;
                    break;
                
                // Standard status buckets (Fresh, Deal Done, Purchased, Lost)
                default:
                    $statusKey = array_search($statusInput, $normalizedStatuses);
                    if ($statusKey === false) {
                        $errors['status'] = 'Status is not valid';
                    } else {
                        $filters['status'] = $statusKey;
                    }
                    break;
            }
        }

        if ($errors) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }

        // Pagination
        $current_page = (int)($_POST['current_page'] ?? 1);
        $perPage      = (int)($_POST['perPage'] ?? 10);

        // Build filters dynamically
        foreach ($fields as $field => $_) {
            if (!empty($_POST[$field]) && $field !== 'status') {
                $filters[$field] = $_POST[$field];
            }
        }

        // Handle evaluated filter (checkbox group sends comma-separated values)
        if (!empty($_POST['evaluated'])) {
            $evaluatedValue = $_POST['evaluated'];
            // Checkbox sends '1' when checked, we filter for evaluation_done = 'y'
            if ($evaluatedValue === '1' || strpos($evaluatedValue, '1') !== false) {
                $filters['evaluation_done'] = 'y';
            }
        }

        // Handle Master Search (global search across multiple fields)
        $masterSearch = trim($_POST['search'] ?? '');
        
        if (!empty($masterSearch)) {
            // Define fields to search across (similar to individual filters)
            $searchableFields = [
                'search_id',      // PM ID
                'seller_name',    // Name
                'mobile',         // Mobile
                'email',          // Email
                'reg_num',        // Registration Number
                'chassis',        // Chassis Number
                'make',           // Make (from master_variants_new)
                'model'           // Model (from master_variants_new)
            ];
            
            // Pass to custom_filters for OR condition search
            $custom_filters['search'] = [
                'value' => $masterSearch,
                'fields' => $searchableFields
            ];
        }

        $sellleads = new Sellleads();
        $sellleads->logged_user_id      = $logged_user_id;
        $sellleads->logged_dealer_id    = $logged_dealer_id;
        $sellleads->logged_branch_id    = $logged_branch_id;
        $sellleads->logged_executive_id = $logged_executive_id;

        $leads = $sellleads->getLeads($filters, $custom_filters, $current_page, $perPage);

        api_response(200, 'ok', 'Leads retrieved successfully.', $leads, [
            'logged_user_id'      => $logged_user_id,
            'logged_dealer_id'    => $logged_dealer_id,
            'logged_branch_id'    => $logged_branch_id,
            'logged_executive_id' => $logged_executive_id
        ]);
        break;


    /** --------------------------------------------------------------
     *  GET SINGLE LEAD
     *  -------------------------------------------------------------- */
    case 'getlead':
        $sellleads = new Sellleads();
        $sellleads->logged_user_id      = $logged_user_id;
        $sellleads->logged_dealer_id    = $logged_dealer_id;
        $sellleads->logged_branch_id    = $logged_branch_id;
        $sellleads->logged_executive_id = $logged_executive_id;

        $input_id = $_POST['id'] ?? null;
        if (!$input_id) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $lead_id = data_decrypt($input_id);
        if (!is_numeric($lead_id) || $lead_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }

        $lead_details = $sellleads->getLead((int)$lead_id);
        if (empty($lead_details)) {
            api_response(404, 'fail', 'Lead not found.', [], []);
        }

        $moduleConfig = new moduleConfig();
        $lead_details['menu'] = $moduleConfig->getConfig($module, 'menu', $lead_details);

        api_response(200, 'ok', 'Lead fetched successfully.', $lead_details, [
            'logged_user_id'      => $logged_user_id,
            'logged_dealer_id'    => $logged_dealer_id,
            'logged_branch_id'    => $logged_branch_id,
            'logged_executive_id' => $logged_executive_id
        ]);
        break;


    /** --------------------------------------------------------------
     *  ADD NEW LEAD
     *  -------------------------------------------------------------- */
    case 'addlead':
        $sellleads = new Sellleads();
        $sellleads->logged_user_id      = $logged_user_id;
        $sellleads->logged_dealer_id    = $logged_dealer_id;
        $sellleads->logged_branch_id    = $logged_branch_id;
        $sellleads->logged_executive_id = $logged_executive_id;

        $moduleConfig = new moduleConfig();
        $config_data  = $moduleConfig->getConfig($module)['addConfig'] ?? [];

        // Branch access check
        $branchIds = array_map('strval', json_decode($logged_branch_id ?? '[]', true) ?: []);
        if (!empty($_POST['branch']) && (int)$_POST['branch'] > 0 && !in_array((string)$_POST['branch'], $branchIds, true)) {
            api_response(403, 'fail', 'Access denied for branch.', [], []);
        }

        $validation = validate_addconfig($config_data, $_POST);
        if (!empty($validation['errors'])) {
            api_response(400, 'fail', 'Validation failed.', [], [], $validation['errors']);
        }

        // Ensure is_exchange has a valid default value ('n' if not set)
        if (!isset($validation['data']['is_exchange']) || !in_array($validation['data']['is_exchange'], ['y', 'n'])) {
            $validation['data']['is_exchange'] = 'n';
        }

        $lead_result = $sellleads->addLead($validation['data']);
        if (!empty($lead_result[0]['id'])) {
            api_response(200, 'ok', 'Lead added successfully.', [
                'id' => $lead_result[0]['id']
            ]);
        } else {
            api_response(400, 'fail', 'Failed to save lead. Please try again.');
        }
        break;

    /** --------------------------------------------------------------
     *  UPDATE LEAD
     *  -------------------------------------------------------------- */
    case 'update':
        $sub_action = $_POST['sub_action'] ?? '';
        if (empty($sub_action)) {
            api_response(400, "fail", "Validation failed", [], [], ['sub_action' => "Sub-action is required."]);
        }

        $sellleads = new Sellleads();
        $sellleads->logged_user_id      = $logged_user_id;
        $sellleads->logged_dealer_id    = $logged_dealer_id;
        $sellleads->logged_branch_id    = $logged_branch_id;
        $sellleads->logged_executive_id = $logged_executive_id;

        $files         = new Files();
        $moduleConfig  = new moduleConfig();
        $config_data   = $moduleConfig->getConfig($module)['addConfig'] ?? [];
        $errors        = [];

        // Validate lead ID
        if (!isset($_POST['id'])) {
            $errors['id'] = "Lead ID is required";
        } else {
            $lead_id = data_decrypt($_POST['id']);
            if (!is_numeric($lead_id) || $lead_id <= 0) {
                $errors['id'] = "Lead ID is not valid";
            } elseif (!$sellleads->ownerCheck($lead_id)) {
                api_response(403, 'fail', 'Access denied.', [], []);
            }
        }

        // Branch access check
        $branchIds = array_map('strval', json_decode($logged_branch_id ?? '[]', true) ?: []);
        if (!empty($_POST['branch']) && (int)$_POST['branch'] > 0 && !in_array((string)$_POST['branch'], $branchIds, true)) {
            api_response(403, 'fail', 'Access denied for branch.', [], []);
        }

        switch ($sub_action) {

            case 'updatelead':
                $validation = validate_addconfig($config_data, $_POST);
                if (!empty($validation['errors'])) {
                    api_response(400, 'fail', 'Validation failed', [], [], $validation['errors']);
                }

                // Ensure is_exchange has a valid value ('n' if not set or invalid)
                if (!isset($validation['data']['is_exchange']) || !in_array($validation['data']['is_exchange'], ['y', 'n'])) {
                    $validation['data']['is_exchange'] = 'n';
                }

                $status = $sellleads->updateLead($validation['data'], $lead_id);
                
                if ($status) {
                    // Fetch updated lead with fresh vahanInfo
                    api_response(200, 'ok', 'Lead updated successfully.');
                } else {
                    api_response(400, 'fail', 'Lead not updated.');
                }
                break;

            case 'updateExecutive':
                if (!isset($_POST['branch'])) $errors['branch'] = "Branch is required";
                if (!isset($_POST['executive']) || !validate_field_regex('numeric', $_POST['executive'])) {
                    $errors['executive'] = "Executive is not valid";
                }
                if (!empty($errors)) api_response(400, 'fail', 'Validation failed', [], [], $errors);

                $update_data = $_POST;
                $update_data['id'] = $lead_id;

                $status = $sellleads->updateExecutive($update_data, $lead_id);
                $status
                    ? api_response(200, 'ok', 'Lead executive updated successfully.')
                    : api_response(400, 'fail', 'Lead executive not updated.');
                break;

            case 'uploadimages':
                $image_tag = $_POST['tag'] ?? '';
                if (empty($image_tag)) $errors['tag'] = "Image tag is required.";
                if (!array_key_exists('image', $_FILES) || empty($_FILES['image'])) $errors['image'] = "Image not selected.";
                if (!empty($errors)) api_response(400, 'fail', 'Validation failed.', [], [], $errors);

                $file_rename = $lead_id . '-' . $logged_dealer_id . '-' . round(microtime(true) * 1000) . '-' . rand(1000, 9999);
                $result = $files->uploadFiles('image', $file_rename, 'vimages');

                if ($result['status']) {
                    $saveResult = $sellleads->saveImage($result['file_name'], $lead_id, $image_tag);
                    $saveResult['status']
                        ? api_response(200, 'ok', 'Image uploaded successfully.', $saveResult)
                        : api_response(400, 'fail', 'Image not saved.');
                } else {
                    api_response(400, 'fail', 'Image upload failed.', [], [], $result['msg']);
                }
                break;

            case 'deleteimage':
                $image_id = $_POST['image_id'] ?? '';
                if (empty($lead_id)) $errors[] = "Lead ID is required";
                if (empty($image_id)) $errors[] = "Image ID is required";

                if (!$sellleads->findImage($image_id, $lead_id)) {
                    $errors[] = "Image not exist.";
                }
                if (count($errors) > 0) {
                    api_response(400, 'fail', 'Validation failed.', [], [], $errors);
                }

                $res = $sellleads->deleteImage($image_id, $lead_id);
                $res
                    ? api_response(200, 'ok', 'Image deleted successfully.')
                    : api_response(400, 'fail', 'Image not deleted.');
                break;

            case 'updatedentmap':
                $errors = [];
                $input_id = $_POST['selllead_id'] ?? '';  
                $dent_id  = $_POST['id'] ?? 0;              
                $lead_id  = data_decrypt($input_id);

                if (empty($lead_id)) {
                    $errors[] = "Lead ID is required";            
                } elseif (!is_numeric($lead_id) || $lead_id <= 0) {
                    $errors[] = "Lead ID is not valid";
                }

                if (!empty($dent_id) && !$sellleads->findDentmap($lead_id, $dent_id)) {
                    $errors[] = "Dentmap not exist.";
                }

                if (count($errors) > 0) {
                    api_response(400, 'fail', 'Validation failed.', [], [], $errors);
                }

                // Handle image upload (if provided)
                if (!empty($_FILES['imageLink']) && isset($_FILES['imageLink']['name']) && $_FILES['imageLink']['name'] !== '') {
                    $file_rename = $lead_id . "-dent-image-" . $sellleads->logged_user_id . '-' . round(microtime(true) * 1000);
                    $result = $files->uploadFiles("imageLink", $file_rename, "docs");
                    if ($result['status']) {
                        $_POST['imageLink'] = $result['file_name'];                   
                    } else {
                        api_response(400, 'fail', 'Image not uploaded.', [], [], [$result['msg']]);
                    }
                }

                $dents = $sellleads->saveDentmap($_POST, $lead_id);
                if ($dents) {
                    api_response(200, 'ok', 'Dentmap updated successfully.', $dents);
                } else {
                    api_response(400, 'fail', 'Dentmap not updated.', []);
                }
                break;
                

            /** --------------------------------------------------------------
             *  UPDATE STATUS
             *  -------------------------------------------------------------- */
            case 'updatestatus':
                $local_errors = [];
                $file1_name = $file2_name = null;

                $lead_details = $sellleads->getLead($lead_id);

                // Previous values for history tracking
                $previous_status                      = $lead_details['detail']['status'] ?? $lead_details['status'] ?? null;
                $previous_sub_status                  = $lead_details['detail']['sub_status'] ?? $lead_details['sub_status'] ?? null;
                $previous_followup_date               = $lead_details['detail']['followup_date'] ?? $lead_details['followup_date'] ?? null;

                $sellleads->id                  = $lead_id;
                $sellleads->status              = $_POST['status'] ?? "";
                $sellleads->sub_status          = $_POST['sub_status'] ?? "";
                $sellleads->lead_classification = $_POST['lead_classification'] ?? "";
                $sellleads->evaluation_place    = $_POST['evaluation_place'] ?? "";
                $sellleads->remarks             = $_POST['remarks'] ?? "";
                $sellleads->price_customer      = $_POST['price_customer'] ?? "";
                $sellleads->price_quote         = $_POST['price_quote'] ?? "";
                $sellleads->price_expenses      = $_POST['price_expenses'] ?? "";
                $sellleads->price_selling       = $_POST['price_selling'] ?? "";
                $sellleads->price_indicative    = $_POST['price_indicative'] ?? "";
                $sellleads->is_exchange         = $_POST['is_exchange'] ?? 'n';

                // Handle evaluation_type
                $sellleads->evaluation_type = "";
                if (!empty($_POST['evaluation_type'])) {
                    $sellleads->evaluation_type = is_array($_POST['evaluation_type'])
                        ? implode(',', $_POST['evaluation_type'])
                        : $_POST['evaluation_type'];
                }

                $sellleads->followup_date               = $_POST['followup_date'] ?? "";
                $sellleads->evaluation_place            = $_POST['evaluation_place'] ?? "";

                // Get the actual evaluation_done value from database (set when evaluation is completed)
                // Check both nested structure (detail.evaluation_done) and flat structure (evaluation_done)
                $sellleads->evaluation_done = $lead_details['detail']['evaluation_done'] ?? $lead_details['evaluation_done'] ?? 'n';

                // Pricing fields
                $sellleads->price_customer  = $_POST['price_customer'] ?? "";
                $sellleads->price_quote     = $_POST['price_quote'] ?? "";
                $sellleads->price_expenses  = $_POST['price_expenses'] ?? "";
                $sellleads->price_margin    = $_POST['price_margin'] ?? "";
                $sellleads->price_agreed    = $_POST['price_agreed'] ?? "";
                $sellleads->token_amount    = $_POST['token_amount'] ?? "";

                if (empty($_POST['status'])) {
                    $local_errors['status'] = "Status is required.";
                } elseif (!validate_field_regex('id', $_POST['status'])) {
                    $local_errors['status'] = "Invalid status.";
                }

                // Validation: Cannot move to Deal Done (status 3) without evaluation_done = 'y'
                if ($_POST['status'] == '3' && $sellleads->evaluation_done != 'y') {
                    $local_errors['status'] = "Please complete the evaluation before moving to Deal Done.";
                }
              
               $file_doc1_name = $lead_details['detail']['file_doc1'] ?? $lead_details['file_doc1'] ?? '';
               $file_doc2_name = $lead_details['detail']['file_doc2'] ?? $lead_details['file_doc2'] ?? '';

                            
                // Handle both files
                foreach (['file_doc1', 'file_doc2'] as $docKey) {
                    $fileVar = $docKey . '_name';
                    ${$fileVar} = ''; // Default

                    $fileInfo = $_FILES[$docKey] ?? null;
                    $formValue = trim($_POST[$docKey] ?? '');
                    $fileRename = $lead_id . '-' . $logged_dealer_id . '-' . $docKey . '-' . round(microtime(true) * 1000);

                    if ($fileInfo && $fileInfo['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = $files->uploadFiles($docKey, $fileRename, 'docs');
                        if (!empty($uploadResult['status'])) {
                            ${$fileVar} = $uploadResult['file_name'];
                        } else {
                            $local_errors[$docKey] = $uploadResult['msg'] ?? 'File upload failed';
                        }
                        continue;
                    }

                    // ---- Case ②: File upload error ----
                    if ($fileInfo && $fileInfo['error'] !== UPLOAD_ERR_NO_FILE && $fileInfo['error'] !== UPLOAD_ERR_OK) {
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit',
                            UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                        ];
                        $errorCode = $fileInfo['error'];
                        $local_errors[$docKey] = $errorMessages[$errorCode] ?? "File upload failed with error code: $errorCode";
                        continue;
                    }

                    // ---- Case ③: No new upload, but value exists in POST ----
                    if ($formValue !== '') {
                        if (preg_match('/^https?:\/\//i', $formValue)) {
                            // Convert full URL to relative path if it matches document base URL
                            $baseUrl = rtrim($common_config['document_base_url'] ?? '', '/') . '/';
                            ${$fileVar} = (strpos($formValue, $baseUrl) === 0)
                                ? substr($formValue, strlen($baseUrl))
                                : $formValue;
                        } else {
                            // Already a relative path
                            ${$fileVar} = $formValue;
                        }
                        continue;
                    }

                    // ---- Case ④: Nothing uploaded or provided → clear field ----
                    ${$fileVar} = '';
                }



                $moduleConfig = new moduleConfig();
                $config_data  = $moduleConfig->getConfig($module)['statusConfig'] ?? [];
                $config_result = validate_statusconfig($config_data, $_POST);

                $errors = $local_errors;
                if (!$config_result['status']) {
                    $errors = array_merge($errors, $config_result['errors'] ?? []);
                }

                if (count($errors) > 0) {
                    api_response(400, "fail", "Validation failed.", [], [], $errors);
                }

                $result = $sellleads->updateLeadStatus(
                    $previous_status,
                    $previous_sub_status,
                    $previous_followup_date,
                    $file_doc1_name,
                    $file_doc2_name
                );

                if ($result['status']) {
                    api_response(200, "ok", "Lead status updated successfully.");
                } else {
                    $status_errors = isset($result['field'])
                        ? [$result['field'] => $result['msg']]
                        : [$result['msg']];
                    api_response(400, "fail", "Failed to update lead status.", [], [], $status_errors);
                }
                break;



            default:
                api_response(400, 'fail', 'Invalid sub-action for update.');
                break;
        }
        break;


    case 'addevaluation':
        $errors   = $sections = [];
        $input_id = $_POST['id'] ?? '';
        $sections = json_decode($_POST['checklist'], true); 
        //echo '<pre>'; print_r($_POST);print_r($_FILES); echo '</pre>'; 
        if (empty($input_id)) {
            $errors[] = "Lead ID is required";
        } else {
            $lead_id = data_decrypt($input_id);

            $sellleads = new Sellleads();
            $sellleads->logged_user_id      = $logged_user_id;
            $sellleads->logged_dealer_id    = $logged_dealer_id;
            $sellleads->logged_branch_id    = $logged_branch_id;
            $sellleads->logged_executive_id = $logged_executive_id;

            if (!is_numeric($lead_id) || $lead_id <= 0) {
                $errors[] = "Lead ID is not valid";
            } elseif (!$sellleads->ownerCheck($lead_id)) {
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
        
        $status = $sellleads->saveEvaluation($request);

        if ($status) {
            api_response(200, 'ok', 'Checklist updated successfully.');
        } else {
            api_response(400, 'fail', 'Checklist not updated.', [], [], $errors);
        }
        break;
    case 'getleadevaluation':
        $sellleads = new Sellleads();
        $sellleads->logged_user_id      = $logged_user_id;
        $sellleads->logged_dealer_id    = $logged_dealer_id;
        $sellleads->logged_branch_id    = $logged_branch_id;
        $sellleads->logged_executive_id = $logged_executive_id;

        $input_id = $_POST['id'] ?? null;
        if (!$input_id) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $lead_id = data_decrypt($input_id);
        if (!is_numeric($lead_id) || $lead_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }
        elseif (!$sellleads->ownerCheck($lead_id)) {
            api_response(403, 'fail', 'Access denied.', [], []);
        }

        $lead_details = $sellleads->getEvaluationDataNew((int)$lead_id);
        //echo '<pre>'; print_r($lead_details); echo '</pre>';exit;


        if (empty($lead_details)) {
            api_response(404, 'fail', 'Lead not found.', [], []);
        }

        api_response(200, 'ok', 'Lead fetched successfully.', $lead_details, [
            'logged_user_id'      => $logged_user_id,
            'logged_dealer_id'    => $logged_dealer_id,
            'logged_branch_id'    => $logged_branch_id,
            'logged_executive_id' => $logged_executive_id
        ]);
        break;

    case 'gethistory':
        $sellleads = new Sellleads();
        $sellleads->logged_user_id      = $logged_user_id;
        $sellleads->logged_dealer_id    = $logged_dealer_id;
        $sellleads->logged_branch_id    = $logged_branch_id;
        $sellleads->logged_executive_id = $logged_executive_id;

        $input_id = $_POST['id'] ?? null;
        if (!$input_id) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $lead_id = data_decrypt($input_id);
        if (!is_numeric($lead_id) || $lead_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }
        elseif (!$sellleads->ownerCheck($lead_id)) {
            api_response(403, 'fail', 'Access denied.', [], []);
        }

        $history_details = $sellleads->getLeadHistory($lead_id);

        api_response(200, 'ok', '', ['history'=>$history_details], [
            'logged_user_id'      => $logged_user_id,
            'logged_dealer_id'    => $logged_dealer_id,
            'logged_branch_id'    => $logged_branch_id,
            'logged_executive_id' => $logged_executive_id
        ]);
        break;

        // this exportdata is not used currently due to functionality not in use
        case 'exportdata':
            exit; // temporarily disable export functionality
            // validations of search is pending
            $sellleads = new Sellleads();
            $sellleads->logged_user_id      = $logged_user_id;
            $sellleads->logged_dealer_id    = $logged_dealer_id;
            $sellleads->logged_branch_id    = $logged_branch_id;
            $sellleads->logged_executive_id = $logged_executive_id;

            $data = $sellleads->exportSellleads();
            if ($data) {
                api_response(200, 'ok', 'Dealerships exported successfully', $data);
            } else {
                api_response(500, 'fail', 'Failed to export dealerships', [], []);
            }
        break;


    case 'getfilters':
        
        $moduleConfig = new moduleConfig();
        $filters_data  = $moduleConfig->pmFilters();
    
        if ($filters_data) {
            api_response(200, 'ok', '', $filters_data);
        } else {
            api_response(500, 'fail', 'Failed', [], []);
        }
        break;

    
    /** --------------------------------------------------------------
     *  DEFAULT
     *  -------------------------------------------------------------- */
    default:
        api_response(400, 'fail', 'Invalid action for leads.');
        break;
}
