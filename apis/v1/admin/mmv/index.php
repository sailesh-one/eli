<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_mmv.php';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
global $connection;
$mmv_obj = new MMV($connection);
$make_id=isset($_POST['make_id'])?$_POST['make_id']:"";
$model_id=isset($_POST['model_id'])?$_POST['model_id']:"";
$data=isset($_POST['form_data'])?$_POST['form_data']:[];

switch ($action) {
    /**
     * Get all Makes + Models + Variants
     */
    case 'makeList':
        $result = $mmv_obj->getAllMakes();
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $result);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;

    case 'modelList':
        $result = $mmv_obj->getModelsByMakeId('',$make_id);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $result);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;

    case 'variantList':
        $result = $mmv_obj->getVariantsByModelId('',$model_id);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $result);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
    case 'addMake':
        $result = $mmv_obj->addNewMake($data);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $data);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
    case 'editMake':
        $result = $mmv_obj->updateMakeData($data);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $data);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
    case 'addModel':
        $result = $mmv_obj->addNewModel($data);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $data);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
    case 'editModel':
        $result = $mmv_obj->editModel($data);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $data);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
    case 'addVariant':
        $result = $mmv_obj->addNewVariant($data);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $data);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
    case 'editVariant':
        $result = $mmv_obj->updateNewVariant($data);
        if ($result) {
            api_response(200, 'ok', 'Fetched data', $data);
        } else {
            api_response(500, 'fail', 'Failed to fetch data', []);
        }
        break;
}