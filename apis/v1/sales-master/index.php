<?php
global $auth; 

require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_buyleads.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';

$common_config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';


$action = strtolower($_REQUEST['action'] ?? '');
$module = 'sm';
$logged_user_id = $GLOBALS['api_user']['uid'] ?? null;
$logged_dealer_id = $GLOBALS['dealership']['dealership_id'] ?? null;
$logged_branch_id = $GLOBALS['dealership']['branch_id'] ?? null;
$role_main = $GLOBALS['dealership']['role_main'] ?? null;
$logged_executive_id = 0; 
$sm_common_status = $common_config['common_statuses']['sm'];


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
            
            // Validation check
            $filters = $errors  = [];

            // Validation rules
            $fields = [
                'id'                  => ['rule' => 'alphanumeric', 'error' => 'ID is not valid'],
                'mobile'              => ['rule' => 'mobile', 'error' => 'Mobile number is not valid'],
                'email'               => ['rule' => 'email', 'error' => 'Email address is not valid'],
                'buyer_name'          => ['rule' => 'alphanumericspecial', 'error' => 'Buyer name is not valid'],
                'make'                => ['rule' => 'id', 'error' => 'Make is not valid'],
                'model'               => ['rule' => 'id', 'error' => 'Model is not valid'],
                'lead_classification' => ['rule' => 'alpha', 'error' => 'Lead classification is not valid'],
                'branch'              => ['rule' => 'id', 'error' => 'Branch is not valid'],
                'executive'           => ['rule' => 'id', 'error' => 'Executive is not valid'],
                'test_drive_done'     => ['rule' => 'active', 'error' => 'Test drive done is not valid'],
            ];
            
            // Validate input fields
            foreach($fields as $field=>$data)
            {
                if(!empty($_POST[$field]) && !validate_field_regex($data['rule'],$_POST[$field]))
                {
                    $errors[$field] = $data['error'];
                }
            }
            if(count($errors)>0)
            {
                api_response(400,'fail','Validation failed',[],[],$errors);
            }

        // Status handling
        $statusInput = strtolower(str_replace(' ', '', $_POST['status'] ?? ''));

        if ($statusInput && $statusInput !== 'undefined' && $statusInput !== 'all') {

            $smStatusList = $common_config['sm_sidebar_statuses'] ?? [];
            $normalizedStatuses = array_map(fn($s) => strtolower(str_replace(' ', '', $s)), $smStatusList);
        
            // Custom sub-status map
            $customMap = [
                'followup-overdue'    => ['status' => 'followup', 'sub_status' => 'followup-overdue'],
                'followup-today'      => ['status' => 'followup', 'sub_status' => 'followup-today'],
                'followup-upcoming'   => ['status' => 'followup', 'sub_status' => 'followup-upcoming'],
                'testdrive-overdue'   => ['status' => 'testdrive', 'sub_status' => 'testdrive-overdue'],
                'testdrive-today'     => ['status' => 'testdrive', 'sub_status' => 'testdrive-today'],
                'testdrive-upcoming'  => ['status' => 'testdrive', 'sub_status' => 'testdrive-upcoming'],
            ];



            // Active leads bucket (combines Fresh + Follow up + Test Drive + Booked)
            if ($statusInput === 'active' || $statusInput === 'activeleads' || $statusInput === 'active-leads') {
                $filters['active_bucket'] = true;
            } elseif (isset($customMap[$statusInput])) {
                $filters['status']     = array_search($customMap[$statusInput]['status'], $normalizedStatuses);
                $filters['sub_status'] = $customMap[$statusInput]['sub_status'];
            } else {
                // Remove hyphens for matching (test-drive → testdrive)
                $cleanInput = str_replace('-', '', $statusInput);
                $baseStatus = preg_match("/followup|testdrive/i", $cleanInput, $m) ? $m[0] : $cleanInput;
                $statusKey  = array_search($baseStatus, $normalizedStatuses);

                if ($statusKey === false) {
                    $errors['status'] = 'Status is not valid';
                } else {
                    $filters['status'] = $statusKey;
                }
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

        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id;

        $leads = $buyleads->getLeads($filters, [], $current_page, $perPage);

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

        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id; 

        $input_id = $_POST['id'] ?? null;
        if (!$input_id) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $lead_id = data_decrypt($input_id);
        if (!is_numeric($lead_id) || $lead_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }

        $lead_details = $buyleads->getLead($lead_id);

        $moduleConfig = new moduleConfig();
        $lead_details['menu'] = $moduleConfig->getConfig($module, 'menu', $lead_details);
        if (empty($lead_details)) {
            api_response(404, 'fail', 'Lead not found.', [], []);
        }

       
        api_response(200, 'ok', 'Lead fetched successfully.', $lead_details,
        [
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
        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id; 

        $moduleConfig = new moduleConfig();
        $config_data = $moduleConfig->getConfig($module)['addConfig'] ?? [];

        // Branch access check
        $branchIds = array_map('strval', json_decode($logged_branch_id ?? '[]', true) ?: []);
        if (!empty($_POST['branch']) && (int)$_POST['branch'] > 0 && !in_array((string)$_POST['branch'], $branchIds, true)) {
            api_response(403, 'fail', 'Access denied for branch.', [], []);
        }

        $validation  = validate_addconfig($config_data, $_POST);

        if (!empty($validation['errors'])) {
            api_response(400, 'fail', 'Validation Failed', [], [], $validation['errors']);
        }

        $lead_result = $buyleads->addLead($validation['data']);
        if (!empty($lead_result) && is_array($lead_result) && isset($lead_result[0]['id'])) {
            $lead_id = $lead_result[0]['id'];           
            api_response(200, 'ok', 'Lead added successfully.', ['id' => $lead_id]);
        } else {
            api_response(400, 'fail', 'Failed to save lead. Please try again.', []);
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
        $lead_id = data_decrypt($_POST['id']) ?? 0;

        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id;

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
            } elseif (!$buyleads->ownerCheck($lead_id)) {
                api_response(403, 'fail', 'Access denied.', [], []);
            }
        }

        // Branch access check
        $branchIds = array_map('strval', json_decode($logged_branch_id ?? '[]', true) ?: []);
        if (!empty($_POST['branch']) && (int)$_POST['branch'] > 0 && !in_array((string)$_POST['branch'], $branchIds, true)) {
            api_response(403, 'fail', 'Access denied for branch.', [], []);
        }

        switch($sub_action)
        {
            case 'updatelead':
                $validation = validate_addconfig($config_data, $_POST);
                if (!empty($validation['errors'])) {
                    api_response(400, 'fail', 'Validation failed', [], [], $validation['errors']);
                }

                $status = $buyleads->updateLead($validation['data'], $lead_id);
                
                if ($status) {
                    // Fetch updated lead 
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

                $status = $buyleads->updateExecutive($update_data, $lead_id);
                
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
                break;

             /** --------------------------------------------------------------
             *  UPDATE STATUS
             *  -------------------------------------------------------------- */
            case 'updatestatus':
                $local_errors = [];
                $file1_name = $file2_name = null;

                $lead_details = $buyleads->getLead($lead_id);
                
                $previous_status = $lead_details['detail']['status'] ?? null;
                $previous_sub_status = $lead_details['detail']['sub_status'] ?? null;
                $previous_followup_date = $lead_details['detail']['followup_date'] ?? null;

                if ($previous_status == $sm_common_status['status']['sold']) {
                    api_response(400, "fail", "This lead is already sold.", [], []);
                } elseif ($previous_status == $sm_common_status['status']['lost']) {
                    api_response(400, "fail", "This lead is already lost.", [], []);
                }

                $buyleads->id = $lead_id; 
                $buyleads->status = $_POST['status'] ?? 1;
                $buyleads->sub_status = $_POST['sub_status'] ?? "";
                $buyleads->lead_classification = $_POST['lead_classification'] ?? "";
                $buyleads->buying_horizon = $_POST['buying_horizon'] ?? "";
                $buyleads->sold_vehicle = $_POST['sold_vehicle'] ?? "";
                $buyleads->sold_by = $_POST['sold_by'] ?? "";
                $buyleads->booked_vehicle = $_POST['booked_vehicle'] ?? "";
                $buyleads->test_drive_place = $_POST['test_drive_place'] ?? "";
                $buyleads->test_drive_status = $_POST['test_drive_status'] ?? "1";
                $buyleads->test_drive_done = $_POST['test_drive_done'] ?? "";
                $buyleads->remarks = $_POST['remarks'] ?? "";


                // Price related fields
                $buyleads->price_sold = $_POST['price_sold'] ?? '';
                $buyleads->price_indicative = $_POST['price_indicative'] ?? '';
                $buyleads->price_customer = $_POST['price_customer'] ?? '';
                $buyleads->price_quote = $_POST['price_quote'] ?? '';
                $buyleads->price_margin = $_POST['price_margin'] ?? '';
                $buyleads->price_agreed = $_POST['price_agreed'] ?? '';
                $buyleads->token_amount = $_POST['token_amount'] ?? '';
            
                // Date related fields
                $buyleads->followup_date = $_POST['followup_date'] ?? "";
                $buyleads->test_drive_date = $_POST['followup_date'] ?? "";
                $buyleads->customer_visited = $_POST['customer_visited'] ?? "";
                $buyleads->customer_visited_date = $_POST['customer_visited_date'] ?? "";
                $buyleads->sold_date = $_POST['sold_date'] ?? "";
                $buyleads->delivery_date = $_POST['delivery_date'] ?? "";

                $buyleads->test_drive_vehicle = "";
                if (!empty($_POST['test_drive_vehicle'])) {
                    $vehicle_ids = explode('|', $_POST['test_drive_vehicle']);
                    foreach ($vehicle_ids as $vid) {
                        $vid = intval(trim($vid));
                        if ($vid > 0) {
                            $buyleads->test_drive_vehicle = $vid;
                            $buyleads->addTestDriveVehicle();
                        }
                    }
                }

                /* If status is followup (2) and sub-status is test drive sheduled (9) then nxt followup date is considered as 
                   test drive date */ 
                if($buyleads->status == array_search('2',$common_config['sm_status']) && $buyleads->sub_status == '9')
                {
                    $buyleads->test_drive_date = $buyleads->followup_date;
                }

                if(empty($_POST['status']))
                {
                    $local_errors['status'] = "Status is required.";
                }
                if(!empty($_POST['status']) && !validate_field_regex('id',$_POST['status']))
                {
                    $local_errors['status'] = "Invalid status.";
                }
        
                $status = $_POST['status'] ?? '';
    
                $lead = $buyleads->getLead($lead_id);  

                $file_doc1_name = $lead['file_doc1'] ?? '';
                $file_doc2_name = $lead['file_doc2'] ?? ''; 
                
                // Handle both files
                foreach (['file_doc1', 'file_doc2'] as $docKey) {
                    $file_rename = $lead_id . "-" . $logged_dealer_id . '-' . $docKey . '-' . round(microtime(true) * 1000);

                    $hasFileObject = isset($_FILES[$docKey]) && $_FILES[$docKey]['error'] === UPLOAD_ERR_OK;
                    $hasFileError = isset($_FILES[$docKey]) && $_FILES[$docKey]['error'] !== UPLOAD_ERR_NO_FILE && $_FILES[$docKey]['error'] !== UPLOAD_ERR_OK;
                    $formValue = $_POST[$docKey] ?? '';

                    if ($hasFileObject) {
                        // Case ① - New file uploaded → upload & replace
                        $uploadResult = $files->uploadFiles($docKey, $file_rename, 'docs');
                        if ($uploadResult['status']) {
                            ${$docKey . '_name'} = $uploadResult['file_name'];
                        } else {
                            $local_errors[$docKey] = $uploadResult['msg'];
                        }
                    } elseif ($hasFileError) {
                        // Case ② - File upload error occurred
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit',
                            UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
                            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                        ];
                        $errorCode = $_FILES[$docKey]['error'];
                        $local_errors[$docKey] = $errorMessages[$errorCode] ?? "File upload failed with error code: $errorCode";
                    } elseif (is_string($formValue) && trim($formValue) !== '') {
                    
                        // Extract relative path if full URL was sent back
                        $trimmedValue = trim($formValue);
                        if (preg_match('/^https?:\/\//i', $trimmedValue)) {
                            // It's a full URL - extract the relative path by removing base URL
                            $baseUrl = rtrim($common_config['document_base_url'] ?? '', '/') . '/';
                            if (strpos($trimmedValue, $baseUrl) === 0) {
                                ${$docKey . '_name'} = substr($trimmedValue, strlen($baseUrl));
                            } else {
                            
                                ${$docKey . '_name'} = $trimmedValue;
                            }
                        } else {
                            // Already a relative path
                            ${$docKey . '_name'} = $trimmedValue;
                        }
                    } else {
                        // Case ④ - Empty in both POST & FILE → clear field
                        ${$docKey . '_name'} = '';
                    }
                }
                
                $moduleConfig = new moduleConfig();
                $config_data = $moduleConfig->getConfig($module)['statusConfig'] ?? [];
                
                $config_result = validate_statusconfig($config_data,$_POST);
                $errors = $local_errors ?? [];
    
                // Only merge if config validation failed
                if (!$config_result['status']) {
                    $errors = array_merge($errors, $config_result['errors'] ?? []);
                }
    
                if (count($errors) > 0) {
                    api_response(400, "fail", "Validation failed.", [], [], $errors);
                }
                
                $result = $buyleads->updateLeadStatus(
                    $previous_status,
                    $previous_sub_status,
                    $previous_followup_date,
                    $file_doc1_name,
                    $file_doc2_name
                );
                if($result['status'])
                {
                    api_response(200,"ok","Lead status updated successfully.",[],[]);
                }
                else 
                {
                    $status_errors = [];
                    if (isset($result['field'])) 
                    {
                        $status_errors[$result['field']] = $result['msg'];
                    } 
                    else 
                    {
                        $status_errors[] = $result['msg'];
                    }
                    api_response(400, "fail", "Failed to update lead status.", [], [], $status_errors);
                }
            break;

            case 'savetestdrivevehicle':
                $local_errors = [];

                $row_id_enc = $_POST['row_id'] ?? '';
                $row_id = intval(!empty($row_id_enc) ? data_decrypt($row_id_enc) : 0);

                $buyleads->id                     = intval($lead_id);
                $buyleads->test_drive_place       = intval($_POST['test_drive_place'] ?? 0);
                $buyleads->test_drive_status      = intval($_POST['test_drive_status'] ?? 0);
                $buyleads->test_drive_date        = trim($_POST['scheduled_date'] ?? '');
                $buyleads->test_drive_completed_date = trim($_POST['completed_date'] ?? '');
                $test_drive_vehicle_str           = trim($_POST['test_drive_vehicle'] ?? '');
                $form_doc_name                    = '';

                // ---- Basic validations ----
                if ($buyleads->id <= 0) {
                    $local_errors['lead_id'] = "Invalid lead ID.";
                }
                if (empty($buyleads->test_drive_date)) {
                    $local_errors['test_drive_date'] = "Test drive date is required.";
                }
                

                if (count($local_errors) > 0) {
                    api_response(400, "fail", "Validation failed.", [], [], $local_errors);
                }

                // ---- Handle form_doc ----
                $existing_doc = '';
                if ($row_id > 0) {
                    $existing = $buyleads->testDriveVehicle($row_id);
                    $existing_doc = $existing['form_doc'] ?? '';
                }

                $file_doc_name = $existing_doc;
                $fileInfo = $_FILES['form_doc'] ?? null;
                $formValue = trim($_POST['form_doc'] ?? '');
                $fileRename = $lead_id . '-' . $logged_dealer_id . '-form_doc-' . round(microtime(true) * 1000);

                if ($fileInfo && $fileInfo['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = $files->uploadFiles('form_doc', $fileRename, 'docs');
                    if (!empty($uploadResult['status'])) {
                        $file_doc_name = $uploadResult['file_name'];
                    } else {
                        $local_errors['form_doc'] = $uploadResult['msg'] ?? 'File upload failed';
                    }
                } elseif ($fileInfo && $fileInfo['error'] !== UPLOAD_ERR_NO_FILE && $fileInfo['error'] !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit',
                        UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    $errorCode = $fileInfo['error'];
                    $local_errors['form_doc'] = $errorMessages[$errorCode] ?? "File upload failed with error code: $errorCode";
                } elseif ($formValue !== '') {
                    if (preg_match('/^https?:\/\//i', $formValue)) {
                        $baseUrl = rtrim($common_config['document_base_url'] ?? '', '/') . '/';
                        $file_doc_name = (strpos($formValue, $baseUrl) === 0)
                            ? substr($formValue, strlen($baseUrl))
                            : $formValue;
                    } else {
                        $file_doc_name = $formValue;
                    }
                }

                if (count($local_errors) > 0) {
                    api_response(400, "fail", "Validation failed.", [], [], $local_errors);
                }

                if ($row_id > 0) {
                    // $buyleads->test_drive_vehicle = intval($test_drive_vehicle_str);
                    $buyleads->form_doc = $file_doc_name;

                    $result = $buyleads->updateTestDriveVehicle($row_id);

                    if (!empty($result['success'])) {
                        api_response(200, "ok", "Test drive updated successfully.", [], []);
                    } else {
                        api_response(400, "fail", $result['msg'] ?? "Update failed.", [], []);
                    }
                } else {
                    if (empty($test_drive_vehicle_str)) {
                        $local_errors['test_drive_vehicle'] = "At least one vehicle must be selected.";
                    }
                    $vehicle_ids_enc = array_filter(array_map('trim', explode('|', $test_drive_vehicle_str)));
                    $inserted = 0;
                    $errors = [];

                    $ready_list = $buyleads->getReadyForSaleVehiclesList($lead_id);
                    $valid_ids = array_column($ready_list, 'value');

                    foreach ($vehicle_ids_enc as $vid_enc) {
                        $vid = intval(data_decrypt($vid_enc));

                        if ($vid > 0 && in_array($vid, $valid_ids)) {
                            $buyleads->test_drive_vehicle = $vid;
                            $buyleads->form_doc = $file_doc_name;
                            $result = $buyleads->addTestDriveVehicle();

                            if (!empty($result['success'])) {
                                $inserted++;
                            } else {
                                $errors[] = "Vehicle ID $vid: " . ($result['msg'] ?? 'Unknown error');
                            }
                        } elseif ($vid > 0) {
                            $errors[] = "Vehicle ID $vid is not ready for sale or not linked to this lead.";
                        } else {
                            $errors[] = "Invalid or corrupted vehicle ID: $vid_enc";
                        }
                    }

                    if (!empty($errors)) {
                        api_response(400, "fail", "Some test drives could not be added.", [], [], $errors);
                    } elseif ($inserted > 0) {
                        api_response(200, "ok", "$inserted test drive(s) added successfully.", [], []);
                    } else {
                        api_response(400, "fail", "No valid vehicles found to add.", [], []);
                    }
                }

                break;

            /** --------------------------------------------------------------
            *  SAVE EMATCHES
            *  -------------------------------------------------------------- */
        case 'saveematches':
            $buyleads->id = (int) $lead_id;

            $list_raw = $_POST['list'] ?? '';
            if (is_array($list_raw)) {
                $list = $list_raw;
            } elseif (is_string($list_raw)) {
                $list = json_decode($list_raw, true);
            } else {
                $list = [];
            }

            $type_id_raw = $_POST['type_id'] ?? '';
            $type = $_POST['type'] ?? '';

            if (strpos($type, '_') !== false) {
                $type = substr($type, strpos($type, '_') + 1);
            }

            $type_ids = [];
            if (!empty($type_id_raw)) {
                $type_parts = explode('|', $type_id_raw);
                foreach ($type_parts as $t) {
                    $t = trim($t);
                    if ($t !== '') {
                        $dec = data_decrypt($t);
                        if (!empty($dec)) {
                            $type_ids[] = $dec;
                        }
                    }
                }
            }

            if (!empty($list)) {
                foreach ($list as $index => $item) {
                    $make = $item['make'] ?? '';
                    if (empty($make) || (int)$make == 0) {
                        api_response(400, "fail", "Validation failed.", [], [], "Make is required and cannot be empty");
                    }

                    if (!empty($type) && !empty($type_ids)) {
                        foreach ($type_ids as $tid) {
                            $buyleads->addShortListItem($lead_id, $tid, $type);
                        }
                    }
                }
                $result = $buyleads->saveExactMatches($lead_id, $list);

                if ($result) {
                    api_response(200, "ok", "Exact matches saved successfully.");
                } else {
                    api_response(400, "fail", "Exact matches not saved.");
                }
            } elseif (!empty($type) && !empty($type_ids)) {
                foreach ($type_ids as $tid) {
                    $res = $buyleads->addShortListItem($lead_id, $tid, $type);
                }
                if($res){
                    api_response(200, "ok", "Shortlist items added successfully.");
                }
                else{
                    api_response(400, "fail", "Shortlist items not saved.");
                }
            }else {
                api_response(400, "fail", "Empty or invalid exact matches input.", [], []);
            }

            break;
            
            /** --------------------------------------------------------------
            *  DELETE EXACT MATCH
            *  -------------------------------------------------------------- */

            case 'deleteshortlistitem':
                $buyleads->id = (int) $lead_id;

                $encrypted_id = $_POST['row_id'] ?? '';
                if (empty($encrypted_id)) {
                    api_response(400, "fail", "Missing shortlist item ID.");
                }

                $id = data_decrypt($encrypted_id);
                if (empty($id) || !is_numeric($id)) {
                    api_response(400, "fail", "Invalid or corrupted shortlist item ID.");
                }

                $result = $buyleads->deleteShortlistItem($id);
                if ($result) {
                    api_response(200, "ok", "Shortlist item deleted successfully.", $result);
                } else {
                    api_response(500, "fail", "Failed to delete shortlist item.");
                }
                break;

            
            /** --------------------------------------------------------------
            *  DELETE EMATCHE
            *  -------------------------------------------------------------- */

            case 'deleteintresteditem':
                $encrypted_id = $_POST['row_id'] ?? '';
                if (empty($encrypted_id)) {
                    api_response(400, "fail", "Missing row ID.");
                }

                $id = data_decrypt($encrypted_id);
                if (empty($id) || !is_numeric($id)) {
                    api_response(400, "fail", "Invalid or corrupted row ID.");
                }
                $result = $buyleads->deleteIntrestedVehicle((int)$id);

                if ($result) {
                    api_response(200, "ok", "Interested vehicle deleted successfully.", $result);
                } else {
                    api_response(500, "fail", "Failed to delete interested vehicle.");
                }
                break;

            default:
                api_response(400, 'fail', 'Invalid sub-action for update.');
                break;

        }
        break;

     /** --------------------------------------------------------------
     *  GET EXACT MATCH
     *  -------------------------------------------------------------- */

    case 'getexactmatch':
        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id;
        $buyleads->id                  = data_decrypt($_POST['id']);
        //  Validation check
        $errors = [];

        $make = $_POST['make'];
        $model = $_POST['model'];
        $year = $_POST['year'];
        $budget = isset($_POST['budget']) ? trim($_POST['budget']) : '';

        $min_budget = 0;
        $max_budget = 0;

        if (!empty($budget) && strpos($budget, '-') !== false) {
            $parts = explode('-', $budget);
            $min_budget = isset($parts[0]) ? (int) trim($parts[0]) : 0;
            $max_budget = isset($parts[1]) ? (int) trim($parts[1]) : 0;
        }

        // Validation rules
        $fields = [
            'make'        => [
                               'rule' => 'id',
                               'error' => 'Make is not valid',
                               'isRequired'=>true,
                               'requiredError' => 'Make is required'
                             ],
            'model'       => ['rule' => 'id', 'error' => 'Model is not valid', 'isRequired'=>false, 'requiredError' =>''],
            'year'        => ['rule' => 'year', 'error' => 'Year is not valid', 'isRequired'=>false, 'requiredError' =>''],
            'min_budget'        => ['rule' => 'numeric', 'error' => 'Budget is not valid', 'isRequired'=>false, 'requiredError' =>''],
            'max_budget'        => ['rule' => 'numeric', 'error' => 'Budget is not valid', 'isRequired'=>false, 'requiredError' =>''],
        ];
        
        // Validate input fields
        foreach($fields as $field=>$data)
        {
            if(!empty($_POST[$field]) && !validate_field_regex($data['rule'],$_POST[$field]))
            {
                $errors[$field] = $data['error'];
            }
            if($data['isRequired'] && empty($_POST[$field]))
            {
                $errors[$field] = $data['requiredError'];
            }
        }

        if(count($errors)>0)
        {
            api_response(400,'fail', 'Validation failed.', [], [],$errors);
        }

        $type = !empty($_POST['type']) ? $_POST['type'] : 'counts';
        $id = data_decrypt($_POST['id']);

        switch ($type) {
            case 'counts':
                $counts = $buyleads->getMatchCounts($make, $model, $year, $min_budget, $max_budget, $id);
                api_response(200, 'ok', 'Exact match counts fetched.', ['counts' => $counts]);
                break;

            case 'inventory':
                $inventory = $buyleads->getInventoryMatches($make, $model, $year, $min_budget, $max_budget, $id, false);
                if (!empty($inventory)) {
                    api_response(200, 'ok', 'Inventory exact matches found.', ['inventory' => $inventory]);
                } else {
                    api_response(200, 'ok', 'No inventory exact matches found.', ['inventory' => []]);
                }
                break;

            case 'sellleads':
                $sellleads = $buyleads->getSellleadMatches($make, $model, $year, $min_budget, $max_budget, $id, false);
                if (!empty($sellleads)) {
                    api_response(200, 'ok', 'Selllead exact matches found.', ['sellleads' => $sellleads]);
                } else {
                    api_response(200, 'ok', 'No selllead exact matches found.', ['sellleads' => []]);
                }
                break;

            case 'codealer_inventory':
                $inventory = $buyleads->getInventoryMatches($make, $model, $year, $min_budget, $max_budget, $id, true);
                if (!empty($inventory)) {
                    api_response(200, 'ok', 'Inventory exact matches found.', ['codealer_inventory' => $inventory]);
                } else {
                    api_response(200, 'ok', 'No inventory exact matches found.', ['codealer_inventory' => []]);
                }
                break;

            case 'codealer_sellleads':
                $sellleads = $buyleads->getSellleadMatches($make, $model, $year, $min_budget, $max_budget, $id, true);
                if (!empty($sellleads)) {
                    api_response(200, 'ok', 'Selllead exact matches found.', ['codealer_sellleads' => $sellleads]);
                } else {
                    api_response(200, 'ok', 'No selllead exact matches found.', ['codealer_sellleads' => []]);
                }
                break;

            default:
                api_response(400,'fail', 'Invalid action');
                break;
        }
        break;
        
    
     /** --------------------------------------------------------------
     *  GET READY FOR SALE VEHICLES LIST
     *  -------------------------------------------------------------- */

    case 'getreadyforsalevehicleslist':
          $buyleads = new Buyleads();
          $buyleads->logged_user_id      = $logged_user_id;
          $buyleads->logged_dealer_id    = $logged_dealer_id;
          $buyleads->logged_branch_id    = $logged_branch_id;
          $buyleads->logged_executive_id = $logged_executive_id;

        $input_id = $_POST['id'] ?? '';
        $lead_id = !empty($input_id) ? data_decrypt($input_id) : 0;
        $result = $buyleads->getReadyForSaleVehiclesList($lead_id);
        if(!empty($result))
        {
            api_response(200,"ok","Ready for sale vehicles list fetched successfully.",["list" => $result]);
        }
        else
        {
            api_response(200,"ok","Fetched empty vehicles list.",["list" => []]);
        }
        break; 

    case 'gettestdrivevehicles':
        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id;

        $input_id = $_POST['id'] ?? null;
        if (!$input_id) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }

        $lead_id = data_decrypt($input_id);
        if (!is_numeric($lead_id) || $lead_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }
        $result = $buyleads->getTestDriveVehicles($lead_id);
        if(!empty($result))
        {
          api_response(200,"ok","Test Drive Vehicles list fetched successfully.",["list" => $result]);
        }
        else
        {
          api_response(200,"ok","Fetched empty test drive vehicles list.",["list" => []]);
        }
        break; 
// export functionality not in use
     case 'exportdata':
        exit; //  disable export functionality for now. 
            $buyleads = new Buyleads();
            $buyleads->logged_user_id      = $logged_user_id;
            $buyleads->logged_dealer_id    = $logged_dealer_id;
            $buyleads->logged_branch_id    = $logged_branch_id;
            $buyleads->logged_executive_id = $logged_executive_id;

            $filters = $errors = [];

            // Validation rules
            $fields = [
                'mobile'      => ['rule' => 'mobile', 'error' => 'Mobile number is not valid'],
                'email'       => ['rule' => 'email', 'error' => 'Email address is not valid'],
                'buyer_name'  => ['rule' => 'alphanumericspecial', 'error' => 'Buyer name is not valid'],
                'make'        => ['rule' => 'id', 'error' => 'Make is not valid'],
                'model'       => ['rule' => 'id', 'error' => 'Model is not valid'],
            ];
            
            // Validate input fields
            foreach($fields as $field=>$data)
            {
                if(!empty($_POST[$field]) && !validate_field_regex($data['rule'],$_POST[$field]))
                {
                    $errors[$field] = $data['error'];
                }
            }
            if(count($errors)>0)
            {
                api_response(400,'fail','Validation failed',[],[],$errors);
            }

            if (!empty($_POST['id'])) { $filters['id'] = $_POST['id'];}
            if (!empty($_POST['buyer_name'])) { $filters['buyer_name'] = $_POST['buyer_name'];}
            if (!empty($_POST['mobile'])) { $filters['mobile'] = $_POST['mobile'];}
            if (!empty($_POST['email'])) { $filters['email'] = $_POST['email'];}
            if (!empty($_POST['make'])) { $filters['make'] = $_POST['make'];}
            if (!empty($_POST['model'])) { $filters['model'] = $_POST['model'];}

            $data = $buyleads->exportBuyleads($filters);

            if ($data) {
                api_response(200, 'ok', 'Dealerships exported successfully', $data);
            } else {
                api_response(500, 'fail', 'Failed to export dealerships', [], []);
            }
        break;

    case 'gethistory':
        $buyleads = new Buyleads();
        $buyleads->logged_user_id      = $logged_user_id;
        $buyleads->logged_dealer_id    = $logged_dealer_id;
        $buyleads->logged_branch_id    = $logged_branch_id;
        $buyleads->logged_executive_id = $logged_executive_id;


        $input_id = $_POST['id'] ?? null;
        if (!$input_id) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is required.');
        }
        $lead_id = data_decrypt($input_id);
        if (!is_numeric($lead_id) || $lead_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Lead ID is not valid.');
        }

        $history_details = $buyleads->getLeadHistory($lead_id);

        api_response(200, 'ok', '', ['history'=>$history_details], [
            'logged_user_id'      => $logged_user_id,
            'logged_dealer_id'    => $logged_dealer_id,
            'logged_branch_id'    => $logged_branch_id,
            'logged_executive_id' => $logged_executive_id
        ]);
        break;



     /** --------------------------------------------------------------
     *  DEFAULT
     *  -------------------------------------------------------------- */

    default:
        api_response(400,'fail', 'Invalid action');
        break;
}
?>