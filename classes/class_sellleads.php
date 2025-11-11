<?php
class Sellleads
{

    // Database & config
    public $connection;
    public $commonConfig;

    // Logged-in user/dealer/branch context
    public $logged_user_id;
    public $logged_dealer_id;
    public $logged_branch_id;
    public $logged_executive_id;

    // Lead properties (all table columns)
    public $id;
    public $dealer;
    public $branch;
    public $user;
    public $executive;
    public $title;
    public $first_name;
    public $last_name;
    public $contact_name;
    public $contact_method;
    public $mobile;
    public $email;
    public $car_type;
    public $park_and_sell;
    public $make;
    public $model;
    public $body_type;
    public $variant;
    public $mfg_year;
    public $mfg_month;
    public $reg_type;
    public $reg_date;
    public $reg_num;
    public $chassis;
    public $transmission;
    public $mileage;
    public $fuel;
    public $color; //  stores exterior_color ID from exterior_colors table
    public $interior_color;
    public $source;
    public $source_sub;
    public $source_other;
    public $status;
    public $sub_status;
    public $lead_classification;
    public $followup_date;
    public $price_customer;
    public $price_quote;
    public $price_expenses;
    public $price_margin;
    public $price_selling;
    public $price_agreed;
    public $price_indicative;
    public $token_amount;
    public $evaluation_type;
    public $evaluation_place;
    public $evaluation_done;
    public $evaluation_date;
    public $state;
    public $city;
    public $address;
    public $pin_code;
    public $hypothecation;
    public $hypothecation_by;
    public $owners;
    public $insurance_type;
    public $insurance_exp_date;
    public $file_doc1;
    public $file_doc2;
    public $is_exchange;
    public $purchased_date;
    public $created;
    public $updated;
    public $created_by;
    public $updated_by;
    public $remarks;
    public $is_jlr_vehicle;
    public $fuel_end;
    public $rc_pin_code;
    public $rc_state;
    public $rc_city;
    public $rc_address;
    public $bank_name;
    public $loan_paid_off;
    public $loan_amount;
    public $reason_for_selling;
    public $rs_subsection;
    public $rs_make;
    public $rs_model;
    public $rs_variant;
    public $rs_reason;
    public $rs_remarks;
    public $buying_horizon;
    public $budget;

    public function __construct($id = 0) {
        global $connection;
        $this->connection = $connection;
        $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        if ($id > 0) {
            $this->id = $id;
            // optionally: load lead data from DB into properties
        }
    }

    public function ownerCheck(int $leadId): bool
    {
        if ($leadId <= 0 || empty($this->logged_dealer_id)) {
            return false;
        }

        $leadId = (int)$leadId;
        $dealerId = (int)$this->logged_dealer_id;

        $userIds = [];

        // Collect user IDs based on branch
        $branches = getUsersByBranchIds($this->logged_branch_id);
        foreach ($branches as $branch) {
            foreach ($branch['executives'] ?? [] as $exec) {
                $userIds[] = (int)$exec['id'];
            }
        }
        $userIds = array_unique(array_filter($userIds));

        // Build query
        $query = "SELECT COUNT(*) AS cnt FROM sellleads AS a 
                  WHERE a.id = $leadId 
                  AND a.dealer = $dealerId";
        if (!empty($userIds)) {
            $inUsers = implode(',', $userIds);
            $query .= " AND a.user IN ($inUsers)";
        }

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-ownercheck', true);
            return false;
        }

        $row = mysqli_fetch_assoc($res);
        return ($row['cnt'] ?? 0) > 0;
    }



private function buildLeadBaseQuery($includeDetailFields = true)
{
    $select_fields = [
        'a.id',
        'a.dealer',
        'a.branch',
        'IFNULL(db.name, \'\') AS branch_name',
        'IFNULL(d.name, \'\') AS dealer_name',
        'a.user',
        'IFNULL(usrs.name, \'\') AS user_name',
        'a.executive',
        'IFNULL(exec_user.name, \'\') AS executive_name',
        'a.title',
        'a.first_name',
        'a.last_name',
        'a.contact_name',
        'a.contact_method',
        'a.mobile',
        'a.email',
        'a.make',
        'IFNULL(v.make, \'\') AS make_name',
        'a.model',
        'IFNULL(v.model, \'\') AS model_name',
        'a.variant',
        'IFNULL(v.variant, \'\') AS variant_name',
        'a.body_type',
        buildSqlCaseFromConfig('a.body_type', $this->commonConfig['body_types']) . ' AS body_type_name',
        'a.mfg_year',
        'a.mfg_month',
        buildSqlCaseFromConfig('a.mfg_month', $this->commonConfig['months']) . ' AS mfg_month_name',
        'a.reg_type',
        buildSqlCaseFromConfig('a.reg_type', $this->commonConfig['reg_type']) . ' AS reg_type_name',
        'a.reg_date',
        'a.reg_num',
        'a.chassis',
        'a.fuel',
        buildSqlCaseFromConfig('a.fuel', $this->commonConfig['fuel']) . ' AS fuel_name',
        'a.color',
        'IFNULL(ec.exterior_color, \'\') AS color_name',
        'IFNULL(ec.base_color, \'\') AS base_color_name',
        'a.interior_color',
        'IFNULL(ic.interior_color, \'\') AS interior_color_name',
        'a.source',
        'IFNULL(src.source, \'\') AS source_name',
        'a.source_sub',
        'IFNULL(srcs.sub_source, \'\') AS source_sub_name',
        'a.source_other',
        buildSqlCaseFromConfig('a.source_other', $this->commonConfig['source_other']) . ' AS source_other_name',
        'a.park_and_sell',
        buildSqlCaseFromConfig('a.park_and_sell', $this->commonConfig['active_type']) . ' AS park_and_sell_name',
        'a.status',
        buildSqlCaseFromConfig('a.status', $this->commonConfig['pm_status']) . ' AS status_name',
        'a.followup_date',
        'a.evaluation_done',
        'a.state',
        'a.city',
        'a.pin_code',
        'IFNULL(msa.cw_state, \'\') AS state_name',
        'IFNULL(msa.cw_city, \'\') AS city_name',
        'a.created',
        'a.updated',
        'a.lead_classification',
        'a.is_jlr_vehicle',
        'a.hypothecation',
        'a.bank_name',
        'a.fuel_end',
        'a.rc_address',
        'a.rc_pin_code',
        'a.rc_city',
        'a.rc_state',
        'a.loan_amount',
        'a.rs_remarks',
        'a.loan_paid_off',
        'a.reason_for_selling',
        buildSqlCaseFromConfig('a.reason_for_selling', $this->commonConfig['reason_for_selling']) . ' AS reason_for_selling_name',
        'a.rs_subsection',
        buildSqlCaseFromConfig('a.rs_subsection', $this->commonConfig['rs_subsection_options']) . ' AS rs_subsection_name',
        'a.rs_make',
        'IFNULL(rsv.make, \'\') AS rs_make_name',
        'a.rs_model',
        'IFNULL(rsv.model, \'\') AS rs_model_name',
        'a.rs_variant',
        'IFNULL(rsv.variant, \'\') AS rs_variant_name',
        'a.rs_reason',
        'a.buying_horizon',
        buildSqlCaseFromConfig('a.buying_horizon', $this->commonConfig['buying_horizon']) . ' AS buying_horizon_name',
        'a.budget',
         buildSqlCaseFromConfig('a.budget', $this->commonConfig['budget_range']) . ' AS budget_name',
        // Price fields 
        'a.price_customer',
        'a.price_quote',
        'a.price_indicative',
    ];
    

    if ($includeDetailFields) {
        $select_fields = array_merge($select_fields, [
            'a.mileage',
            'a.address',
            'a.customer_notes',
            'a.car_type',
            buildSqlCaseFromConfig('a.car_type', $this->commonConfig['car_type']) . ' AS car_type_name',
            'a.transmission',
            buildSqlCaseFromConfig('a.transmission', $this->commonConfig['transmission']) . ' AS transmission_name',
            'a.hypothecation_by',
            buildSqlCaseFromConfig('a.hypothecation', $this->commonConfig['hypothecation']) . ' AS hypothecation_name',
            'a.owners',
            buildSqlCaseFromConfig('a.owners', $this->commonConfig['owners']) . ' AS owners_name',
            'a.insurance_type',
            buildSqlCaseFromConfig('a.insurance_type', $this->commonConfig['insurance_type']) . ' AS insurance_type_name',
            'a.insurance_exp_date',
            'a.evaluation_date',
            'a.evaluation_place',
            buildSqlCaseFromConfig('a.evaluation_place', $this->commonConfig['pm_evaluation_place']) . ' AS evaluation_place_name',
            'a.evaluation_type',
            'a.evaluation_done',
            'a.remarks',
            'a.price_expenses',
            'a.price_margin',
            'a.price_selling',
            'a.price_agreed',
            'a.token_amount',
            'a.is_exchange',
            'a.purchased_date',
            'a.file_doc1',
            'a.file_doc2',
            'a.sub_status',
            buildSubStatusSqlCase('a.status', 'a.sub_status', $this->commonConfig['pm_sub_status']) . ' AS sub_status_name',
            'a.vahan_response',
            'a.vahan_fetched_at',
        ]);
    }

    // Auto-wrap fields with IFNULL() except CASE or existing IFNULL
    $select_fields = array_map(function($field) {
        if (
            strpos($field, 'IFNULL(') !== false ||
            strpos($field, 'CASE') !== false ||
            stripos($field, ' AS ') !== false
        ) {
            return $field;
        }

        // Extract clean alias name
        if (preg_match('/\ba\.(\w+)/', $field, $m)) {
            $alias = $m[1];
            return "IFNULL($field, '') AS $alias";
        }

        return $field;
    }, $select_fields);


    $joins = [
        'LEFT JOIN master_variants_new v ON a.variant = v.id',
        'LEFT JOIN master_variants_new rsv ON a.rs_variant = rsv.id',
        'LEFT JOIN (SELECT DISTINCT make_id, make FROM master_variants_new) vmk ON a.make = vmk.make_id',
        'LEFT JOIN (SELECT DISTINCT model_id, model FROM master_variants_new) vmd ON a.model = vmd.model_id',
        'LEFT JOIN master_sources src ON a.source = src.id',
        'LEFT JOIN master_sources_sub srcs ON a.source_sub = srcs.id',
        'LEFT JOIN master_states_areaslist msa ON a.pin_code = msa.cw_zip',
        'LEFT JOIN dealer_groups d ON a.dealer = d.id',
        'LEFT JOIN users usrs ON a.user = usrs.id',
        'LEFT JOIN users exec_user ON a.executive = exec_user.id',
        'LEFT JOIN dealer_branches db ON a.branch = db.id',
        'LEFT JOIN exterior_colors ec ON a.color = ec.id AND ec.active = \'y\'',
        'LEFT JOIN interior_colors ic ON a.interior_color = ic.id AND ic.active = \'y\''
    ];

    return [
        'select' => 'SELECT ' . implode(",\n            ", $select_fields),
        'from_joins' => 'FROM sellleads a ' . implode(' ', $joins)
    ];
}



    public function getLeadImages(array $leadIds): array
    {
        $leadImages = [];
        $leadIds = array_filter(array_map('intval', $leadIds), fn($id) => $id > 0);

        if (empty($leadIds)) return $leadImages;

        $moduleConfig = new moduleConfig();
        $configData = (array)($moduleConfig->getConfig('pm')['images'] ?? []);
        $imageKeys = array_keys($configData);

        if (empty($imageKeys)) return $leadImages; // Nothing to fetch

        $in = implode(',', $leadIds);

        // Use WHERE IN for selllead_id and filter by image_tag
        $tagsIn = "'" . implode("','", array_map('addslashes', $imageKeys)) . "'";

        $query = "
            SELECT selllead_id, id, image AS url, default_image, image_tag 
            FROM sellleads_images 
            WHERE selllead_id IN ($in) 
            AND status = 1
            AND image_tag IN ($tagsIn)
            ORDER BY selllead_id ASC, default_image DESC, id ASC
        ";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-getleadimages', true);
            return $leadImages;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $sid = (int)$row['selllead_id'];
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
        $configTemplate = (array)($moduleConfig->getConfig('pm')['images'] ?? []);

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


    private function getLeadDents($lead_id = 0)
    {
        $dents = [];
        $baseUrl = rtrim($this->commonConfig['document_base_url'] ?? '', '/').'/';

        if (empty($lead_id) || $lead_id <= 0) {
            return $dents;
        }
        $lead_id = (int)$lead_id;
        $query = "SELECT 
            id,
            xpos AS xPos,
            ypos AS yPos,
            width,
            height,
            imperfection_type AS imperfectionType,
            imperfection_impact AS imperfectionImpact,
            imperfection_part AS imperfectionPart,
            imperfection_position AS imperfectionPosition,
            remarks,
            image,
            status
        FROM dentmap_list
        WHERE selllead_id = $lead_id AND status = 'y'";
        $res = mysqli_query($this->connection, $query);

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $dents[] = [
                    'id' => $row['id'],
                    'xPos' => $row['xPos'],
                    'yPos' => $row['yPos'],
                    'width' => $row['width'],
                    'height' => $row['height'],
                    'imperfectionType' => $row['imperfectionType'],
                    'imperfectionImpact' => $row['imperfectionImpact'],
                    'imperfectionPart' => $row['imperfectionPart'],
                    'imperfectionPosition' => $row['imperfectionPosition'],
                    'remarks' => $row['remarks'],
                    'removed' => ($row['status'] === 'y') ? 'n' : 'y',
                    'imageLink' => !empty($row['image']) ? $baseUrl . ltrim($row['image'], '/') : ''
                ];
            }
        }
        return $dents;
    }

    public function getLeadHistory(int $leadId, int $limit = 10): array
    {
        if ($leadId <= 0) return [];

        $leadId = (int)$leadId; // sanitize

        // Build query
        $query = "
            SELECT 
                h.id,
                h.status,
                " . buildSqlCaseFromConfig('h.status', $this->commonConfig['pm_status']) . " AS status_name,
                IFNULL(h.sub_status, '') AS sub_status,
                " . buildSubStatusSqlCase('h.status', 'h.sub_status', $this->commonConfig['pm_sub_status']) . " AS sub_status_name,
                IFNULL(h.followup_date, '') AS followup_date,
             
                IFNULL(h.price_customer, '') AS price_customer,
                IFNULL(h.price_quote, '') AS price_quote,
                IFNULL(h.price_expenses, '') AS price_expenses,
                IFNULL(h.price_selling, '') AS price_selling,
                IFNULL(h.price_agreed, '') AS price_agreed,
                IFNULL(h.price_indicative, '') AS price_indicative,
                " . buildSqlCaseFromConfig('h.evaluation_place', $this->commonConfig['pm_evaluation_place']) . " AS evaluation_place,
                " . buildMultiSelectCase('h.evaluation_type', $this->commonConfig['evaluation_types']) . " AS evaluation_type,
                IFNULL(h.remarks, '') AS remarks,
                h.created AS updated_date,
                IFNULL(u.name, '') AS updated_by
            FROM sellleads_history h
            LEFT JOIN users u ON h.created_by = u.id
            WHERE h.selllead_id = $leadId
            ORDER BY h.id DESC
            LIMIT $limit
        ";

        $history = [];
        $res = mysqli_query($this->connection, $query);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                // Normalize dates
                foreach (['followup_date',  'updated_date'] as $field) {
                    if (empty($row[$field]) || $row[$field] === '0000-00-00 00:00:00' || $row[$field] === '0000-00-00') {
                        $row[$field] = '';
                    }
                }
                
                $history[] = $row;
            }
        } else {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-getlead-history', true);
        }

        return $history;
    }

    private function processDocuments(array $lead): array {
        $documents = [];
        $baseUrl = rtrim($this->commonConfig['document_base_url'] ?? '', '/') . '/';

        foreach (['file_doc1', 'file_doc2'] as $key) {
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
                $stmt = mysqli_prepare($this->connection, "SELECT template_id FROM sellleads_evaluation WHERE selllead_id = ? AND status = 1");
                
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


    public function getEvaluationData($lead_id = 0){   
        // Use static cache for template and checklist data to avoid repeated queries
        static $templates = null;
        static $checklistStructure = null;
        
        $evaluations = [
            'templates' => [],
            'checklist' => [],
            'count' => [] // Initialize empty count structure
        ];
        
        // Get templates (cached)
        if ($templates === null) {
            $templates = [];
            $temp_query = "SELECT id, template_name, template_description FROM `evaluation_templates` WHERE status = 1";
            $temp_res = mysqli_query($this->connection, $temp_query);
            if ($temp_res) {
                while ($temp_row = mysqli_fetch_assoc($temp_res)) {
                    $templates[$temp_row['id']] = $temp_row;
                }
            }
        }
        $evaluations['templates'] = $templates;
        
        // Get checklist structure with items (cached)
        if ($checklistStructure === null) {
            $checklistStructure = [];
            $items = [];
            
            // Get all checklist items in a single query
            $item_query = "SELECT * FROM evaluation_checklist_items WHERE active = 'y'";
            $item_res = mysqli_query($this->connection, $item_query);
            if (!$item_res) {
                logSqlError(mysqli_error($this->connection), $item_query, 'sellleads-getEvaluation', true);
            } else {
                while ($item_row = mysqli_fetch_assoc($item_res)) { 
                    $item_row['selected'] = false;
                    $item_row['checkoptions'] = [];
                    $item_row['item_name'] = mb_convert_encoding($item_row['item_name'], 'UTF-8', 'UTF-8');
                    $items[$item_row['checklist_id']][] = $item_row;
                }
            }
            
            // Get checklist sections
            $query = "SELECT * FROM evaluation_checklist WHERE active = 'y' ORDER BY sort_order ASC";
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-getEvaluation', true);
            } else {
                while ($row = mysqli_fetch_assoc($res)) {
                    $checklistStructure[$row['evaluation_type']]['sections'][] = [
                        "section_id" => $row['id'],
                        "section_name" => $row['section_name'],
                        "sort_order" => $row['sort_order'],
                        "items" => isset($items[$row['id']]) ? $items[$row['id']] : []
                    ];
                }
            }
        }
        
        // Start with the base checklist structure
        $evaluations['checklist'] = $checklistStructure;
        
        // If lead ID is provided, get specific evaluation data for this lead
        if (!empty($lead_id) && $lead_id > 0) {
            // Use prepared statement for better security
            $stmt = mysqli_prepare($this->connection, 
                "SELECT * FROM sellleads_evaluation WHERE selllead_id = ? AND status = 1");
                
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $lead_id);
                mysqli_stmt_execute($stmt);
                $pm_res = mysqli_stmt_get_result($stmt);
                
                if ($pm_res && mysqli_num_rows($pm_res) > 0) {
                    $evaluations['mapped_template'] = [];
                    $templateCounts = []; // Track counts for each template
                    
                    while ($pm_row = mysqli_fetch_assoc($pm_res)) {
                        $evaluations['mapped_template'][] = $pm_row['template_id'];
                        $checklist = json_decode($pm_row['checklist'], true);
                        
                        // Only replace if we have valid JSON data
                        if (is_array($checklist)) {
                            $evaluations['checklist'][$pm_row['template_id']]['sections'] = $checklist;
                            
                            // Count selections for this template
                            $selectionCount = 0;
                            foreach ($checklist as $section) {
                                if (isset($section['items']) && is_array($section['items'])) {
                                    foreach ($section['items'] as $item) {
                                        if ((isset($item['field_type']) && $item['field_type'] === 'radio' && !empty($item['selected'])) ||
                                            (isset($item['field_type']) && $item['field_type'] === 'checkbox' && !empty($item['checkoptions']))) {
                                            $selectionCount++;
                                        }
                                    }
                                }
                            }
                            
                            // Map template ID to readable name and store count
                            $templateName = '';
                            if (isset($templates[$pm_row['template_id']])) {
                                $templateName = strtolower(str_replace(' ', '_', $templates[$pm_row['template_id']]['template_name']));
                            }
                            if (!empty($templateName)) {
                                $templateCounts[$templateName] = $selectionCount;
                            }
                        }
                    }
                    
                    // Add count field to evaluations
                    $evaluations['count'] = $templateCounts;
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        return $evaluations;
    }



    public function getLeads($filters = [], $custom_filters = [], $current_page = 1, $per_page = 10)
    {
        if (empty($this->logged_dealer_id)) {
            return [
                'status' => 'error',
                'msg' => 'Dealer not logged in',
                'errors' => ['dealer' => 'Missing dealer ID'],
                'data' => []
            ];
        }

        $dealer_id = (int)$this->logged_dealer_id;
        $current_date = date('Y-m-d');
        $offset = max(0, ($current_page - 1) * $per_page);

        // --- Base filters: Dealership & Branch ---
        $where = ["a.dealer = $dealer_id"];
        
        // Get branch users for filtering
        $branches = getUsersByBranchIds($this->logged_branch_id);
        $userIds = array_unique(array_filter(array_merge(
            ...array_map(fn($b) => array_column($b['executives'] ?? [], 'id'), $branches)
        )));
        
        // --- Role-based access control ---
        // Executives (role_main = 'n') should only see leads assigned to them
        // Managers (role_main = 'y') can see all leads from their branch users
        $role_main = strtolower(trim($GLOBALS['dealership']['role_main'] ?? 'n'));
        $current_user_id = (int)$this->logged_user_id;
        
        if ($role_main !== 'y') {
            // Executive: Only leads assigned to them (not leads they created but are no longer assigned to)
            if (!empty($userIds)) {
                // Leads must be: (created by branch users) AND (assigned to me)
                $where[] = "a.user IN (" . implode(',', $userIds) . ")";
                $where[] = "a.executive = $current_user_id";
            } else {
                // Fallback: just leads assigned to them
                $where[] = "a.executive = $current_user_id";
            }
        } else {
            // Manager: All leads from branch users
            if (!empty($userIds)) {
                $where[] = "a.user IN (" . implode(',', $userIds) . ")";
            }
        }

        // --- Dynamic filters ---
        $listFilters = [];
        
        // Master Search - Search across multiple fields with OR condition
        if (!empty($custom_filters['search'])) {
            $searchTerm = mysqli_real_escape_string($this->connection, trim($custom_filters['search']['value']));
            $searchFields = $custom_filters['search']['fields'] ?? [];
            
            $orConditions = [];
            foreach ($searchFields as $field) {
                switch ($field) {
                    case 'search_id':
                        $num = (int)preg_replace('/^PM/i', '', $searchTerm);
                        $orConditions[] = "(a.id LIKE '%$searchTerm%' OR a.id = $num)";
                        break;
                    case 'seller_name':
                        $orConditions[] = "(a.first_name LIKE '%$searchTerm%' OR a.last_name LIKE '%$searchTerm%' OR CONCAT(a.first_name,' ',a.last_name) LIKE '%$searchTerm%')";
                        break;
                    case 'mobile':
                        $isNumeric = is_numeric($searchTerm);
                        $hasPMPrefix = preg_match('/^PM\d+$/i', $searchTerm);
                        
                        if (!$hasPMPrefix && (!$isNumeric || strlen($searchTerm) >= 5)) {
                            $orConditions[] = "a.mobile LIKE '%$searchTerm%'";
                        }
                        break;
                    case 'email':
                        $orConditions[] = "a.email LIKE '%$searchTerm%'";
                        break;
                    case 'reg_num':
                        $orConditions[] = "a.reg_num LIKE '%$searchTerm%'";
                        break;
                    case 'chassis':
                        $orConditions[] = "a.chassis LIKE '%$searchTerm%'";
                        break;
                    case 'make':
                        // Search using same logic as individual filter for consistency
                        // Get all make_ids that match the search term, then search both a.make and v.make_id
                        $orConditions[] = "((a.make IN (SELECT DISTINCT make_id FROM master_variants_new WHERE make LIKE '%$searchTerm%')) OR (v.make_id IN (SELECT DISTINCT make_id FROM master_variants_new WHERE make LIKE '%$searchTerm%')))";
                        break;
                    case 'model':
                        // Search using same logic as individual filter for consistency
                        $orConditions[] = "((a.model IN (SELECT DISTINCT model_id FROM master_variants_new WHERE model LIKE '%$searchTerm%')) OR (v.model_id IN (SELECT DISTINCT model_id FROM master_variants_new WHERE model LIKE '%$searchTerm%')))";
                        break;
                }
            }

            // echo "OR Conditions: " . implode(', ', $orConditions);
            // exit;
            
            if (!empty($orConditions)) {
                $masterSearchFilter = '(' . implode(' OR ', $orConditions) . ')';
                $listFilters[] = $masterSearchFilter;
                // error_log("=== MASTER SEARCH FILTER ===");
                // error_log("Search Term: $searchTerm");
                // error_log("Filter: " . $masterSearchFilter);
            }
        }
        
        // Individual field filters
        foreach ($filters as $key => $val) {
            if ($val === '' || $val === null) continue;
            $val = mysqli_real_escape_string($this->connection, $val);
            switch ($key) {
                case 'make': 
                    // Search both a.make (direct) AND v.make_id (via variant)
                    // This catches: 1) Records with correct a.make, 2) Records where variant has the make
                    $listFilters[] = "(a.make = '$val' OR v.make_id = '$val')";
                    // error_log("Individual Make Filter: (a.make = '$val' OR v.make_id = '$val')");
                    break;
                case 'model': 
                    // Search both a.model (direct) AND v.model_id (via variant)
                    $listFilters[] = "(a.model = '$val' OR v.model_id = '$val')";
                    // error_log("Individual Model Filter: (a.model = '$val' OR v.model_id = '$val')");
                    break;
                case 'seller_name':
                    $listFilters[] = "(a.first_name LIKE '%$val%' OR a.last_name LIKE '%$val%' OR CONCAT(a.first_name,' ',a.last_name) LIKE '%$val%')";
                    break;
                case 'search_id':
                    $num = (int)preg_replace('/^PM/i', '', $val);
                    $listFilters[] = "(a.id LIKE '%$val%' OR a.id = $num)";
                    break;
                case 'mobile': $listFilters[] = "a.mobile LIKE '%$val%'"; break;
                case 'email': $listFilters[] = "a.email LIKE '%$val%'"; break;
                case 'reg_num': $listFilters[] = "a.reg_num LIKE '%$val%'"; break;
                case 'chassis': $listFilters[] = "a.chassis LIKE '%$val%'"; break;
                case 'reason_for_selling': $listFilters[] = "a.reason_for_selling = '$val'"; break;
                case 'rs_make': $listFilters[] = "a.rs_make = '$val'"; break;
                case 'rs_model': $listFilters[] = "a.rs_model = '$val'"; break;
                case 'rs_variant': $listFilters[] = "a.rs_variant = '$val'"; break;
                case 'evaluation_done': $listFilters[] = "a.evaluation_done = '$val'"; break;
            }
        }

        // --- Status & sub-status maps ---
        $pmStatus = $this->commonConfig['pm_sidebar_statuses'] ?? [];
        $pmActualStatus = $this->commonConfig['pm_status'] ?? [];
        $followupSub = [
            'followup-overdue'  => ['followup_date' => '<'],    // Past dates only (before today)
            'followup-today'    => ['followup_date' => '='],    // Today only
            'followup-upcoming' => ['followup_date' => '>']     // Future dates only (after today)
        ];
        $evaluationSub = [
            'evaluation-overdue'  => ['followup_date' => '<'],   // Past dates only (before today)
            'evaluation-today'    => ['followup_date' => '='],   // Today only
            'evaluation-upcoming' => ['followup_date' => '>']    // Future dates only (after today)
        ];

        // --- Initialize counts ---
        $counts = [];
        foreach ($pmStatus as $sid => $label) {
            $key = strtolower(str_replace(' ', '', $label));
            $counts[$sid] = [
                'status_id' => $sid,
                'label' => $label,
                'count' => 0
            ];

            if ($key === 'followup') {
                $counts[$sid]['sub'] = [];
                foreach ($followupSub as $subKey => $subFilter) {
                    $counts[$sid]['sub'][$subKey] = ['label' => ucwords(str_replace(['-', '_'], ' ', $subKey)), 'count' => 0];
                }
            }
            if ($key === 'evaluation') {
                $counts[$sid]['sub'] = [];
                foreach ($evaluationSub as $subKey => $subFilter) {
                    $counts[$sid]['sub'][$subKey] = ['label' => ucwords(str_replace(['-', '_'], ' ', $subKey)), 'count' => 0];
                }
            }
        }

        // --- Count main + sub-status separately ---
        // IMPORTANT: Include listFilters (search filters) in count queries so counts reflect filtered results
        $baseCountWhere = array_merge($where, $listFilters);
        
        foreach ($counts as $sid => $data) {
            $w = $baseCountWhere; // Start with base filters + search filters
            $key = strtolower(str_replace(' ', '', $data['label']));
            
            // Special handling for Evaluation bucket (ID 3) - it's virtual, maps to status=2 AND sub_status=7
            if ($key === 'evaluation') {
                $actualStatusId = array_search('Follow up', $pmActualStatus);
                $w[] = "a.status = $actualStatusId";
                $w[] = "a.sub_status = 7";  // Evaluation Scheduled sub-status
            } else {
                // Map sidebar ID to actual status ID
                $actualStatusId = array_search($data['label'], $pmActualStatus);
                if ($actualStatusId !== false) {
                    $w[] = "a.status = $actualStatusId";
                    
                    //  Exclude Evaluation Scheduled (sub_status=7) from Follow up bucket
                    // These leads belong ONLY in the Evaluation bucket
                    if ($key === 'followup') {
                        $w[] = "(a.sub_status IS NULL OR a.sub_status != 7)";
                    }
                } else {
                    continue; // Skip if status doesn't exist in pm_status
                }
            }

            $sqlMain = "SELECT COUNT(*) AS total FROM sellleads a 
                LEFT JOIN master_variants_new v ON a.variant = v.id 
                LEFT JOIN (SELECT DISTINCT make_id, make FROM master_variants_new) vmk ON a.make = vmk.make_id
                LEFT JOIN (SELECT DISTINCT model_id, model FROM master_variants_new) vmd ON a.model = vmd.model_id
                WHERE " . implode(" AND ", $w);
            $resMain = mysqli_query($this->connection, $sqlMain);
            $counts[$sid]['count'] = ($resMain && $r = mysqli_fetch_assoc($resMain)) ? (int)$r['total'] : 0;

            if (!empty($data['sub'])) {
                $subTotalCount = 0;
                foreach ($data['sub'] as $subKey => $_) {
                    $subWhere = $w;
                    
                    // The parent $w already has sub_status exclusion for followup buckets
                    // No need to add it again here
                    
                    $subMap = $followupSub[$subKey] ?? $evaluationSub[$subKey] ?? null;
                    if ($subMap) {
                        foreach ($subMap as $col => $op) {
                            // Match the list query logic exactly
                            if (strpos($subKey, 'overdue') !== false) {
                                // Overdue: Include NULL/empty OR use operator from config
                                $subWhere[] = "(a.$col IS NULL OR a.$col = '0000-00-00 00:00:00' OR DATE(a.$col) $op '$current_date')";
                            } else {
                                // Today/Upcoming: Only valid dates
                                $subWhere[] = "a.$col IS NOT NULL AND a.$col <> '0000-00-00 00:00:00' AND DATE(a.$col) $op '$current_date'";
                            }
                        }
                    }
                    $sqlSub = "SELECT COUNT(*) AS total FROM sellleads a 
                        LEFT JOIN master_variants_new v ON a.variant = v.id 
                        LEFT JOIN (SELECT DISTINCT make_id, make FROM master_variants_new) vmk ON a.make = vmk.make_id
                        LEFT JOIN (SELECT DISTINCT model_id, model FROM master_variants_new) vmd ON a.model = vmd.model_id
                        WHERE " . implode(" AND ", $subWhere);
                    $resSub = mysqli_query($this->connection, $sqlSub);
                    $counts[$sid]['sub'][$subKey]['count'] = ($resSub && $rSub = mysqli_fetch_assoc($resSub)) ? (int)$rSub['total'] : 0;
                }
            }
        }

        // --- Build menu ---
        $total = array_sum(array_column($counts, 'count'));
        
        // Calculate Active Leads count separately (Fresh + Follow up + Deal Done)
        $activeLeadsCount = 0;
        foreach ($counts as $sid => $data) {
            $key = strtolower(str_replace(' ', '', $data['label']));
            // Sum up Fresh, Follow up, Evaluation, and Deal Done
            // Note: Evaluation is virtual (status=2, sub_status=7), Follow up excludes it
            // So we need to include both Follow up + Evaluation to get full status=2 count
            if (in_array($key, ['fresh', 'followup', 'evaluation', 'dealdone'])) {
                $activeLeadsCount += $data['count'];
            }
        }
        
        $menu = [
            'all' => ['status_id' => 'all', 'label' => 'All', 'count' => $total, 'is_active' => (empty($filters['status']) && empty($filters['active_bucket']) || $filters['status'] === 'all') ? 'y' : ''],
            'active-leads' => ['status_id' => 'active', 'label' => 'Active Leads', 'count' => $activeLeadsCount, 'is_active' => !empty($filters['active_bucket']) ? 'y' : '']
        ];
        foreach ($counts as $sid => $data) {
            $menuItem = ['status_id' => $data['status_id'], 'label' => $data['label'], 'count' => $data['count']];
            
            // Handle sub-buckets
            if (!empty($data['sub'])) {
                // Mark active sub-bucket if sub_status filter is present
                if (!empty($filters['sub_status'])) {
                    foreach ($data['sub'] as $subKey => &$subItem) {
                        if ($subKey === $filters['sub_status']) {
                            $subItem['is_active'] = 'y';
                        }
                    }
                    unset($subItem); // Break reference
                }
                $menuItem['sub'] = $data['sub'];
            }
            
             // Mark parent as active ONLY if viewing the main bucket (no sub_status)
            // When sub-bucket is selected, only the sub-bucket should be active, not the parent
            if (!empty($filters['status']) && empty($filters['sub_status']) && empty($filters['active_bucket']) && empty($filters['evaluation_bucket'])) {
                $key = strtolower(str_replace(' ', '', $data['label']));
                
                // Map sidebar label to actual status ID from pm_status
                $actualStatusId = array_search($data['label'], $pmActualStatus);
                if ($actualStatusId !== false && $filters['status'] == $actualStatusId) {
                    $menuItem['is_active'] = 'y';
                }
            }
            
            // Special case: Mark evaluation bucket as active when evaluation_bucket filter is present
            if (!empty($filters['evaluation_bucket']) && strtolower(str_replace(' ', '', $data['label'])) === 'evaluation') {
                $menuItem['is_active'] = 'y';
            }
            
            $menu[strtolower(str_replace(' ', '-', $data['label']))] = $menuItem;
        }

        // --- Apply filters for pagination and list ---
        $customDateFilters = [];
        if (!empty($filters['sub_status'])) {
            $subKey = $filters['sub_status'];
            $subMap = $followupSub[$subKey] ?? $evaluationSub[$subKey] ?? null;
            if ($subMap) {
                foreach ($subMap as $col => $op) {
                    if (strpos($subKey, 'overdue') !== false) {
                        // Overdue: Include NULL/empty OR use operator from config
                        $customDateFilters[] = "(a.$col IS NULL OR a.$col = '0000-00-00 00:00:00' OR DATE(a.$col) $op '$current_date')";
                    } else {
                        // Today/Upcoming: Only valid dates
                        $customDateFilters[] = "a.$col IS NOT NULL AND a.$col <> '0000-00-00 00:00:00' AND DATE(a.$col) $op '$current_date'";
                    }
                }
            }
        }

        $allWhere = array_merge($where, $listFilters, $customDateFilters);
        if (!empty($filters['status'])) $allWhere[] = "a.status = " . (int)$filters['status'];
        
        // Special handling for active leads bucket - status IN (1, 2, 3)
        if (!empty($filters['active_bucket'])) {
            $allWhere[] = "a.status IN (1, 2, 3)";  // Fresh + Follow up (including Evaluation) + Deal Done
        }
        
        // Special handling for evaluation bucket - add sub_status=7 filter
        if (!empty($filters['evaluation_bucket'])) {
            $allWhere[] = "a.sub_status = 7";  // Evaluation Scheduled
        }
        
        // Exclude evaluation leads (sub_status=7) when viewing Follow up bucket directly
        if (!empty($filters['exclude_evaluation'])) {
            $allWhere[] = "(a.sub_status IS NULL OR a.sub_status != 7)";
        }
        
        $allWhereSql = "WHERE " . implode(" AND ", $allWhere);

        // --- Count total for pagination ---
        $countSql = "SELECT COUNT(*) AS total FROM sellleads a 
            LEFT JOIN master_variants_new v ON a.variant = v.id 
            LEFT JOIN (SELECT DISTINCT make_id, make FROM master_variants_new) vmk ON a.make = vmk.make_id
            LEFT JOIN (SELECT DISTINCT model_id, model FROM master_variants_new) vmd ON a.model = vmd.model_id
            $allWhereSql";       
        // error_log("=== PAGINATION COUNT QUERY ===");
        // error_log($countSql);
        $resCount = mysqli_query($this->connection, $countSql);
        $filtered_total = ($resCount && $r = mysqli_fetch_assoc($resCount)) ? (int)$r['total'] : 0;

        $pages = ceil($filtered_total / max(1, $per_page));
        $offset = max(0, ($current_page - 1) * $per_page);

        // --- Determine sorting based on bucket type ---
        $orderBy = "a.updated DESC"; // Default sorting
        
        // If viewing evaluation bucket, sort by followup_date (evaluation uses followup_date)
        if (!empty($filters['evaluation_bucket']) || !empty($filters['sub_status']) && strpos($filters['sub_status'], 'evaluation') !== false) {
            // For evaluation buckets, sort by followup_date
            // Overdue/Today/Upcoming: Show oldest to newest for overdue, newest to oldest for upcoming
            if (!empty($filters['sub_status'])) {
                if (strpos($filters['sub_status'], 'overdue') !== false) {
                    $orderBy = "a.followup_date ASC, a.updated DESC"; // Oldest overdue first
                } elseif (strpos($filters['sub_status'], 'upcoming') !== false) {
                    $orderBy = "a.followup_date ASC, a.updated DESC"; // Nearest upcoming first
                } else {
                    $orderBy = "a.followup_date ASC, a.updated DESC"; // Today: by time
                }
            } else {
                $orderBy = "a.followup_date ASC, a.updated DESC"; // General evaluation bucket
            }
        }
        // If viewing follow-up bucket with sub-status, sort by followup_date
        elseif (!empty($filters['sub_status']) && strpos($filters['sub_status'], 'followup') !== false) {
            if (strpos($filters['sub_status'], 'overdue') !== false) {
                $orderBy = "a.followup_date ASC, a.updated DESC"; // Oldest overdue first
            } elseif (strpos($filters['sub_status'], 'upcoming') !== false) {
                $orderBy = "a.followup_date ASC, a.updated DESC"; // Nearest upcoming first
            } else {
                $orderBy = "a.followup_date ASC, a.updated DESC"; // Today: by time
            }
        }

        // --- List query ---
        $queryParts = $this->buildLeadBaseQuery(false);
        $listQuery = "
            {$queryParts['select']}
            {$queryParts['from_joins']}
            $allWhereSql
            ORDER BY $orderBy
            LIMIT $offset, $per_page
        ";

        // error_log("=== LIST QUERY ===");
        // error_log($listQuery);

        $rows = mysqli_query($this->connection, $listQuery);
        $leads = [];
        if ($rows) {
            while ($r = mysqli_fetch_assoc($rows)) {
                $r['numeric_id'] = $r['id'];
                $r['formatted_id'] = "PM{$r['id']}";
                $r['id'] = function_exists('data_encrypt') ? data_encrypt($r['id']) : $r['id'];
                $r['documents'] = (object)$this->processDocuments($r);
                
                if (!empty($r['documents'])) {
                    foreach ($r['documents'] as $key => $doc) {
                        if (!empty($doc['url'])) {
                            $r[$key] = $doc['url'];
                        }
                    }
                }
            
                $leads[] = $r;
            }
        }

        $leadIds = array_column($leads, 'numeric_id');
        $leadImages = $this->getLeadImages($leadIds);
        foreach ($leads as &$lead) {
            $lead['images'] = $leadImages[$lead['numeric_id']] ?? [];
            
            // Convert mfg_month from "01" to "1" format to match config
            if (!empty($lead['mfg_month'])) {
                $lead['mfg_month'] = (string)((int)$lead['mfg_month']);
            }
        }
        unset($lead);

        return [
            'pagination' => [
                'total' => $filtered_total,
                'pages' => $pages,
                'per_page' => $per_page,
                'current_page' => (int)$current_page,
                'start_count' => $filtered_total ? $offset + 1 : 0,
                'end_count' => min($offset + $per_page, $filtered_total)
            ],
            'menu' => $menu,
            'list' => $leads
        ];
    }


    // -------------------- GET SINGLE LEAD --------------------
    public function getLead(int $leadId)
    {
        if ($leadId <= 0) return null;

        // Check ownership
        if (!$this->ownerCheck($leadId)) {
            return null;
        }

        // Build query
        $queryParts = $this->buildLeadBaseQuery(true);
        $query = "{$queryParts['select']} 
                  {$queryParts['from_joins']} 
                  WHERE a.id = $leadId 
                  LIMIT 1";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-getlead', true);
            return null;
        }

        $lead_res = mysqli_fetch_assoc($res);
        if (!$lead_res) return null;
        $lead['id'] = function_exists('data_encrypt') ? data_encrypt($lead_res['id']) : $lead_res['id'];
        // Encrypt ID if function exists
        $lead['detail'] = $lead_res;
        $lead['detail']['numeric_id'] = $lead_res['id'];
        $lead['detail']['formatted_id'] = "PM{$lead_res['id']}";
        $lead['detail']['id'] = $lead['id'];

        // Process documents - pass $lead_res which has file_doc1 and file_doc2
        $lead['documents'] = (object)$this->processDocuments($lead_res);
        if (!empty($lead['documents'])) {
            foreach ($lead['documents'] as $key => $doc) {
                if (!empty($doc['url'])) {
                    $lead['detail'][$key] = $doc['url'];
                }
            }
        }

        $lead['dent_map'] = $this->getLeadDents($leadId);

        // Fetch images
        $leadImages = $this->getLeadImages([$leadId]);
        $rawImages = $leadImages[$leadId] ?? [];
        $lead['images'] = $this->mapLeadImages($rawImages);
        $lead['history'] = $this->getLeadHistory($leadId);        
        $lead['evaluation_templates'] = (object)$this->evaluationTemplates($leadId);
        
        //  Add Vahan Info - use $lead_res which has the actual database fields
        $lead['vahanInfo'] = $this->getVahanInfo($leadId, $lead_res['reg_num'] ?? '', $lead_res['vahan_response'] ?? null);
        unset($lead['detail']['vahan_response']); // Remove raw response for security
        // Convert mfg_month from "01" to "1" format to match config
        if (!empty($lead['mfg_month'])) {
            $lead['mfg_month'] = (string)((int)$lead['mfg_month']);
        }
        
        return $lead;
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
                        
                        $update_query = "UPDATE sellleads 
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

// Sync latest Vahan data from vahan_api_log to sellleads.vahan_response .  Called during form submission (add/update)

    private function syncVahanFromLog($leadId, $regNum)
    {
        if (empty($regNum) || $leadId <= 0) {
            return false;
        }

        try {
            $reg_num_escaped = mysqli_real_escape_string($this->connection, $regNum);
            
            // Get latest vahan_api_log entry for this registration number
            $log_query = "SELECT response, created_at 
                         FROM vahan_api_log 
                         WHERE UPPER(REPLACE(reg_num, ' ', '')) = UPPER(REPLACE('$reg_num_escaped', ' ', '')) 
                         ORDER BY created_at DESC 
                         LIMIT 1";
            
            $log_result = mysqli_query($this->connection, $log_query);
            
            if ($log_result && mysqli_num_rows($log_result) > 0) {
                $log_row = mysqli_fetch_assoc($log_result);
                $log_response = $log_row['response'] ?? '';
                $log_created_at = $log_row['created_at'] ?? '';
                
                if (!empty($log_response)) {
                    // Parse and validate the response
                    $log_decoded = json_decode($log_response, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && is_array($log_decoded)) {
                        // Update sellleads.vahan_response with the log data
                        $escaped_response = mysqli_real_escape_string($this->connection, $log_response);
                        $escaped_timestamp = mysqli_real_escape_string($this->connection, $log_created_at);
                        
                        $update_query = "UPDATE sellleads 
                                       SET vahan_response = '$escaped_response',
                                           vahan_fetched_at = '$escaped_timestamp'
                                       WHERE id = $leadId";
                        
                        $update_result = mysqli_query($this->connection, $update_query);
                        
                        if ($update_result) {
                            return true;
                        } else {
                            error_log("Failed to sync vahan data from log to sellleads {$leadId}: " . mysqli_error($this->connection));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error syncing vahan data from log for sellleads {$leadId}: " . $e->getMessage());
        }
        
        return false;
    }


    public function addLead($request){

        $dateNow = date('Y-m-d H:i:s');

        // Validate executive if provided
        if (!empty($request['executive'])) {
            $validation = validateExecutiveActive($request['executive'], $this->connection);
            if (!$validation['valid']) {
                return ['status' => 'fail', 'message' => $validation['message']];
            }
        }

        // Merge system fields
        $request = array_merge($request, [
            'dealer'     => (int)$this->logged_dealer_id,
            'user'       => (int)$this->logged_user_id,
            // 'executive' => (int)$this->executive_id, // uncomment if needed
            'status'     => 1,
            'created_by' => (int)$this->logged_user_id,
            'updated_by' => (int)$this->logged_user_id,
            'created'    => $dateNow
        ]);

        // Escape and wrap columns and values
        // Handle empty values as NULL to prevent 0 or empty string insertion
        $columns = [];
        $values = [];
        foreach ($request as $key => $val) {
            $columns[] = "`" . mysqli_real_escape_string($this->connection, $key) . "`";
            
            // Convert empty strings to NULL, but preserve numeric 0 as valid value
            if ($val === '' || $val === null) {
                $values[] = "NULL";
            } else {
                $values[] = "'" . mysqli_real_escape_string($this->connection, $val) . "'";
            }
        }

        $query = "INSERT INTO sellleads (" . implode(", ", $columns) . ") 
                VALUES (" . implode(", ", $values) . ")";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-addlead', true);
            return false;
        }

        $lastInsertId = mysqli_insert_id($this->connection);

        // Sync latest Vahan data from vahan_api_log after lead creation
        if ($lastInsertId > 0 && !empty($request['reg_num'])) {
            $this->syncVahanFromLog($lastInsertId, $request['reg_num']);
        }

        // Insert into history
        $this->id = $lastInsertId;
        $this->status = 1;
        $this->created_by = $this->logged_user_id;
        $this->created = $dateNow;

        $result = $this->insertSellleadsHistory();

        if ($lastInsertId > 0 && $result) {
            logTableInsertion('sellleads', $lastInsertId);
            return [['id' => data_encrypt($lastInsertId)]];
        }

        return false;
    }


    public function updateLead($request, $leadId)
    {
        // Validate executive if provided in update request
        if (isset($request['executive'])) {
            $validation = validateExecutiveActive($request['executive'], $this->connection);
            if (!$validation['valid']) {
                return ['status' => 'fail', 'message' => $validation['message']];
            }
        }

        // Sync latest Vahan data from vahan_api_log to sellleads.vahan_response
        // This happens on every update (if reg_num exists)
        if (!empty($request['reg_num'])) {
            $this->syncVahanFromLog($leadId, $request['reg_num']);
        }

        $sets = [];
        foreach ($request as $key => $val) {
            // Convert empty strings to NULL, but preserve numeric 0 as valid value
            if ($val === '' || $val === null) {
                $sets[] = "`" . mysqli_real_escape_string($this->connection, $key) . "` = NULL";
            } else {
                $sets[] = "`" . mysqli_real_escape_string($this->connection, $key) . "` = '" . 
                        mysqli_real_escape_string($this->connection, $val) . "'";
            }
        }

        $sets[] = "`updated` = '" . date('Y-m-d H:i:s') . "'";
        $sets[] = "`updated_by` = " . (int)$this->logged_user_id;

        $query = "UPDATE sellleads SET " . implode(", ", $sets) . 
                " WHERE id = $leadId AND dealer = " . (int)$this->logged_dealer_id;

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-updatelead', true);
        }

        if(mysqli_affected_rows($this->connection) > 0) {
            logTableInsertion('sellleads', $leadId);
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
            return ['status' => 'fail', 'message' => $validation['message']];
        }

        if ($lead_id > 0) // Only require valid lead ID
        {
            $query = "UPDATE sellleads 
                    SET executive=$exec_id, 
                        branch=$branch_id, 
                        updated='" . date('Y-m-d H:i:s') . "', 
                        updated_by=" . (int)$this->logged_user_id . "
                    WHERE id = $lead_id";

            $res = mysqli_query($this->connection, $query); 
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-assignExecutive', true);
            }

            return mysqli_affected_rows($this->connection) > 0;
        }
        return false;
    }


    public function saveImage($image, $leadId, $image_tag){
        if (empty($image)) {
            return ['status'=>false, 'details'=>[]];
        }

        $leadId    = intval($leadId);
        $image_tag = mysqli_real_escape_string($this->connection, $image_tag);
        $user_id   = (int)$this->login_user_id;
        $now       = date('Y-m-d H:i:s');
        $escaped_image = mysqli_real_escape_string($this->connection, $image);

        // OPTIMIZATION: Use INSERT ... ON DUPLICATE KEY UPDATE
        // First, ensure we have a unique index on (selllead_id, image_tag)
        $query = "INSERT INTO sellleads_images 
                  (selllead_id, image, image_tag, uploaded_by, created_date, status) 
                  VALUES ($leadId, '$escaped_image', '$image_tag', $user_id, '$now', 1)
                  ON DUPLICATE KEY UPDATE 
                  image = VALUES(image),
                  status = 1,
                  updated_date = '$now',
                  uploaded_by = $user_id";
        
        $res = mysqli_query($this->connection, $query);
        
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-saveimage', true);
            return ['status' => false, 'details' => []];
        }
        
        // Get the ID (either inserted or updated)
        $image_id = mysqli_insert_id($this->connection);
        if ($image_id == 0) {
            // This was an update, get the existing ID
            $query = "SELECT id FROM sellleads_images 
                      WHERE selllead_id = $leadId AND image_tag = '$image_tag' LIMIT 1";
            $res = mysqli_query($this->connection, $query);
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $image_id = $row['id'];
            }
        }
        
        return [
            "status" => true,
            "details" => [
                "id"  => $image_id,
                "url" => Files::imageLink($image, '600x800'),
                "tag" => $image_tag
            ]
        ];
        
    }

    public function findImage($image_id,$leadId)
    {
        $leadId = mysqli_real_escape_string($this->connection,$leadId);
        $image_id = mysqli_real_escape_string($this->connection,$image_id);
        if( $leadId>0 && $image_id>0 )
        {
            $query = "SELECT id FROM sellleads_images WHERE id=$image_id AND selllead_id=$leadId AND status=1";
            $res = mysqli_query($this->connection,$query);
            if (!$res) 
            {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-findimage', true);
            }
            if(mysqli_num_rows($res)>0) return true;
            else return false;
        }
    }

    public function deleteImage($image_id,$leadId)
    {
        $leadId = mysqli_real_escape_string($this->connection,$leadId);   
        $image_id = mysqli_real_escape_string($this->connection,$image_id);
        if( $leadId>0 && $image_id>0 )
        {
            $query = "UPDATE sellleads_images SET status=0,updated_date='".date('Y-m-d H:i:s')."' WHERE id=$image_id AND selllead_id=$leadId";
            $res = mysqli_query($this->connection,$query); 
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-deleteimage', true);
            }   
            if(mysqli_affected_rows($this->connection)>0) return true;
            else return false;
        }
        return false;
    }

    public function findDentmap($leadId,$dent_id)
    {
        $leadId = (int) mysqli_real_escape_string($this->connection, $leadId);
        $dent_id = (int) mysqli_real_escape_string($this->connection, $dent_id);
        if( $leadId>0 && $dent_id>0 )
        {
            $query = "SELECT id FROM dentmap_list WHERE id=$dent_id AND selllead_id=$leadId AND status='y'";
            $res = mysqli_query($this->connection,$query);
            if (!$res) 
            {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-findDentmap', true);
            }
            if(mysqli_num_rows($res)>0) return true;
            else return false;
        }
        return false;
    }

    public function saveDentmap($request,$leadId)
    {
        if($leadId>0)
        {
            $leadId = (int) mysqli_real_escape_string($this->connection, $leadId);
            $xPos = isset($request['xPos']) ? (int) mysqli_real_escape_string($this->connection, $request['xPos']) : 0;
            $yPos = isset($request['yPos']) ? (int) mysqli_real_escape_string($this->connection, $request['yPos']) : 0;
            $width = isset($request['width']) ? (int) mysqli_real_escape_string($this->connection, $request['width']) : 0;
            $height = isset($request['height']) ? (int) mysqli_real_escape_string($this->connection, $request['height']) : 0;
            $imperfectionType = isset($request['imperfectionType']) ? mysqli_real_escape_string($this->connection, $request['imperfectionType']) : '';
            $imperfectionImpact = isset($request['imperfectionImpact']) ? mysqli_real_escape_string($this->connection, $request['imperfectionImpact']) : '';
            $imperfectionPart = isset($request['imperfectionPart']) ? mysqli_real_escape_string($this->connection, $request['imperfectionPart']) : '';
            $imperfectionPosition = isset($request['imperfectionPosition']) ? mysqli_real_escape_string($this->connection, $request['imperfectionPosition']) : '';
            $downCoat = isset($request['downCoat']) ? mysqli_real_escape_string($this->connection, $request['downCoat']) : '';
            $remarks = isset($request['remarks']) ? mysqli_real_escape_string($this->connection, $request['remarks']) : '';
            $status = ($request['removed']=='n' )?'y':'n';
            $image = isset($request['imageLink']) ? mysqli_real_escape_string($this->connection, $request['imageLink']) : '';
             
            // INSERT
            if( empty($request['id']) || $request['id']==0 )
            {
                $query = "INSERT INTO dentmap_list SET 
                    selllead_id = $leadId,
                    xpos = $xPos,
                    ypos = $yPos,
                    width = $width,
                    height = $height,
                    imperfection_type = '$imperfectionType',
                    imperfection_impact = '$imperfectionImpact',
                    imperfection_part = '$imperfectionPart',
                    imperfection_position = '$imperfectionPosition',
                    down_coat = '$downCoat',
                    remarks = '$remarks',
                    status = '$status',
                    image = '$image',
                    created ='".date('Y-m-d H:i:s')."',
                    updated_by =".(int)$this->login_user_id;
                //echo $query;exit;
                mysqli_query($this->connection,$query);
                if( mysqli_insert_id($this->connection)>0 ) return true;
                else return false;
            }
            // UPDATE
            if(!empty($request['id']) && $request['id']>0)
            {
                $dent_id = (int) mysqli_real_escape_string($this->connection, $request['id']);
                $query = "UPDATE dentmap_list SET 
                    xpos = $xPos,
                    ypos = $yPos,
                    width = $width,
                    height = $height,
                    imperfection_type = '$imperfectionType',
                    imperfection_impact = '$imperfectionImpact',
                    imperfection_part = '$imperfectionPart',
                    imperfection_position = '$imperfectionPosition',
                    down_coat = '$downCoat',
                    remarks = '$remarks',
                    status = '$status',
                    image = '$image',
                    updated = '".date('Y-m-d H:i:s')."',
                    updated_by = ".(int)$this->login_user_id."
                    WHERE id=$dent_id AND selllead_id=$leadId";
                mysqli_query($this->connection,$query);
                if( mysqli_affected_rows($this->connection)>0 ) return true;
                else return false;
            }                                        
        }
    }


    private function updateEvaluationDoneStatus($request)
    {
        // Get evaluation count for this lead
        /*$lead = $this->getLead($leadId);
        $evaluationCount = 0;
        
        if (isset($lead['evaluation']['count'])) {
            $countObj = $lead['evaluation']['count'];
            // Check for full_mp1, full_mpi, or any template with count > 0
            $evaluationCount = $countObj['full_mp1'] ?? $countObj['full_mpi'] ?? 
                             array_values(array_filter($countObj, fn($v) => is_numeric($v) && $v > 0))[0] ?? 0;
        }
        
        // Update evaluation_done: 'y' if >= 15 items checked, 'n' otherwise
        $evaluation_done = ($evaluationCount >= 15) ? 'y' : 'n';
        */
         $evaluation_done =  'n';
        if( $request['lead_id'] >0 )
        {
            $leadId = $request['lead_id'];
            $evaluation_type = $request['evaluation_type'];
            $evaluation_date = $request['evaluation_date'];
            $evaluation_done =  'y';
            $evaluation_place =  1;
            
            $update_query = "UPDATE sellleads SET 
                evaluation_done = '" . mysqli_real_escape_string($this->connection, $evaluation_done) . "',
                evaluation_type = '" . mysqli_real_escape_string($this->connection, $evaluation_type) . "',
                evaluation_date = '" . mysqli_real_escape_string($this->connection, $evaluation_date) . "',
                evaluation_place = '" . mysqli_real_escape_string($this->connection, $evaluation_place) . "'
                WHERE id = '" . mysqli_real_escape_string($this->connection, $leadId) . "'";
            
            $update_result = mysqli_query($this->connection, $update_query);
            
            if(!$update_result) {
                logSqlError(mysqli_error($this->connection), $update_query, 'sellleads-updateEvaluationDoneStatus', true);
            }
        }
        
        return $evaluation_done;
    }

    public function updateLeadStatus($previous_status,$previous_sub_status,$previous_followup_date,$doc1 = null,$doc2 = null){
        // echo "<pre>";
        //    print_r($this);
        // echo "</pre>";
        // exit;
        $this->file_doc1 = $doc1 ?? ""; 
        $this->file_doc2 = $doc2 ?? ""; 
    
        if($this->status < $previous_status)
        {
           return ['status' => false,'msg' => "Updated status should not be earlier than current status.",'field' => 'status'];
        }

        // Validation for Deal Done status (3) - pricing fields must be filled
        if($this->status == '3') {
            $requiredPricing = [
                'price_customer' => 'Customer Expected Price',
                'price_quote' => 'Retailer Offered Price',
                'price_expenses' => 'Estimated Refurbishment Cost',
                'price_margin' => 'Provisioned Margin'
            ];

            foreach($requiredPricing as $field => $label) {
                $value = $this->$field ?? '';
                if(empty($value) || $value === '0' || intval($value) === 0) {
                    return [
                        'status' => false,
                        'msg' => "$label is required when moving to Deal Done status.",
                        'field' => $field
                    ];
                }
            }

            // Validation for Token Amount when sub_status is Token Paid (1) or Token Pending (2)
            if(in_array($this->sub_status, ['1', '2'])) {
                $tokenAmount = $this->token_amount ?? '';
                if(empty($tokenAmount) || $tokenAmount === '0' || intval($tokenAmount) === 0) {
                    return [
                        'status' => false,
                        'msg' => "Token Amount is required for Token Paid or Token Pending status.",
                        'field' => 'token_amount'
                    ];
                }
            }
        }

        // Validation for Follow up status (2) - followup_date is required
        if($this->status == '2' && (empty($this->followup_date) || $this->followup_date === '0000-00-00 00:00:00')) {
            return [
                'status' => false,
                'msg' => 'Follow-up Date & Time is required for Follow up status.',
                'field' => 'followup_date'
            ];
        }

        // Note: price_selling validation for status '4' is now handled by config-driven validation in common_functions.php
        // The config has minValue: 1 and isRequired for status '4'

        if (!empty($this->followup_date) && $this->followup_date !== '0000-00-00 00:00:00') 
        {

            $followupDateTime = new DateTime($this->followup_date); 
            $now = new DateTime();

            $followupDate = $followupDateTime->format('Y-m-d');
            $todayDate    = $now->format('Y-m-d');


            if ($followupDate < $todayDate) {
                return [
                    'status' => false,
                    'msg' => "Follow-up date can't be earlier than today.",
                    'field' => 'followup_date'
                ];
            }

            if ($followupDate === $todayDate && $followupDateTime < $now) {
                return [
                    'status' => false,
                    'msg' => "Follow-up time can't be earlier than current time.",
                    'field' => 'followup_date'
                ];
            }
        }
        
        $status_update = "UPDATE sellleads SET 
                            status = '".mysqli_real_escape_string($this->connection,$this->status)."',
                            sub_status = ".(empty($this->sub_status) && $this->sub_status !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->sub_status)."'").",
                            lead_classification = '".mysqli_real_escape_string($this->connection,$this->lead_classification)."',
                            followup_date = ".(empty($this->followup_date) ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->followup_date)."'").",
                            evaluation_type = ".(empty($this->evaluation_type) ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->evaluation_type)."'").",
                            evaluation_place = ".(empty($this->evaluation_place) && $this->evaluation_place !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->evaluation_place)."'").",
                            evaluation_done = '".mysqli_real_escape_string($this->connection,$this->evaluation_done)."',
                            price_customer = ".(empty($this->price_customer) && $this->price_customer !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_customer)."'").",
                            price_quote = ".(empty($this->price_quote) && $this->price_quote !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_quote)."'").",
                            price_expenses = ".(empty($this->price_expenses) && $this->price_expenses !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_expenses)."'").",
                            price_margin = ".(empty($this->price_margin) && $this->price_margin !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_margin)."'").",
                            price_agreed = ".(empty($this->price_agreed) && $this->price_agreed !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_agreed)."'").",
                            file_doc1 = ".(empty($this->file_doc1) ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->file_doc1)."'").",
                            file_doc2 = ".(empty($this->file_doc2) ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->file_doc2)."'").",
                            is_exchange = '".mysqli_real_escape_string($this->connection,$this->is_exchange)."',
                            price_selling = ".(empty($this->price_selling) && $this->price_selling !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_selling)."'").",
                            price_indicative = ".(empty($this->price_indicative) && $this->price_indicative !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->price_indicative)."'").",
                            token_amount = ".(empty($this->token_amount) && $this->token_amount !== '0' ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->token_amount)."'").",
                            remarks = ".(empty($this->remarks) ? "NULL" : "'".mysqli_real_escape_string($this->connection,$this->remarks)."'").",
                            updated = '" . date('Y-m-d H:i:s') . "',
                            updated_by = '".mysqli_real_escape_string($this->connection,$this->logged_user_id)."'
                            WHERE id = '".mysqli_real_escape_string($this->connection,$this->id)."'";

        $status_res = mysqli_query($this->connection,$status_update);
        if(!$status_res)
        {
            logSqlError(mysqli_error($this->connection), $status_update, 'sellleads-updateleadstatus');
            return ["status" => false,"msg"=> "Log table insertion failed"];
        }
        // Log insertion
        $result = logTableInsertion("sellleads",$this->id);
        
        if($this->status != $previous_status || $this->sub_status != $previous_sub_status || $this->followup_date != $previous_followup_date ) 
        {
            if(!$this->insertSellleadsHistory())
            {
               return ["status" => false,"msg"=> "Failed to insert lead in status history table."];
            }
        }

        $inv_result = $this->isVehiclePurchased();
        if(!$inv_result['status'])
        {
          return ["status" => false,"msg"=> "Failed to move vehicle from sellleads to inventory"];
        }

        $exchange_result = $this->isVehicleExchaged($inv_result['id']);
        if(!$exchange_result)
        {
          return ["status" => false,"msg"=> "Failed to move vehicle from sellleads to exchange"];
        }
        
        if($result)
        { 
            return ["status" => true,"msg"=> ""];
        }
        else
        {
            return ["status" => false,"msg"=> ""];
        }
    }


    private function isVehiclePurchased()
    {
        $inv_query = "SELECT COUNT(*) AS cnt FROM inventory WHERE selllead_id = '".mysqli_real_escape_string($this->connection,$this->id)."'";
        $inv_res = mysqli_query($this->connection,$inv_query);
        if(!$inv_res)
        {
            logSqlError(mysqli_error($this->connection), $inv_query, 'inventory-addlead');
            return ["status" => false, "id" => null];
        }

        $inv_cnt_res = mysqli_fetch_assoc($inv_res);
        $inv_cnt = $inv_cnt_res['cnt'];

        if(empty($inv_cnt))
        {
                if($this->status == 4) //Purchased
                {
                    $id = (int) mysqli_real_escape_string($this->connection,$this->id);

                    $query = "INSERT INTO inventory (
                        user, car_type, source_other, make, model, body_type, variant, color,mileage,
                        mfg_month, mfg_year, reg_type, reg_date, reg_num, 
                        dealer,owners,executive,branch,
                        selllead_id,chassis,transmission,fuel,fuel_end,
                        hypothecation,insurance_type,insurance_exp_date,file_doc1,file_doc2,added_by,status,
                        vahan_response,vahan_fetched_at
                    ) 
                    SELECT 
                       user, car_type, source_other, make, model, body_type, variant, color, mileage,
                       mfg_month, mfg_year, reg_type, reg_date, reg_num, 
                       dealer,owners,executive,branch,
                       id,chassis,transmission,fuel,fuel_end,
                       hypothecation,insurance_type,insurance_exp_date,file_doc1,file_doc2,created_by,1,
                       vahan_response,vahan_fetched_at
                    FROM sellleads 
                    WHERE id = $id";

                    $res = mysqli_query($this->connection,$query);
                    if(!$res)
                    {
                        logSqlError(mysqli_error($this->connection), $query, 'inventory-addlead');
                        return ["status" => false, "id" => null];
                    }
                    $insert_id = mysqli_insert_id($this->connection);

                    logTableInsertion('inventory',  $insert_id);

                    // updating purchased_date in sellleads table
                    $update_query = "UPDATE sellleads SET purchased_date = '".date('Y-m-d H:i:s')."'
                                     WHERE id = '".mysqli_real_escape_string($this->connection,$this->id)."'";
                    $update_res = mysqli_query($this->connection,$update_query);
                    if(!$update_res)
                    {
                        logSqlError(mysqli_error($this->connection), $update_query, 'sellleads-updatepurchaseddate');
                        return ["status" => false,"msg"=> "Failed to update purchased date in sellleads table."];
                    }

                    logTableInsertion('sellleads',  $this->id);

                    // Copy all sellead images to inventory images
                    $copy_images_result = $this->copySelleadImagesToInventory($this->id, $insert_id);
                    
                    if(!$copy_images_result)
                    {
                    return ["status" => false,"msg"=> "Failed to copy sellead images to inventory."];
                    }

                    return ["status" => true, "id" => $insert_id];
                }
                else
                {
                 return ["status" => true, "id" => null];
                }
        }
        else
        {
            return ["status" => true, "id" => null];
        }
    }

    public function isVehicleExchaged($inv_id)
    {
        $status = 1;
        $inv_query = "SELECT COUNT(*) AS cnt FROM `inventory` where selllead_id = '".mysqli_real_escape_string($this->connection,$this->id)."'";
        $inv_res = mysqli_query($this->connection,$inv_query);
        if(!$inv_res)
        {
            logSqlError(mysqli_error($this->connection), $inv_query, 'exchange-addlead');
            return false;
        }

        $cnt_res = mysqli_fetch_assoc($inv_res);
        $count = $cnt_res['cnt'];

        if($this->is_exchange === 'y' && $count == 1)
        {
            $query = "INSERT INTO exchange (inventory_id,selllead_id,status,file_doc1,
                      file_doc2,created_by)
                      VALUES (
                        '".mysqli_real_escape_string($this->connection,$inv_id)."',
                        '".mysqli_real_escape_string($this->connection,$this->id)."',
                        '".mysqli_real_escape_string($this->connection,$status)."',
                        '".mysqli_real_escape_string($this->connection,$this->file_doc1)."',
                        '".mysqli_real_escape_string($this->connection,$this->file_doc2)."',
                        '".mysqli_real_escape_string($this->connection,$this->logged_user_id)."'
                      )";
            $res = mysqli_query($this->connection,$query);
            if(!$res)
            {
                logSqlError(mysqli_error($this->connection), $query, 'exchange-addlead');
                return false;
            }
            return true;
        }
        else
        {
            return true;
        }
    }



    
    /**
     * Copy evaluation data from sellleads_evaluation to inventory cert_manual_responses
     * This preserves the evaluation checklist data when a lead is purchased
     */
    private function copyEvaluationDataToInventory($selllead_id, $inventory_id)
    {
        // Sanitize inputs
        $selllead_id = (int) mysqli_real_escape_string($this->connection, $selllead_id);
        $inventory_id = (int) mysqli_real_escape_string($this->connection, $inventory_id);
        
        // Get evaluation data from sellleads_evaluation table
        $eval_query = "SELECT template_id, checklist 
                       FROM sellleads_evaluation 
                       WHERE selllead_id = $selllead_id AND status = 1";
        
        $eval_result = mysqli_query($this->connection, $eval_query);
        
        if (!$eval_result) {
            logSqlError(mysqli_error($this->connection), $eval_query, 'sellleads-get-evaluation');
            return false;
        }
        
        // If no evaluation data found, that's okay - just skip
        if (mysqli_num_rows($eval_result) == 0) {
            return true;
        }
        
        // Get template details from evaluation_templates table
        $template_query = "SELECT id, template_name, template_description 
                          FROM evaluation_templates 
                          WHERE status = 1";
        $template_result = mysqli_query($this->connection, $template_query);
        
        $templates = [];
        if ($template_result) {
            while ($temp_row = mysqli_fetch_assoc($template_result)) {
                $templates[$temp_row['id']] = $temp_row;
            }
        }
        
        // Get base checklist structure from evaluation_checklist
        $checklist_structure = [];
        
        // Get all checklist items
        $items = [];
        $item_query = "SELECT * FROM evaluation_checklist_items WHERE active = 'y'";
        $item_res = mysqli_query($this->connection, $item_query);
        if ($item_res) {
            while ($item_row = mysqli_fetch_assoc($item_res)) {
                $item_row['selected'] = false;
                $item_row['checkoptions'] = [];
                $items[$item_row['checklist_id']][] = $item_row;
            }
        }
        
        // Get checklist sections
        $section_query = "SELECT * FROM evaluation_checklist WHERE active = 'y' ORDER BY sort_order ASC";
        $section_res = mysqli_query($this->connection, $section_query);
        if ($section_res) {
            while ($row = mysqli_fetch_assoc($section_res)) {
                $checklist_structure[$row['evaluation_type']]['sections'][] = [
                    "section_id" => $row['id'],
                    "section_name" => $row['section_name'],
                    "sort_order" => $row['sort_order'],
                    "items" => isset($items[$row['id']]) ? $items[$row['id']] : []
                ];
            }
        }
        
        // Build evaluation data structure matching Purchase Master format
        $evaluation_data = [
            'templates' => $templates, // Full template details, not just IDs
            'checklist' => $checklist_structure,
            'mapped_template' => []
        ];
        
        // Process each evaluation record
        while ($eval_row = mysqli_fetch_assoc($eval_result)) {
            $template_id = $eval_row['template_id'];
            $checklist = json_decode($eval_row['checklist'], true);
            
            if (is_array($checklist)) {
                $evaluation_data['mapped_template'][] = $template_id;
                // Replace base structure with filled data
                $evaluation_data['checklist'][$template_id]['sections'] = $checklist;
            }
        }
        
        // Convert to JSON
        $cert_responses_json = json_encode($evaluation_data, JSON_UNESCAPED_UNICODE);
        
        // Update inventory table with evaluation data
        $update_query = "UPDATE inventory 
                        SET cert_manual_responses = '" . mysqli_real_escape_string($this->connection, $cert_responses_json) . "'
                        WHERE id = $inventory_id";
        
        $update_result = mysqli_query($this->connection, $update_query);
        
        if (!$update_result) {
            logSqlError(mysqli_error($this->connection), $update_query, 'inventory-copy-evaluation');
            return false;
        }
        
        return true;
    }



public function copySelleadImagesToInventory($selllead_id, $inventory_id)
    {
        // Sanitize inputs
        $selllead_id = (int) mysqli_real_escape_string($this->connection, $selllead_id);
        $inventory_id = (int) mysqli_real_escape_string($this->connection, $inventory_id);
        
        // First, get all images from sellleads_images for this lead
        $select_query = "SELECT 
            image, default_image, image_sno, image_tag, 
            approval_status, approved_by, uploaded_by, status
        FROM sellleads_images 
        WHERE selllead_id = $selllead_id AND status = 1";
        
        $select_result = mysqli_query($this->connection, $select_query);
        
        if (!$select_result) {
            logSqlError(mysqli_error($this->connection), $select_query, 'sellleads-select-images');
            return false;
        }
        
        // If no images found, that's still considered success
        if (mysqli_num_rows($select_result) == 0) {
            return true;
        }
        
        // Insert each image into inventory_images
        while ($image_row = mysqli_fetch_assoc($select_result)) {
            $insert_query = "INSERT INTO inventory_images (
                inventory_id, image, default_image, image_sno, image_tag,
                approval_status, approved_by, uploaded_by, status, created_date
            ) VALUES (
                '$inventory_id',
                '" . mysqli_real_escape_string($this->connection, $image_row['image']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['default_image']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['image_sno']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['image_tag']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['approval_status']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['approved_by']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['uploaded_by']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['status']) . "',
                '" . mysqli_real_escape_string($this->connection, $image_row['created_date']) . "'
            )";
            
            $insert_result = mysqli_query($this->connection, $insert_query);
            
            if (!$insert_result) {
                logSqlError(mysqli_error($this->connection), $insert_query, 'inventory-copy-images');
                return false;
            }
        }
        
        return true;
    }




    public function insertSellleadsHistory(): bool
    {
        // Ensure lead ID is valid
        if (empty($this->id) || $this->id <= 0) {
            logSqlError('Invalid or missing lead ID', '', 'sellleads-insertSellleadsHistory', true);
            return false;
        }

        // Sanitize lead ID
        $leadId = (int)$this->id;

        // Define fields to copy from sellleads to sellleads_history (based on provided query)
        $fieldsToCopy = [
            'status',
            'sub_status',
            'price_customer',
            'price_quote',
            'price_expenses',
            'price_margin',
            'price_selling',
            'price_agreed',
            'price_indicative',
            'token_amount',
            'evaluation_type',
            'evaluation_place',
            'evaluation_done',
            'followup_date',
            'remarks'
        ];

        // Sanitize field names for the query
        $selectFields = array_map(function ($field) {
            return "`" . mysqli_real_escape_string($this->connection, $field) . "`";
        }, $fieldsToCopy);

        // Additional fields for sellleads_history
        $historyFields = [
            'selllead_id',
            'created',
            'created_by'
        ];

        // Values for additional fields
        $selectValues = [
            "'$leadId'",
            "'" . mysqli_real_escape_string($this->connection, date('Y-m-d H:i:s')) . "'",
            "'" . mysqli_real_escape_string($this->connection, (int)$this->logged_user_id) . "'"
        ];

        // Combine fields for the INSERT query
        $allFields = array_merge($historyFields, $fieldsToCopy);
        $allFieldsSql = implode(", ", array_map(function ($field) {
            return "`" . mysqli_real_escape_string($this->connection, $field) . "`";
        }, $allFields));

        // Combine select values and fields
        $selectSql = implode(", ", array_merge($selectValues, $selectFields));

        // Build the INSERT ... SELECT query
        $query = "INSERT INTO sellleads_history ($allFieldsSql) 
                SELECT $selectSql 
                FROM sellleads 
                WHERE id = $leadId 
                LIMIT 1";

        // Execute the query
        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-insertSellleadsHistory', true);
            return false;
        }

        // Check if a row was inserted
        if (mysqli_affected_rows($this->connection) === 0) {
            logSqlError('No lead found for ID: ' . $leadId, $query, 'sellleads-insertSellleadsHistory', true);
            return false;
        }
        return true;
    }

// export functionality not in use
      public function exportSellleads(){
        $main_headers = [
            ['name' => 'Lead Info', 'colspan' => 2],
            ['name' => 'Customer Details',  'colspan' => 3],
            ['name' => 'Vehicle Details',    'colspan' => 14],
            ['name' => 'Other Details',  'colspan' => 10],
        ];

        $headers = [
            ['name' => 'ID', 'type' => 'number', 'value' => 'id'],
            ['name' => 'Status', 'type' => 'string', 'value' => 'status_name'],
            ['name' => 'Customer Name', 'type' => 'string', 'value' => 'customer_name'],
            ['name' => 'Customer Mobile', 'type' => 'string', 'value' => 'mobile'],
            ['name' => 'Customer Email', 'type' => 'string', 'value' => 'email'],
            ['name' => 'Registration Type', 'type' => 'string', 'value' => 'reg_type', 'config' => 'reg_type'],
            ['name' => 'Registration Number', 'type' => 'string', 'value' => 'reg_num'],
            ['name' => 'Registration Date', 'type' => 'datetime', 'value' => 'reg_date'],
            ['name' => 'Car Type', 'type' => 'string', 'value' => 'car_type', 'config' => 'car_type'],
            ['name' => 'Make', 'type' => 'string', 'value' => 'make_name'],
            ['name' => 'Model', 'type' => 'string', 'value' => 'model_name'],
            ['name' => 'Variant', 'type' => 'string', 'value' => 'variant_name'],
            ['name' => 'Maufacturing Year', 'type' => 'number', 'value' => 'mfg_year'],
            ['name' => 'Manufacturing Month', 'type' => 'number', 'value' => 'mfg_month', 'config' => 'months'],
            ['name' => 'Chassis Number', 'type' => 'string', 'value' => 'chassis'],
            ['name' => 'Transmission', 'type' => 'string', 'value' => 'transmission', 'config' => 'transmission'],
            ['name' => 'Fuel Type', 'type' => 'string', 'value' => 'fuel', 'config' => 'fuel'],
            ['name' => 'Mileage', 'type' => 'number', 'value' => 'mileage'],
            ['name' => 'Color', 'type' => 'string', 'value' => 'color', 'config' => 'colors'],
            ['name' => 'PinCode', 'type' => 'string', 'value' => 'pin_code'],
            ['name' => 'City', 'type' => 'string', 'value' => 'city_name'],
            ['name' => 'State', 'type' => 'string', 'value' => 'state_name'],
            ['name' => 'Address', 'type' => 'string', 'value' => 'address'],
            ['name' => 'Hypothetication', 'type' => 'boolean', 'value' => 'hypothecation'],
            ['name' => 'Insurance Type', 'type' => 'string', 'value' => 'insurance_type', 'config' => 'insurance_type'],
            ['name' => 'Insurance Expiry Date', 'type' => 'datetime', 'value' => 'insurance_exp_date'],
            ['name' => 'Source', 'type' => 'string', 'value' => 'source_name'],
            ['name' => 'Sub Source', 'type' => 'string', 'value' => 'source_sub_name']
        ];

        $where = "WHERE (executive = " . $this->logged_user_id . " OR user = " . $this->logged_user_id . ") ";
        $query = "SELECT a.*, 
            CONCAT_WS(' ', a.title, a.first_name, a.last_name) AS customer_name,
            " . buildSqlCaseFromConfig('a.status', $this->commonConfig['pm_status']) . " AS status_name,
            IFNULL(v.make, '') AS make_name,
            IFNULL(v.model, '') AS model_name,
            IFNULL(v.variant, '') AS variant_name,
            IFNULL(msa.cw_city, '') AS city_name,
            IFNULL(msa.cw_state, '') AS state_name,
            IFNULL(src.source, '') AS source_name,
            IFNULL(srcs.sub_source, '') AS source_sub_name
            FROM sellleads a
            LEFT JOIN master_variants_new v ON a.variant = v.id
            LEFT JOIN master_states_areaslist msa ON a.pin_code = msa.cw_zip
            LEFT JOIN master_sources src ON a.source = src.id
            LEFT JOIN master_sources_sub srcs ON a.source_sub = srcs.id
            $where
            ORDER BY a.updated DESC";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-export', true);
        }
        $data = [];
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        if (mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
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
        $filename = "sellleads_".date('Ymd_His').".xlsx";
        $url = exportExcelFile($headers, $data, $filename, $main_headers);
        return ['file_url' => $url];
    }

   
    public function saveEvaluation($request)
    {
        $template_id = $request['template_id'];        
        $checklist = $request['checklist'];
        $dec_lead_id = data_decrypt($request['id']);
        $leadId = mysqli_real_escape_string($this->connection, $dec_lead_id);
        // Check if record exists
        $cnt_query = "SELECT id FROM sellleads_evaluation WHERE selllead_id = $leadId AND template_id = $template_id AND status = 1";
        //echo $cnt_query; exit;
        $cnt_res = mysqli_query($this->connection, $cnt_query);
        
        if(mysqli_num_rows($cnt_res) > 0) {
            // UPDATE existing record
            $up_query = "UPDATE sellleads_evaluation SET 
                checklist = '" . mysqli_real_escape_string($this->connection, json_encode($checklist)) . "' 
                WHERE selllead_id = $leadId AND template_id = $template_id AND status = 1";
            
            $update_res = mysqli_query($this->connection, $up_query);
            
            if(!$update_res) {
                logSqlError(mysqli_error($this->connection), $up_query, 'sellleads-saveEvaluation-update', true);
                return false;
            }
            
            // Update evaluation_done field in sellleads table based on checklist count
            $evaluation = [];
            $evaluation['evaluation_type'] = $template_id;
            $evaluation['lead_id'] = $leadId;
            $evaluation['evaluation_date'] = date('Y-m-d');


            $this->updateEvaluationDoneStatus($evaluation);            
            return true;           
        } else {
            // INSERT new record
            $query = "INSERT INTO sellleads_evaluation SET 
                selllead_id = $leadId,
                template_id = $template_id,
                checklist = '" . mysqli_real_escape_string($this->connection, json_encode($checklist)) . "',
                status = 1";
            
            $res = mysqli_query($this->connection, $query);
            
            if(!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'sellleads-saveEvaluation-insert', true);
                return false; // Return immediately if insert failed
            }
            
            $last_insert_id = mysqli_insert_id($this->connection);
            $evaluation = [];
            $evaluation['evaluation_type'] = $template_id;
            $evaluation['lead_id'] = $leadId;
            $evaluation['evaluation_date'] = date('Y-m-d');
            $this->updateEvaluationDoneStatus($evaluation); 
            return true;
        }
        return false;  
    } 
    public function LeadEvaluation($leadId)
    {
        $evaluation = [];
        $check_list = [];
        if (!empty($leadId) && $leadId > 0)
        {
            // Use prepared statement for better security
            $stmt = mysqli_prepare($this->connection, "SELECT * FROM sellleads_evaluation WHERE selllead_id = ? AND status = 1");                
            if ($stmt)
            {
                mysqli_stmt_bind_param($stmt, "i", $leadId);
                mysqli_stmt_execute($stmt);
                $pm_res = mysqli_stmt_get_result($stmt);
                
                if ($pm_res && mysqli_num_rows($pm_res) > 0)
                {                                        
                    while( $pm_row = mysqli_fetch_assoc($pm_res))
                    {
                        $evaluation['mapped_template'][] = $pm_row['template_id'];
                        $evaluation[$pm_row['template_id']] = json_decode($pm_row['checklist'], true);
                        //$evaluation['checklist'][$pm_row['template_id']] = json_decode($pm_row['checklist'], true);
                    }
                }                
            }
        }
        return $evaluation;
    }
    
     public function getEvaluationDataNew($leadId)
    {
        $lead_data = $this->LeadEvaluation($leadId);
        //echo '<pre>'; print_r($lead_data); echo '</pre>'; exit;
        // Use static cache for template and checklist data to avoid repeated queries
      
        static $checklistStructure = null;
        // Get checklist structure with items (cached)
        if ($checklistStructure === null) {
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
                        "maxLength"=> 9,                       
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
}
