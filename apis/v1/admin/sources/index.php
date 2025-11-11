<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_sources.php';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$sources = new Sources();

switch ($action) {

    /**
     * Get all sources + subsources
     */
    case 'list':
        $result = $sources->getAllSources();
        api_response(200, 'ok', 'Fetched Sources', $result);
        break;

    /**
     * Add new source
     */
    case 'add_source':
        $data=[
            'name' =>$_POST['name'] ?? '',
            'active' =>$_POST['active'] ?? 1,
            'pm_flag'=>$_POST['pm_flag'] ?? '',
            'sm_flag'=>$_POST['sm_flag'] ?? '',
        ];
        $add = $sources->addSource($data);
        if ($add['success']) {
            api_response(200, 'ok', 'Source added successfully', ['id' => $add['id']]);
        } else {
            api_response(400, 'fail', $add['error']);
        }
        break;

    /**
     * Update existing source
     */
    case 'update_source':
        $data=[
            'id' =>$_POST['id'] ?? 0,
            'name' =>$_POST['name'] ?? '',
            'active' =>$_POST['active'] ?? 0,
            'pm_flag'=>$_POST['pm_flag'] ?? '',
            'sm_flag'=>$_POST['sm_flag'] ?? '',
            'is_selected'=>$_POST['is_selected'] ?? 0,
        ];
        $update = $sources->updateSource($data);
        if ($update['success']) {
            api_response(200, 'ok', 'Source updated successfully');
        } else {
            api_response(400, 'fail', $update['error']);
        }
        break;

    /**
     * Add new subsource
     */
    case 'add_subsource':
        $sourceId = $_POST['source_id'] ?? 0;
        $name     = $_POST['name']      ?? '';
        $active   = $_POST['active']    ?? 'y';
        $add = $sources->addSubsource($sourceId, $name, $active);
        if ($add['success']) {
            api_response(200, 'ok', 'Subsource added successfully', ['id' => $add['id']]);
        } else {
            api_response(400, 'fail', $add['error']);
        }
        break;

    /**
     * Update existing subsource
     */
    case 'update_subsource':
        $id       = $_POST['id']        ?? 0;
        $sourceId = $_POST['source_id'] ?? 0;
        $name     = $_POST['name']      ?? '';
        $active   = $_POST['active']    ?? 'y';
        $update = $sources->updateSubsource($id, $sourceId, $name, $active);
        if ($update['success']) {
            api_response(200, 'ok', 'Subsource updated successfully');
        } else {
            api_response(400, 'fail', $update['error']);
        }
        break;

    default:
        api_response(400, 'fail', 'Invalid action');
        break;
}
?>
