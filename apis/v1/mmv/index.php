
<?php
// MMV API endpoints
global $connection;

$action = strtolower($_REQUEST['action'] ?? '');

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_mmv.php';
    
    // Validate database connection
    if (!isset($connection) || !$connection) {
        api_response(500, 'fail', 'Database connection not available', [], []);
    }
    
    // Test connection
    if (!mysqli_ping($connection)) {
        api_response(500, 'fail', 'Database connection lost', [], []);
    }
    
    $mmv = new MMV($connection);
    
    switch ($action) {
        // ===== FETCHING DATA =====
        case 'get_makes':
            $includeInactive = ($_REQUEST['include_inactive'] ?? 'false') === 'true';
            $data = $mmv->getMakes($includeInactive);
            api_response(200, 'success', 'Makes fetched', $data);
            break;
            
        case 'get_models_by_make':
            $make_id = $_GET['make_id'] ?? $_POST['make_id'] ?? 0;
            $includeInactive = ($_REQUEST['include_inactive'] ?? 'false') === 'true';
            $data = $mmv->getModelsByMake($make_id, $includeInactive);
            api_response(200, 'success', 'Models fetched', $data);
            break;
            
        case 'get_variants_by_model':
            $model_id = $_GET['model_id'] ?? $_POST['model_id'] ?? 0;
            $includeInactive = ($_REQUEST['include_inactive'] ?? 'false') === 'true';
            $data = $mmv->getVariantsByModel($model_id, $includeInactive);
            api_response(200, 'success', 'Variants fetched', $data);
            break;
            
        case 'get_mmv_tree':
            $includeInactive = ($_REQUEST['include_inactive'] ?? 'false') === 'true';
            $adminView = ($_REQUEST['admin_view'] ?? 'false') === 'true';
            $data = $mmv->getMMVTree($includeInactive, $adminView);
            api_response(200, 'success', 'MMV tree fetched', $data);
            break;
            
        // ===== MAKES MANAGEMENT =====
        case 'add_make':
            $data = [
                'name' => $_REQUEST['name'] ?? '',
                'description' => $_REQUEST['description'] ?? ''
            ];
            $makeId = $mmv->addMake($data);
            api_response(200, 'success', 'Make added successfully', ['id' => $makeId]);
            break;
            
        case 'update_make':
            $id = $_REQUEST['id'] ?? 0;
            $data = [
                'name' => $_REQUEST['name'] ?? '',
                'description' => $_REQUEST['description'] ?? '',
                'active' => $_REQUEST['active'] ?? 'y'
            ];
            $mmv->updateMake($id, $data);
            api_response(200, 'success', 'Make updated successfully');
            break;
            
        // ===== MODELS MANAGEMENT =====
        case 'add_model':
            $data = [
                'make_id' => $_REQUEST['make_id'] ?? 0,
                'name' => $_REQUEST['name'] ?? '',
                'description' => $_REQUEST['description'] ?? '',
                'fuel_type' => $_REQUEST['fuel_type'] ?? '',
                'body_type' => $_REQUEST['body_type'] ?? ''
            ];
            $modelId = $mmv->addModel($data);
            api_response(200, 'success', 'Model added successfully', ['id' => $modelId]);
            break;
            
        case 'update_model':
            $id = $_REQUEST['id'] ?? 0;
            $data = [
                'name' => $_REQUEST['name'] ?? '',
                'description' => $_REQUEST['description'] ?? '',
                'fuel_type' => $_REQUEST['fuel_type'] ?? '',
                'body_type' => $_REQUEST['body_type'] ?? '',
                'active' => $_REQUEST['active'] ?? 'y'
            ];
            $mmv->updateModel($id, $data);
            api_response(200, 'success', 'Model updated successfully');
            break;
            
        // ===== VARIANTS MANAGEMENT =====
        case 'add_variant':
            $data = [
                'model_id' => $_REQUEST['model_id'] ?? 0,
                'name' => $_REQUEST['name'] ?? '',
                'description' => $_REQUEST['description'] ?? '',
                'engine_capacity' => $_REQUEST['engine_capacity'] ?? '',
                'transmission' => $_REQUEST['transmission'] ?? '',
                'fuel_type' => $_REQUEST['fuel_type'] ?? '',
                'price_range' => $_REQUEST['price_range'] ?? ''
            ];
            $variantId = $mmv->addVariant($data);
            api_response(200, 'success', 'Variant added successfully', ['id' => $variantId]);
            break;
            
        case 'update_variant':
            $id = $_REQUEST['id'] ?? 0;
            $data = [
                'name' => $_REQUEST['name'] ?? '',
                'description' => $_REQUEST['description'] ?? '',
                'engine_capacity' => $_REQUEST['engine_capacity'] ?? '',
                'transmission' => $_REQUEST['transmission'] ?? '',
                'fuel_type' => $_REQUEST['fuel_type'] ?? '',
                'price_range' => $_REQUEST['price_range'] ?? '',
                'active' => $_REQUEST['active'] ?? 'y'
            ];
            $mmv->updateVariant($id, $data);
            api_response(200, 'success', 'Variant updated successfully');
            break;
            
        default:
            api_response(400, 'fail', 'Invalid action');
    }
} catch (Throwable $e) {
    // Log the full error for debugging
    error_log("MMV API Error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    api_response(500, 'fail', 'Internal server error: ' . $e->getMessage(), [], []);
}

