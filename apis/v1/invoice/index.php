<?php
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_invoice.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_sources.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_curl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_buyleads.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_chrome_headless_pdf.php';

$module = 'invoice';
$api_user_id = $GLOBALS['api_user']['uid'] ?? null;
$dealer_id = $GLOBALS['dealership']['dealership_id'] ?? null;
$branch_id = $GLOBALS['dealership']['branch_id'] ?? null;
$role_main = $GLOBALS['dealership']['role_main'] ?? null;
$auth_user = $GLOBALS['api_user']['auth_user'] ?? null;  
$executive_id = 0; 

if ($dealer_id != $api_user_id) {
    $executive_id = $api_user_id;
}
$executive_id = ($role_main == 'y') ? 0 : $api_user_id;

// Handle ID for constructor - always decrypt
$constructor_id = 0;
if (!empty($_POST['id'])) {
    $constructor_id = (int)data_decrypt($_POST['id']);
}

$invoice = new Invoices($constructor_id);
$invoice->dealer_id = $dealer_id;
$invoice->executive_id = $executive_id;
$invoice->login_user_id = $api_user_id;
$invoice->branch_id = $branch_id;
$invoice->auth_user = $auth_user;

$files = new Files();

$action = strtolower($_REQUEST['action'] ?? '');

switch ($action) {
    case 'getconfig':
        $moduleConfig = new moduleConfig();
        api_response(200, 'ok', 'Configuration retrieved successfully.', [
            'config' => $moduleConfig->getConfig($module)
        ]);
        break;

    case 'getlist':
        $current_page = $_POST['current_page'] ?? 1;
        $perPage = $_POST['perPage'] ?? 10;
        $filters = $errors = [];
        if( !empty($_POST['invoice_number']) && !validate_field_regex('alphanumericspecial',$_POST['invoice_number']) )
        {
            $errors['invoice_number'] = "Invoice number is not valid";
        }
        if( !empty($_POST['customer_name']) && !validate_field_regex('alphanumeric',$_POST['customer_name']) )
        {
            $errors['customer_name'] = "Customer name is not valid";
        }
        if( !empty($_POST['customer_mobile']) && !validate_field_regex('mobile',$_POST['customer_mobile']) )
        {
            $errors['customer_mobile'] = "Customer mobile is not valid";
        }
        if( !empty($_POST['registration_no']) && !validate_field_regex('alphanumeric',$_POST['registration_no']) )
        {
            $errors['registration_no'] = "Registration number is not valid";
        }
        if (!empty($errors)) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }
        if (!empty($_POST['invoice_number'])) { $filters['invoice_number'] = $_POST['invoice_number'];}
        if (!empty($_POST['customer_name'])) { $filters['customer_name'] = $_POST['customer_name'];}
        if (!empty($_POST['customer_mobile'])) { $filters['customer_mobile'] = $_POST['customer_mobile'];}
        if (!empty($_POST['registration_no'])) { $filters['registration_no'] = $_POST['registration_no'];}

        $post_status = $_POST['status'] ?? '';
        if (empty($post_status)) {
            $post_status = 'all';
        }
        $statuses = $invoice->getStatuses();
        $status = '';
        $sub_status = '';

        if ($post_status === 'all') {
        } elseif (isset($statuses[$post_status])) {
            $status = $statuses[$post_status]['status_id'];
        } else {
            $found = false;
            foreach ($statuses as $topKey => $topStatus) {
                if (!empty($topStatus['sub'][$post_status])) {
                    $status = $topStatus['status_id'];
                    $sub_status = $topStatus['sub'][$post_status]['status_id'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                api_response(400, 'fail', 'Invalid status.', [], []);
            }
        }

        $invoices = $invoice->getInvoices($filters,$status, $sub_status, $current_page, $perPage);

        if (!empty($invoices['list'])) {
            api_response(200, 'ok', 'Invoices fetched', $invoices);
        } else {
            api_response(200, 'ok', 'No invoices found', $invoices);
        }
        break;

    case 'getlead':
        try {
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                api_response(400, 'fail', 'Validation failed.', [], [], 'Invoice id is required.');
            }

            $input_id = $_POST['id'];
            $invoice_id = data_decrypt($input_id);

            if (!is_numeric($invoice_id) || $invoice_id <= 0) {
                api_response(400, 'fail', 'Validation failed.', [], [], 'Invoice ID is not valid.');
            }

            $invoice_id = (int)$invoice_id;
            $invoice_data = $invoice->getInvoice($invoice_id);

            if (empty($invoice_data)) {
                api_response(404, 'fail', 'Invoice not found.', [], []);
            }

            api_response(200, 'ok', 'Invoice fetched', $invoice_data);

        } catch (Exception $e) {
            api_response(500, 'fail', 'Internal server error. Please try again later.');
        }
        break;

    case 'addinvoice':
        $moduleConfig = new moduleConfig();
        $config_data = $moduleConfig->getConfig($module)['customerAddConfig'] ?? [];

        $validation = validate_addconfig($config_data, $_POST);

        if (!empty($validation['errors'])) {
            api_response(400, 'fail', 'Validation Failed', [], [], $validation['errors']);
        }

        $invoice_id = $invoice->addInvoice($validation['data']);
        if ($invoice_id) {
            api_response(200, 'ok', 'Invoice added successfully.', ['id' => $invoice_id]);
        } else {
            api_response(400, 'fail', 'Failed to save invoice. Please try again.', []);
        }
        break;

    case 'update':
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            api_response(400, 'fail', 'Invoice id is required.', [], []);
        }

        $invoice_id = (int)data_decrypt($_POST['id']);
        if ($invoice_id <= 0) {
            api_response(400, 'fail', 'Invalid Invoice ID.', [], []);
        }

        $status_check = $invoice->getStatus($invoice_id);
        if ($status_check['status'] > 1) {
            api_response(400, 'fail', 'Cannot update the values', [], []);
        }
        $moduleConfig = new moduleConfig();
        $detailKey = trim($_POST['sub_action'] ?? '');
        $config_data = $moduleConfig->getConfig($module)[$detailKey] ?? [];

        $validation  = validate_addconfig($config_data, $_POST);

        if (!empty($validation['errors'])) {
            api_response(400, 'fail', 'Validation Failed', [], [], $validation['errors']);
        }

        $local_errors = [];
        $data_payload = $validation['data'];

        foreach ($_FILES as $fieldKey => $fileInfo) {
            if (isset($fileInfo) && $fileInfo['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $files->uploadFiles($fieldKey, $file_rename, 'docs');
                if ($uploadResult['status']) {
                    $data_payload[$fieldKey] = $uploadResult['file_name'];
                } else {
                    $local_errors[$fieldKey] = $uploadResult['msg'];
                }
            } elseif (!empty($_POST[$fieldKey]) && is_string($_POST[$fieldKey])) {
                $data_payload[$fieldKey] = $_POST[$fieldKey];
            }
        }
        if (!empty($local_errors)) {
            api_response(400, 'fail', 'File upload failed', [], [], $local_errors);
        }
        switch ($detailKey) {
            case 'customerAddConfig':
                $result = $invoice->updateCustomerData($invoice_id, $data_payload);
                if ($result) {
                    $invoice_id = data_encrypt($invoice_id);
                    api_response(200, 'ok', 'Invoice updated successfully.', ['id' => $invoice_id]);
                } else {
                    api_response(400, 'fail', 'Failed to update invoice.', []);
                }
                break;
            case 'paymentAddConfig':
                $result = $invoice->updatePaymentData($invoice_id, $data_payload);
                if ($result) {
                    $invoice_id = data_encrypt($invoice_id);
                    api_response(200, 'ok', 'Invoice payment updated successfully.', ['id' => $invoice_id]);
                } else {
                    api_response(400, 'fail', 'Failed to update invoice payment.', []);
                }
                break;
            case 'invoiceAddConfig':
                $result = $invoice->updateInvoiceData($invoice_id, $data_payload);
                if ($result) {
                    $invoice_id = data_encrypt($invoice_id);
                    api_response(200, 'ok', 'Invoice details updated successfully.', ['id' => $invoice_id]);
                } else {
                    api_response(400, 'fail', 'Failed to update invoice details.', []);
                }
                break;
            case 'documentAddConfig':
                $result = $invoice->updateDocumentData($invoice_id, $data_payload);
                if ($result) {
                    $invoice_id = data_encrypt($invoice_id);
                    api_response(200, 'ok', 'Invoice documents updated successfully.', ['id' => $invoice_id]);
                } else {
                    api_response(400, 'fail', 'Failed to update invoice documents.', []);
                }
                break;
            default:
                api_response(400, 'fail', 'Invalid sub action for invoice update.', [], []);
                break;
        }
        break;

    case 'updatestatus':
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            api_response(400, 'fail', 'Invoice id is required.', [], []);
        }
        if(!isset($_POST['status']) || empty($_POST['status'])) {
            api_response(400, 'fail', 'Status is required', [], []);
        } 
        if($_POST['status'] == 3 && (!isset($_POST['sub_status']) || empty($_POST['sub_status']))){
            api_response(400, 'fail', 'Sub status is required', [], []);
        }
        $invoice_id = (int)data_decrypt($_POST['id']);
        if ($invoice_id <= 0) {
            api_response(400, 'fail', 'Invalid Invoice ID.', [], []);
        }

        $get_invoice = $invoice->getInvoice($invoice_id);
        $moduleConfig = new moduleConfig();
        $cfg = $moduleConfig->getConfig($module) ?? [];

        $validate_customer = $cfg['customerAddConfig'] ?? [];
        $validate_invoice  = $cfg['invoiceAddConfig'] ?? [];
        $validate_payment  = $cfg['paymentAddConfig'] ?? [];

        $validation_customer = validate_addconfig($validate_customer, $get_invoice);
        $validation_invoice  = validate_addconfig($validate_invoice,  $get_invoice);
        $validation_payment  = validate_addconfig($validate_payment,  $get_invoice);

        $merged_errors = array_merge($validation_customer['errors'] ?? [],$validation_invoice['errors']  ?? [],$validation_payment['errors']  ?? [] );

        if (!empty($merged_errors) && (int)$_POST['status'] > 1) {
            api_response(400, 'fail', 'Cannot update status. Invoice data is incomplete.', [], [], $merged_errors);
        }

        $data = [
            'status' => $_POST['status'],
            'sub_status' => $_POST['sub_status']
        ];

        // Add invoice_cancellation_date if status = 3 and sub_status = 2
        if ($_POST['status'] == 3 && $_POST['sub_status'] == 2) {
            $data['invoice_cancellation_date'] = date('Y-m-d H:i:s');

            $stock_id = intval($get_invoice['stock_id'] ?? 0);
            $buylead_id = intval($get_invoice['buylead_id'] ?? 0);

            if ($stock_id > 0 && $buylead_id > 0) {
                // mark buylead as lost
                $buylead = new Buyleads($buylead_id);

                $previous_status = $buylead->status ?? 0;
                $previous_sub_status = $buylead->sub_status ?? 0;
                $previous_followup_date = $buylead->followup_date ?? 0;

                $buylead->id = $buylead_id;
                $buylead->status = '7'; // lost
                $buylead->sub_status = '8'; // cancelled invoice

                $lost_lead = $buylead->updateLeadStatus($previous_status, $previous_sub_status, $previous_followup_date);
                if (empty($lost_lead['status']) || !$lost_lead['status']) {
                    api_response(400, 'fail', 'Failed to update buylead status as lost', [], []);
                }
            }
            else{
                api_response(400, 'fail', 'buylead and stock id are required to update stock status', [], []);
            }
        }

        $result = $invoice->updateInvoice($invoice_id, $data);
        if ($result) {
            api_response(200, 'ok', 'Invoice status updated successfully.', ['id' => $invoice_id]);
        } else {
            api_response(400, 'fail', 'Failed to update invoice.', []);
        }
        break;

    case 'getInvoiceHistory':
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            api_response(400, 'fail', 'Invoice id is required.', [], []);
        }

        $invoice_id = (int)data_decrypt($_POST['id']);
        if ($invoice_id <= 0) {
            api_response(400, 'fail', 'Invalid Invoice ID.', [], []);
        }

        $history = $invoice->getInvoiceHistory($invoice_id);
        if (!empty($history)) {
            api_response(200, 'ok', 'Invoice history fetched successfully.', ['history' => $history]);
        } else {
            api_response(200, 'ok', 'No history found for this invoice.', ['history' => []]);
        }
        break;

    case 'deleteinvoice':
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            api_response(400, 'fail', 'Invoice id is required.', [], []);
        }

        $invoice_id = (int)data_decrypt($_POST['id']);
        if ($invoice_id <= 0) {
            api_response(400, 'fail', 'Invalid Invoice ID.', [], []);
        }

        $result = $invoice->deleteInvoice($invoice_id);
        if ($result) {
            api_response(200, 'ok', 'Invoice deleted successfully.', ['id' => $invoice_id]);
        } else {
            api_response(400, 'fail', 'Failed to delete invoice.', []);
        }
        break;


     // this exportdata is not being used currently due to export functionality is not in use
    case 'exportdata':
        exit; // Disable this action for now
        $filters = $errors = [];
        if( !empty($_POST['invoice_number']) && !validate_field_regex('alphanumeric',$_POST['invoice_number']) )
        {
            $errors['invoice_number'] = "Invoice number is not valid";
        }
        if( !empty($_POST['customer_name']) && !validate_field_regex('alphanumeric',$_POST['customer_name']) )
        {
            $errors['customer_name'] = "Customer name is not valid";
        }
        if( !empty($_POST['customer_mobile']) && !validate_field_regex('mobile',$_POST['customer_mobile']) )
        {
            $errors['customer_mobile'] = "Customer mobile is not valid";
        }
        if( !empty($_POST['registration_no']) && !validate_field_regex('alphanumeric',$_POST['registration_no']) )
        {
            $errors['registration_no'] = "Registration number is not valid";
        }
        if (!empty($errors)) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }
        if (!empty($_POST['invoice_number'])) { $filters['invoice_number'] = $_POST['invoice_number'];}
        if (!empty($_POST['customer_name'])) { $filters['customer_name'] = $_POST['customer_name'];}
        if (!empty($_POST['customer_mobile'])) { $filters['customer_mobile'] = $_POST['customer_mobile'];}
        if (!empty($_POST['registration_no'])) { $filters['registration_no'] = $_POST['registration_no'];}

        $data = $invoice->exportInvoice($filters);

        if ($data) {
            api_response(200, 'ok', 'Invoice exported successfully', $data);
        } else {
            api_response(500, 'fail', 'Failed to export Invoices', [], []);
        }
        break;


    case 'download_preview':
        global $config;
        $id = $_POST['id'] ?? null;
        if (!$id) api_response(400, 'fail', 'Missing ID');

        $url = $config['service_base_url'] . "/generate/invoice?id=" . urlencode($id);
        $fileName = "preview_{$id}_" . date('Ymd_His') . ".pdf";
        $targetDir = 'docs';

        $chromePDF = new chrome_headless_pdf();
        $result = $chromePDF->generate($url, $fileName);

        if ($result['status'] === 'success') {
            api_response(200, 'success', $result['msg'], [
                'file_name' => $result['file_name'],
                'file_url' => $result['file_url']
            ]);
        } else {
            api_response(500, 'fail', $result['msg']);
        }
        break;

    case 'gethistory':
      
        $input_id = $_POST['id'];
        $invoice_id = data_decrypt($input_id);
        if (!is_numeric($invoice_id) || $invoice_id <= 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], 'Invoice ID is not valid.');
        }

        $history_details = $invoice->getInvoiceHistory($invoice_id);

        api_response(200, 'ok', '', ['history'=>$history_details], []);
        break;

    default:
        api_response(400, 'fail', 'Invalid action for invoices');
        break;
}
