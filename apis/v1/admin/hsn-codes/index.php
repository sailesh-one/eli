<?php
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_hsn-codes.php';

$hsn = new HSN();
$user_id = $GLOBALS['api_user']['uid'] ?? null;

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'getAllHsnCodes':
        $page = isset($_POST['page']) ? $_POST['page'] : 1;
        $perPage = isset($_POST['perPage']) ? $_POST['perPage'] : 1;

        $result = $hsn->getAllHsnCodes($page,$perPage);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $result);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;

    case 'saveHSN':
        $errors = [];
        if(empty($_POST['hsn_code'])){$errors['hsn_code'] = "HSN code is required";}            
        if(empty($_POST['cgst'])){$errors['cgst'] = "CGST is required";}            
        if(empty($_POST['sgst'])){$errors['sgst'] = "SGST is required";}            
        if(empty($_POST['igst'])){$errors['igst'] = "IGST is required";}    
        
        if(!empty($_POST['hsn_code']) && validate_number_dots('number_dot',$_POST['hsn_code'])) {
            $errors['hsn_code'] = "! Invalid HSN code, it should be number and dots format";
        }
        if(!empty($_POST['hsn_code']) && strlen($_POST['hsn_code'])>15) {
            $errors['hsn_code'] = "HSN number length should not cross 15";
        }
        if(!empty($_POST['cgst']) && validate_number_dots('number_dot',$_POST['cgst'])) {
            $errors['cgst'] = "! Invalid CGST format";
        }
        if(!empty($_POST['cgst']) && strlen($_POST['cgst'])>100) {
            $errors['cgst'] = "! CGST should be less than 100";
        }
        if(!empty($_POST['sgst']) && validate_number_dots('number_dot',$_POST['sgst'])) {
            $errors['sgst'] = "! Invalid SGST format";
        }
        if(!empty($_POST['cgst']) && strlen($_POST['sgst'])>100) {
            $errors['cgst'] = "! SGST should be less than 100";
        }
        if(!empty($_POST['igst']) && validate_number_dots('number_dot',$_POST['igst'])) {
            $errors['igst'] = "! Invalid IGST format";
        }
        if(!empty($_POST['cgst']) && strlen($_POST['igst'])>100) {
            $errors['cgst'] = "! CGST should be less than 100";
        }
        if(count($errors) > 0){
            api_response(200, 'fail', 'Validation error.', [], [], $errors);
        }
       
        $result = $hsn->addHsnCodes();
        if ($result) {
            api_response(200, 'ok', 'HSN code added successfully.', []);
        } else {
            api_response(500, 'fail', 'Failed to update HSN Code.', []);
        }
        break;

    case 'editHSN':
            $errors = [];
            if(empty($_POST['id'])){$errors['id'] = "Edit ID is required";}  
            if(empty($_POST['hsn_code'])){$errors['hsn_code'] = "HSN code is required";}            
            if(empty($_POST['cgst'])){$errors['cgst'] = "CGST is required";}            
            if(empty($_POST['sgst'])){$errors['sgst'] = "SGST is required";}            
            if(empty($_POST['igst'])){$errors['igst'] = "IGST is required";}    
            
            if(!empty($_POST['id']) && $_POST['id']<=0){
                $errors['id'] = "Edit ID Should not be zero";
            }
            if(!empty($_POST['hsn_code']) && validate_number_dots('number_dot',$_POST['hsn_code'])) {
                $errors['hsn_code'] = "! Invalid HSN code, it should be number and dots format";
            }
            if(!empty($_POST['hsn_code']) && strlen($_POST['hsn_code'])>15) {
                $errors['hsn_code'] = "HSN number length should not cross 15";
            }
            if(!empty($_POST['cgst']) && validate_number_dots('number_dot',$_POST['cgst'])) {
                $errors['cgst'] = "! Invalid CGST format";
            }
            if(!empty($_POST['cgst']) && strlen($_POST['cgst'])>100) {
                $errors['cgst'] = "! CGST should be less than 100";
            }
            if(!empty($_POST['sgst']) && validate_number_dots('number_dot',$_POST['sgst'])) {
                $errors['sgst'] = "! Invalid SGST format";
            }
            if(!empty($_POST['cgst']) && strlen($_POST['sgst'])>100) {
                $errors['cgst'] = "! SGST should be less than 100";
            }
            if(!empty($_POST['igst']) && validate_number_dots('number_dot',$_POST['igst'])) {
                $errors['igst'] = "! Invalid IGST format";
            }
            if(!empty($_POST['cgst']) && strlen($_POST['igst'])>100) {
                $errors['cgst'] = "! CGST should be less than 100";
            }
            if(count($errors) > 0){
                api_response(200, 'fail', 'Validation error.', [], [], $errors);
            }
            
            $result = $hsn->updateHsnCodes($_POST['id']);
            if ($result) {
                api_response(200, 'ok', 'HSN code updated successfully.', []);
            } else {
                api_response(500, 'fail', 'Failed to update HSN Code.', []);
            }
        break;
    default:
        api_response(400, 'fail', 'Invalid action in panel');
        break;
}
?>