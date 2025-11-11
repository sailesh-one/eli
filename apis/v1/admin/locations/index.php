<?php

    global $auth;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_locations.php';

    $common_config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';

    $action = strtolower($_POST['action'] ?? '');
    $module = 'locations';

    switch ($action) {
        case 'getconfig':
                $moduleConfig = new moduleConfig();
                api_response(200, 'ok', 'Configuration retrieved successfully.', [
                    'config' => $moduleConfig->getConfig($module)           
                ]);
            break;
        case 'getlist':
                $filters = $errors = [];

                // Validation rules
                $fields = [
                    'cw_state' => ['rule' => 'id', 'error' => 'State is not valid'],
                    'cw_city' => ['rule' => 'id', 'error' => 'City is not valid'],
                    'cw_zip' => ['rule' => 'id', 'error' => 'Pincode is not valid'],
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

                // Pagination
                $current_page = (int)($_POST['current_page'] ?? 1);
                $perPage      = (int)($_POST['perPage'] ?? 10);

                // Build filters dynamically
                foreach ($fields as $field => $_) {
                    if (!empty($_POST[$field]) && $field !== 'status') {
                        $filters[$field] = $_POST[$field];
                    }
                }
                $Locations = new Locations();
                $lists = $Locations->getList($filters, $current_page, $perPage);

                api_response(200, 'ok', 'Lists retrieved successfully.', $lists);
            break;

        default:
            api_response(400, 'fail', 'Invalid action in panel');
            break;

    }

?>
