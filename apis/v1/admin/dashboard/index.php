<?php

// Leads route handler (protected, optimized)
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_dashboard.php';


$dashboard = new Dashboard();
$user_id = $GLOBALS['api_user']['uid'] ?? null;


$action = strtolower($_POST['action'] ?? '');

switch ($action) {

    case 'get':
        $pm_source = $dashboard->pm_source();
        $sm_source = $dashboard->sm_source();
        $pm_status = $dashboard->pm_status();
        $sm_status = $dashboard->sm_status();
        $kra_tagets = $dashboard->kra_target_by_month();
        $evaluation_data = $dashboard->evaluation_data();
        $purchased_data = $dashboard->purchased_data();
        $sold_data = $dashboard->sold_data();
        api_response(200, 'ok', 'Form fields fetched successfully', array_merge($pm_source , $sm_source, $pm_status, $sm_status, $kra_tagets, $evaluation_data, $purchased_data, $sold_data));
        break;

    default:
        api_response(400, 'fail', 'Invalid action in dashboard');
        break;

}
