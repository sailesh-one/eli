<?php
// require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
class MMV {
    private $sql;
    
    public function __construct($connection) {
        $this->sql = $connection;
    }
    
    // ===== MAKES MANAGEMENT =====
    
    public function getMakes($includeInactive = false) {
        $activeCondition = $includeInactive ? '' : 'WHERE m.is_visible = 1';
        
        $query = "SELECT 
                    m.id,
                    m.name,
                    m.description,
                    m.manual_active,
                    m.auto_active,
                    m.is_visible,
                    m.created_at,
                    m.updated_at,
                    COUNT(DISTINCT mo.id) as models_count,
                    COUNT(DISTINCT v.id) as variants_count
                  FROM makes m
                  LEFT JOIN models mo ON m.id = mo.make_id AND mo.is_visible = 1
                  LEFT JOIN variants v ON mo.id = v.model_id AND v.is_visible = 1
                  $activeCondition
                  GROUP BY m.id
                  ORDER BY m.name";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch makes: " . mysqli_error($this->sql));
        }
        
        $makes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $makes[] = $row;
        }
        return $makes;
    }
    
    public function addMake($data) {
        $name = mysqli_real_escape_string($this->sql, trim($data['name']));
        $description = mysqli_real_escape_string($this->sql, trim($data['description'] ?? ''));
        
        if (empty($name)) {
            throw new Exception("Make name is required");
        }
        
        // Check if make already exists
        $checkQuery = "SELECT id FROM makes WHERE name = '$name'";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing make: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Make name already exists");
        }
        
        $query = "INSERT INTO makes (name, description, manual_active, auto_active) 
                  VALUES ('$name', '$description', 'y', 'y')";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add make: " . mysqli_error($this->sql));
        }
        
        return mysqli_insert_id($this->sql);
    }
    
    public function updateMake($id, $data) {
        $id = (int)$id;
        $name = mysqli_real_escape_string($this->sql, trim($data['name']));
        $description = mysqli_real_escape_string($this->sql, trim($data['description'] ?? ''));
        $active = in_array($data['active'] ?? 'y', ['y', 'n']) ? $data['active'] : 'y';
        
        if (empty($name)) {
            throw new Exception("Make name is required");
        }
        
        // Check if name already exists for different make
        $checkQuery = "SELECT id FROM makes WHERE name = '$name' AND id != $id";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing make: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Make name already exists");
        }
        
        $query = "UPDATE makes 
                  SET name = '$name', 
                      description = '$description',
                      manual_active = '$active'
                  WHERE id = $id";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to update make: " . mysqli_error($this->sql));
        }
        
        // If manually deactivated, cascade to models and variants
        if ($active === 'n') {
            $this->cascadeDeactivateMake($id);
        }
        
        // Recalculate auto_active status
        $this->updateMakeAutoActive($id);
        
        return true;
    }
    
    // ===== MODELS MANAGEMENT =====
    
    public function getModelsByMake($makeId, $includeInactive = false) {
        $makeId = (int)$makeId;
        $activeCondition = $includeInactive ? '' : 'AND mo.is_visible = 1';
        
        $query = "SELECT 
                    mo.id,
                    mo.make_id,
                    mo.name,
                    mo.description,
                    mo.manual_active,
                    mo.auto_active,
                    mo.is_visible,
                    mo.created_at,
                    mo.updated_at,
                    COUNT(v.id) as variants_count
                  FROM models mo
                  LEFT JOIN variants v ON mo.id = v.model_id AND v.is_visible = 1
                  WHERE mo.make_id = $makeId $activeCondition
                  GROUP BY mo.id
                  ORDER BY mo.name";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch models: " . mysqli_error($this->sql));
        }
        
        $models = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $models[] = $row;
        }
        return $models;
    }
    
    public function addModel($data) {
        $makeId = (int)($data['make_id'] ?? 0);
        $name = mysqli_real_escape_string($this->sql, trim($data['name']));
        $description = mysqli_real_escape_string($this->sql, trim($data['description'] ?? ''));
        
        if ($makeId <= 0 || empty($name)) {
            throw new Exception("Make ID and model name are required");
        }
        
        // Check if model already exists for this make
        $checkQuery = "SELECT id FROM models WHERE name = '$name' AND make_id = $makeId";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing model: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Model name already exists for this make");
        }
        
        $query = "INSERT INTO models (make_id, name, description, manual_active, auto_active) 
                  VALUES ($makeId, '$name', '$description', 'y', 'y')";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add model: " . mysqli_error($this->sql));
        }
        
        $modelId = mysqli_insert_id($this->sql);
        
        // Update the new model's auto_active status (will be 'n' since it has no variants)
        $this->updateModelAutoActive($modelId);
        
        // Update parent make's auto_active status
        $this->updateMakeAutoActive($makeId);
        
        return $modelId;
    }
    
    public function updateModel($id, $data) {
        $id = (int)$id;
        $name = mysqli_real_escape_string($this->sql, trim($data['name']));
        $description = mysqli_real_escape_string($this->sql, trim($data['description'] ?? ''));
        $active = in_array($data['active'] ?? 'y', ['y', 'n']) ? $data['active'] : 'y';
        
        if (empty($name)) {
            throw new Exception("Model name is required");
        }
        
        // Get make_id for this model
        $makeQuery = "SELECT make_id FROM models WHERE id = $id";
        $makeResult = mysqli_query($this->sql, $makeQuery);
        if (!$makeResult) {
            throw new Exception("Failed to get model info: " . mysqli_error($this->sql));
        }
        
        $makeRow = mysqli_fetch_assoc($makeResult);
        if (!$makeRow) {
            throw new Exception("Model not found");
        }
        
        $makeId = $makeRow['make_id'];
        
        // Check if name already exists for different model in same make
        $checkQuery = "SELECT id FROM models WHERE name = '$name' AND make_id = $makeId AND id != $id";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing model: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Model name already exists for this make");
        }
        
        $query = "UPDATE models 
                  SET name = '$name', 
                      description = '$description',
                      manual_active = '$active'
                  WHERE id = $id";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to update model: " . mysqli_error($this->sql));
        }
        
        // If manually deactivated, cascade to variants
        if ($active === 'n') {
            $this->cascadeDeactivateModel($id);
        }
        
        // Recalculate auto_active status
        $this->updateModelAutoActive($id);
        $this->updateMakeAutoActive($makeId);
        
        return true;
    }
    
    // ===== VARIANTS MANAGEMENT =====
    
    public function getVariantsByModel($modelId, $includeInactive = false) {
        $modelId = (int)$modelId;
        $activeCondition = $includeInactive ? '' : 'AND v.is_visible = 1';
        
        $query = "SELECT 
                    v.id as variant_id,
                    v.model_id,
                    v.name,
                    v.description,
                    v.manual_active,
                    v.auto_active,
                    v.is_visible,
                    v.created_at,
                    v.updated_at
                  FROM variants v
                  WHERE v.model_id = $modelId $activeCondition
                  ORDER BY v.name";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch variants: " . mysqli_error($this->sql));
        }
        
        $variants = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $variants[] = $row;
        }
        return $variants;
    }
    
    public function addVariant($data) {
        $modelId = (int)($data['model_id'] ?? 0);
        $name = mysqli_real_escape_string($this->sql, trim($data['name']));
        $description = mysqli_real_escape_string($this->sql, trim($data['description'] ?? ''));
        
        if ($modelId <= 0 || empty($name)) {
            throw new Exception("Model ID and variant name are required");
        }
        
        // Check if variant already exists for this model
        $checkQuery = "SELECT id FROM variants WHERE name = '$name' AND model_id = $modelId";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing variant: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Variant name already exists for this model");
        }
        
        $query = "INSERT INTO variants (model_id, name, description, manual_active, auto_active) 
                  VALUES ($modelId, '$name', '$description', 'y', 'y')";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add variant: " . mysqli_error($this->sql));
        }
        
        $variantId = mysqli_insert_id($this->sql);
        
        // Auto-activate parent model and make
        $this->updateModelAutoActive($modelId);
        
        // Get make_id for this model
        $makeQuery = "SELECT make_id FROM models WHERE id = $modelId";
        $makeResult = mysqli_query($this->sql, $makeQuery);
        if ($makeResult) {
            $makeRow = mysqli_fetch_assoc($makeResult);
            if ($makeRow) {
                $this->updateMakeAutoActive($makeRow['make_id']);
            }
        }
        
        return $variantId;
    }
    
    public function updateVariant($id, $data) {
        $id = (int)$id;
        $name = mysqli_real_escape_string($this->sql, trim($data['name']));
        $description = mysqli_real_escape_string($this->sql, trim($data['description'] ?? ''));
        $active = in_array($data['active'] ?? 'y', ['y', 'n']) ? $data['active'] : 'y';
        
        if (empty($name)) {
            throw new Exception("Variant name is required");
        }
        
        // Get model_id for this variant
        $modelQuery = "SELECT model_id FROM variants WHERE id = $id";
        $modelResult = mysqli_query($this->sql, $modelQuery);
        if (!$modelResult) {
            throw new Exception("Failed to get variant info: " . mysqli_error($this->sql));
        }
        
        $modelRow = mysqli_fetch_assoc($modelResult);
        if (!$modelRow) {
            throw new Exception("Variant not found");
        }
        
        $modelId = $modelRow['model_id'];
        
        // Check if name already exists for different variant in same model
        $checkQuery = "SELECT id FROM variants WHERE name = '$name' AND model_id = $modelId AND id != $id";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing variant: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Variant name already exists for this model");
        }
        
        $query = "UPDATE variants 
                  SET name = '$name', 
                      description = '$description',
                      manual_active = '$active'
                  WHERE id = $id";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to update variant: " . mysqli_error($this->sql));
        }
        
        // If manually deactivated, update auto_active status
        if ($active === 'n') {
            // No cascade needed for variants as they are leaf nodes
        }
        
        // Recalculate auto_active status for parent model and make
        $this->updateModelAutoActive($modelId);
        
        $makeQuery = "SELECT make_id FROM models WHERE id = $modelId";
        $makeResult = mysqli_query($this->sql, $makeQuery);
        if ($makeResult) {
            $makeRow = mysqli_fetch_assoc($makeResult);
            if ($makeRow) {
                $this->updateMakeAutoActive($makeRow['make_id']);
            }
        }
        
        return true;
    }
    
    // ===== HIERARCHY TREE =====
    
    public function getMMVTree($includeInactive = false, $adminView = false) {
        // For admin view, we need to use different filtering logic
        if ($adminView) {
            $makes = $this->getMakesForAdmin($includeInactive);
            
            foreach ($makes as &$make) {
                $models = $this->getModelsByMakeForAdmin($make['id'], $includeInactive);
                
                foreach ($models as &$model) {
                    $model['variants'] = $this->getVariantsByModelForAdmin($model['id'], $includeInactive);
                }
                
                $make['models'] = $models;
            }
            
            return $makes;
        } else {
            // Dealer view - existing logic using is_visible
            $makes = $this->getMakes($includeInactive);
            
            foreach ($makes as &$make) {
                $models = $this->getModelsByMake($make['id'], $includeInactive);
                
                foreach ($models as &$model) {
                    $model['variants'] = $this->getVariantsByModel($model['id'], $includeInactive);
                }
                
                $make['models'] = $models;
            }
            
            return $makes;
        }
    }
    
    // Admin-specific methods that filter by manual_active instead of is_visible
    
    private function getMakesForAdmin($includeInactive = false) {
        $activeCondition = $includeInactive ? '' : 'WHERE m.manual_active = "y"';
        
        $query = "SELECT 
                    m.id,
                    m.name,
                    m.description,
                    m.manual_active,
                    m.auto_active,
                    m.is_visible,
                    m.created_at,
                    m.updated_at,
                    COUNT(DISTINCT mo.id) as models_count,
                    COUNT(DISTINCT v.id) as variants_count
                  FROM makes m
                  LEFT JOIN models mo ON m.id = mo.make_id
                  LEFT JOIN variants v ON mo.id = v.model_id
                  $activeCondition
                  GROUP BY m.id
                  ORDER BY m.name";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch makes for admin: " . mysqli_error($this->sql));
        }
        
        $makes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $makes[] = $row;
        }
        return $makes;
    }
    
    private function getModelsByMakeForAdmin($makeId, $includeInactive = false) {
        $makeId = (int)$makeId;
        $activeCondition = $includeInactive ? '' : 'AND mo.manual_active = "y"';
        
        $query = "SELECT 
                    mo.id,
                    mo.make_id,
                    mo.name,
                    mo.description,
                    mo.manual_active,
                    mo.auto_active,
                    mo.is_visible,
                    mo.created_at,
                    mo.updated_at,
                    COUNT(v.id) as variants_count
                  FROM models mo
                  LEFT JOIN variants v ON mo.id = v.model_id
                  WHERE mo.make_id = $makeId $activeCondition
                  GROUP BY mo.id
                  ORDER BY mo.name";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch models for admin: " . mysqli_error($this->sql));
        }
        
        $models = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $models[] = $row;
        }
        return $models;
    }
    
    private function getVariantsByModelForAdmin($modelId, $includeInactive = false) {
        $modelId = (int)$modelId;
        $activeCondition = $includeInactive ? '' : 'AND v.manual_active = "y"';
        
        $query = "SELECT 
                    v.id,
                    v.model_id,
                    v.name,
                    v.description,
                    v.manual_active,
                    v.auto_active,
                    v.is_visible,
                    v.created_at,
                    v.updated_at
                  FROM variants v
                  WHERE v.model_id = $modelId $activeCondition
                  ORDER BY v.name";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch variants for admin: " . mysqli_error($this->sql));
        }
        
        $variants = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $variants[] = $row;
        }
        return $variants;
    }
    
    // ===== HELPER METHODS =====
    
    private function updateModelAutoActive($modelId) {
        $modelId = (int)$modelId;
        
        // Check if model has any active variants
        $variantCheck = "SELECT COUNT(*) as count FROM variants WHERE model_id = $modelId AND is_visible = 1";
        $result = mysqli_query($this->sql, $variantCheck);
        $row = mysqli_fetch_assoc($result);
        $hasActiveVariants = $row['count'] > 0;
        
        $autoActive = $hasActiveVariants ? 'y' : 'n';
        
        $updateQuery = "UPDATE models SET auto_active = '$autoActive' WHERE id = $modelId";
        mysqli_query($this->sql, $updateQuery);
    }
    
    private function updateMakeAutoActive($makeId) {
        $makeId = (int)$makeId;
        
        // Check if make has any active models
        $modelCheck = "SELECT COUNT(*) as count FROM models WHERE make_id = $makeId AND is_visible = 1";
        $result = mysqli_query($this->sql, $modelCheck);
        $row = mysqli_fetch_assoc($result);
        $hasActiveModels = $row['count'] > 0;
        
        $autoActive = $hasActiveModels ? 'y' : 'n';
        
        $updateQuery = "UPDATE makes SET auto_active = '$autoActive' WHERE id = $makeId";
        mysqli_query($this->sql, $updateQuery);
    }
    
    private function cascadeDeactivateMake($makeId) {
        $makeId = (int)$makeId;
        
        // Deactivate all models in this make
        mysqli_query($this->sql, "UPDATE models SET active = 'n' WHERE make_id = $makeId");
        
        // Deactivate all variants in models of this make
        mysqli_query($this->sql, "UPDATE variants SET active = 'n' WHERE model_id IN (SELECT id FROM models WHERE make_id = $makeId)");
    }
    
    private function cascadeDeactivateModel($modelId) {
        $modelId = (int)$modelId;
        
        // Deactivate all variants in this model
        mysqli_query($this->sql, "UPDATE variants SET active = 'n' WHERE model_id = $modelId");
    }
    //lathif start
    /**
     * Get make data using make_id
     */
    private function getMake($make_id){
   
        $query="select id,make from master_makes where id=".$make_id;
        $result = mysqli_query($this->sql, $query);
        $row = mysqli_fetch_assoc($result);
        return $row;
    }
     /**
     * Get Model data using model_id
     */
    public function getModel($model_id){
   
        $query="select id,model,make_id,make from master_models where id=".$model_id;
        $result = mysqli_query($this->sql, $query);
        $row = mysqli_fetch_assoc($result);
        return $row;
    }
    /**
     * Get Variant data using variant_id
     */
    public function getVariant($model_id){
   
        $query="select id,variant,model_id,model,make_id,make from master_variants_new where id=".$model_id;
        $result = mysqli_query($this->sql, $query);
        $row = mysqli_fetch_assoc($result);
        return $row;
    }
    public function getAllMakes($includeInactive = false) {
        //$activeCondition = $includeInactive ? '' : 'WHERE active = "y"';
        $query = "SELECT 
                    id,
                    make,
                    is_popular,
                    active,
                    is_brand_group
                  FROM master_makes 
                  ORDER BY is_popular desc";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch makes for admin: " . mysqli_error($this->sql));
        }
        
        $makes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $makes[] = $row;
        }
        if(empty($makes))
          {
              api_response(200,"empty","Empty makes list.");
          }
          else
          {
             api_response(200,"ok","Makes list fetched successsfully.",["makes_list" => $makes]);
          }
    }
    public function getModelsByMakeId($includeInactive = false,$make_id) {

        $activeCondition = $includeInactive ? '' : 'WHERE make_id='.$make_id.'';
        $query = "SELECT 
                    id as model_id,
                    model,
                    make_id,
                    make,
                    active
                  FROM master_models 
                  $activeCondition
                  ORDER BY model asc";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch models for admin: " . mysqli_error($this->sql));
        }
        
        $models = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $models[] = $row;
        }
        if(empty($models))
          {
              api_response(200,"empty","Empty models list.");
          }
          else
          {
             api_response(200,"ok","Models list fetched successsfully.",["models_list" => $models]);
          }
    }
    public function getVariantsByModelId($includeInactive = false,$model_id) {

        $activeCondition = $includeInactive ? '' : 'WHERE model_id='.$model_id.'';
        $query = "SELECT 
                    id as variant_id,
                    variant,
                    model_id,
                    model,
                    make_id,
                    make,
                    active
                  FROM master_variants_new 
                  $activeCondition
                  ORDER BY variant asc";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to fetch models for admin: " . mysqli_error($this->sql));
        }
        
        $variants = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $variants[] = $row;
        }
        if(empty($variants))
          {
              api_response(200,"empty","Empty models list.");
          }
          else
          {
             api_response(200,"ok","Models list fetched successsfully.",["variant_list" => $variants]);
          }
    }
    public function addNewMake($data){
        
        $make_name = mysqli_real_escape_string($this->sql, trim($data['make']));
        $is_popular = mysqli_real_escape_string($this->sql, trim($data['is_popular'] ?? ''));
        $active = mysqli_real_escape_string($this->sql, trim($data['active'] ?? ''));
        $is_brand_group = mysqli_real_escape_string($this->sql, trim($data['is_brand_group'] ?? ''));
        
        if (empty($make_name)) {
            api_response(400,"fail","Make name is required");
        }
        
        // Check if make already exists
        $checkQuery = "SELECT id FROM master_makes WHERE make = '$make_name'";
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing make: " . mysqli_error($this->sql));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(400,"fail","Make name already exists");
        }
        
        $query = "INSERT INTO master_makes (make, active, is_popular,is_brand_group) 
                  VALUES ('$make_name','y', '$active','$is_brand_group')";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add make: " . mysqli_error($this->sql));
        }
        
        api_response(200,"ok","Make added successsfully.");
    }
    public function updateMakeData($data){
        
        $edit_id=mysqli_real_escape_string($this->sql, trim($data['id']));
        $make_name = mysqli_real_escape_string($this->sql, trim($data['make']));
        $is_popular = mysqli_real_escape_string($this->sql, trim($data['is_popular'] ?? ''));
        $active = mysqli_real_escape_string($this->sql, trim($data['active'] ?? ''));
        $is_brand_group = mysqli_real_escape_string($this->sql, trim($data['is_brand_group'] ?? ''));
        
        if (empty($edit_id)) {
            api_response(400,"fail","Make edit ID is required");
        }
        if (empty($make_name)) {
            api_response(400,"fail","Make name is required");
        }
        
        // Check if make already exists
        $checkQuery = "SELECT id FROM master_makes WHERE make = '$make_name' and id!=".$edit_id;
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            throw new Exception("Failed to check existing make: " . mysqli_error($this->sql));
        }
        
        if (mysqli_num_rows($checkResult) > 0) {
             api_response(400,"fail","Make name already exists");
        }
        
        $query = "update master_makes set make='$make_name',is_popular='$is_popular',is_brand_group='$is_brand_group',active='$active' where id=".$edit_id;
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to update make: " . mysqli_error($this->sql));
        }
        
         api_response(200,"ok","Make name updated successfully.");
    }
    public function addNewModel($data){
        
        $model_name = mysqli_real_escape_string($this->sql, trim($data['model']));
        $make_id = mysqli_real_escape_string($this->sql, trim($data['make_id']));
        $active = mysqli_real_escape_string($this->sql, trim($data['active'] ?? ''));
        
        if (empty($model_name)) {
            api_response(400,"fail","Model name is required");
        }
        if (empty($make_id)) {
            api_response(400,"fail","Make_id is required");
        }
        // Check if make already exists
        $checkQuery = "SELECT id FROM master_models WHERE model = '$model_name' and make_id=".$make_id;
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing Model: " . mysqli_error($this->sql));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(400,"fail","Model name already exists");
        }
        $record=$this->getMake($make_id);
        $make_name=$record['make']?$record['make']:"";
        
        $query = "INSERT INTO master_models (model,make_id,make,active) 
                  VALUES ('$model_name','$make_id','$make_name','$active')";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add Model: " . mysqli_error($this->sql));
        }
        api_response(200,"ok","Model added successsfully.");
    }
    public function editModel($data){
        $model_id = mysqli_real_escape_string($this->sql, trim($data['model_id']));
        $model_name = mysqli_real_escape_string($this->sql, trim($data['model']));
        $make_id = mysqli_real_escape_string($this->sql, trim($data['make_id']));
        $active = mysqli_real_escape_string($this->sql, trim($data['active'] ?? ''));
        
        if (empty($model_name)) {
            api_response(400,"fail","Model name is required");
        }
        if (empty($model_id)) {
            api_response(400,"fail","Model ID is required");
        }
        if (empty($make_id)) {
            api_response(400,"fail","Make ID is required");
        }
        // Check if make already exists
        $checkQuery = "SELECT id FROM master_models WHERE model = '$model_name' and make_id='$make_id' and id!=".$model_id;
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing Model: " . mysqli_error($this->sql));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(400,"fail","Model name already exists");
        }
        $record=$this->getMake($make_id);
        $make_name=$record['make']?$record['make']:"";
        
        $query = "update master_models set model='$model_name',make='$make_name',make_id='$make_id',active='$active' where id=".$model_id;
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add Model: " . mysqli_error($this->sql));
        }
        api_response(200,"ok","Model updated successsfully.");
    }
    public function addNewVariant($data){
        
        $variant_name = mysqli_real_escape_string($this->sql, trim($data['variant']));
        $make_id = mysqli_real_escape_string($this->sql, trim($data['make_id']));
        $model_id = mysqli_real_escape_string($this->sql, trim($data['model_id']));
        $active = mysqli_real_escape_string($this->sql, trim($data['active'] ?? ''));
        
        if (empty($variant_name)) {
            api_response(400,"fail","Variant name is required");
        }
        if (empty($make_id)) {
            api_response(400,"fail","Make id is required");
        }
        if (empty($model_id)) {
            api_response(400,"fail","Model id is required");
        }
        // Check if make already exists
        $checkQuery = "SELECT id FROM master_variants_new WHERE variant = '$variant_name' and make_id='$make_id' and model_id=".$model_id;
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing Variant: " . mysqli_error($this->sql));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(400,"fail","Variant name already exists");
        }
        $record=$this->getMake($make_id);
        $make_name=$record['make']?$record['make']:"";

        $record=$this->getModel($model_id);
        $model_name=$record['model']?$record['model']:"";
        
        $query = "INSERT INTO master_variants_new (variant,make_id,make,model_id,model,active) 
                  VALUES ('$variant_name','$make_id','$make_name','$model_id','$model_name','$active')";
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add Variant: " . mysqli_error($this->sql));
        }
        api_response(200,"ok","Variant added successsfully.");
    }
    public function updateNewVariant($data){
        
        $variant_name = mysqli_real_escape_string($this->sql, trim($data['variant']));
        $variant_id = mysqli_real_escape_string($this->sql, trim($data['variant_id']));
        $make_id = mysqli_real_escape_string($this->sql, trim($data['make_id']));
        $model_id = mysqli_real_escape_string($this->sql, trim($data['model_id']));
        $active = mysqli_real_escape_string($this->sql, trim($data['active'] ?? ''));
        
        if (empty($variant_name)) {
            api_response(400,"fail","Variant name is required");
        }
        if (empty($variant_id)) {
            api_response(400,"fail","Variant ID is required");
        }
        if (empty($make_id)) {
            api_response(400,"fail","Make ID is required");
        }
        if (empty($model_id)) {
            api_response(400,"fail","Model ID is required");
        }
        // Check if make already exists
        $checkQuery = "SELECT id FROM master_variants_new WHERE variant = '$variant_name' and make_id='$make_id' and model_id='$model_id' and id!=".$variant_id;
        $checkResult = mysqli_query($this->sql, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing Variant: " . mysqli_error($this->sql));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(400,"fail","Variant name already exists");
        }
        $record=$this->getMake($make_id);
        $make_name=$record['make']?$record['make']:"";

        $record=$this->getModel($model_id);
        $model_name=$record['model']?$record['model']:"";
        
        $query = "update master_variants_new set variant='$variant_name',make_id='$make_id',model_id='$model_id',active='$active' where id=".$variant_id;
        
        $result = mysqli_query($this->sql, $query);
        if (!$result) {
            throw new Exception("Failed to add Variant: " . mysqli_error($this->sql));
        }
        api_response(200,"ok","Variant added successsfully.");
    }
    //lathif ends
}
?>