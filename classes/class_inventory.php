<?php
class MyStock
{
    public $dealer_id;
    public $executive_id;
    public $login_user_id;
    public $executive_name;
    public $branch_id;
    public $connection;
    public $commonConfig;
    public $id;
    public $area;
    public $status;
    public $car_type;
    public $make;
    public $make_name;
    public $model;
    public $model_name;
    public $body_type;
    public $body_type_name;
    public $variant;
    public $variant_name;
    public $color;
    public $color_name;
    public $interior_color;
    public $mileage;
    public $mfg_month;
    public $mfg_year;
    public $owners;
    public $reg_type;
    public $reg_date;
    public $reg_num;
    public $regcity;
    public $chassis;
    public $transmission;
    public $fuel;
    public $fuel_end;
    public $is_sold;
    public $price_quoted;
    public $price_selling;
    public $price_expenses;
    public $selllead_id;
    public $buylead_id;
    public $file_doc1;
    public $file_doc2;
    public $added_by;
    public $updated_by;
    public $added_on;
    public $updated_on;
    public $remarks;
    public $certification_remarks;
    public $state;
    public $state_name;
    public $city;
    public $city_name;
    public $certification_carage;
    public $certification_carmileage;
    public $certification_type;
    public $certification_by;
    public $certified_date;
    public $date_of_handover;
    public $date_of_sale;
    public $date_of_warranty_start;
    public $certification_checklist;
    public $certification_documents;
    
    // Ordered keys for the inventory images
    private $orderedImageKeys = ['front','front-rhs','left','right','rear','rear-rhs','dashboard','dicky-floor','speedometer','upholstery','wheels'];

    public function __construct($id = 0) {
        global $connection;               
        $this->connection = $connection;
        $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        $this->id = (int)$id;
        $this->state = 0;
        $this->state_name = '';
        $this->city = 0;
        $this->city_name = '';
        $this->area = '';
        $this->status = 0;
        $this->car_type = 0;
        $this->make = 0;
        $this->make_name = '';
        $this->model = 0;
        $this->model_name = '';
        $this->variant = 0;
        $this->variant_name = '';
        $this->color = '';
        $this->color_name = '';
        $this->mileage = 0;
        $this->mfg_month = 0;
        $this->mfg_year = 0;
        $this->owners = '';
        $this->reg_type = '';
        $this->reg_date = '';
        $this->reg_num = '';
        $this->regcity = '';
        $this->chassis = '';
        $this->transmission = 0;
        $this->fuel = 0;
        $this->fuel_end = 0;
        $this->is_sold = 'n';
        $this->price_quoted = 0;
        $this->price_selling = 0;
        $this->price_expenses = 0;
        $this->selllead_id = 0;
        $this->buylead_id = 0;
        $this->file_doc1 = null;
        $this->file_doc2 = null;
        $this->added_by = $this->login_user_id;
        $this->updated_by = $this->login_user_id;
        $this->added_on = date('Y-m-d H:i:s');
        $this->updated_on = date('Y-m-d H:i:s');
        $this->remarks = '';
        $this->certification_carage = 5; //carage Years
        $this->certification_carmileage = 125000; //mileage
        
        // if($id && is_numeric($id)) {
        //     // Use standardized base query for consistency
        //     // $query = $this->buildInventoryBaseQuery() . " WHERE i.id = " . $id . " LIMIT 1";
            
        //     $res = mysqli_query($this->connection, $query);
            
        //     if (!$res) {
        //         logSqlError(mysqli_error($this->connection), $query, 'inventory-constructor', true);
        //     }
            
        //     if ($res && mysqli_num_rows($res) > 0) {
        //         $row = mysqli_fetch_assoc($res);
        //         foreach ($row as $key => $val) {
        //             if (property_exists($this, $key)) {
        //                 $this->$key = $val;
        //             }
        //         }
        //     }
        // }
        
        // require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
    }

    /**
     * Executive condition check for query filtering
     * Used consistently across all query methods
     */
    private function executiveCondition()
    {
        if ($this->executive_id > 0 && $this->dealer_id != $this->executive_id) {
            return " AND i.executive = " . intval($this->executive_id);
        }
        return '';
    }

    public function ownerCheck($inventoryId)
    {
        try {
            if (empty($inventoryId) || $inventoryId <= 0) return false;
            if (empty($this->dealer_id) || $this->dealer_id <= 0) return false;

            $inventory_id = (int)$inventoryId;
            $dealer_id = (int)$this->dealer_id;

            $query = "SELECT COUNT(*) AS cnt FROM inventory i WHERE i.id = $inventory_id AND i.dealer = $dealer_id";

            $query .= $this->executiveCondition();

   

            // Branch scoping similar to sellleads: gather allowed user ids (executives) and ensure record is among them when filtered
            $userIds = [];
            $branches = getUsersByBranchIds($this->branch_id);

            if (!empty($branches)) {
                foreach ($branches as $branch) {
                    if (!empty($branch['executives'])) {
                        foreach ($branch['executives'] as $exec) {
                            if (!empty($exec['id'])) $userIds[] = (int)$exec['id'];
                        }
                    }
                }
            }

            $userIds = array_values(array_unique(array_filter($userIds, function($v){ return $v>0; })));         
 

            if (!empty($userIds)) {
                $inUsers = implode(',', $userIds);
                $query .= " AND i.user IN ($inUsers)";
            }

            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'inventory-ownercheck', true);
                return false;
            }
            $row = mysqli_fetch_assoc($res);
            if (!$row || !isset($row['cnt'])) return false;


                    //  echo $row['cnt'] > 0;
                    // exit;
            return ($row['cnt'] > 0);
        } catch (Exception $e) {
            logError($e->getMessage(), 'inventory-ownercheck', true);
            return false;
        }
    }


    private function ensureFilesClass()
    {
        if (!class_exists('Files')) {
            $basePath = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);
            require_once rtrim($basePath, '/\\') . '/classes/class_files.php';
        }
    }

    public function getLeadImages(array $leadIds): array
    {
        $leadImages = [];
        $leadIds = array_filter(array_map('intval', $leadIds), fn($id) => $id > 0);

        if (empty($leadIds)) return $leadImages;

        $moduleConfig = new moduleConfig();
        $configData = (array)($moduleConfig->getConfig('my-stock')['images'] ?? []);
    
        $imageKeys = array_keys($configData);

        if (empty($imageKeys)) return $leadImages; // Nothing to fetch

        $in = implode(',', $leadIds);

        // Use WHERE IN for selllead_id and filter by image_tag
        $tagsIn = "'" . implode("','", array_map('addslashes', $imageKeys)) . "'";
      
        $query = "
            SELECT inventory_id, id, image AS url, default_image, image_tag 
            FROM inventory_images 
            WHERE inventory_id IN ($in) 
            AND status = 1
            AND image_tag IN ($tagsIn)
            ORDER BY inventory_id ASC, default_image DESC, id ASC
        ";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'inventory-getleadimages', true);
            return $leadImages;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $sid = (int)$row['inventory_id'];            
            $isFirst = empty($leadImages[$sid]);
            $leadImages[$sid][] = [
                'id'    => $row['id'],
                'tag'   => $row['image_tag'] ?? '',
                'thumb' => $isFirst ? Files::imageLink($row['url'], '225x300') : '',
                'url'   => Files::imageLink($row['url'], '600x800')
            ];
        }
        return $leadImages;
    }

    public function mapLeadImages(array $rawImages): object
    {
        $mapped = [];
        $rawByTag = [];
        foreach ($rawImages as $img) {
            $tag = $img['tag'] ?? '';
            if ($tag && !empty($img['url'])) { 
                $rawByTag[$tag] = $img;
            }
        }

        if (empty($rawByTag)) return (object)[];

        $moduleConfig = new moduleConfig();
        $configTemplate = (array)($moduleConfig->getConfig('stock')['images'] ?? []);

        // Loop only over raw images that actually exist
        foreach ($rawByTag as $tag => $dbImg) {
            $cfg = $configTemplate[$tag] ?? [];

            $mapped[$tag] = [
                'imgId' => $dbImg['id'] ?? '',
                'imgSno' => $tag,
                'imgName' => $cfg['fieldLabel'] ?? ucwords(str_replace(['-', '_'], ' ', $tag)),
                'imgLogo' => '',
                'imgOrientation' => 'L',
                'imgAction' => 'add',
                'imgEdit' => '',
                'imgLat' => '',
                'imgLong' => '',
                'imgTime' => '',
                'imgPath' => $dbImg['url'] ?? '',
                'imgAngle' => $cfg['imgPart']->imgAngle ?? '',
                'imgOverlayLogo' => $cfg['imgPart']->imgOverlayLogo ?? '',
            ];
        }

        return (object)$mapped;
    }

    private function mapLeadImagesLeads($rawImages)
    {
        if (empty($rawImages) || !is_array($rawImages)) {
            return (object)[];
        }

        $mapped = [];
        $fallbackIndex = 1;

        foreach ($rawImages as $image) {
            $tag = $image['tag'] ?? '';

            if (!empty($tag) && in_array($tag, $this->orderedImageKeys, true)) {
                $mapped[$tag] = $image;
                continue;
            }

            $key = !empty($tag) ? $tag : 'other_img' . $fallbackIndex++;

            // Avoid overwriting identical keys
            while (isset($mapped[$key])) {
                $key = !empty($tag) ? $tag . '_' . $fallbackIndex++ : 'other_img' . $fallbackIndex++;
            }

            $mapped[$key] = $image;
        }

        return (object)$mapped;
    }

    private function processDocuments(array $lead): array {
        global $config;
        $documents = [];
        $baseUrl = $config['document_base_url'];

        foreach (['file_doc1', 'file_doc2','certification_documents'] as $key) {
            if (!empty($lead[$key]) && trim($lead[$key]) !== '') {
                $trimmedValue = trim($lead[$key]);
                
                // Check if already a full URL (starts with http:// or https://)
                if (preg_match('/^https?:\/\//i', $trimmedValue)) {
                    $fullUrl = $trimmedValue; // Already a full URL, use as-is
                } else {
                    $fullUrl = $baseUrl . $trimmedValue; // Relative path, prepend base URL
                }

                // Update lead with full URL
                $lead[$key] = $fullUrl;

                // Add to documents collection
                $documents[$key] = [
                    'name' => ucfirst(str_replace('_', ' ', $key)),
                    'url'  => $fullUrl,
                    'key'  => $key
                ];
            } else {
                // If missing, keep empty string (not null)
                $lead[$key] = '';
            }
        }

        return  $documents;
    }

    /**
     * Build standardized base query for inventory records
     * Uses consistent table aliases and JOIN patterns
     */
    private function buildInventoryBaseQuery($includeDetailFields = true)
    {
        $select_fields = [
            'i.id',
            'i.selllead_id',
            'i.dealer',
            'i.branch',
            'IFNULL(d.name, \'\') AS dealer_name',
            'IFNULL(b.name, \'\') AS branch_name',
            'i.user',
            'IFNULL(u.name, \'\') AS user_name',
            'i.executive',
            'IFNULL(e.name, \'\') AS executive_name',
            'i.make',
            'IFNULL(v.make, \'\') AS make_name',
            'IFNULL(mk.is_certifiable, \'\') AS is_certifiable',
            'i.model',
            'IFNULL(v.model, \'\') AS model_name',
            'i.variant',
            'IFNULL(v.variant, \'\') AS variant_name',
            'i.body_type',
            buildSqlCaseFromConfig('i.body_type', $this->commonConfig['body_types']) . ' AS body_type_name',
            'i.status',
            buildSqlCaseFromConfig('i.status', $this->commonConfig['inventory_lead_statuses']) . ' AS status_name',
            'i.reg_type',
            buildSqlCaseFromConfig('i.reg_type', $this->commonConfig['reg_type']) . ' AS reg_type_name',
            'i.reg_num',
            'i.reg_date',
            'i.chassis',
            'i.certification_type',
            buildSqlCaseFromConfig('i.certification_type', $this->commonConfig['certification_type']) . ' AS certification_type_name',
            'i.is_jlr_vehicle',
            'i.listing_price',
            'i.added_on',
            'i.updated_on',
            'i.mfg_year',
            'i.mfg_month',
            buildSqlCaseFromConfig('i.mfg_month', $this->commonConfig['months']) . ' AS mfg_month_name',
            'i.car_type',
            buildSqlCaseFromConfig('i.car_type', $this->commonConfig['car_type']) . ' AS car_type_name',
            'i.remarks',
            'i.certification_checklist',
            'i.certification_status'
        ];

        if($includeDetailFields)
        {
            $select_fields = array_merge($select_fields,[
                'i.color',
                buildSqlCaseFromConfig('i.color', $this->commonConfig['colors']) . ' AS color_name',
                'i.interior_color',
                'i.transmission',
                buildSqlCaseFromConfig('i.transmission', $this->commonConfig['transmission']) . ' AS transmission_name',
                'i.fuel',
                buildSqlCaseFromConfig('i.fuel', $this->commonConfig['fuel']) . ' AS fuel_name',
                'i.fuel_end',
                'i.hypothecation',
                buildSqlCaseFromConfig('i.hypothecation', $this->commonConfig['hypothecation']) . ' AS hypothecation_name',
                'i.mfg_month',
                buildSqlCaseFromConfig('i.mfg_month', $this->commonConfig['months']) . ' AS mfg_month_name',
                'i.mfg_year',
                'i.insurance_type',
                buildSqlCaseFromConfig('i.insurance_type', $this->commonConfig['insurance_type']) . ' AS insurance_type_name',
                'i.insurance_exp_date',
                'i.mileage',
                'i.owners',
                buildSqlCaseFromConfig('i.owners', $this->commonConfig['owners']) . ' AS owners_name',
                'i.file_doc1',
                'i.file_doc2',
                'i.car_type',
                buildSqlCaseFromConfig('i.car_type', $this->commonConfig['car_type']) . ' AS car_type_name',
                'i.source_other',
                buildSqlCaseFromConfig('i.source_other', $this->commonConfig['source_other']) . ' AS source_other_name',
                'i.date_of_sale',
                'i.date_of_handover',
                'i.date_of_warranty_start',            
                'i.certified_by',
                'su.name AS certified_by_name',
                'i.certified_date',
                'i.certification_status',
                buildSqlCaseFromConfig('i.certification_status', $this->commonConfig['certification_status']) . ' AS certification_status_name',
                'i.certification_remarks',
                'i.certification_documents',
                'i.vahan_response',
                'i.vahan_fetched_at',
                // Sellleads data
                's.title',
                's.first_name',
                's.last_name',
                's.mobile',
                's.email',
                's.state',
                's.city',
                's.pin_code',
                'IFNULL(msa.cw_state, \'\') AS state_name',
                'IFNULL(msa.cw_city, \'\') AS city_name',
                's.address',
                's.source',
                'IFNULL(src.source, \'\') AS source_name',
                's.source_sub',
                'IFNULL(srcs.sub_source, \'\') AS source_sub_name',
            ]);
        }

        // Auto-wrap fields with IFNULL() except CASE or existing IFNULL
        $select_fields = array_map(function ($field) {
            $f = trim($field);

            // If it's already an IFNULL, CASE, contains AS (alias) or is a function/expression, leave as-is
            if (
                stripos($f, 'ifnull(') !== false ||
                stripos($f, 'case') !== false ||
                stripos($f, ' as ') !== false ||
                preg_match('/\w+\(.*\)/', $f) // any function call like SUM(...), CONCAT(...)
            ) {
                return $f;
            }

            // Try to extract alias from last token after dot, fallback to whole token
            $alias = $f;
            if (preg_match('/\.(\w+)$/', $f, $m)) {
                $alias = $m[1];
            } elseif (preg_match('/(\w+)$/', $f, $m2)) {
                $alias = $m2[1];
            }

            // sanitize alias
            $alias = preg_replace('/[^A-Za-z0-9_]/', '', $alias);
            if ($alias === '') $alias = 'col';

            return "IFNULL($f, '') AS $alias";
        }, $select_fields);

        $joins = [
            'LEFT JOIN dealer_groups d ON i.dealer = d.id',
            'LEFT JOIN users u ON i.user = u.id',
            'LEFT JOIN users e ON i.executive = e.id',
            'LEFT JOIN master_variants_new v ON i.variant = v.id',
            'LEFT JOIN dealer_branches b ON i.branch = b.id',
            'LEFT JOIN master_makes mk ON v.make_id = mk.id',
            'LEFT JOIN sellleads s ON i.selllead_id = s.id',
            'LEFT JOIN master_states_areaslist msa ON s.pin_code = msa.cw_zip',
            'LEFT JOIN master_sources src ON s.source = src.id',
            'LEFT JOIN master_sources_sub srcs ON s.source_sub = srcs.id',
            ' LEFT JOIN users su ON i.certified_by = su.id',
        ];

        return [
            'select' => 'SELECT ' . implode(",\n            ", $select_fields),
            'from_joins' => 'FROM inventory i ' . implode(' ', $joins)
        ];
    }
    
    public function getLead($leadId) 
    {
         global $config;
        $lead = [];
        
        // Input validation
        if (empty($leadId) || $leadId <= 0) { 
            return $lead; 
        }
        
        $lead_id = (int)$leadId;
        
        // Check database connection
        if (!$this->connection) {
            return $lead;
        }
       
        $query_parts = $this->buildInventoryBaseQuery(true);
       
        $query =  "{$query_parts['select']} 
                     {$query_parts['from_joins']}
                     WHERE i.id = $lead_id LIMIT 1";
                
        $res = @mysqli_query($this->connection, $query);
        if (!$res) { 
            $error = mysqli_error($this->connection);
            logSqlError($error, $query, 'sellleads-getlead', true); 
            return $lead; 
        }
        if( mysqli_num_rows($res)>0 )
        {
            $lead_res = mysqli_fetch_assoc($res);

            $lead['id'] = data_encrypt($lead_res['id']);
            if (isset($lead_res['reg_type']) && $lead_res['reg_type'] == '1') { // '1' = 'Unregistered'
                $lead_res['reg_num'] = '';
                $lead_res['reg_date'] = '';
            }            
            if($lead_res['is_certifiable'] == "y"){
                $carage = $this->calculateCarAge($lead_res['reg_date']);
                $lead_res['car_age'] = $carage['car_age']." years";
                $lead_res['age_criteria'] = $carage['age_criteria'];
                $lead_res['mileage_criteria'] = $lead_res['mileage'] < $this->certification_carmileage;
                $lead_res['car_age_allowed']  = $this->certification_carage." years";
                $lead_res['mileage_age_allowed']  = $this->certification_carmileage." km";
            }
            if( !empty($lead_res['certification_checklist']) ){
                $cert_checklist = json_decode($lead_res['certification_checklist'],true);
                foreach($cert_checklist as $kk=>$vv){
                    $lead_res[$kk] = $vv;
                }
            }
            
            $lead['documents'] = (object)$this->processDocuments($lead_res);
            if (!empty($lead['documents'])) {
                foreach ($lead['documents'] as $key => $doc) {
                    if (!empty($doc['url'])) {
                        $lead['detail'][$key] = $doc['url'];
                    }
                }
            }
            /*
            if( !empty($lead_res['file_doc1']) ){
                $lead['documents']['file_doc1'] = $config['document_base_url'].$lead_res['file_doc1'];
            }
            if( !empty($lead_res['file_doc2']) ){
                $lead['documents']['file_doc2'] = $config['document_base_url'].$lead_res['file_doc2'];
            }
            if( !empty($lead_res['certification_documents']) ){
                $lead['documents']['certification_documents'] = $config['document_base_url'].$lead_res['certification_documents'];
                //$lead_res['certification_documents'] = $config['document_base_url'].$lead_res['certification_documents'];
            }
                */
          

            if (!empty($lead_res['certified_by'])) {
                $lead_res['certified_by_name'] = getUserNameById($lead_res['certified_by']);
            }

            $lead['detail'] = $lead_res;
            $lead['detail']['numeric_id'] = $lead_res['id'];
            $lead['detail']['formatted_id'] = "INV{$lead_res['id']}";
            $lead['detail']['id'] = $lead['id'];

            try {
                $leadImages = $this->getLeadImages([$lead_id]);
                $rawImages = $leadImages[$lead_id] ?? [];
                $lead['images'] = $this->mapLeadImages($rawImages);
            } catch (Exception $e) {
                $lead['images'] = (object)[];
            }
            $lead['vahanInfo'] = $this->getVahanInfo($leadId, $lead_res['reg_num'] ?? '', $lead_res['vahan_response'] ?? null);
            unset($lead['detail']['vahan_response']); // Remove raw response for security
            $lead['evaluation_templates'] = $this->evaluationTemplates($lead_id);
            $lead['history'] = $this->getStockHistory($lead_id);
        }
        return $lead;
    }

  
    public function evaluationTemplates($lead_id)
    {
        static $templates = null;
        if ($templates === null) {
            $templates = [];
            $mapped_templates = [];
            $temp_query = "SELECT id, template_name, template_description FROM `evaluation_templates` WHERE status = 1";
            $temp_res = mysqli_query($this->connection, $temp_query);
            if ($temp_res) {
                while ($temp_row = mysqli_fetch_assoc($temp_res)) {
                    $templates[$temp_row['id']] = $temp_row;
                }
            }
            if (!empty($lead_id) && $lead_id > 0) {
            // Use prepared statement for better security
                $stmt = mysqli_prepare($this->connection, "SELECT template_id FROM inventory_evaluation WHERE inventory_id = ? AND status = 1");
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $lead_id);
                    mysqli_stmt_execute($stmt);
                    $pm_res = mysqli_stmt_get_result($stmt);
                    
                    if ($pm_res && mysqli_num_rows($pm_res) > 0) {                       
                        $templateCounts = []; // Track counts for each template
                        
                        while ($pm_row = mysqli_fetch_assoc($pm_res)) {
                            $mapped_templates[] = $pm_row['template_id'];
                        }                        
                        sort($mapped_templates);                        
                    }
                }
            } 
        }      
        return ["templates"=>$templates,"mapped_templates"=>$mapped_templates];
    }
   
    private function getVahanInfo(int $leadId, string $regNum, $vahanResponse)
    {
        // Step 1: Try to use existing vahan_response
        if (!empty($vahanResponse)) {
            try {
                $parsed = is_string($vahanResponse) ? json_decode($vahanResponse, true) : $vahanResponse;
                if (is_array($parsed) && !empty($parsed)) {
                    // If the response has 'result' key, extract it (full API response structure)
                    // Otherwise, return as-is (already extracted result data)
                    if (isset($parsed['result']) && is_array($parsed['result'])) {
                        return (object)$parsed['result'];
                    }
                    return (object)$parsed;
                }
            } catch (Exception $e) {
                error_log("Error parsing vahan_response for lead {$leadId}: " . $e->getMessage());
            }
        }
        
        // Step 2: No data in sellleads, try to sync from vahan_api_log
        if (!empty($regNum) && $leadId > 0) {
            $regNum = mysqli_real_escape_string($this->connection, $regNum);
            
            // Get latest response from vahan_api_log
            $log_query = "SELECT response, created_at 
                         FROM vahan_api_log 
                         WHERE reg_num = '{$regNum}' 
                         ORDER BY id DESC 
                         LIMIT 1";
            
            $log_result = mysqli_query($this->connection, $log_query);
            
            if ($log_result && mysqli_num_rows($log_result) > 0) {
                $log_row = mysqli_fetch_assoc($log_result);
                $log_response = $log_row['response'];
                
                // Parse the log response
                try {
                    $parsed_log = json_decode($log_response, true);
                    $vahan_data = $parsed_log['result'] ?? [];
                    
                    if (!empty($vahan_data)) {
                        // Update sellleads with this data
                        $vahan_json = json_encode($vahan_data, JSON_UNESCAPED_UNICODE);
                        $timestamp = date('Y-m-d H:i:s');
                        
                        $update_query = "UPDATE inventory 
                                        SET vahan_response = '" . mysqli_real_escape_string($this->connection, $vahan_json) . "',
                                            vahan_fetched_at = '{$timestamp}'
                                        WHERE id = {$leadId}";
                        
                        $update_result = mysqli_query($this->connection, $update_query);
                        
                        if (!$update_result) {
                            error_log("Failed to sync vahan data to sellleads for lead {$leadId}: " . mysqli_error($this->connection));
                        }
                        
                        // Return the synced data
                        return (object)$vahan_data;
                    }
                } catch (Exception $e) {
                    error_log("Error parsing vahan_api_log response for lead {$leadId}: " . $e->getMessage());
                }
            }
        }
        
        // Step 3: No data found anywhere, return empty object
        return (object)[];
    }

   
    public function saveImage($image, $inventoryId, $image_tag)
    {
        if (empty($image)) {
            return ['status' => false, 'details' => []];
        }

        $inventoryId = (int)$inventoryId;
        if ($inventoryId <= 0) {
            return ['status' => false, 'details' => []];
        }

        $image_tag = mysqli_real_escape_string($this->connection, $image_tag);

        // Check if record already exists for this inventory + tag
        $check_query = "SELECT id FROM inventory_images WHERE inventory_id = $inventoryId AND image_tag = '$image_tag' AND status = 1 LIMIT 1";
        $check_res = mysqli_query($this->connection, $check_query);

        if (!$check_res) {
            logSqlError(mysqli_error($this->connection), $check_query, 'inventory-saveimage', true);
            return ['status' => false, 'details' => []];
        }

        $existing = mysqli_fetch_assoc($check_res);
        if ($existing) {
            // Update old record to inactive
            $update = "UPDATE inventory_images SET status = 0, updated_date = '" . date('Y-m-d H:i:s') . "' WHERE id = " . (int)$existing['id'];
            $res = mysqli_query($this->connection, $update);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $update, 'inventory-saveimage', true);
                return ['status' => false, 'details' => []];
            }
        }

        // Insert new record
        $imageEscaped = mysqli_real_escape_string($this->connection, $image);
        $insert = "INSERT INTO inventory_images (inventory_id, image, image_tag, uploaded_by) VALUES ($inventoryId, '$imageEscaped', '$image_tag', " . (int)$this->login_user_id . ")";
        $res = mysqli_query($this->connection, $insert);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $insert, 'inventory-saveimage', true);
            return ['status' => false, 'details' => []];
        }

        $lastId = mysqli_insert_id($this->connection);

        $this->ensureFilesClass();

        $details = [
            'id' => $lastId,
            'tag' => $image_tag,
            'url' => Files::imageLink($image, '600x800'),
            'thumb' => Files::imageLink($image, '225x300'),
            'default_image' => false
        ];

        return ['status' => true, 'details' => $details];
    }

    public function findImage($image_id, $inventory_id)
    {
        $inventory_id = (int)$inventory_id;
        $image_id = (int)$image_id;

        if ($inventory_id <= 0 || $image_id <= 0) {
            return false;
        }

        $query = "SELECT id FROM inventory_images WHERE id = $image_id AND inventory_id = $inventory_id AND status = 1";
        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'inventory-findimage', true);
            return false;
        }

        return mysqli_num_rows($res) > 0;
    }

    public function deleteImage($image_id, $inventory_id)
    {
        $inventory_id = (int)$inventory_id;
        $image_id = (int)$image_id;

        if ($inventory_id <= 0 || $image_id <= 0) {
            return false;
        }

        $query = "UPDATE inventory_images SET status = 0, updated_date = '" . date('Y-m-d H:i:s') . "' WHERE id = $image_id AND inventory_id = $inventory_id";
        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'inventory-deleteimage', true);
            return false;
        }

        return mysqli_affected_rows($this->connection) > 0;
    }

    public function setDefalultImage($image_id, $inventory_id)
    {
        $inventory_id = (int)$inventory_id;
        $image_id = (int)$image_id;

        if ($inventory_id <= 0 || $image_id <= 0) {
            return false;
        }

        // Set the specified image as default
        $query = "UPDATE inventory_images SET default_image = 'y', updated_date = '" . date('Y-m-d H:i:s') . "' WHERE id = $image_id AND inventory_id = $inventory_id AND status = 1";
        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'inventory-setdefaultimage', true);
            return false;
        }

        if (mysqli_affected_rows($this->connection) > 0) {
            // Reset other images to not default for this inventory
            $reset = "UPDATE inventory_images SET default_image = 'n' WHERE id <> $image_id AND inventory_id = $inventory_id";
            mysqli_query($this->connection, $reset);
            return true;
        }

        return false;
    }
    
    public function getLeads($current_page = 1, $per_page = 10, $filters = []) 
    {
        $leads = [];
        $inventory_ids = []; // For fetching images later
        
        // Input validation
        $current_page = (int)$current_page;
        $per_page = (int)$per_page;
        $per_page = (int)$per_page;
        if ($current_page < 1) $current_page = 1;
        if ($per_page < 1) $per_page = 10;
        if ($per_page > 100) $per_page = 100;
        
        // Check database connection
        if (!$this->connection) {
            return ["count" => 0, "list" => [], "current_page" => $current_page, "per_page" => $per_page];
        }
        
        // Check dealer_id
        if (empty($this->dealer_id)) {
            return ["count" => 0, "list" => [], "current_page" => $current_page, "per_page" => $per_page];
        }
        
        $dealer_id = (int)$this->dealer_id;
        $offset = ($current_page - 1) * $per_page;
        
        // Build WHERE conditions with security checks
        $where_conditions = ["i.dealer = $dealer_id"];
        
        // Add branch scoping for security
        $branches = getUsersByBranchIds($this->branch_id);
        $userIds = [];
        
        if (!empty($branches)) {
            foreach ($branches as $branch) {
                if (!empty($branch['executives'])) {
                    foreach ($branch['executives'] as $exec) {
                        $userIds[] = intval($exec['id']);
                    }
                }
            }
        }
        $userIds = array_values(array_unique(array_filter($userIds, function($v){ return $v > 0; })));
        
        if (!empty($userIds)) {
            $inUsers = implode(',', $userIds);
            $where_conditions[] = "i.user IN ($inUsers)";
        }
        
        // Add executive condition
        $executive_condition = $this->executiveCondition();
        if (!empty($executive_condition)) {
            $where_conditions[] = ltrim($executive_condition, ' AND');
        }
        
        // Add search filters
        if (!empty($filters)) {
            if (!empty($filters['id'])) {
                // Handle flexible ID search - both exact and partial matching
                $id_value = trim($filters['id']);
                
                // Build flexible search conditions for ID
                $id_conditions = [];
                
                // 1. Always check for exact numeric match (like "9" matches id=9)
                if (is_numeric($id_value)) {
                    $numeric_id = (int)$id_value;
                    $id_conditions[] = "i.id = $numeric_id";
                }
                
                // 2. Check if it matches full INV pattern (like "INV9")
                if (preg_match('/^INV(\d+)$/i', $id_value, $matches)) {
                    $numeric_id = (int)$matches[1];
                    $id_conditions[] = "i.id = $numeric_id";
                }
                
                // 3. Always add partial text search for cases like "I", "IN", "V9", etc.
                // This will work alongside numeric search
                $search_term = mysqli_real_escape_string($this->connection, strtoupper($id_value));
                $id_conditions[] = "UPPER(CONCAT('INV', i.id)) LIKE '%$search_term%'";
                
                // Use OR condition for ID matching (any of the conditions can match)
                if (!empty($id_conditions)) {
                    $where_conditions[] = "(" . implode(" OR ", $id_conditions) . ")";
                }
            }
            
            if (!empty($filters['reg_num'])) {
                $reg_num = mysqli_real_escape_string($this->connection, $filters['reg_num']);
                $where_conditions[] = "i.reg_num LIKE '%$reg_num%'";
            }
            
            if (!empty($filters['chassis'])) {
                $chassis = mysqli_real_escape_string($this->connection, $filters['chassis']);
                // Handle potential character encoding issues and case insensitivity
                $where_conditions[] = "UPPER(i.chassis) LIKE UPPER('%$chassis%')";
            }
            
            if (!empty($filters['make']) && is_numeric($filters['make'])) {
                $make = (int)$filters['make'];
                $where_conditions[] = "i.make = $make";
            }
            
            if (!empty($filters['model']) && is_numeric($filters['model'])) {
                $model = (int)$filters['model'];
                $where_conditions[] = "i.model = $model";
            }
            
            if (!empty($filters['status']) && is_numeric($filters['status'])) {
                $status = (int)$filters['status'];
                $where_conditions[] = "i.status = $status";
            }
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        $query_parts = $this->buildInventoryBaseQuery(false);
        $query =  "{$query_parts['select']} 
                   {$query_parts['from_joins']}
                   $where_clause
                   ORDER BY i.id DESC 
                   LIMIT $offset, $per_page";
        
        //echo $query; exit;
        $res = @mysqli_query($this->connection, $query);
        if (!$res) { 
            $error = mysqli_error($this->connection);
            logSqlError($error, $query, 'inventory-getleads', true); 
            return ["count" => 0, "list" => [], "current_page" => $current_page, "per_page" => $per_page]; 
        }
        
        // Process results exactly like getLead
        while ($row = mysqli_fetch_assoc($res)) {
            // Hide registration fields if vehicle is unregistered
            if (isset($row['reg_type']) && $row['reg_type'] == '1') { // '1' = 'Unregistered'
                $row['reg_num'] = '';
                $row['reg_date'] = '';
            }
            
            // Store numeric ID and convert main id to encrypted (same as getLead)
            if (isset($row['id']) && !empty($row['id']) && is_numeric($row['id'])) {
                $numeric_id = $row['id'];
                $row['numeric_id'] = $numeric_id;
                $row['formatted_id'] = "INV{$numeric_id}";
                
                // Replace main id with encrypted version
                try {
                    $encrypted_id = data_encrypt($numeric_id);
                    if (!empty($encrypted_id)) {
                        $row['id'] = $encrypted_id;
                    } else {
                        $row['id'] = (string)$numeric_id;
                    }
                } catch (Exception $e) {
                    $row['id'] = (string)$numeric_id;
                }
            }
            $inventory_ids[] = $numeric_id; // Collect inventory IDs for image fetching
            $leads[] = $row;
        }
        
        // Fetch and map images for all leads (like purchase-master does)
        if (!empty($inventory_ids)) {
            $lead_images = $this->getLeadImages($inventory_ids); 
            // Map images & documents for each lead
            $leadCount = count($leads);
            for ($i = 0; $i < $leadCount; $i++) {
                $lid = $leads[$i]['numeric_id']; // Use numeric ID for image lookup
                $raw = $lead_images[$lid] ?? [];
                $leads[$i]['images'] = $this->mapLeadImagesLeads($raw);
                $leads[$i]['docs'] = $this->processDocuments($leads[$i]);
            }
        }
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM inventory i $where_clause";
        $count_res = @mysqli_query($this->connection, $count_query);
        $total_count = 0;
        if ($count_res) {
            $count_row = mysqli_fetch_assoc($count_res);
            $total_count = (int)$count_row['total'];
        }
        
        // Get status counts for menu 
        $status_count_where = ["i.dealer = $dealer_id"];
        
      
        if (!empty($userIds)) {
            $inUsers = implode(',', $userIds);
            $status_count_where[] = "i.user IN ($inUsers)";
        }
        
      
        if (!empty($executive_condition)) {
            $status_count_where[] = ltrim($executive_condition, ' AND');
        }
        
    
        if (!empty($filters)) {
            if (!empty($filters['id'])) {
                // Handle flexible ID search - both exact and partial matching
                $id_value = trim($filters['id']);
                
                // Build flexible search conditions for ID
                $id_conditions = [];
                
                // 1. Always check for exact numeric match (like "9" matches id=9)
                if (is_numeric($id_value)) {
                    $numeric_id = (int)$id_value;
                    $id_conditions[] = "i.id = $numeric_id";
                }
                
                // 2. Check if it matches full INV pattern (like "INV9")
                if (preg_match('/^INV(\d+)$/i', $id_value, $matches)) {
                    $numeric_id = (int)$matches[1];
                    $id_conditions[] = "i.id = $numeric_id";
                }
                
                // 3. Always add partial text search for cases like "I", "IN", "V9", etc.
                $search_term = mysqli_real_escape_string($this->connection, strtoupper($id_value));
                $id_conditions[] = "UPPER(CONCAT('INV', i.id)) LIKE '%$search_term%'";
                
                // Use OR condition for ID matching (any of the conditions can match)
                if (!empty($id_conditions)) {
                    $status_count_where[] = "(" . implode(" OR ", $id_conditions) . ")";
                }
            }
            
            if (!empty($filters['reg_num'])) {
                $reg_num = mysqli_real_escape_string($this->connection, $filters['reg_num']);
                $status_count_where[] = "i.reg_num LIKE '%$reg_num%'";
            }
            
            if (!empty($filters['chassis'])) {
                $chassis = mysqli_real_escape_string($this->connection, $filters['chassis']);
                $status_count_where[] = "UPPER(i.chassis) LIKE UPPER('%$chassis%')";
            }
            
            if (!empty($filters['make']) && is_numeric($filters['make'])) {
                $make = (int)$filters['make'];
                $status_count_where[] = "i.make = $make";
            }
            
            if (!empty($filters['model']) && is_numeric($filters['model'])) {
                $model = (int)$filters['model'];
                $status_count_where[] = "i.model = $model";
            }
            
            // NOTE: We deliberately exclude status filter here so counts show for all statuses
        }
        
        $status_count_where_clause = "WHERE " . implode(" AND ", $status_count_where);
        $status_counts_query = "SELECT i.status, COUNT(*) as count FROM inventory i $status_count_where_clause GROUP BY i.status";
        $status_counts_res = @mysqli_query($this->connection, $status_counts_query);
        $status_counts = [];
        
        if ($status_counts_res) {
            while ($status_row = mysqli_fetch_assoc($status_counts_res)) {
                $status_counts[$status_row['status']] = (int)$status_row['count'];
            }
        }
        
        // Calculate pagination
        $total_pages = $per_page > 0 ? (int)ceil($total_count / $per_page) : 1;
        $start_count = $total_count > 0 ? (($current_page - 1) * $per_page) + 1 : 0;
        $end_count = min($current_page * $per_page, $total_count);
        
        // Calculate total count for "All" tab by summing all individual status counts
        $all_count = array_sum($status_counts);

        $menu = [
            "all" => [
                "status_id" => "all",
                "label" => "All",
                // mark All active when no status filter or when status === 'all'
                'is_active' => (empty($filters['status']) || (isset($filters['status']) && (string)$filters['status'] === 'all')) ? 'y' : '',
                "count" => $all_count
            ]
        ];
        
        // Get status list with proper hyphenated keys
        $status_list = $this->getStatuses();
        foreach ($status_list as $key => $status) {
            // Preserve the original key (which already has hyphens)
            $status['count'] = isset($status_counts[$status['status_id']])
                ? $status_counts[$status['status_id']]
                : 0;

            // set is_active when filters['status'] matches this status id
            $status['is_active'] = (isset($filters['status']) && (string)$filters['status'] === (string)$status['status_id']) ? 'y' : '';

            // Use the same key as in $status_list to maintain consistency
            $menu[$key] = $status;
        }
        
        return [
            "pagination" => [
                "total" => $total_count,
                "pages" => $total_pages,
                "per_page" => $per_page,
                "current_page" => $current_page,
                "start_count" => $start_count,
                "end_count" => $end_count
            ],
            "menu" => $menu,
            "list" => $leads
        ];
    }

    public function updateLead($request, $stockId)
    {
        //echo '<pre>'; print_r($request); echo '</pre>'; 
        if( $stockId >0 )
        {
            $lead_row = $this->leadDetails($stockId);
            $listing_price = !empty($request['listing_price'])?mysqli_real_escape_string($this->connection, $request['listing_price']):0;
            $mileage = !empty($request['mileage'])?mysqli_real_escape_string($this->connection, $request['mileage']):0;
            $remarks = !empty($request['remarks'])?mysqli_real_escape_string($this->connection, $request['remarks']):0;
            $query = "UPDATE inventory SET 
                    listing_price = '".$listing_price."',
                    mileage = '".$mileage."',
                    remarks = '".$remarks."'
                    WHERE id = $stockId";
            //echo $query; exit;
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'inventory-updatelead', true);
                return false;
            }
            if(mysqli_affected_rows($this->connection) > 0) {           
                logTableInsertion('inventory', $stockId);
                if( $lead_row['status'] == 1 ){
                    $lead_history['id'] = $stockId;
                    $lead_history['status'] = 1;
                    $lead_history['listing_price'] = $listing_price;
                    $lead_history['action'] = "The Listing Price is updated.";
                    $lead_history['created'] = date('Y-m-d H:i:s');
                    $lead_history['created_by'] = $this->login_user_id;
                    $this->saveHistory($lead_history);     
                }
                return true;
            }

            return true;
        }
        return false;       
    }

    public function updateExecutive($request, $leadId)
    {
        $lead_id = (int) mysqli_real_escape_string($this->connection, $leadId);   
        $branch_id = isset($request['branch']) ? (int) mysqli_real_escape_string($this->connection, $request['branch']) : 0;
        $exec_id = isset($request['executive']) ? (int) mysqli_real_escape_string($this->connection, $request['executive']) : 0;

        // Validate executive before assignment
        $validation = validateExecutiveActive($exec_id, $this->connection);
        if (!$validation['valid']) {
            return ['status' => false, 'message' => $validation['message']];
        }

        if ($lead_id > 0) // Only require valid lead ID
        {
            $query = "UPDATE inventory 
                    SET executive=$exec_id, 
                        branch=$branch_id, 
                        updated_on='" . date('Y-m-d H:i:s') . "', 
                        updated_by=" . (int)$this->loggin_user_id . "
                    WHERE id = $lead_id";       
            $res = mysqli_query($this->connection, $query); 
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'inventory-assignExecutive', true);
            }

            return mysqli_affected_rows($this->connection) > 0;
        }
        return false;
    }

    // export functionality not in use
      public function exportInventory(){
        $main_headers = [
            ['name' => 'Inventory Info', 'colspan' => 2],
            ['name' => 'Vehicle Details', 'colspan' => 12],
            ['name' => 'Registration Details', 'colspan' => 4],
            ['name' => 'Other Details', 'colspan' => 6],
        ];

        $headers = [
            ['name' => 'ID', 'type' => 'number', 'value' => 'formatted_id'],
            ['name' => 'Status', 'type' => 'string', 'value' => 'status_name'],
            ['name' => 'Car Type', 'type' => 'string', 'value' => 'car_type', 'config' => 'car_type'],
            ['name' => 'Make', 'type' => 'string', 'value' => 'make_name'],
            ['name' => 'Model', 'type' => 'string', 'value' => 'model_name'],
            ['name' => 'Variant', 'type' => 'string', 'value' => 'variant_name'],
            ['name' => 'Color', 'type' => 'string', 'value' => 'color', 'config' => 'colors'],
            ['name' => 'Manufacturing Year', 'type' => 'number', 'value' => 'mfg_year'],
            ['name' => 'Manufacturing Month', 'type' => 'number', 'value' => 'mfg_month', 'config' => 'months'],
            ['name' => 'Mileage', 'type' => 'number', 'value' => 'mileage'],
            ['name' => 'Owners', 'type' => 'string', 'value' => 'owners', 'config' => 'owners'],
            ['name' => 'Transmission', 'type' => 'string', 'value' => 'transmission', 'config' => 'transmission'],
            ['name' => 'Fuel Type', 'type' => 'string', 'value' => 'fuel', 'config' => 'fuel'],
            ['name' => 'Chassis Number', 'type' => 'string', 'value' => 'chassis'],
            ['name' => 'Registration Type', 'type' => 'string', 'value' => 'reg_type', 'config' => 'reg_type'],
            ['name' => 'Registration Number', 'type' => 'string', 'value' => 'reg_num'],
            ['name' => 'Registration Date', 'type' => 'datetime', 'value' => 'reg_date'],
            ['name' => 'Hypothecation', 'type' => 'string', 'value' => 'hypothecation', 'config' => 'hypothecation'],
            ['name' => 'Insurance Type', 'type' => 'string', 'value' => 'insurance_type', 'config' => 'insurance_type'],
            ['name' => 'Insurance Expiry Date', 'type' => 'datetime', 'value' => 'insurance_exp_date'],
            ['name' => 'User', 'type' => 'string', 'value' => 'user_name'],
            ['name' => 'Executive', 'type' => 'string', 'value' => 'executive_name'],
            ['name' => 'Added On', 'type' => 'datetime', 'value' => 'added_on'],
            ['name' => 'Updated On', 'type' => 'datetime', 'value' => 'updated_on'],
          
        ];

        // Build WHERE clause with proper security
        $where_conditions = ["i.dealer = " . (int)$this->dealer_id];
        
        // Add executive condition if not dealer admin
        $executive_condition = $this->executiveCondition();
        if (!empty($executive_condition)) {
            $where_conditions[] = substr($executive_condition, 5); // Remove ' AND ' prefix
        }
        
        // Add branch scoping
        $branches = getUsersByBranchIds($this->branch_id);
        $userIds = [];
        if (!empty($branches)) {
            foreach ($branches as $branch) {
                if (!empty($branch['executives'])) {
                    foreach ($branch['executives'] as $exec) {
                        $userIds[] = intval($exec['id']);
                    }
                }
            }
        }
        $userIds = array_values(array_unique(array_filter($userIds, function($v){ return $v > 0; })));
        if (!empty($userIds)) {
            $inUsers = implode(',', $userIds);
            $where_conditions[] = "i.user IN ($inUsers)";
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);

        $query = "SELECT 
            i.id,
            CONCAT('INV', i.id) AS formatted_id,
            i.status,
            i.car_type,
            i.make,
            i.model,
            i.variant,
            i.color,
            i.mfg_year,
            i.mfg_month,
            i.mileage,
            i.owners,
            i.reg_type,
            i.reg_num,
            i.reg_date,
            i.chassis,
            i.transmission,
            i.fuel,
            i.fuel_end,
            i.hypothecation,
            i.insurance_type,
            i.insurance_exp_date,
            i.added_on,
            i.updated_on,
            IFNULL(d.name, '') AS dealer_name,
            IFNULL(u.name, '') AS user_name,
            IFNULL(e.name, '') AS executive_name,
            IFNULL(v.make, '') AS make_name,
            IFNULL(v.model, '') AS model_name,
            IFNULL(v.variant, '') AS variant_name,
            " . buildSqlCaseFromConfig('i.status', $this->commonConfig['inventory_lead_statuses']) . " AS status_name
            FROM inventory i
            LEFT JOIN dealer_groups d ON i.dealer = d.id
            LEFT JOIN users u ON i.user = u.id
            LEFT JOIN users e ON i.executive = e.id
            LEFT JOIN master_variants_new v ON i.variant = v.id
            $where_clause
            ORDER BY i.id DESC";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'inventory-export', true);
            return false;
        }
        
        $data = [];
        $config = $this->commonConfig;
        if (mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                // Handle empty registration fields for unregistered vehicles
                if (isset($row['reg_type']) && $row['reg_type'] == '2') {
                    $row['reg_num'] = '';
                    $row['reg_date'] = '';
                }
                
                $rowData = [];
                foreach ($headers as $header) {
                    $col = $header['value'];
                    if (isset($header['config']) && isset($config[$header['config']])) {
                        $val = $row[$col] ?? '';
                        $rowData[] = $config[$header['config']][$val] ?? '';
                    } else {
                        $rowData[] = $row[$col] ?? '';
                    }
                }
                $data[] = $rowData;
            }
        }
        
        $filename = "inventory_".date('Ymd_His').".xlsx";
        $url = exportExcelFile($headers, $data, $filename, $main_headers);
        return ['file_url' => $url];
    }
   

    public function updateInventoryStatus($inventory_id, $new_status, $action_type = 'status_update', $notes = '', $additional_remarks = '')
    {
        global $connection;
        
        $inventory_id = (int)$inventory_id;
        $new_status = (int)$new_status;
        
        if ($inventory_id <= 0 || $new_status < 0) {
            return false;
        }

        // Get current status
        $current_query = "SELECT status FROM inventory WHERE id = ? AND dealer = ?";
        $current_stmt = mysqli_prepare($connection, $current_query);
        mysqli_stmt_bind_param($current_stmt, 'ii', $inventory_id, $this->dealer_id);
        mysqli_stmt_execute($current_stmt);
        $current_result = mysqli_stmt_get_result($current_stmt);
        $current_data = mysqli_fetch_assoc($current_result);
        $old_status = $current_data['status'] ?? 0;

        // Start transaction
        mysqli_begin_transaction($connection);

        try {
            // Update inventory status
            $update_query = "UPDATE inventory SET 
                           status = ?,
                           updated_by = ?,
                           updated_on = NOW()
                           WHERE id = ? AND dealer = ?";
            
            $stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($stmt, 'iiii', $new_status, $this->login_user_id, $inventory_id, $this->dealer_id);
            $result = mysqli_stmt_execute($stmt);

            if (!$result || mysqli_stmt_affected_rows($stmt) == 0) {
                mysqli_rollback($connection);
                return false;
            }
            
        } catch (Exception $e) {
            mysqli_rollback($connection);
            return false;
        }
    }
   

    /**
     * Format action type for user-friendly display
     */
    private function formatActionType($action_type)
    {
        $actionMap = [
            'status_update' => 'Status Updated',
            'certification_approved' => 'Certification Approved',
            'certification_rejected' => 'Certification Rejected',
            'approval_requested' => 'Approval Requested',
            'approval_granted' => 'Approval Granted',
            'approval_rejected' => 'Approval Rejected',
            'ready_for_sale' => 'Ready for Sale',
            'booked' => 'Vehicle Booked',
            'refurbishment_started' => 'Refurbishment Started',
            'refurbishment_completed' => 'Refurbishment Completed'
        ];
        
        return $actionMap[$action_type] ?? ucwords(str_replace('_', ' ', $action_type));
    }

    /**
     * Format datetime for display
     */
    private function formatDateTime($datetime)
    {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return '';
        }
        return date('d M Y, h:i A', strtotime($datetime));
    }

    /**
   
     * Get inventory status mappings for sidebar filtering 
     */
    public function getStatuses()
    {
        static $cached_statuses = null;
        
        if ($cached_statuses !== null) {
            return $cached_statuses;
        }
        
        $statuses = [];
        
        // Use the inventory_lead_statuses from common_config.php
        foreach ($this->commonConfig['inventory_lead_statuses'] as $status_id => $label) {
            $key = strtolower(str_replace(' ', '-', $label));
            $statuses[$key] = [
                'status_id' => $status_id,
                'label' => $label,
                'count' => 0
            ];
        }
        
        $cached_statuses = $statuses;
        return $statuses;
    }
    public function LeadEvaluation($leadId)
    {
        $evaluation = [];
        if( $leadId>0 )
        {
            $lead_id = (int) mysqli_real_escape_string($this->connection, $leadId);
            $query = "SELECT * FROM inventory_evaluation WHERE inventory_id =  $lead_id";
            $res = mysqli_query($this->connection,$query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'Inventory-get-evaluation');
                return false;
            }        
            if( mysqli_num_rows($res)>0 )
            {
                while( $row = mysqli_fetch_assoc($res))
                {
                    $evaluation['mapped_template'][] = $row['template_id'];
                    $evaluation[$row['template_id']] = json_decode($row['checklist'], true);                               
                }
            }
            else{
                
                $inv_query = "SELECT id,user,dealer,selllead_id FROM inventory WHERE id =  $lead_id";
                $inv_res = mysqli_query($this->connection,$inv_query);
                $inv_row = mysqli_fetch_assoc($inv_res);
                if( $inv_row['selllead_id'] >0 )
                {
                    $lead_que = mysqli_prepare($this->connection, "SELECT * FROM sellleads_evaluation WHERE selllead_id = ? AND status = 1");                
                    if ($lead_que)
                    {
                        mysqli_stmt_bind_param($lead_que, "i", $inv_row['selllead_id']);
                        mysqli_stmt_execute($lead_que);
                        $lead_res = mysqli_stmt_get_result($lead_que);
                        
                        if ($lead_res && mysqli_num_rows($lead_res) > 0)
                        {                                        
                            while( $lead_row = mysqli_fetch_assoc($lead_res))
                            {
                                $evaluation['mapped_template'][] = $lead_row['template_id'];
                                $evaluation[$lead_row['template_id']] = json_decode($lead_row['checklist'], true);                               
                            }
                        }                
                    }
                }
            }
        }
        return $evaluation;
    }
    public function getRefurbishmentData($leadId)
    {        
        static $checklistStructure = null;
        if( empty($leadId) ){
            return [];
        }
        $lead_data = $this->LeadEvaluation($leadId);
        //echo '<pre>'; print_r($lead_data); echo '</pre>'; exit;

        // Use static cache for template and checklist data to avoid repeated queries      
        // Get checklist structure with items (cached)
        if ($checklistStructure === null) 
        {
            $checklistStructure = [];
            $items = [];
            
            // Get all checklist items in a single query
            $item_query = "SELECT a.*,b.template_id FROM evaluation_checklist_items_new as a left join evaluation_checklist_new as b on(a.checklist_id = b.id) WHERE a.active = 'y'";
            $item_res = mysqli_query($this->connection, $item_query);
            if (!$item_res) {
                logSqlError(mysqli_error($this->connection), $item_query, 'sellleads-getEvaluation', true);
            } else {
                while ($item_row = mysqli_fetch_assoc($item_res)) { 
                    $options = $sub_options = [];
                    if( !empty($item_row['options']) ){
                        $options =  json_decode($item_row['options'], true);
                        $options['isRequired'] = ($item_row['required'] == 'yes') ? true : false;                        
                        $options['fieldKey'] = "imgData_".$item_row['id'];
                    }
                    if( !empty($item_row['sub_options']) ){
                        $sub_options =  json_decode($item_row['sub_options'], true);
                        foreach($options['fieldOptionIds'] as $k => $v){
                            $sub_options[$v['label']][0]['isRequired'] = ($item_row['required'] == 'yes') ? true : false;                            
                            $sub_options[$v['label']][0]['fieldKey'] = "imgSubData_".$v['label'];
                        }
                    }
                    
                    $item_data = [];                   
                    $item_data['inputType']     = "ref_img"; 
                    $item_data['fieldLabel']    = mb_convert_encoding($item_row['item_name'], 'UTF-8', 'UTF-8'); 
                    $item_data['fieldKey']      = $item_row['id'];
                    
                    $item_data['imgPart'] = [
                        "imgId"=> "",
                        "imgSno"=> $item_row['id'],
                        "imgName"=> "",
                        "imgMand"=> "",
                        "imgOrientation"=>"L",
                        "imgAction"=> "add",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgRemark"=> "",
                        "imgRefCost"=> "",
                    ];
                    if( !empty($lead_data[$item_row['template_id']][$item_row['checklist_id']][$item_row['id']]) ){
                        $item_data['imgPart'] = $lead_data[$item_row['template_id']][$item_row['checklist_id']][$item_row['id']];
                    }                    
                    $item_data['fields'] = [];
                    if(!empty($options)){
                        array_push($item_data['fields'],$options);
                    }
                    if(!empty($sub_options)){
                        $item_data['fields'][0]['conditionalFields']  = $sub_options;                       
                    }

                    $refurb_arr = [
                        "inputType"=>"numeric",
                        "fieldLabel"=>"Refurbishment Cost",
                        "fieldKey"=>"imgRefCost_".$item_row['id'],
                        "isRequired"=>false,
                        "maxLength" => 9,                       
                        "defaultInputValue"=>"",
                        "validation"=> [    
                            "validationPattern" => "^[0-9]+$", 
                            "errorMessageRequired" => "Refurbishment Cost is required",
                            "errorMessageInvalid" => "Enter Valid Refurbishment Cost"                           
                        ]
                    ];
                    array_push($item_data['fields'],$refurb_arr);

                    $remarks_arr = [
                        "inputType"=>"alphanumeric",
                        "fieldLabel"=>"Remarks",
                        "fieldKey"=>"imgRemark_".$item_row['id'],
                        "isRequired"=>($item_row['required'] == 'yes') ? true : false,    
                        "maxLength" => 250,                    
                        "defaultInputValue"=>"",
                        "validation"=>[
                             "validationPattern" => "^[a-zA-Z0-9.,@\\-\\/ ]+$",
                             "errorMessageRequired" => "Remarks is required",
                             "errorMessageInvalid" => "Enter Valid Remarks"
                        ]
                    ];  
                    array_push($item_data['fields'],$remarks_arr);                                                                      
                    $items[$item_row['checklist_id']][] = $item_data;
                }
            }
            
            //echo '<pre>'; print_r($items);echo '</pre>'; exit;
            // Get checklist sections
            $query = "SELECT * FROM evaluation_checklist_new WHERE active = 'y' ORDER BY sort_order ASC";
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-getEvaluation', true);
            } else {
                while ($row = mysqli_fetch_assoc($res)) {
                    $checklistStructure[$row['template_id']]['fields'][] = [

                        "inputType"=>"ref_expand",
                        "fieldKey" => $row['id'],
                        "fieldLabel" => $row['checklist_title'],
                        "sort_order" => $row['sort_order'],                   
                        "fields" => isset($items[$row['id']]) ? $items[$row['id']] : []
                    ];
                }
            }
        }
        $evaluations['checklist'] = $checklistStructure;
        return $evaluations;
    }   

    public function leadDetails($leadId)
    {
        $lead_row = [];
        if( $leadId>0 )
        {
            $lead_que = "SELECT id,make,status,mileage,listing_price,remarks,certified_by,certified_date from inventory WHERE id = $leadId";
            $lead_res = mysqli_query($this->connection,$lead_que);
            $lead_row = mysqli_fetch_assoc($lead_res);     
            return $lead_row;
        }
        return $lead_row;
    }
    // Add and Edit Inventory evaluation
    public function saveEvaluation($request)
    {
        $template_id = (int) $request['template_id'];        
        $checklist = $request['checklist'];

        $dec_lead_id = data_decrypt($request['id']);
        if (empty($dec_lead_id) || !is_numeric($dec_lead_id)) {
            logSqlError('Invalid decrypted id', '', 'inventory-saveEvaluation-decrypt', true);
            return false;
        }

        $leadId = (int) mysqli_real_escape_string($this->connection, $dec_lead_id);
        $lead_row = $this->leadDetails($leadId);

        // Check if record exists
        $cnt_query = "SELECT id FROM inventory_evaluation WHERE inventory_id = $leadId AND template_id = $template_id AND status = 1";
        $cnt_res = mysqli_query($this->connection, $cnt_query);

        if (!$cnt_res) {
            logSqlError(mysqli_error($this->connection), $cnt_query, 'inventory-saveEvaluation-check', true);
            return false;
        }

        $lead_history = []; 
        $lead_history['id'] = $leadId;
        $lead_history['listing_price'] = $lead_row['listing_price'];
        $lead_history['refurb_type'] = $template_id;
        $lead_history['refurb_date'] = date('Y-m-d');
        $lead_history['remarks'] = $lead_row['remarks'];

        if (mysqli_num_rows($cnt_res) > 0) {
            // UPDATION
            $up_query = "UPDATE inventory_evaluation SET 
                checklist = '" . mysqli_real_escape_string($this->connection, json_encode($checklist)) . "' 
                WHERE inventory_id = $leadId AND template_id = $template_id AND status = 1";
            $update_res = mysqli_query($this->connection, $up_query);
            if (!$update_res) {
                logSqlError(mysqli_error($this->connection), $up_query, 'inventory-saveEvaluation-update', true);
                return false;
            }
            $lead_history['action'] = "Refurbishment is updated";            
        } else {
            // INSERTION
            $query = "INSERT INTO inventory_evaluation SET 
                inventory_id = $leadId,
                template_id = $template_id,
                checklist = '" . mysqli_real_escape_string($this->connection, json_encode($checklist)) . "',
                status = 1";
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'inventory-saveEvaluation-insert', true);
                return false;
            }
            $lead_history['action'] = "Refurbishment is submitted";
        }

        // Check if the make is JLR make (or) Non-JLR make     
        $make = (int)$lead_row['make'] ?? 0;
        $sql_query = "SELECT is_brand_group FROM master_makes WHERE id = $make";
        $result = mysqli_query($this->connection,$sql_query);
        if (!$sql_query) {
            logSqlError(mysqli_error($this->connection), $sql_query, 'inventory-saveEvaluation-status', true);
            return false;
        }

        $final_result = mysqli_fetch_assoc($result);
        $vehicle_status = 0;
        
        if($final_result['is_brand_group'] == 'y')
        {
           $vehicle_status = $this->commonConfig['common_statuses']['inventory']['certification'];
        }
        else
        {
           $vehicle_status = $this->commonConfig['common_statuses']['inventory']['ready_for_sale'];
        }
             
        // Update inventory status
        $inv_query = "UPDATE inventory SET 
                         status = $vehicle_status
                         WHERE id = $leadId";
        $inv_res = mysqli_query($this->connection, $inv_query);
        if (!$inv_res) {
            logSqlError(mysqli_error($this->connection), $inv_query, 'inventory-saveEvaluation-status', true);
            return false;
        }
        $lead_history['status'] = $vehicle_status;
        if( $lead_row['status'] != $vehicle_status ){
            $this->saveHistory($lead_history);        
        }

        logTableInsertion('inventory', $dec_lead_id);
        return true;
    }

    public function saveHistory($request)
    {
        $inventory_id = $request['id']??0;
        if( $inventory_id >0 && !empty($request)>0 )
        {
            $status = !empty($request['status'])?mysqli_real_escape_string($this->connection, $request['status']):'';
            $action = !empty($request['action'])?mysqli_real_escape_string($this->connection, $request['action']):'';
            $listing_price = !empty($request['listing_price'])?mysqli_real_escape_string($this->connection, $request['listing_price']):0;
            $refurb_type = !empty($request['refurb_type'])?mysqli_real_escape_string($this->connection, $request['refurb_type']):'';
            $refurb_date = !empty($request['refurb_date'])?mysqli_real_escape_string($this->connection, $request['refurb_date']):'';
            $certification_date = !empty($request['certification_date'])?mysqli_real_escape_string($this->connection, $request['certification_date']):'';
            $certified_by = !empty($request['certified_by'])?mysqli_real_escape_string($this->connection, $request['certified_by']):'';
            $booked_date = !empty($request['booked_date'])?mysqli_real_escape_string($this->connection, $request['booked_date']):'';
            $sold_date = !empty($request['sold_date'])?mysqli_real_escape_string($this->connection, $request['sold_date']):'';
            $remarks = !empty($request['remarks'])?mysqli_real_escape_string($this->connection, $request['remarks']):'';
            $certification_remarks = !empty($request['certification_remarks'])?mysqli_real_escape_string($this->connection, $request['certification_remarks']):'';
            $created = date('Y-m-d H:i:s');
            $created_by = (int)$this->login_user_id;

            $query = "INSERT INTO inventory_history SET 
                    inventory_id = $inventory_id,
                    status = '".$status."',
                    action = '".$action."',
                    listing_price = '".$listing_price."',
                    refurb_type = '".$refurb_type."',
                    refurb_date = '".$refurb_date."',
                    certified_by = '".$certified_by."',
                    certification_date = '".$certification_date."',
                    booked_date = '".$booked_date."',
                    sold_date = '".$sold_date."',
                    remarks = '".$remarks."',
                    certification_remarks = '".$certification_remarks."',
                    created = '".$created."',
                    created_by = '".$created_by."'
            ";     
            //echo $query; exit;      
            $res = mysqli_query($this->connection,$query);
            if(!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'Inventory-Historysave', true);
                return false; 
            }
            $last_insert_id = mysqli_insert_id($this->connection);
            if( $last_insert_id>0 ){
                return true;
            }
        }       
        return false;
    }
    public function getStockHistory($leadId)
    {
        $history = [];
        if( $leadId>0 ){
            $query = "SELECT 
                    a.inventory_id as id,                    
                    a.status,
                    ".buildSqlCaseFromConfig('a.status', $this->commonConfig['inventory_lead_statuses'])."  AS status_name,
                    a.action as action_type,
                    IFNULL(a.listing_price,'') as listing_price,
                    IF(a.refurb_date = '0000-00-00','',a.refurb_date) as refurb_date,                    
                    ".buildSqlCaseFromConfig('a.refurb_type', $this->commonConfig['evaluation_types'])."  AS refurb_name,
                    IF(a.certification_date = '0000-00-00','',a.certification_date) as certification_date,                    
                    IFNULL(u.name, '') AS certified_by,
                    IF(a.booked_date = '0000-00-00','',a.booked_date) as booked_date,
                    IF(a.sold_date = '0000-00-00','',a.sold_date) as sold_date,
                    a.created,
                    IFNULL(us.name, '') AS created_by,
                    a.remarks,
                    a.certification_remarks
                    FROM inventory_history as a 
                    LEFT JOIN users u ON a.certified_by = u.id
                    LEFT JOIN users us ON a.created_by = us.id                    
                    WHERE a.inventory_id = $leadId ORDER BY a.id DESC";
            //echo $query; exit;
            $res = mysqli_query($this->connection,$query);            
            if(!$res)
            {
                logSqlError(mysqli_error($this->connection), $query, 'getStockHistory');
                return false;
            }
            if( mysqli_num_rows($res) >0 )
            {
                while( $row = mysqli_fetch_assoc($res) )
                {
                    $history[] = $row;
                }
            }
        }
        return $history;
    }
    public function calculateCarAge($reg_date) {
        if (empty($reg_date)) {
            return [
                'calculatedAge' => null,
                'meetsAgeCriteria' => false
            ];
        }       
        $baseDate = new DateTime($reg_date);
        $currentDate = new DateTime();
       
        $interval = $currentDate->diff($baseDate);
        $ageInYears = $interval->days / 365.25; 
       
        $calculatedAge = round($ageInYears, 1);
       
        $meetsAgeCriteria = $ageInYears < $this->certification_carage;        
        return [
            'car_age' => $calculatedAge,
            'age_criteria' => $meetsAgeCriteria
        ];
    }

    public function updateCertification($request,$stockId)
    {
        //echo $stockId; 
        //echo '<pre>'; print_r($request); echo '</pre>';
        if( $stockId >0 )
        {
            $cert_checklist = [];
            for( $i=1;$i<=6;$i++)
            {
                if(!empty($request['question'.$i])){
                    $cert_checklist["question".$i] = $request['question'.$i];
                }
            }
           
            $certified_date = !empty($request['certified_date'])?mysqli_real_escape_string($this->connection,date('Y-m-d',strtotime($request['certified_date']))):'';
            $certified_by = !empty($request['certified_by'])?mysqli_real_escape_string($this->connection, $request['certified_by']):'';
            $certification_type = !empty($request['certification_type'])?mysqli_real_escape_string($this->connection, $request['certification_type']):'';
            $certification_documents = !empty($request['certification_documents'])?mysqli_real_escape_string($this->connection, $request['certification_documents']):'';
            $certified_checklist = !empty($cert_checklist)?json_encode($cert_checklist):'';

            //Status =3 Need for certification Approval
            $status = $this->commonConfig['common_statuses']['inventory']['need_certification_approval'];
            $query = "UPDATE inventory SET 
                    certified_date = '".$certified_date."',
                    certified_by = '".$certified_by."',
                    certification_checklist = '".$certified_checklist."',
                    certification_type = '".$certification_type."',
                    certification_documents = '".$certification_documents."',
                    status = '".$status."',
                    updated_on = '".date('Y-m-d H:i:s')."'
                    WHERE id = $stockId";
            //echo $query; exit;
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'inventory-certificationupdate', true);
                return false;
            }
            if(mysqli_affected_rows($this->connection) > 0) {           
                $lead_history = [];
                $lead_history['id'] = $stockId;
                $lead_history['status'] = $status;
                $lead_history['action'] = "Send request for certification approval";
                $lead_history['certification_date'] = $certified_date;
                $lead_history['certified_by'] = $certified_by;
                $lead_history['created'] = date('Y-m-d H:i:s');
                $lead_history['created_by'] = $this->login_user_id;
                $this->saveHistory($lead_history); 

                logTableInsertion('inventory', $stockId);
                
                return true;
            }
            return true;
        }
        return false;  
    }
    public function updateCertStatus($request,$stockId)
    {
         if( $stockId >0 )
        {
            
            $certification_status = !empty($request['certification_status'])?mysqli_real_escape_string($this->connection, $request['certification_status']):'';
            $certification_remarks = !empty($request['certification_remarks'])?mysqli_real_escape_string($this->connection, $request['certification_remarks']):'';
            $status = $this->commonConfig['common_statuses']['inventory']['ready_for_sale'];
            if( $certification_status == 1 ) //Approved status=4 Ready of Sale
            {
                $query = "UPDATE inventory SET 
                        certification_status = '".$certification_status."',
                        certification_remarks = '".$certification_remarks."',
                        status = '".$status."',
                        updated_on = '".date('Y-m-d H:i:s')."'
                        WHERE id = $stockId";
                $res = mysqli_query($this->connection, $query);
                if (!$res) {
                    logSqlError(mysqli_error($this->connection), $query, 'inventory-certificationupdate', true);
                    return false;
                }
                if(mysqli_affected_rows($this->connection) > 0) {           
                    logTableInsertion('inventory', $stockId);

                    $lead_history['id'] = $stockId;
                    $lead_history['status'] = $status;
                    $lead_history['action'] = "The certification is approved";
                    $lead_history['certified_by'] = $this->login_user_id;
                    $lead_history['created'] = date('Y-m-d H:i:s');
                    $lead_history['created_by'] = $this->login_user_id;
                    $lead_history['certification_remarks'] = $certification_remarks;

                    $this->saveHistory($lead_history);     

                    return true;
                }
                return true;
            }
            if( $certification_status == 2 ) //Rejected status=2 Certification In Progress
            {
                $query = "UPDATE inventory SET 
                        certification_status = '".$certification_status."',
                        certification_remarks = '".$certification_remarks."',
                        status = '".$this->commonConfig['common_statuses']['inventory']['certification']."',
                        updated_on = '".date('Y-m-d H:i:s')."'
                        WHERE id = $stockId";
                $res = mysqli_query($this->connection, $query);
                if (!$res) {
                    logSqlError(mysqli_error($this->connection), $query, 'inventory-certificationupdate', true);
                    return false;
                }
                if(mysqli_affected_rows($this->connection) > 0) {           
                    logTableInsertion('inventory', $stockId);

                    $lead_history['id'] = $stockId;
                    $lead_history['status'] = $this->commonConfig['common_statuses']['inventory']['certification'];
                    $lead_history['action'] = "The certification is rejected.";
                    $lead_history['certified_by'] = $this->login_user_id;
                    $lead_history['created'] = date('Y-m-d H:i:s');
                    $lead_history['created_by'] = $this->login_user_id;
                    $lead_history['certification_remarks'] = $certification_remarks;
                    $this->saveHistory($lead_history);     

                    return true;
                }
                return true;
            }
        }
        return false;
    }

}

?>
