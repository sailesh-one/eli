<?php
   
class MasterData
{
    public $connection;
    
    public function __construct()
    {
        global $connection;
        global $dealer_id;
        
        $this->dealer_id = $dealer_id;
        $this->connection = $connection;
    }

    // Search and return cities (mirrors existing Buyleads::searchCities behavior)
    public function searchCities($search = '', $limit = 50, $offset = 0) {
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);

        $searchCondition = '';
        if (!empty($search)) {
            $escaped_search = mysqli_real_escape_string($this->connection, $search);
            $searchCondition = " AND (city_name LIKE '%{$escaped_search}%' OR state_name LIKE '%{$escaped_search}%')";
        }

        // total count
        $countQuery = "SELECT COUNT(*) as total FROM master_cw_cities WHERE status = 1" . $searchCondition;
        $countResult = mysqli_query($this->connection, $countQuery);
        if (!$countResult) {
            error_log("MasterData::searchCities count failed: " . mysqli_error($this->connection) . " -- " . $countQuery);
            return false;
        }
        $totalCount = (int)mysqli_fetch_assoc($countResult)['total'];
        mysqli_free_result($countResult);

        $query = "SELECT city_id, city_name, state_name, status 
                  FROM master_cw_cities 
                  WHERE status = 1" . $searchCondition . "
                  ORDER BY city_name ASC 
                  LIMIT {$limit} OFFSET {$offset}";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            error_log("MasterData::searchCities query failed: " . mysqli_error($this->connection) . " -- " . $query);
            return false;
        }
        $cities = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cities[] = [
                'city_id' => (int)$row['city_id'],
                'city_name' => $row['city_name'],
                'state' => $row['state_name'],
                'status' => $row['status']
            ];
        }
        mysqli_free_result($result);

        return [
            'data' => $cities,
            'meta' => [
                'total' => $totalCount,
                'count' => count($cities),
                'limit' => $limit,
                'offset' => $offset,
                'search' => $search
            ]
        ];
    }

    public function getYears()
    {
       $query = "SELECT DISTINCT start_year FROM master_variants_new WHERE active = 'y' ORDER BY start_year ASC";
       $res = mysqli_query($this->connection,$query);
       if(!$res)
       {
         logSqlError(mysqli_error($this->connection),$query,"masterdata-getyears");
         return false;
       }

       $years = [];
       while($row = mysqli_fetch_assoc($res))
       {
         $years[] = $row['start_year'];
       }

       return ["list" => $years];
    }

   

public function getMMVData()
{
    mysqli_set_charset($this->connection, "utf8mb4"); // Ensure UTF-8

    $query = "SELECT    make_id, make, is_popular, is_brand_group, model_id, model, 
                        id AS variant_id, variant, start_year AS sy, end_year AS ey, active
                        FROM master_variants_new
                        WHERE active = 'y'
                        ORDER BY 
                        CASE WHEN is_brand_group = 'y' THEN 0 ELSE 1 END,
                        CASE WHEN is_popular = 'y' THEN 0 ELSE 1 END,
                        make ASC;
                        ";

    $res = mysqli_query($this->connection, $query);

    if (!$res) {
        logSqlError(mysqli_error($this->connection), $query, "masterdata-getmmv");
        return ["status" => 0, "details" => "Error", "message" => "Internal error"];
    }

    $mmv = [];

    while ($row = mysqli_fetch_assoc($res)) {
        // Find or create make
        $makeIndex = null;
        foreach ($mmv as $k => $m) {
            if ($m['make_id'] == $row['make_id']) {
                $makeIndex = $k;
                break;
            }
        }

        if ($makeIndex === null) {
            $mmv[] = [
                "make_id"    => $row['make_id'],
                "make_name"  => $row['make'],
                "is_popular" => $row['is_popular'],
                "is_color" =>  $row['is_brand_group'],
                "models"     => []
            ];
            $makeIndex = count($mmv) - 1;
        }

        // Find or create model
        $modelIndex = null;
        foreach ($mmv[$makeIndex]['models'] as $k => $m) {
            if ($m['model_id'] == $row['model_id']) {
                $modelIndex = $k;
                break;
            }
        }

        if ($modelIndex === null) {
            $mmv[$makeIndex]['models'][] = [
                "model_id"   => $row['model_id'],
                "model_name" => $row['model'],
                "variants"   => []
            ];
            $modelIndex = count($mmv[$makeIndex]['models']) - 1;
        }

        // Add variant
        $mmv[$makeIndex]['models'][$modelIndex]['variants'][] = [
            "variant_id"   => $row['variant_id'],
            "variant_name" => $row['variant'],
            "sy"           => $row['sy'],
            "ey"           => $row['ey'],
            "active"       => $row['active']
        ];
    }

    return [
        "makes" => $mmv // already numeric-indexed
    ];
}



    public function getMakes()
    {
        $query = "SELECT id, make, CASE WHEN is_popular = 'y' THEN 'Popular' ELSE 'Others' END AS grp FROM master_makes WHERE active = 'y' ORDER BY (is_popular = 'y') DESC,make ASC";
        $res = mysqli_query($this->connection,$query);
        if(!$res) {
            logSqlError(mysqli_error($this->connection),$query,"masterdata-getmakes");
            return false;
        }
        $makesList = [];
        while($row = mysqli_fetch_assoc($res)) {
            $makesList[] = [
                'value' => $row['id'],
                'label' => $row['make'],
                'group' => $row['grp']
            ];
        }
        return ['list' => $makesList];
    }

    public function getMakesFiltered($filter = 'all')
    {
        // Use is_certifiable field to identify JLR makes
        // is_certifiable = 'y' means GROUP BRAND
        
        $whereClause = "active = 'y'";
        
        if ($filter === 'jlr') {
            $whereClause .= " AND is_certifiable = 'y'";
        } elseif ($filter === 'non-jlr') {
            $whereClause .= " AND (is_certifiable != 'y' OR is_certifiable IS NULL)";
        }
        // if 'all', no additional filter
        
        $query = "SELECT id, make, CASE WHEN is_popular = 'y' THEN 'Popular' ELSE 'Others' END AS grp 
                  FROM master_makes 
                  WHERE {$whereClause} 
                  ORDER BY (is_popular = 'y') DESC, make ASC";
                  
        $res = mysqli_query($this->connection, $query);
        if(!$res) {
            logSqlError(mysqli_error($this->connection), $query, "masterdata-getmakesfiltered");
            return false;
        }
        
        $makesList = [];
        while($row = mysqli_fetch_assoc($res)) {
            $makesList[] = [
                'value' => (int)$row['id'],
                'label' => $row['make'],
                'group' => $row['grp']
            ];
        }
        return ['list' => $makesList];
    }



   function getModelsByMake() {
        $make_id = isset($_POST['make']) ? $_POST['make'] : '';

        if (empty($make_id) || $make_id === '0') {
            return ["list" => []];
        }

        $make_id = mysqli_real_escape_string($this->connection, $make_id);

        $query = "SELECT DISTINCT model_id, model 
                FROM master_variants_new
                WHERE make_id = '$make_id' 
                    AND active = 'y' 
                ORDER BY (is_popular = 'y') DESC, model ASC";

        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, "masterdata-getmodelsbymake");
            return ["list" => []];
        }

        $models = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $modelId = (int)$row['model_id'];

            // Fetch variants for this model
            $variantQuery = "SELECT DISTINCT id, variant 
                            FROM master_variants_new 
                            WHERE model_id = '$modelId' 
                            AND make_id = '$make_id'
                            AND active = 'y' 
                            ORDER BY variant ASC";

            $variantRes = mysqli_query($this->connection, $variantQuery);

            $variants = [];
            if ($variantRes) {
                while ($vrow = mysqli_fetch_assoc($variantRes)) {
                    $variants[] = [
                        'value' => (int)$vrow['id'],
                        'label' => $vrow['variant']
                    ];
                }
            }

            $models[] = [
                'value' => $modelId,
                'label' => $row['model'],
                'variants' => $variants
            ];
        }

        return ["list" => $models];
    }



    
    // public function getvariantsbyModel()
    // {
    //     $model_id = isset($_POST['model']) ? $_POST['model'] : '';

    //     $query = "SELECT DISTINCT id,variant FROM master_variants_new WHERE model_id = '".mysqli_real_escape_string($this->connection,$model_id)."' AND active = 'y' ORDER BY variant ASC";
    //     $res = mysqli_query($this->connection,$query);
    //     if(!$res)
    //     {
    //         logSqlError(mysqli_error($this->connection),$query,"masterdata-getvariantssbymodel");
    //         return false;
    //     }

    //     $variants = [];
    //     while($row = mysqli_fetch_assoc($res))
    //     {
    //         $variants[] = [
    //             'value' => (int)$row['id'],
    //             'label' => $row['variant']
    //         ];
    //     }

    //     return ['list' => $variants];
    // }


    public function getModuleMappedRoles($moduleId) {
        $roles = [];
        $moduleId = mysqli_real_escape_string($this->connection, $moduleId);

        $query = "
            SELECT 
                R.role_id,
                R.role_name,
                GROUP_CONCAT(
                    DISTINCT CONCAT(SM.submodule_id, ':', SM.submodule_name)
                    ORDER BY SM.submodule_id
                    SEPARATOR ';'
                ) AS submodules
            FROM 
                config_role_permissions P
            JOIN 
                config_roles R ON P.role_id = R.id
            LEFT JOIN 
                config_submodules SM ON P.submodule_id = SM.id
            WHERE 
                P.module_id = '$moduleId'
            GROUP BY 
                R.role_id, R.role_name
            ORDER BY 
                R.role_id
        ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'masterdata-getModuleMappedRoles');
            return ["list" => []]; // Return an empty array on failure
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $role = [
                'id' => $row['role_id'],
                'name' => $row['role_name'],
                'sublist' => []
            ];

            // Parse the concatenated submodules string
            if (!empty($row['submodules'])) {
                $submodules = explode(';', $row['submodules']);
                foreach ($submodules as $submodule) {
                    list($submoduleId, $submoduleName) = explode(':', $submodule);
                    $role['sublist'][] = [
                        'id' => $submoduleId,
                        'name' => $submoduleName
                    ];
                }
            }
            $roles[] = $role;
        }

        return ["list" => $roles];
    }

  
    // Get subsources for a specific source or all subsources
    public function getSubSources($source_id = null) {
        $subSources = [];
        
        if ($source_id) {
            $source_id = intval($source_id);
            $query = "SELECT mss.id AS value, mss.sub_source As label
                      FROM master_sources_sub mss 
                      LEFT JOIN master_sources ms ON mss.source_id = ms.id 
                      WHERE mss.active = '1' AND mss.source_id = $source_id 
                      ORDER BY mss.sub_source ASC";
                      
            $result = mysqli_query($this->connection, $query);
            if (!$result) {
                error_log("MasterData::getSubSources Error: " . mysqli_error($this->connection) . " -- " . $query);
                return ["list" => $subSources];
            }
            
            $subSources = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        
        return ["list" => $subSources];
    }

    // Get all active executives (users) from users table
    public function getExecutives() {
        // Role-based filtering: Executives (role_main='n') should only see themselves
        $role_main = strtolower(trim($GLOBALS['dealership']['role_main'] ?? 'n'));
        $whereClause = "u.active = 'y' AND r.active = 'y'";
        
        if ($role_main !== 'y') {
            // Non-managers (UCE/UCS) only see themselves
            $current_user_id = (int)($GLOBALS['dealership']['id'] ?? 0);
            if ($current_user_id > 0) {
                $whereClause .= " AND u.id = $current_user_id";
            }
        }
        
        $query = "
            SELECT u.id AS value, u.name AS label 
            FROM users u
            JOIN config_roles r ON u.role_id = r.id
            WHERE $whereClause
            ORDER BY u.name ASC
        ";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'masterdata-getExecutives');
            return ["executive-list" => []];  // Consistent naming with frontend
        }
        
        $executives = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $executives[] = [
                'value' => (int)$row['value'],
                'label' => $row['label']
            ];
        }
        
        
        return ["list" => $executives];
    }



    public function getExecutivesByBranch() {
        // Get branch_id from POST, GET or global variable (in order of preference)
        $branch_id = isset($_POST['branch_id']) ? $_POST['branch_id'] : (isset($_GET['branch_id']) ? $_GET['branch_id'] : null);
        
        // If no branch_id provided in request, fall back to global variable
        if (!$branch_id) {
            GLOBAL $branch_id;
        }
        
        // Handle branch_id as JSON string (as it's stored in the database)
        if ($branch_id && !is_array($branch_id)) {
            // Try to decode if it looks like JSON
            if (strpos($branch_id, '[') === 0) {
                $branch_id = json_decode($branch_id, true);
            } else {
                // Convert single branch ID to array format
                $branch_id = [$branch_id];
            }
        }
        
        $users = getUsersByBranchIds($branch_id);
        
        // Role-based filtering: Executives (role_main='n') should only see themselves
        $role_main = strtolower(trim($GLOBALS['dealership']['role_main'] ?? 'n'));
        if ($role_main !== 'y') {
            $current_user_id = (int)($GLOBALS['dealership']['id'] ?? 0);
            
            if ($current_user_id > 0) {
                // Filter the users array to only include current user
                foreach ($users as &$branch) {
                    if (isset($branch['executives']) && is_array($branch['executives'])) {
                        $branch['executives'] = array_filter($branch['executives'], function($exec) use ($current_user_id) {
                            return (int)$exec['id'] === $current_user_id;
                        });
                        // Re-index array after filtering
                        $branch['executives'] = array_values($branch['executives']);
                    }
                }
                unset($branch);
            }
        }
        
        return ["list" => $users];
    }

    public function getBranchByDealer()
    {
        $query = "SELECT id AS value, name AS label  FROM dealer_branches WHERE active = 'y' AND dealer_group_id = '".$this->dealer_id."' ";
        $branches = [];
        $res = mysqli_query($this->connection,$query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'branches');
            return ["list" => []]; 
        }
        while( $row = mysqli_fetch_assoc($res) ){
            $branches['list'][] = $row;
        }
        return $branches;
    }

    public function getBranches($dealership_id) {
        $dealership_id = mysqli_real_escape_string($this->connection, $dealership_id);
        $query = "
            SELECT *  FROM dealer_branches 
            WHERE dealer_group_id = '$dealership_id' AND active = 'y' 
            ORDER BY name ASC ";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'masterdata-getBranches');
            return ["branches" => []]; 
        }
        $branches = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $branches[] = $row;
        }
        return ["branches" => $branches];
    }
    public function getColors()
    {
        $query = "SELECT id AS value, color AS label  FROM master_colors WHERE status=1";
        $colors = [];
        $res = mysqli_query($this->connection,$query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'colors');
            return ["list" => []]; 
        }
        while( $row = mysqli_fetch_assoc($res) ){
            $colors['list'][] = $row;
        }
        return $colors;
    }
    
    public function getMasterStates(){
        $query = "SELECT DISTINCT cw_state_id as id, cw_state as state FROM master_states_areaslist ORDER BY state ASC";
        $result = mysqli_query($this->connection, $query);
        if(!$result){
            logSqlError(mysqli_error($this->connection), $query, 'getmasterstates');
            return false;
        }
        $masterStates = [];
        while( $row = mysqli_fetch_assoc($result)){
            $masterStates[] = [
                'value' => (int)$row['id'],
                'label' => $row['state']
            ];
        }
        return [
            'list' => $masterStates,
        ];
    }

    // public function getCitiesByState($state)
    // {
    //     $citiesByStates = [];

    //     // Sanitize and ensure numeric
    //     $state = (int)$state;

    //     $query = "
    //         SELECT cw_city_id AS id, cw_city AS city
    //         FROM master_states_areaslist
    //         WHERE cw_state_id = $state
    //         ORDER BY cw_city ASC
    //     ";

    //     $result = mysqli_query($this->connection, $query);

    //     if (!$result) {
    //         logSqlError(mysqli_error($this->connection), $query, 'citiesByStates');
    //         return ["citiesByStates" => []];
    //     }

    //     while ($row = mysqli_fetch_assoc($result)) {
    //         $citiesByStates[] = [
    //             'value' => (int)$row['id'],
    //             'label' => $row['city']
    //         ];
    //     }

    //     return [
    //         'citiesByState' => $citiesByStates,
    //     ];
    // }

    function getCitiesByState() {
        $state_id = isset($_POST['cw_state']) ? $_POST['cw_state'] : '';

        if (empty($state_id) || $state_id === '0') {
            return ["list" => []];
        }

        $state_id = mysqli_real_escape_string($this->connection, $state_id);

        $query = "SELECT DISTINCT cw_city_id, cw_city 
                FROM master_states_areaslist
                WHERE cw_state_id = '$state_id' 
                ORDER BY cw_city DESC";

        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, "masterdata-getcitiesbystate");
            return ["list" => []];
        }

        $cities = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $cityId = (int)$row['cw_city_id'];

            $cities[] = [
                'value' => $cityId,
                'label' => $row['cw_city'],
            ];
        }

        return ["list" => $cities];
    }


    public function getStateCityByPincode($pin_code)
    {
        try {
            if (empty($pin_code)) {
                return ['state' => [], 'city' => []];
            }

            $pin_code = mysqli_real_escape_string($this->connection, $pin_code);
            $query = "SELECT cw_state_id, cw_state, cw_city_id, cw_city 
                    FROM master_states_areaslist 
                    WHERE cw_zip = '$pin_code' 
                    LIMIT 1";

            $res = mysqli_query($this->connection, $query);

            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'masterdata-getStateCityByPincode');
                return ['state' => [], 'city' => []];
            }

            $row = mysqli_fetch_assoc($res);

            if (!$row) {
                return ['state' => [], 'city' => []];
            }

            return [
                'state' => [
                    'state' => $row['cw_state_id'],
                    'state_name' => $row['cw_state'],
                ],
                'city' => [
                    'city' => $row['cw_city_id'],
                    'city_name' => $row['cw_city'],
                ],
            ];

        } catch (Exception $e) {
            logSqlError($e->getMessage(), $query ?? '', 'masterdata-getStateCityByPincode-exception');
            return ['state' => [], 'city' => []];
        }
    }


   

    public function getAllStatesCitiesPincodes()
    {
        $query = "SELECT cw_state_id, cw_state, cw_city_id, cw_city, cw_area_id, cw_area, cw_zip 
                  FROM master_states_areaslist 
                  ORDER BY cw_state_id, cw_city_id, cw_area_id, cw_zip";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, "masterdata-getAllStatesCitiesPincodes");
            return [
                "status" => 0,
                "details" => "Error fetching locations",
                "states" => []
            ];
        }

        $states = [];
        $stateIndex = [];
        $cityIndex = [];
        $areaIndex = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $stateId = $row['cw_state_id'];
            $cityId = $row['cw_city_id'];
            $areaId = $row['cw_area_id'];
            $pincode = $row['cw_zip'];

            // Add state to output array if not already present
            if (!isset($stateIndex[$stateId])) {
                $stateIndex[$stateId] = count($states);
                $states[] = [
                    "s_id" => $stateId,
                    "state" => $row['cw_state'],
                    "cities" => []
                ];
            }

            $stateIdx = $stateIndex[$stateId];

            // Add city to state's cities array if not already present
            $cityKey = $stateId . '_' . $cityId;
            if (!isset($cityIndex[$cityKey])) {
                $cityIndex[$cityKey] = count($states[$stateIdx]['cities']);
                $states[$stateIdx]['cities'][] = [
                    "c_id" => $cityId,
                    "city" => $row['cw_city'],
                    "areas" => [],
                    "pin_codes" => []
                ];
            }

            $stateIdx = $stateIndex[$stateId];
            $cityIdx = $cityIndex[$cityKey];

            // Handle areas - only add if area name is not empty
            if (!empty($row['cw_area']) && trim($row['cw_area']) !== '') {
                $areaKey = $stateId . '_' . $cityId . '_' . $areaId;
                if (!isset($areaIndex[$areaKey])) {
                    $areaIndex[$areaKey] = count($states[$stateIdx]['cities'][$cityIdx]['areas']);
                    $states[$stateIdx]['cities'][$cityIdx]['areas'][] = [
                        "a_id" => $areaId,
                        "area" => $row['cw_area'],
                        "pin_codes" => []
                    ];
                }
                
                $areaIdx = $areaIndex[$areaKey];
                
                // Add pincode to area if not already present
                if (!empty($pincode)) {
                    $existingPincodes = array_column($states[$stateIdx]['cities'][$cityIdx]['areas'][$areaIdx]['pin_codes'], 'pin_code');
                    if (!in_array($pincode, $existingPincodes)) {
                        $states[$stateIdx]['cities'][$cityIdx]['areas'][$areaIdx]['pin_codes'][] = ["pin_code" => $pincode];
                    }
                }
            }

            // Add pincode to city if not already present
            if (!empty($pincode)) {
                $existingPincodes = array_column($states[$stateIdx]['cities'][$cityIdx]['pin_codes'], 'pin_code');
                if (!in_array($pincode, $existingPincodes)) {
                    $states[$stateIdx]['cities'][$cityIdx]['pin_codes'][] = ["pin_code" => $pincode];
                }
            }
        }

        // Sort pin codes for consistent output
        foreach ($states as &$state) {
            foreach ($state['cities'] as &$city) {
                usort($city['pin_codes'], function($a, $b) {
                    return strcmp($a['pin_code'], $b['pin_code']);
                });
                foreach ($city['areas'] as &$area) {
                    usort($area['pin_codes'], function($a, $b) {
                        return strcmp($a['pin_code'], $b['pin_code']);
                    });
                }
            }
        }

        return [
            "status" => 1,
            "details" => "Locations available",
            "states" => $states
        ];
    }

    public function getAllSourcesSubSources($type = '')     
    {
        $type = trim($type);

        // Build WHERE clause based on type
        $whereClause = "WHERE ms.active = '1'";
        if ($type === 'pm' || $type === 'purchase-master' || $type === 'my-stock') {
            $whereClause .= " AND ms.pm_flag = 1";
        } elseif ($type === 'sm' || $type === 'sales-master') {
            $whereClause .= " AND ms.sm_flag = 1";
        }
        // else: no filter → fetch all

        $query = "
            SELECT 
                ms.id AS source_id,
                ms.source,
                mss.id AS source_sub_id,
                mss.sub_source
            FROM master_sources ms
            LEFT JOIN master_sources_sub mss 
                ON ms.id = mss.source_id 
                AND mss.active = '1'
            $whereClause
            ORDER BY ms.id, mss.id
        ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'masterdata-getAllSourcesSubSources');
            return [
                "sources" => []
            ];
        }

        $sources = [];
        $sourceIndex = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $sourceId = $row['source_id'];

            // Add main source if not already added
            if (!isset($sourceIndex[$sourceId])) {
                $sourceIndex[$sourceId] = count($sources);
                $sources[] = [
                    "source_id" => (string)$sourceId,
                    "source_name" => $row['source'],
                    "sub_source" => []
                ];
            }

            $sourceIdx = $sourceIndex[$sourceId];

            // Add sub-source if available
            if (!empty($row['source_sub_id']) && !empty($row['sub_source'])) {
                $sources[$sourceIdx]['sub_source'][] = [
                    "source_sub_id" => (string)$row['source_sub_id'],
                    "source_sub_name" => $row['sub_source']
                ];
            }
        }

        return [
            "sources" => $sources
        ];
    }


    public function getSources() {
        $type = isset($_POST['type']) ? trim($_POST['type']) : '';

        $whereClause = "WHERE active = '1'";

        // Apply filters based on type
        if ($type === 'pm' || $type === 'purchase-master' || $type === 'my-stock') {
            $whereClause .= " AND pm_flag = 1";
        } elseif ($type === 'sm' || $type === 'sales-master') {
            $whereClause .= " AND sm_flag = 1";
        } elseif (!empty($type)) {
            return [
                "status" => 0,
                "error" => "Invalid type parameter. Expected: purchase-master, sales-master, or my-stock",
                "list" => []
            ];
        }
        // if type is empty, just get all (no extra filter)

        $query = "
            SELECT id AS value, source AS label 
            FROM master_sources 
            $whereClause
            ORDER BY id ASC
        ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'masterdata-getSources');
            return [
                "list" => []
            ];
        }

        return [
            "list" => mysqli_fetch_all($result, MYSQLI_ASSOC)
        ];
    }




    // Get all active colors
    public function getAllColors()
    {
        $query = "SELECT id AS color_id, color FROM master_colors WHERE status = 1 ORDER BY color ASC";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, "masterdata-getAllColors");
            return [
                "status" => 0,
                "details" => "Error fetching colors",
                "colors" => []
            ];
        }

        $colors = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $colors[] = [
                "color_id" => (int)$row['color_id'],
                "color_name" => $row['color']
            ];
        }

        return [
            "status" => 1,
            "details" => "Colors available",
            "colors" => $colors
        ];
    }


    public function getColorsByMake($make_id = null)
    {
        // Sanitize make_id
        $make_id = $make_id ? (int)$make_id : 0;


        // Check if this make is a JLR brand (is_brand_group = 'y')
        $isJLRMake = false;
        if ($make_id > 0) {
            $makeCheckQuery = "SELECT EXISTS (
                                                SELECT 1
                                                FROM master_makes
                                                WHERE id = $make_id
                                                    AND active = 'y'
                                                    AND is_brand_group = 'y'
                                                ) AS is_jlr;";

         $makeCheckResult = mysqli_query($this->connection, $makeCheckQuery);
        //  echo $makeCheckQuery;
        //  exit;
            if ($makeCheckResult) {
                $makeCheckRow = mysqli_fetch_assoc($makeCheckResult);
                $isJLRMake = ($makeCheckRow['is_jlr'] > 0);
            }
        }
        
        // Build WHERE clause based on make type
        if ($isJLRMake) {
            // JLR makes: show ONLY colors for this specific make
            $whereClause = "make_id = $make_id";
            $orderClause = "exterior_color ASC";
        } else {
            // Non-JLR makes: show ONLY "Other" colors (make_id = 0)
            $whereClause = "make_id = 0";
            $orderClause = "exterior_color ASC";
        }
        
        // Fetch exterior colors
        $exteriorQuery = "SELECT id, make_id, make, exterior_color, base_color 
                          FROM exterior_colors 
                          WHERE active = 'y' AND $whereClause
                          ORDER BY $orderClause";
        
        $exteriorResult = mysqli_query($this->connection, $exteriorQuery);
        if (!$exteriorResult) {
            logSqlError(mysqli_error($this->connection), $exteriorQuery, 'masterdata-getColorsByMake-exterior');
            return [
                "status" => 0,
                "details" => "Error fetching colors",
                "exterior_colors" => [],
                "interior_colors" => []
            ];
        }

        $exteriorColors = [];
        while ($row = mysqli_fetch_assoc($exteriorResult)) {
            $exteriorColors[] = [
                "value" => (int)$row['id'],
                "label" => $row['exterior_color'],
                "base_color" => $row['base_color'],
                "make" => $row['make'],
                "make_id" => (int)$row['make_id']
            ];
        }

        // Fetch interior colors (use same WHERE clause logic)
        $interiorOrderClause = str_replace("exterior_color", "interior_color", $orderClause);
        $interiorQuery = "SELECT id, make_id, make, interior_color, base_color 
                          FROM interior_colors 
                          WHERE active = 'y' AND $whereClause
                          ORDER BY $interiorOrderClause";
        
        $interiorResult = mysqli_query($this->connection, $interiorQuery);
        if (!$interiorResult) {
            logSqlError(mysqli_error($this->connection), $interiorQuery, 'masterdata-getColorsByMake-interior');
            return [
                "status" => 0,
                "details" => "Error fetching colors",
                "exterior_colors" => [],
                "interior_colors" => []
            ];
        }

        $interiorColors = [];
        while ($row = mysqli_fetch_assoc($interiorResult)) {
            $interiorColors[] = [
                "value" => (int)$row['id'],
                "label" => $row['interior_color'],
                "base_color" => $row['base_color'],
                "make" => $row['make'],
                "make_id" => (int)$row['make_id']
            ];
        }

        return [
            "status" => 1,
            "details" => "Colors fetched successfully",
            "exterior_colors" => $exteriorColors,
            "interior_colors" => $interiorColors
        ];
    }




}

?>