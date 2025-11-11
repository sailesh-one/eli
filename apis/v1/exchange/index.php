<?php
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_exchange.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_curl.php';

$module = 'exchange';
$api_user_id = $GLOBALS['api_user']['uid'] ?? null;
$dealer_id = $GLOBALS['dealership']['dealership_id'] ?? null;
$branch_id = $GLOBALS['dealership']['branch_id'] ?? null;
$role_main = $GLOBALS['dealership']['role_main'] ?? null;

// $executive_id = 0; 
// $executive_name = '';

// if ($dealer_id != $api_user_id) {
//     $executive_id = $api_user_id;
//     $executive_name = $GLOBALS['dealership']['name'];
// }
// $executive_id = ($role_main == 'y') ? 0 : $api_user_id;

// // Handle ID for constructor - always decrypt
// $constructor_id = 0;
// if (!empty($_POST['id'])) {
//     $constructor_id = (int)data_decrypt($_POST['id']);
// }

$exchange = new Exchange();
$exchange->dealer_id = $dealer_id;
$exchange->executive_id = $executive_id;
$exchange->executive_name = $executive_name;
$exchange->login_user_id = $api_user_id;
$exchange->branch_id = $branch_id;

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
        $filters = $errors = [];
        
        // Validate filters
        if (!empty($_POST['reg_num']) && !validate_field_regex('reg_num', $_POST['reg_num'])) {
            $errors['reg_num'] = "Registration number is not valid";
        }
        if (!empty($_POST['make']) && !validate_field_regex('id', $_POST['make'])) {
            $errors['make'] = "Make is not valid";
        }
        if (!empty($_POST['model']) && !validate_field_regex('id', $_POST['model'])) {
            $errors['model'] = "Model is not valid";
        }
        if (!empty($_POST['new_reg_num']) && !validate_field_regex('reg_num', $_POST['new_reg_num'])) {
            $errors['new_reg_num'] = "New registration number is not valid";
        }
        
        if (count($errors) > 0) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }
        
        // Build filters
        if (!empty($_POST['id'])) {
            $filters['id'] = data_decrypt($_POST['id']);
        }
        if (!empty($_POST['status']) && trim($_POST['status']) !== '' && $_POST['status'] !== 'undefined') {
            $filters['status'] = $_POST['status'];
        }
         if (!empty($_POST['reg_num'])) {
            $filters['reg_num'] = $_POST['reg_num'];
        }
        if (!empty($_POST['chassis'])) {
            $filters['chassis'] = $_POST['chassis'];
        }
        if (!empty($_POST['new_reg_num'])) {
            $filters['new_reg_num'] = $_POST['new_reg_num'];
        }
        if (!empty($_POST['new_chassis'])) {
            $filters['new_chassis'] = $_POST['new_chassis'];
        }
        if (!empty($_POST['customer_name'])) {
            $filters['customer_name'] = $_POST['customer_name'];
        }
        if (!empty($_POST['mobile'])) {
            $filters['mobile'] = $_POST['mobile'];
        }
        if (!empty($_POST['date'])) {
            $filters['date'] = $_POST['date'];
        }
    
        $current_page = $_POST['current_page'] ?? 1;
        $perPage = $_POST['perPage'] ?? 10;
        
        $result = $exchange->getExchanges($filters, $current_page, $perPage);

        if (!empty($result['data'])) {
           api_response(200, 'ok', 'Exchanges fetched', $result);
        } else {
           api_response(200, 'ok', 'No exchanges found', $result);
        }
        break;

    case 'getdetail':
        try {
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                api_response(400, 'fail', 'Validation failed.', [], [], 'Exchange id is required.');
            }
            
            $input_id = $_POST['id'];
            $exchange_id = data_decrypt($input_id);
            
            // Validate ID is usable
            if (!is_numeric($exchange_id) || $exchange_id <= 0) {
                api_response(400, 'fail', 'Validation failed.', [], [], 'Exchange ID is not valid.');
            }
            
            // Explicitly cast to int
            $exchange_id = (int)$exchange_id;
            
            // Check ownership
            try {
                if (!$exchange->ownerCheck($exchange_id)) {
                    api_response(403, 'fail', 'Access denied.', [], []);
                }
            } catch (Exception $ownerException) {
                api_response(500, 'fail', 'Error checking permissions.');
            }
            
            try {
                // Get exchange details
                $detail = $exchange->getDetail($exchange_id);
                
                if (empty($detail)) {
                    api_response(404, 'fail', 'Exchange not found.', [], []);
                }
            } catch (Exception $getDetailException) {
                api_response(500, 'fail', 'Error retrieving exchange details.');
            }
            
            api_response(200, 'ok', 'Exchange fetched', ['detail' => $detail]);
            
        } catch (Exception $e) {
            api_response(500, 'fail', 'Internal server error. Please try again later.');
        }
        break;
    case 'updatenewcardata':
        $errors = [];
        $data=[];
        // Validation rules
        if($_POST['new_chassis']==''){
            $errors['new_chassis']='Enter Chassis number';
        }else if(!empty($_POST['new_chassis']) && ! validate_field_regex('chassis',$_POST['new_chassis'])){
             $errors['new_chassis']='Enter Valid Chassis number';
        }
        if(empty($_POST['benefit_flag'])){
            $errors['benefit_flag']='Select offer exchange benefit';
        }
        if(isset($_POST['benefit_flag']) && $_POST['benefit_flag']==1 && $_POST['bonus_price']==''){
            $errors['bonus_price']="Bonus price required";
        }
        if(isset($_POST['bonus_price']) && $_POST['bonus_price']!='' && $_POST['bonus_price']<=0){
            $errors['bonus_price']="Bonus price should not be zero";
        }
        if ($errors) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }
        $data['new_chassis']=$_POST['new_chassis']?? '';
        $data['bonus_price']=$_POST['bonus_price']?? '';
        $data['benefit_flag']=$_POST['benefit_flag']?? '';
        $data['id']=$_POST['id'] ?? '';
        $check_vin=$exchange->checkNewCarVinExists($data);
        if($check_vin){
            $errors['new_chassis']='The Chassis number already exists to another vehicle';
            api_response(400,"fail","Validation failed.",[], [], $errors);
        }
        $result=$exchange->updateNewcarVinBonus($data);
        if($result['status']=='200'){
            api_response(200,"ok","New car data added successsfully.");
        }else{
            api_response(400,"fail","Failed to add new car data.");
        }
        break;
        // this exportdata is not being used currently due to export functionality is not in use
     case 'exportdata':
        // validations of search is pending
        exit; // remove this exit when using this case

        $exchange = new Exchange();
        $exchange->logged_user_id      = $api_user_id;
        $exchange->logged_dealer_id    = $logged_dealer_id;
        $exchange->logged_branch_id    = $logged_branch_id;
        $exchange->logged_executive_id = $logged_executive_id;
        $data = $exchange->exportExchangeleads();
        if ($data) {
            api_response(200, 'ok', 'Exchange leads exported successfully', $data);
        } else {
            api_response(500, 'fail', 'Failed to export Exchange', [], []);
        }
        break;  
  
    default:
        api_response(400, 'fail', 'Invalid action.');
        break;
}