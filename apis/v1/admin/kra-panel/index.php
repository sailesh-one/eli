<?php

global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_kra_targets.php';

$kra = new KRA();
$user_id = $GLOBALS['api_user']['uid'] ?? null;

$action = strtolower($_POST['action'] ?? '');


switch ($action) {
    case 'list':
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        $months = $config['months'];
        $errors = [];
        if (!isset($_POST['kra_year']) || trim($_POST['kra_year']) === '') {
            $errors['kra_year'] = 'KRA Year is empty';
        }

        if (!isset($_POST['kra_month']) || trim($_POST['kra_month']) === '') {
            $errors['kra_month'] = 'KRA Month is empty';
        }
        if(!empty($_POST['kra_year']) && !validate_field_regex('year', $_POST['kra_year'])){
            $errors['kra_year'] = 'Invalid KRA Year.';
        }
        if(!empty($_POST['kra_month']) && !validate_field_regex('month', $_POST['kra_month'])){
            $errors['kra_month'] = 'Invalid KRA Month.';
        }
        if(count($errors) > 0) {
            api_response(400, 'fail', 'Validation error.', [], [], $errors);
        }
        $kra_targets = [];
        $kra_targets = $kra->getTargets();
        if ($kra_targets) {
            api_response(200, 'ok', 'Targets fetched successfully.', [$kra_targets,$months]);
        } else {
            api_response(500, 'fail', 'Failed to fetch Targets.', []);
        }
        break;
        
    case 'save':
        $errors = [];
        if(empty($_POST['branch'])){$errors['branch'] = "Branch is required";}            
        if(empty($_POST['dealer'])){$errors['branch'] = "Dealer is required";}            
        if(empty($_POST['month'])){$errors['month'] = "Month is required";}            
        if(empty($_POST['year'])){$errors['year'] = "Year is required";}    
        if(empty($_POST['field'])){$errors['field'] = "Field is required";}    
        if(empty($_POST['value'])){$errors['value'] = "Value is required";}    
        
        if(!empty($_POST['branch']) && !validate_field_regex('numeric', $_POST['branch'])) {
            $errors['branch'] = "Branch is not valid";
        }
        if(!empty($_POST['dealer']) && !validate_field_regex('numeric', $_POST['dealer'])) {
            $errors['dealer'] = "Branch is not valid";
        }
        if(!empty($_POST['year']) && !validate_field_regex('year', $_POST['year'])){
            $errors['year'] = 'Invalid KRA Year.';
        }
        if(!empty($_POST['month']) && !validate_field_regex('month', $_POST['month'])){
            $errors['month'] = 'Invalid Month.';
        }
        if(!empty($_POST['field']) && !validate_field_regex('field', $_POST['field'])){
            $errors['field'] = 'Invalid Field.';
        }
        if(!empty($_POST['value']) && !validate_field_regex('numeric', $_POST['value'])){
            $errors['value'] = 'Invalid value.';
        }
        if(count($errors) > 0){
            api_response(400, 'fail', 'Validation error.', [], [], $errors);
        }

        //check availability
        $id = $kra->checkAvailability();
        if($id && $id > 0){
            $result = $kra->updateKraTarget($id);
        }else{
            $result = $kra->addKraTarget();
        }
        if ($result) {
            api_response(200, 'ok', 'Targets updated successfully.', []);
        } else {
            api_response(500, 'fail', 'Failed to update Targets.', []);
        }
        break;

    default:
        api_response(400, 'fail', 'Invalid action in panel');
        break;

}

?>
