<?php
class Exchange
{
    public $connection;   
    public $commonConfig;
    public $login_user_id;
    public $dealer_id;
    public $executive_id;
    public $executive_name;
    public $branch_id;
    public $id;
    public $inventory_id;
    public $selllead_id;
    public $new_make;
    public $new_model;
    public $new_variant;
    public $new_reg_num;
    public $new_chassis;
    public $file_doc1;
    public $file_doc2;
    public $file_doc3;
    public $remarks;
    public $status;
    public $sub_status;
    public $created;
    public $updated;
    public $created_by;
    public $updated_by;

    public function __construct($id = 0) {
        global $connection;
        $this->connection = $connection;
        $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        $this->id = (int)$id;
        $this->inventory_id = null;
        $this->selllead_id = null;
        $this->new_make = null;
        $this->new_model = null;
        $this->new_variant = null;
        $this->new_reg_num = null;
        $this->new_chassis = null;
        $this->file_doc1 = null;
        $this->file_doc2 = null;
        $this->file_doc3 = null;
        $this->remarks = null;
        $this->status = null;
        $this->sub_status = null;
        $this->created = date('Y-m-d H:i:s');
        $this->updated = date('Y-m-d H:i:s');
        $this->created_by = $this->login_user_id;
        $this->updated_by = $this->login_user_id;

        if ($id && is_numeric($id)) {
            $exchange_details = $this->getDetail($id); 
            if (!empty($exchange_details)) {
                $this->id = data_decrypt($exchange_details['id']);
                $this->inventory_id = $exchange_details['inventory_id'] ?? null;
                $this->selllead_id = $exchange_details['selllead_id'] ?? null;
                $this->new_make = $exchange_details['new_make'] ?? null;
                $this->new_model = $exchange_details['new_model'] ?? null;
                $this->new_variant = $exchange_details['new_variant'] ?? null;
                $this->new_reg_num = $exchange_details['new_reg_num'] ?? null;
                $this->new_chassis = $exchange_details['new_chassis'] ?? null;
                $this->file_doc1 = $exchange_details['file_doc1'] ?? null;
                $this->file_doc2 = $exchange_details['file_doc2'] ?? null;
                $this->file_doc3 = $exchange_details['file_doc3'] ?? null;
                $this->remarks = $exchange_details['remarks'] ?? null;
                $this->status = $exchange_details['status'] ?? null;
                $this->sub_status = $exchange_details['sub_status'] ?? null;
                $this->created = $exchange_details['created'] ?? $this->created;
                $this->updated = $exchange_details['updated'] ?? $this->updated;
                $this->created_by = $exchange_details['created_by'] ?? $this->login_user_id;
                $this->updated_by = $exchange_details['updated_by'] ?? $this->login_user_id;
            }
        }
    }

    /**
     * Build base query for exchange data with inventory joins
     */
    private function buildExchangeBaseQuery($includeDetailFields = true)
    {
        // Core fields for list view
        $select_fields = [
            'e.id',
            'e.inventory_id',
            'e.selllead_id',
            'e.status',
            buildSqlCaseFromConfig('e.status', $this->commonConfig['boolean']) . ' AS status_name',
            'e.new_make',
            'e.new_model',
            'e.new_variant',
            'e.new_reg_num',
            'CASE  WHEN e.benefit_flag=1 THEN "Yes"  WHEN e.benefit_flag=2 THEN "No"  ELSE ""  END AS benefit_flag',
            'e.new_chassis',
            'CASE   WHEN e.bonus_price > 0 THEN e.bonus_price   ELSE ""  END AS bonus_price',
            'e.created',
            'e.updated',
            'e.created_by',
            'e.updated_by',
            
            // Inventory fields
            'i.user AS inventory_user',
            'i.executive AS inventory_executive',
            'IFNULL(exec_user.name, \'\') AS executive_name',
            'i.branch AS inventory_branch',
            'IFNULL(db.name, \'\') AS branch_name',
            'i.status AS inventory_status',
            'i.car_type',
            buildSqlCaseFromConfig('i.car_type', $this->commonConfig['car_type']) . ' AS car_type_name',
            'i.make',
            'IFNULL(v.make, \'\') AS make_name',
            'i.model',
            'IFNULL(v.model, \'\') AS model_name',
            'i.variant',
            'IFNULL(v.variant, \'\') AS variant_name',
            'i.reg_num',
            'i.chassis',
            's.source',
            's.title',
            's.first_name',
            's.last_name',
            's.mobile',
            's.email',
            's.state', 's.city', 's.pin_code',
            'IFNULL(msa.cw_state, \'\') AS state_name',
            'IFNULL(msa.cw_city, \'\') AS city_name',
            's.status', buildSqlCaseFromConfig('s.status', $this->commonConfig['exchange_status']) . ' AS status_name',
            'src.source', 'IFNULL(src.source, \'\') AS source_name',
           
        ];

        // Additional fields only needed for detail view
        if ($includeDetailFields) {
            $detail_fields = [
                'e.file_doc1',
                'e.file_doc2',
                'e.file_doc3',
                'e.remarks',
                'i.selllead_id AS inventory_selllead_id',
                'i.buylead_id',
                'i.file_doc1 AS inventory_doc1',
                'i.file_doc2 AS inventory_doc2',
                'i.added_by',
                'i.updated_by',
                'i.added_on',
                'i.updated_on',
                'i.jlr_approval_category',
                'i.date_of_sale',
                'i.date_of_handover',
                'i.date_of_warranty_start',
                'i.cert_manual_responses',
                'i.certified_by',
                'i.certified_date',
                'i.certification_documents'
            ];
           
            //$select_fields = array_merge($select_fields, $detail_fields);
           
        }

        // Common JOINs
        $joins = [
            'LEFT JOIN inventory i ON e.inventory_id = i.id',
            'LEFT JOIN master_variants_new v ON i.variant = v.id',
            'LEFT JOIN dealer_groups d ON i.dealer = d.id',
            'LEFT JOIN users exec_user ON i.executive = exec_user.id',
            'LEFT JOIN dealer_branches db ON i.branch = db.id',
            'LEFT JOIN sellleads as s ON s.id=e.selllead_id',
            'LEFT JOIN master_states_areaslist msa ON s.pin_code = msa.cw_zip',
            'LEFT JOIN master_sources as src ON src.id=s.source'
        ];

        return [
            'select' => 'SELECT ' . implode(",\n            ", $select_fields),
            'from_joins' => 'FROM exchange e ' . implode(' ', $joins)
        ];
    }

    /**
     * Get list of exchanges with pagination
     */
public function getExchanges($filters = [], $current_page = 1, $per_page = 10)
{
    
    if (empty($this->dealer_id)) {
        return [
            'data' => $exchanges,
            'pagination' => [
                'total' => 0,
                'pages' => 0,
                'current_page' => $current_page,
                'per_page' => $per_page,
                'start_count' => 0,
                'end_count' => 0
            ],
            'menu' => []
        ];
    }

    $dealer_id = mysqli_real_escape_string($this->connection, $this->dealer_id);
    $status_filter_applied = false;

    // Build count query for statuses
    $query_cnt = "SELECT 
        e.status AS status_id,
        CASE WHEN (e.status = '2'or e.status = '1') THEN 'Yes' WHEN  e.status = '0' THEN 'No' ELSE '' END AS status_name, 
        COUNT(DISTINCT e.id) AS total_count
        FROM exchange e
        LEFT JOIN inventory i ON e.inventory_id = i.id 
        LEFT JOIN sellleads s ON  s.id=e.selllead_id";
    // Check if we need variant join for filters
    $need_variant_join = false;
    if (!empty($filters)) {
        if (isset($filters['make']) || isset($filters['model']) || isset($filters['variant'])) {
            $need_variant_join = true;
            $query_cnt .= " LEFT JOIN master_variants_new v ON i.variant = v.id";
        }
    }

    $query_cnt .= " WHERE i.dealer = $dealer_id";

    // Build main query using base query builder
    $queryParts = $this->buildExchangeBaseQuery(false); // false = list view
    $query = $queryParts['select'] . " " . $queryParts['from_joins'] . 
             " WHERE i.dealer = $dealer_id ";
   
    // Apply executive condition if applicable
    //$condition = $this->executiveCondition();
    //$query_cnt .= $condition;
    //$query .= $condition;
    
    // Apply filters
    $status_lg='';
    if (!empty($filters)) {
        foreach ($filters as $key => $value) {
            if ($key === 'status') {
                // Exchange table filters
                $exchange_key = array_search($value, $this->commonConfig['exchange_status']);
                $escaped_value = mysqli_real_escape_string($this->connection, $exchange_key);
                $status_lg .= " AND e.$key = '$escaped_value'";
            }else if($key=='customer_name'){
                $condition .=" AND (s.first_name LIKE '%$value%' OR s.last_name LIKE '%$value%' OR CONCAT(s.first_name,' ',s.last_name) LIKE '%$value%')";
            }
            else if($key=='mobile'){
                $condition .=" AND s.mobile='$value'";
            }
            else if($key=='reg_num'){
                $condition .=" AND i.reg_num LIKE '%$value%'";
            }
            else if($key=='chassis'){
                $condition .=" AND e.new_chassis LIKE '%$value%'";
            }else if($key=='date'){
                $condition .=" AND date(e.created)='$value'";
            }
           else {
                // Generic inventory field filter
                $escaped_value = mysqli_real_escape_string($this->connection, $value);
                $condition .= " AND i.$key = '$escaped_value'";
            }
        }
    }
    //  echo $condition;
    //  exit;
    $query_cnt .= $condition;
    $query .= $condition;

    // Execute count query
    $query_cnt .= " GROUP BY e.status";
    // echo $query_cnt;
    // exit;
    $res_cnt = mysqli_query($this->connection, $query_cnt);
    if (!$res_cnt) {
        logSqlError(mysqli_error($this->connection), $query_cnt, 'exchange-getexchanges', true);
        return [
            'data' => $exchanges,
            'pagination' => [],
            'menu' => []
        ];
    }

    $status_counts = [];
    $total = 0;
    $filtered_total = 0;
   
    // Process status counts
    while ($row_cnt = mysqli_fetch_assoc($res_cnt)) {
        $status_counts[$row_cnt['status_id']] = $row_cnt;
        $total += (int)$row_cnt['total_count'];
    }
    
    // Check if status filter is applied
    if (!empty($filters['status'])) {
        $status_id = intval($filters['status']);
        if (isset($status_counts[$status_id])) {
            $filtered_total = (int)$status_counts[$status_id]['total_count'];
            $status_filter_applied = true;
        } else {
            $filtered_total = 0;
            $status_filter_applied = true;
        }
    }
    // Use filtered total if status filter is applied, otherwise use total
    $pagination_total = $status_filter_applied ? $filtered_total : $total;

    // Pagination
    $pages = ceil($pagination_total / $per_page);
    $start = ($current_page - 1) * $per_page;
    $end_count = min($start + $per_page, $pagination_total);
    $start_count = $pagination_total > 0 ? ($start + 1) : 0;

    $query.=$status_lg;
    $query .= " ORDER BY e.id DESC LIMIT $start, $per_page";
    //echo $query;exit;
    // Execute main query
    $result = mysqli_query($this->connection, $query);
    if (!$result) {
        logSqlError(mysqli_error($this->connection), $query, 'exchange-getexchanges', true);
        return [
            'data' => $exchanges,
            'pagination' => [],
            'menu' => []
        ];
    }

    while ($row = mysqli_fetch_assoc($result)) {
        // Store numeric ID
        $numeric_id = $row['id'];
        $row['numeric_id'] = $numeric_id;
        $row['formatted_id'] = "EX{$numeric_id}";
        // Encrypt main id
        $row['id'] = data_encrypt($numeric_id);

        $exchanges[] = $row;
    }

    // Build menu
    $menu = [
        'all' => [
            'status_id' => 'all',
            'label' => 'All',
            'is_active' => 'y',
            'count' => $total
        ],
        'pending' => [
            'status_id' => '1',
            'label' => 'Pending',
            'is_active' => 'y',
            'count' => isset($status_counts['1']) ? (int)$status_counts['1']['total_count'] : 0
        ],
        'trade-in' => [
            'status_id' => '2',
            'label' => 'Trade-in',
            'is_active' => 'y',
            'count' => isset($status_counts['2']) ? (int)$status_counts['2']['total_count'] : 0
        ]
    ];

    return [
        'pagination' => [
            'total' => $pagination_total,
            'pages' => $pages,
            'current_page' => (int)$current_page,
            'per_page' => $per_page,
            'start_count' => $start_count,
            'end_count' => $end_count
        ],
        'menu' => $menu,
        'list' => $exchanges
    ];
}

/**
 * Add executive condition for queries
 */
private function executiveCondition()
{
    if ($this->executive_id > 0 && $this->dealer_id != $this->executive_id) {
        return " AND i.executive = " . intval($this->executive_id);
    }
    return '';
}
    /**
     * Get individual exchange detail
     */
    public function getDetail($id = null)
    {
        $exchange_id = $id ?? $this->id;
        
        if (!$exchange_id || !is_numeric($exchange_id)) {
            return [];
        }
        
        $exchange_id = intval($exchange_id);
        
        // Build query with all detail fields
        $queryParts = $this->buildExchangeBaseQuery(true); // true = include detail fields
        $query = $queryParts['select'] . " " . $queryParts['from_joins'] . 
                 " WHERE e.id = $exchange_id LIMIT 1";
        
        $result = mysqli_query($this->connection, $query);
        
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'exchange-getdetail', true);
            return [];
        }
        
        if (mysqli_num_rows($result) === 0) {
            return [];
        }
        
        $row = mysqli_fetch_assoc($result);
        
        // Store numeric ID
        $numeric_id = $row['id'];
        $row['numeric_id'] = $numeric_id;
        $row['formatted_id'] = "EX{$numeric_id}";
        
        // Encrypt main id
        $row['id'] = data_encrypt($numeric_id);
       
        return $row;
    }

    /**
     * Check if user has access to this exchange
     */
    public function ownerCheck($exchangeId)
    {
        try {
            if (empty($exchangeId) || $exchangeId <= 0) {
                return false;
            }
            
            if (empty($this->dealer_id) || $this->dealer_id <= 0) {
                return false;
            }
            
            $exchange_id = (int)$exchangeId;
            $dealer_id = (int)$this->dealer_id;
            
            $query = "SELECT COUNT(*) AS cnt 
                      FROM exchange e
                      INNER JOIN inventory i ON e.inventory_id = i.id
                      WHERE e.id = $exchange_id AND i.dealer = $dealer_id";
            
            // Add executive condition if needed
            if (!empty($this->executive_id) && $this->executive_id > 0 && $this->dealer_id != $this->executive_id) {
                $exec_id = (int)$this->executive_id;
                $query .= " AND i.executive = $exec_id";
            }
            
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'exchange-ownercheck', true);
                return false;
            }
            
            $row = mysqli_fetch_assoc($res);
            if (!$row || !isset($row['cnt'])) {
                return false;
            }
            
            return ($row['cnt'] > 0);
            
        } catch (Exception $e) {
            return false;
        }
    }
    /**
     * Check new car vin exists to any other vehicle?
     */
    public function checkNewCarVinExists($data){

        $dec_exch_id = $data['id']?data_decrypt($data['id']):'';
        $new_chassis=$data['new_chassis']?? '';
        $checkQuery = "SELECT id FROM exchange WHERE new_chassis = '$new_chassis' and id!=".$dec_exch_id;
        $checkResult = mysqli_fetch_assoc(mysqli_query($this->connection, $checkQuery));
        if (isset($checkResult['id']) && $checkResult['id']>=1) {
            return true;
        }else{
       
            return false;
        }

    }
    /**
     * Update New Car Vin & Bonus Price
     */
    public function updateNewcarVinBonus($data){

        $dec_exch_id = $data['id']?data_decrypt($data['id']):'';
        $new_chassis=$data['new_chassis']?? '';
        $benefit_flag=$data['benefit_flag']?? 0;
        $bonus_price=$data['bonus_price']?? '';
        if (empty($dec_exch_id)) {
            api_response(400,"fail","Exchange ID required");
        }
        $query = "update exchange set new_chassis='$new_chassis',
                                      benefit_flag='$benefit_flag',
                                      bonus_price='$bonus_price',
                                      status=2 
                                      where id=".$dec_exch_id;
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            return [
                "status"=>400,
                "message"=>"Failed to add new Car Data:".mysqli_error($this->connection)
            ];
            exit;
        }
        return [
            "status"=>200,
            "message"=>"New Car data added successsfully"
        ];
       
    }

   
    // export functionality not in use
       public function exportExchangeleads(){
        $main_headers = [
            ['name' => 'Lead Info', 'colspan' => 2],
            ['name' => 'Customer Details',  'colspan' => 3],
            ['name' => 'Used Vehicle Details',    'colspan' => 14],
            ['name' => 'New Vehicle Details',  'colspan' => 10],
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
            ['name' => 'Sub Source', 'type' => 'string', 'value' => 'source_sub_name'],
            ['name' => 'New Car VIN', 'type' => 'string', 'value' => 'new_chassis'],
            ['name' => 'Offer Exchange Benefit', 'type' => 'string', 'value' => 'benefit_flag'],
            ['name' => 'Exchange Bonus', 'type' => 'string', 'value' => 'bonus_price']
        ];

        $where = "WHERE (i.executive = " . $this->logged_user_id . " OR i.user = " . $this->logged_user_id . ") ";
        $query = "SELECT e.id,e.new_chassis,e.bonus_price,s.mobile,s.email,CASE  WHEN e.benefit_flag=1 THEN 'Yes'  WHEN e.benefit_flag=2 THEN 'No'  ELSE ''  END AS benefit_flag,s.pin_code,s.address,s.source,s.source_sub,i.reg_type,i.reg_num,i.reg_date,i.car_type,i.mfg_year,i.mfg_month,i.transmission,i.fuel,i.mileage,i.color,i.hypothecation,i.insurance_type,i.insurance_exp_date,i.chassis,
            CONCAT_WS(' ', s.title, s.first_name, s.last_name) AS customer_name,
            " . buildSqlCaseFromConfig('e.status', $this->commonConfig['exchange_status']) . " AS status_name,
            IFNULL(v.make, '') AS make_name,
            IFNULL(v.model, '') AS model_name,
            IFNULL(v.variant, '') AS variant_name,
            IFNULL(msa.cw_city, '') AS city_name,
            IFNULL(msa.cw_state, '') AS state_name,
            IFNULL(src.source, '') AS source_name,
            IFNULL(srcs.sub_source, '') AS source_sub_name
            FROM exchange e
            LEFT JOIN sellleads as s ON s.id=e.selllead_id
            LEFT JOIN inventory as i ON i.id=e.inventory_id
            LEFT JOIN master_variants_new v ON v.id = s.variant
            LEFT JOIN master_states_areaslist msa ON s.pin_code = msa.cw_zip
            LEFT JOIN master_sources src ON src.id=s.source
            LEFT JOIN master_sources_sub srcs ON srcs.id=s.source_sub
            $where
            ORDER BY e.updated DESC";
            // echo $query;
            // exit;
        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'exchange-export', true);
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
        $filename = "exchange_".date('Ymd_His').".xlsx";
        $url = exportExcelFile($headers, $data, $filename, $main_headers);
        return ['file_url' => $url];
    }
   
}
?>