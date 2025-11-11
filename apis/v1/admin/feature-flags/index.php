<?php

global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_features.php';

$feature = new Features();
$user_id = $GLOBALS['api_user']['uid'] ?? null;

$action = strtolower($_POST['action'] ?? '');


switch ($action) {
    case 'list':
        $features = [];
        $features = $feature->get_feature_flags();
        if ($features) {
            api_response(200, 'ok', 'Feature flags fetched successfully.', $features);
        } else {
            api_response(500, 'fail', 'Failed to fetch feature flags.', []);
        }
        break;

    case 'add':
        $errors = [];
        if(empty($_POST['flag_name'])) { $errors['flag_name'] = 'Flag name is required.'; }
        if(empty($_POST['description'])) { $errors['description'] = 'Description is required.'; }
        if(empty($_POST['flag_type'])) { $errors['flag_type'] = 'Flag type is required.'; }
        if(empty($_POST['value'])) { $errors['value'] = 'Value is required.'; }
        if(!empty($_POST['flag_name']) && !validate_field_regex('alpha', $_POST['flag_name'])){
            $errors['flag_name'] = 'Invalid flag name.';
        }
        if(!empty($_POST['description']) && !validate_field_regex('alphanumericspecial', $_POST['description'])){
            $errors['description'] = 'Invalid description.';
        }
        if(!empty($_POST['flag_type']) && !validate_field_regex('flag', $_POST['flag_type'])){
            $errors['flag_type'] = 'Invalid flag type.';
        }
        
        // Validate datetime format for flag_type 4
        if(!empty($_POST['flag_type']) && $_POST['flag_type'] == 4) {
            if(empty($_POST['value'])) {
                $errors['value'] = 'Date and time are required for DateTime type.';
            } else if(!validate_field_regex('date_time', $_POST['value'])){
                $errors['value'] = 'Invalid date/time format. Use YYYY-MM-DD HH:MM:SS';
            }
        }
        
        // if(!empty($_POST['value']) && !validate_field_regex('value', $_POST['value'])){
        //     $errors['value'] = 'Invalid value.';
        // }
        $duplicate = $feature->checkFeature();
        if($duplicate){
            $errors['flag_name'] = 'Flag Name is already existed';
        }
        if(count($errors) > 0) {
            api_response(400, 'fail', 'Validation error.', [], [], $errors);
        }

        $result = $feature->add_feature_flag();
        if ($result) {
            api_response(200, 'ok', 'Feature flag added successfully.', []);
        } else {
            api_response(500, 'fail', 'Failed to add feature flag.', []);
        }
        break;

    case 'update':
        $errors = [];
        if(empty($_POST['id'])) { $errors['id'] = 'ID is required.'; }
        if(empty($_POST['description'])) { $errors['description'] = 'Description is required.'; }
        if(empty($_POST['flag_type'])) { $errors['flag_type'] = 'Flag type is required.'; }
        if(empty($_POST['value'])) { $errors['value'] = 'Value is required.'; }
        if(empty($_POST['flag_name'])) { 
            $errors['flag_name'] = 'Flag name is required.'; 
        }
        else {
            if (!validate_field_regex('alpha', $_POST['flag_name'])) {
                $errors['flag_name'] = 'Invalid flag name.';
            } else {
                $duplicate = $feature->checkFeature();
                if ($duplicate) {
                    $errors['flag_name'] = 'Flag name already exists.';
                }
            }
        }
        if(!empty($_POST['description']) && !validate_field_regex('alphanumericspecial', $_POST['description'])){
            $errors['description'] = 'Invalid description.';
        }
        if(!empty($_POST['flag_type']) && !validate_field_regex('flag', $_POST['flag_type'])){
            $errors['flag_type'] = 'Invalid flag type.';
        }
        
        // Validate datetime format for flag_type 4
        if(!empty($_POST['flag_type']) && $_POST['flag_type'] == 4) {
            if(empty($_POST['value'])) {
                $errors['value'] = 'Date and time are required for DateTime type.';
            } else if(!validate_field_regex('date_time', $_POST['value'])){
                $errors['value'] = 'Invalid date/time format. Use YYYY-MM-DD HH:MM:SS';
            }
        }
        
        // if(!empty($_POST['value']) && !validate_field_regex('value', $_POST['value'])){
        //     $errors['value'] = 'Invalid value.';
        // }
        
        if(count($errors) > 0) {
            api_response(400, 'fail', 'Validation error.', [], [], $errors);
        }

        $result = $feature->update_feature_flag();
        if ($result) {
            api_response(200, 'ok', 'Feature flag updated successfully.', []);
        } else {
            api_response(500, 'fail', 'Failed to update feature flag.', []);
        }
        break;

    case 'delete':
        if(empty($_POST['id']) || !is_numeric($_POST['id'])) { 
            api_response(400, 'fail', 'Validation error.', [], [], 'ID is required');
        }
        $result = $feature->delete_feature_flag();
        if ($result) {
            api_response(200, 'ok', 'Feature flag updated successfully.', []);
        } else {
            api_response(500, 'fail', 'Failed to update feature flag.', []);
        }
        break;

    default:
        api_response(400, 'fail', 'Invalid action for leads');
        break;

}

?>
