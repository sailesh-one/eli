<?php
class Buyleads
{
 
    // Database & config
    public $connection;
    public $commonConfig;

    // Logged-in user/dealer/branch,executive context
    public $logged_user_id;
    public $logged_dealer_id;
    public $logged_branch_id;
    public $logged_executive_id;

    // Lead properties (all table columns)
    public $id;
    public $title;
    public $dealer;
    public $branch;
    public $user;
    public $executive;
    public $first_name;
    public $last_name;
    public $contact_name;
    public $mobile;
    public $email;
    public $state;
    public $city;
    public $source;
    public $source_sub;
    public $status;
    public $sub_status;
    public $followup_date;
    public $address;
    public $pin_code;
    public $lead_classification;
    public $buying_horizon;
    public $customer_visited;
    public $customer_visited_date;
    public $remarks;
    public $token_amount;
    public $paid_amount;
    public $sold_vehicle;
    public $price_sold;
    public $price_indicative;
    public $price_customer;
    public $price_quote;
    public $price_margin;
    public $price_agreed;
    public $sold_by;
    public $sold_date;
    public $booking_date;
    public $booked_vehicle;
    public $test_drive_date;
    public $test_drive_completed_date;
    public $test_drive_vehicle;
    public $test_drive_place;
    public $test_drive_status;
    public $test_drive_done;
    public $form_doc;
    public $delivery_date;
    public $order_id;
    public $buyer_type;
    public $finance;
    public $budget_range;
    public $file_doc1;
    public $file_doc2;
    public $created_by;
    public $updated_by;
    public $created;
    public $updated;
    public $common_status;

    public function __construct($id = 0)
    {
        global $connection;
        $this->connection = $connection;
        $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        $this->id = (int) $id;
        $this->common_status = $this->commonConfig['common_statuses']['sm'];
    }
  
    private function executiveCondition()
    {
        if ($logged_executive_id > 0 && $this->logged_dealer_id != $logged_executive_id) {
            return " AND a.executive = " . intval($logged_executive_id);
        }
        return '';
    }

    public function statusnametoID($val)
    {
        $raw = trim((string)$val);
    
        if ($raw === '') return null;

        if (is_numeric($raw)) return intval($raw);

        $statuses = $this->getStatuses();
        $slug = strtolower(str_replace(' ', '-', $raw));
        if (isset($statuses[$slug]) && !empty($statuses[$slug]['status_id'])) {
            return intval($statuses[$slug]['status_id']);
        }
        return null;
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

        $dealer_id    = (int)$this->logged_dealer_id;
        $current_date = date('Y-m-d');
        $offset       = max(0, ($current_page - 1) * $per_page);

        // --- Executive filter ---
        $branches = getUsersByBranchIds($this->logged_branch_id);
        $userIds = array_unique(array_filter(array_merge(
            ...array_map(fn($b) => array_column($b['executives'] ?? [], 'id'), $branches)
        )));
        $where = ["a.dealer = $dealer_id"];
        
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

        // --- Dynamic search filters ---
        $listFilters = [];
        foreach ($filters as $key => $val) {
            if ($val === '' || $val === null) continue;
            $val = mysqli_real_escape_string($this->connection, $val);
            switch ($key) {
                case 'buyer_name':
                    $listFilters[] = "(a.first_name LIKE '%$val%' OR a.last_name LIKE '%$val%' OR CONCAT(a.first_name,' ',a.last_name) LIKE '%$val%')";
                    break;
                case 'id':
                    $num = (int)preg_replace('/^SM/i', '', $val);
                    $listFilters[] = "(a.id LIKE '%$val%' OR a.id = $num)";
                    break;
                case 'mobile':
                    $listFilters[] = "a.mobile LIKE '%$val%'";
                    break;
                case 'email':
                    $listFilters[] = "a.email LIKE '%$val%'";
                    break;
                case 'lead_classification':
                    $listFilters[] = "a.lead_classification LIKE '%$val%'";
                    break;
                case 'branch':
                    $listFilters[] = "a.branch LIKE '%$val%'";
                    break;
                case 'executive':
                    $listFilters[] = "a.executive LIKE '%$val%'";
                    break;
                case 'test_drive_done':
                    $listFilters[] = "a.test_drive_done = '$val'";
                    break;
                case 'make':
                    $valInt = intval($val);
                    $listFilters[] = "EXISTS (
                        SELECT 1 
                        FROM buyleads_vehicles bv 
                        WHERE bv.buylead = a.id AND bv.make = $valInt
                    )";
                    break;
                case 'model':
                    $valInt = intval($val);
                    $listFilters[] = "EXISTS (
                        SELECT 1 
                        FROM buyleads_vehicles bv 
                        WHERE bv.buylead = a.id AND bv.model = $valInt
                    )";
                    break;
            }
        }

        $smStatus = [
            '1' => 'Fresh',
            '2' => 'Follow up',
            'testdrive' => 'Test Drive',
            '3' => 'Booked',
            '4' => 'Sold',
            '5' => 'Lost'
        ];

        $followupSub = [
            'followup-overdue'  => ['followup_date' => '<='],
            'followup-today'    => ['followup_date' => '='],
            'followup-upcoming' => ['followup_date' => '>']
        ];

        $testdriveSub = [
            'testdrive-overdue'  => ['scheduled_date' => '<='],
            'testdrive-today'    => ['scheduled_date' => '='],
            'testdrive-upcoming' => ['scheduled_date' => '>']
        ];

        $filterWhere = array_merge($where, $listFilters);

        // --- Counts for buckets ---
        $counts = [];
        foreach ($smStatus as $sid => $label) {
            $w = $filterWhere;

            if ($sid === 'testdrive') {
                $testDriveWhere = $filterWhere;
                $testDriveWhere[] = "(a.status = " . $this->common_status['status']['followup'] . ")";
                $testDriveWhere[] = "(b.test_drive_status = 1)"; // Active scheduled test drive

                $sqlMain = "
                    SELECT COUNT(DISTINCT a.id) AS total
                    FROM buyleads a
                    INNER JOIN buyleads_test_drive_vehicles b ON a.id = b.buylead_id
                    WHERE " . implode(" AND ", $testDriveWhere);

                $resMain = mysqli_query($this->connection, $sqlMain);
                $count = ($resMain && $r = mysqli_fetch_assoc($resMain)) ? (int)$r['total'] : 0;

                $counts[$sid] = [
                    'status_id' => $sid,
                    'label'     => $label,
                    'count'     => $count
                ];

                // Sub-status mapping for test drive (based on scheduled_date)
                $counts[$sid]['sub'] = [];
                foreach ($testdriveSub as $subKey => $_) {
                    $subWhere = $testDriveWhere;
                    foreach ($_ as $col => $op) {
                        if (strpos($subKey, 'overdue') !== false) {
                            $subWhere[] = "(b.$col IS NULL OR DATE(b.$col) < '$current_date')";
                        } else {
                            $subWhere[] = "b.$col IS NOT NULL AND DATE(b.$col) $op '$current_date'";
                        }
                    }

                    $sqlSub = "
                        SELECT COUNT(DISTINCT a.id) AS total
                        FROM buyleads a
                        INNER JOIN buyleads_test_drive_vehicles b ON a.id = b.buylead_id
                        WHERE " . implode(" AND ", $subWhere);

                    $resSub = mysqli_query($this->connection, $sqlSub);
                    $counts[$sid]['sub'][$subKey] = [
                        'label' => ucwords(str_replace(['-', '_'], ' ', $subKey)),
                        'count' => ($resSub && $rSub = mysqli_fetch_assoc($resSub)) ? (int)$rSub['total'] : 0
                    ];
                }

                continue;
            }
            elseif ($sid == $this->common_status['status']['followup']) {
                // Follow-up leads excluding pending test drive
                $w[] = "(
                    a.status = ".$this->common_status['status']['followup']."
                    AND (
                        a.sub_status <> ".$this->common_status['sub_status']['test_drive_scheduled']."
                        OR (a.sub_status = ".$this->common_status['sub_status']['test_drive_scheduled']." AND a.test_drive_done = 'y')
                    )
                )";
            } else {
                $w[] = "a.status = $sid";
            }

            $sqlMain = "SELECT COUNT(*) AS total FROM buyleads a WHERE " . implode(" AND ", $w);
            $resMain = mysqli_query($this->connection, $sqlMain);
            $count = ($resMain && $r = mysqli_fetch_assoc($resMain)) ? (int)$r['total'] : 0;

            $counts[$sid] = [
                'status_id' => $sid,
                'label'     => $label,
                'count'     => $count
            ];

            // Keep your existing sub-status logic intact
            $key = strtolower(str_replace(' ', '', $label));
            $subMap = $key === 'followup' ? $followupSub : ($key === 'testdrive' ? $testdriveSub : []);
            if ($subMap) {
                $counts[$sid]['sub'] = [];
                foreach ($subMap as $subKey => $_) {
                    $subWhere = $w;
                    foreach ($_ as $col => $op) {
                        if (strpos($subKey, 'overdue') !== false) {
                            $subWhere[] = "(a.$col IS NULL OR DATE(a.$col) < '$current_date')";
                        } else {
                            $subWhere[] = "a.$col IS NOT NULL AND DATE(a.$col) $op '$current_date'";
                        }
                    }
                    $sqlSub = "SELECT COUNT(*) AS total FROM buyleads a WHERE " . implode(" AND ", $subWhere);
                    $resSub = mysqli_query($this->connection, $sqlSub);
                    $counts[$sid]['sub'][$subKey] = [
                        'label' => ucwords(str_replace(['-', '_'], ' ', $subKey)),
                        'count' => ($resSub && $rSub = mysqli_fetch_assoc($resSub)) ? (int)$rSub['total'] : 0
                    ];
                }
            }
        }

        // --- All tab count ---
        $resAll = mysqli_query($this->connection, "SELECT COUNT(*) AS total FROM buyleads a WHERE " . implode(" AND ", $filterWhere));
        $allCount = ($resAll && $r = mysqli_fetch_assoc($resAll)) ? (int)$r['total'] : 0;

          // Calculate Active Leads count (Fresh + Follow up + Test Drive + Booked)
        $activeLeadsCount = 0;
        foreach ($counts as $sid => $data) {
            $key = strtolower(str_replace(' ', '', $data['label']));
            // Sum up Fresh, Follow up, Test Drive, and Booked
            if (in_array($key, ['fresh', 'followup', 'testdrive', 'booked'])) {
                $activeLeadsCount += $data['count'];
            }
        }

        // --- Build menu ---
        $menu = [
            'all' => ['status_id' => 'all', 'label' => 'All', 'count' => $allCount, 'is_active' => (empty($filters['status']) && empty($filters['active_bucket']) || $filters['status'] === 'all') ? 'y' : ''],
            'active-leads' => ['status_id' => 'active', 'label' => 'Active Leads', 'count' => $activeLeadsCount, 'is_active' => !empty($filters['active_bucket']) ? 'y' : '']
        ];
        foreach ($counts as $sid => $data) {
            $menuItem = [
                'status_id' => $data['status_id'],
                'label'     => $data['label'],
                'count'     => $data['count']
            ];
            
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
            if (!empty($filters['status']) && empty($filters['sub_status']) && empty($filters['active_bucket']) && $filters['status'] == $sid) {
                $menuItem['is_active'] = 'y';
            }
            
            $menu[strtolower(str_replace(' ', '-', $data['label']))] = $menuItem;
        }

        // --- Apply tab filter for list query ---
        $listWhere = $filterWhere;
        $listJoin = ""; 

        // Special handling for active leads bucket - status IN (1, 2, 3)
        if (!empty($filters['active_bucket'])) {
            $listWhere[] = "a.status IN (1, 2, 3)";  // Fresh + Follow up + Booked
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'testdrive') {
                $listJoin = "INNER JOIN buyleads_test_drive_vehicles b ON a.id = b.buylead_id";

                $listWhere[] = "a.status = " . $this->common_status['status']['followup'];
                $listWhere[] = "b.test_drive_status = 1";
            } elseif ($filters['status'] == $this->common_status['status']['followup']) {
                $listWhere[] = "(
                    a.status = ".$this->common_status['status']['followup']."
                    AND (
                        a.sub_status <> ".$this->common_status['sub_status']['test_drive_scheduled']."
                        OR (a.sub_status = ".$this->common_status['sub_status']['test_drive_scheduled']." AND a.test_drive_done = 'y')
                    )
                )";
            } else {
                $listWhere[] = "a.status = " . (int)$filters['status'];
            }
        }

        if (!empty($filters['sub_status'])) {
            $subKey = $filters['sub_status'];

            if (isset($testdriveSub[$subKey])) {
                if (empty($listJoin)) {
                    $listJoin = "INNER JOIN buyleads_test_drive_vehicles b ON a.id = b.buylead_id";
                }

                foreach ($testdriveSub[$subKey] as $col => $op) {
                    if ($subKey === 'testdrive-overdue') {
                        $listWhere[] = "(b.$col IS NULL OR b.$col = '0000-00-00 00:00:00' OR DATE(b.$col) < '$current_date')";
                    } elseif ($subKey === 'testdrive-today') {
                        $listWhere[] = "b.$col IS NOT NULL AND b.$col <> '0000-00-00 00:00:00' AND DATE(b.$col) = '$current_date'";
                    } elseif ($subKey === 'testdrive-upcoming') {
                        $listWhere[] = "b.$col IS NOT NULL AND b.$col <> '0000-00-00 00:00:00' AND DATE(b.$col) > '$current_date'";
                    }
                }
            }
            elseif (isset($followupSub[$subKey])) {
                foreach ($followupSub[$subKey] as $col => $op) {
                    if ($subKey === 'followup-overdue') {
                        $listWhere[] = "(a.$col IS NULL OR a.$col = '0000-00-00 00:00:00' OR DATE(a.$col) < '$current_date')";
                    } elseif ($subKey === 'followup-today') {
                        $listWhere[] = "a.$col IS NOT NULL AND a.$col <> '0000-00-00 00:00:00' AND DATE(a.$col) = '$current_date'";
                    } elseif ($subKey === 'followup-upcoming') {
                        $listWhere[] = "a.$col IS NOT NULL AND a.$col <> '0000-00-00 00:00:00' AND DATE(a.$col) > '$current_date'";
                    }
                }
            }
        }

        $listWhereSql = !empty($listWhere) ? "WHERE " . implode(" AND ", $listWhere) : "";

        // --- Pagination ---
        $resCount = mysqli_query($this->connection, "SELECT COUNT(DISTINCT a.id) AS total FROM buyleads a $listJoin $listWhereSql");
        $filtered_total = ($resCount && $r = mysqli_fetch_assoc($resCount)) ? (int)$r['total'] : 0;
        $pages = ceil($filtered_total / max(1, $per_page));
        $offset = max(0, ($current_page - 1) * $per_page);

        $listQuery = $this->getLeadBaseQuery(false, true) . "
            $listJoin
            $listWhereSql
            ORDER BY a.updated DESC
            LIMIT $offset, $per_page
        ";

        $rows = mysqli_query($this->connection, $listQuery);
        $leads = [];
        if ($rows) {
            while ($r = mysqli_fetch_assoc($rows)) {
                $r['numeric_id']   = $r['id'];
                $r['formatted_id'] = "SM{$r['id']}";
                $r['id']           = function_exists('data_encrypt') ? data_encrypt($r['id']) : $r['id'];
                $r['documents']    = (object)$this->processDocuments($r);

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

        return [
            'pagination' => [
                'total'        => $filtered_total,
                'pages'        => $pages,
                'per_page'     => $per_page,
                'current_page' => (int)$current_page,
                'start_count'  => $filtered_total ? $offset + 1 : 0,
                'end_count'    => min($offset + $per_page, $filtered_total)
            ],
            'menu' => $menu,
            'list' => $leads
        ];
    }


   // Common lead select base query
   private function getLeadBaseQuery(
    bool $includeDetailFields = false,
    bool $includeVehicleList = false
    ): string
    {
         $selectFields = [
            'a.id',
            'IFNULL(NULLIF(a.dealer, 0), \'\') AS dealer',
            'IFNULL(NULLIF(a.branch, 0), \'\') AS branch',
            'IFNULL(h.name,\'\') AS branch_name',
            'IFNULL(f.name,\'\') AS dealer_name',
            'IFNULL(NULLIF(a.user, 0), \'\') AS user',
            'IFNULL(usrs.name,\'\') AS user_name',
            'IFNULL(NULLIF(a.executive, 0), \'\') AS executive',
            'IFNULL(g.name,\'\') AS executive_name',
            'a.title','a.first_name','a.last_name','IFNULL(a.contact_name,\'\') AS contact_name',
            'a.mobile','a.email',
            'IFNULL(NULLIF(a.state, 0), \'\') AS state',
            'IFNULL(e.cw_state,\'\') AS state_name',
            'IFNULL(NULLIF(a.city, 0), \'\') AS city',
            'IFNULL(e.cw_city,\'\') AS city_name',
            'IFNULL(NULLIF(a.source, 0), \'\') AS source',
            'IFNULL(c.source,\'\') AS source_name',
            'IFNULL(NULLIF(a.source_sub, 0), \'\') AS source_sub',
            'IFNULL(d.sub_source,\'\') AS source_sub_name',
            'IFNULL(NULLIF(a.status, 0), \'\') AS status',
             buildSqlCaseFromConfig('a.status', $this->commonConfig['sm_status']) . ' AS status_name',
            'IFNULL(NULLIF(a.sub_status, 0), \'\') AS sub_status',
             buildSubStatusSqlCase('a.status', 'a.sub_status', $this->commonConfig['sm_sub_status']) . ' AS sub_status_name',
            'a.followup_date',
            'a.address',
            'a.notes',
            'IFNULL(NULLIF(a.pin_code, 0), \'\') AS pin_code',
            'a.lead_classification',
            'a.finance',
             buildSqlCaseFromConfig('a.finance', $this->commonConfig['finance']) . ' AS finance_name',
            'a.budget_range',
             buildSqlCaseFromConfig('a.budget_range', $this->commonConfig['budget_range']) . ' AS budget_range_name',
            'IFNULL(NULLIF(a.color, 0), \'\') AS color',
             buildSqlCaseFromConfig('a.color', $this->commonConfig['colors']) . ' AS color_name',
            'IFNULL(NULLIF(a.mileage_range, 0), \'\') AS mileage_range',
             buildSqlCaseFromConfig('a.mileage_range', $this->commonConfig['mileage_range']) . ' AS mileage_range_name',
            'IFNULL(NULLIF(a.car_age, 0), \'\') AS car_age',
             buildSqlCaseFromConfig('a.car_age', $this->commonConfig['car_age']) . ' AS car_age_name',
            'a.file_doc1',
            'a.file_doc2',
            'a.buyer_type',
             buildSqlCaseFromConfig('a.buyer_type', $this->commonConfig['buyer_type']) . ' AS buyer_type_name',
            'a.created',
            'a.updated',
        ];

        $detailFields = [
            'IFNULL(NULLIF(a.buying_horizon, 0), \'\') AS buying_horizon',
            'IFNULL(a.customer_visited, \'\') AS customer_visited',
            'IFNULL(a.customer_visited_date, \'\') AS customer_visited_date',
            'IFNULL(NULLIF(a.remarks, 0), \'\') AS remarks',
            'IFNULL(a.token_amount, \'\') AS token_amount',
            'IFNULL(a.paid_amount, \'\') AS paid_amount',
            'IFNULL(a.price_sold, \'\') AS price_sold',
            'IFNULL(a.price_indicative, \'\') AS price_indicative',
            'IFNULL(a.price_customer, \'\') AS price_customer',
            'IFNULL(a.price_quote, \'\') AS price_quote',
            'IFNULL(a.price_margin, \'\') AS price_margin',
            'IFNULL(a.price_agreed, \'\') AS price_agreed',
            'IFNULL(NULLIF(a.sold_vehicle, 0), \'\') AS sold_vehicle',
            'IFNULL(NULLIF(a.sold_by, 0), \'\') AS sold_by',
            'IFNULL(su.name,\'\') AS sold_by_name',
            'IFNULL(NULLIF(a.sold_date, 0), \'\') AS sold_date',
            'IFNULL(NULLIF(a.booking_date, 0), \'\') AS booking_date',
            'IFNULL(NULLIF(a.booked_vehicle, 0), \'\') AS booked_vehicle',
            'IFNULL(NULLIF(a.delivery_date, 0), \'\') AS delivery_date',
            'IFNULL(NULLIF(a.order_id, 0), \'\') AS order_id',
        ];

        if ($includeDetailFields) {
            $selectFields = array_merge($selectFields, $detailFields);
        }

        // Add vehicle list field if required
        $vehicleField = $includeVehicleList
            ? ['IFNULL(v.vehicle_list, \'\') AS vehicle_list']
            : [];

        $selectFields = array_merge($selectFields, $vehicleField);
        // Auto-wrap fields with IFNULL() except CASE or existing IFNULL
        $selectFields = array_map(function($field) {
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
        }, $selectFields);
        $selectFieldsSql = implode(',', $selectFields);

        $query = "
            SELECT $selectFieldsSql
            FROM buyleads a
            LEFT JOIN master_sources c ON a.source = c.id AND c.active = 1
            LEFT JOIN master_sources_sub d ON a.source_sub = d.id AND d.active = 1
            LEFT JOIN master_states_areaslist e ON a.pin_code = e.cw_zip
            LEFT JOIN dealer_groups f ON a.dealer = f.id
            LEFT JOIN users g ON a.executive = g.id
            LEFT JOIN users usrs ON a.user = usrs.id
            LEFT JOIN dealer_branches h ON a.branch = h.id
            LEFT JOIN users su ON a.sold_by = su.id
        "; // here

        if ($includeVehicleList) {
            $query .= "
            LEFT JOIN (
                SELECT 
                    bv.buylead,
                    GROUP_CONCAT(DISTINCT
                        CASE WHEN bv.model IS NULL OR bv.model = 0 THEN mv.make
                            ELSE CONCAT(mv.make, ' ', mv.model)
                        END SEPARATOR ', ') AS vehicle_list
                FROM buyleads_vehicles bv
                LEFT JOIN master_variants_new mv 
                    ON bv.make = mv.make_id 
                    AND (bv.model = mv.model_id OR bv.model IS NULL OR bv.model = 0)
                GROUP BY bv.buylead
            ) v ON a.id = v.buylead
            ";
        }

        return $query;
    }

    public function addLead($request)
    {
        $dateNow = date('Y-m-d H:i:s');

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
        $columns = [];
        $values = [];
        foreach ($request as $key => $val) {
            $columns[] = "`" . mysqli_real_escape_string($this->connection, $key) . "`";
            $values[]  = "'" . mysqli_real_escape_string($this->connection, $val) . "'";
        }

        $query = "INSERT INTO buyleads (" . implode(", ", $columns) . ") 
                VALUES (" . implode(", ", $values) . ")";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-addlead', true);
            return false;
        }

        $lastInsertId = mysqli_insert_id($this->connection);

        // Insert into history
        $this->id = $lastInsertId;
        $this->status = 1;
        $this->created_by = $this->logged_user_id;
        $this->created = $dateNow;

        $result = $this->insertBuyleadsHistory();

        if ($lastInsertId > 0 && $result) {
            logTableInsertion('buyleads', $lastInsertId);
            return [['id' => data_encrypt($lastInsertId)]];
        }

        return false;
    }

    public function updateLead($request, $leadId)
    {
        $sets = [];
        foreach ($request as $key => $val) {
            $sets[] = "`" . mysqli_real_escape_string($this->connection, $key) . "` = '" . 
                    mysqli_real_escape_string($this->connection, $val) . "'";
        }

        $sets[] = "`updated` = '" . date('Y-m-d H:i:s') . "'";
        $sets[] = "`updated_by` = " . (int)$this->logged_user_id;

        $query = "UPDATE buyleads SET " . implode(", ", $sets) . 
                " WHERE id = $leadId AND dealer = " . (int)$this->logged_dealer_id;

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-updatelead', true);
        }

        if(mysqli_affected_rows($this->connection) > 0) {
            logTableInsertion('buyleads', $leadId);
            return true;
        }
        return false;
    }


    public function updateLeadStatus($previous_status,$previous_sub_status,$previous_followup_date,$doc1 = null,$doc2 = null)
    {
        $this->file_doc1 = $doc1 ?? ""; 
        $this->file_doc2 = $doc2 ?? "";

        $id = (int) mysqli_real_escape_string($this->connection,$this->id);
        if($this->status < $previous_status)
        {
           return ['status' => false,'msg' => "Updated status should not be earlier than current status.",'field' => 'status'];
        }

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

        if (!empty($this->customer_visited_date) && $this->customer_visited_date !== '0000-00-00') 
        {
            $customerVisitedDate = new DateTime($this->customer_visited_date); 
            $todayDate = new DateTime();

            if ($customerVisitedDate > $todayDate) {
                return [
                    'status' => false,
                    'msg' => "Cutomer visited date can't be later than today.",
                    'field' => 'customer_visited_date'
                ];
            }
        }

        // Temporarily considering booked vehcile as sold vehicle
        if($this->status == $this->common_status['status']['sold'])
        {
          $this->sold_vehicle = $this->booked_vehicle;
        }

        if($this->status == $this->common_status['status']['lost']){ //lost
            $res = $this->changeVehicleStatus($id);
            if(!$res['status']){
                return ["status" => false,"msg"=> $res['msg']];
            }
        }

        $status_update = "UPDATE buyleads SET 
                            status = '".mysqli_real_escape_string($this->connection,$this->status)."',
                            sub_status = '".mysqli_real_escape_string($this->connection,$this->sub_status)."',
                            lead_classification = '".mysqli_real_escape_string($this->connection,$this->lead_classification)."',
                            buying_horizon = '".mysqli_real_escape_string($this->connection,$this->buying_horizon)."',
                            followup_date = '".mysqli_real_escape_string($this->connection,$this->followup_date)."',
                            customer_visited = '".mysqli_real_escape_string($this->connection,$this->customer_visited)."',
                            customer_visited_date = '".mysqli_real_escape_string($this->connection,$this->customer_visited_date)."',
                            booked_vehicle = '".mysqli_real_escape_string($this->connection,$this->booked_vehicle)."',
                            order_id = '".mysqli_real_escape_string($this->connection,$this->order_id)."',
                            sold_vehicle = '".mysqli_real_escape_string($this->connection,$this->sold_vehicle)."',
                            sold_date = '".mysqli_real_escape_string($this->connection,$this->sold_date)."',
                            sold_by = '".mysqli_real_escape_string($this->connection,$this->sold_by)."',
                            price_sold = '".mysqli_real_escape_string($this->connection,$this->price_sold)."',
                            price_indicative = '".mysqli_real_escape_string($this->connection,$this->price_indicative)."',
                            price_customer = '".mysqli_real_escape_string($this->connection,$this->price_customer)."',
                            price_quote = '".mysqli_real_escape_string($this->connection,$this->price_quote)."',
                            price_margin = '".mysqli_real_escape_string($this->connection,$this->price_margin)."',
                            price_agreed = '".mysqli_real_escape_string($this->connection,$this->price_agreed)."',
                            token_amount = '".mysqli_real_escape_string($this->connection,$this->token_amount)."',
                            file_doc1 = '".mysqli_real_escape_string($this->connection,$this->file_doc1)."',
                            file_doc2 = '".mysqli_real_escape_string($this->connection,$this->file_doc2)."',
                            remarks = '".mysqli_real_escape_string($this->connection,$this->remarks)."',
                            updated = '" . date('Y-m-d H:i:s') . "',
                            updated_by = '".$this->logged_user_id."'
                            WHERE id = $id";

        $status_res = mysqli_query($this->connection,$status_update);
        if(!$status_res)
        {
            logSqlError(mysqli_error($this->connection), $status_update, 'buyleads-updateleadstatus');
            return ["status" => false,"msg"=> "Log table insertion failed."];
        }
        // Log insertion
        $result = logTableInsertion("buyleads",$id);
        
        if($this->status != $previous_status || $this->sub_status != $previous_sub_status || $this->followup_date != $previous_followup_date) 
        {
            if(!$this->insertBuyleadsHistory())
            {
               return ["status" => false,"msg"=> "Failed to insert lead in status history table."];
            }
        }

        $inv_id = $this->booked_vehicle;
        if($this->status == $this->common_status['status']['booked']) //Booked
        {
            $inventory_res = $this->isVehicleBooked($inv_id, $id);
            if(!$inventory_res['status'])
            {
                return ["status" => false,"msg"=> "Failed to book vehicle"];
            }
        }

        if($this->status == $this->common_status['status']['sold']) //Sold
        {
            $this->sold_vehicle = $this->booked_vehicle;
            $stock_id  = !empty($this->sold_vehicle) ? (int)$this->sold_vehicle : 0;
            $logged_branch_id = (int)preg_replace('/\D/', '', $logged_branch_id);
            $logged_dealer_id = !empty($this->logged_dealer_id) ? (int)$this->logged_dealer_id : 0;
            $lead_id   = !empty($this->id) ? (int)$this->id : 0;

            $this->order_id = "{$logged_dealer_id}-{$logged_branch_id}-{$lead_id}-{$stock_id}-" . rand(1000, 9999);

            $invoice_res = $this->isVehicleSold($stock_id,$id);

            if(!$invoice_res['status'])
            {
                return ["status" => false,"msg"=> "Failed to sold vehicle"];
            }
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

    public function changeVehicleStatus($id)
    {
        $id = intval($id);
        $vehicle_status = $this->commonConfig['common_statuses']['inventory']['ready_for_sale'];

        // Step 1: Get booked_vehicle (inventory_id) from buyleads
        $get_inv_query = "SELECT booked_vehicle FROM buyleads WHERE id = $id LIMIT 1";
        $inv_res = mysqli_query($this->connection, $get_inv_query);

        if (!$inv_res || mysqli_num_rows($inv_res) === 0) {
            return ['status' => true, 'msg' => 'No booking vehicle found, no update required.'];
        }

        $row = mysqli_fetch_assoc($inv_res);
        $inventory_id = intval($row['booked_vehicle']);

        // If no booked_vehicle, just skip further steps — not an error
        if (empty($inventory_id)) {
            return ['status' => true, 'msg' => 'No booked vehicle associated, skipping update.'];
        }

        // Step 2: Check current inventory status
        $check_query = "SELECT status FROM inventory WHERE id = $inventory_id and buylead_id = $id LIMIT 1";
        $res = mysqli_query($this->connection, $check_query);

        if (!$res || mysqli_num_rows($res) === 0) {
            return ['status' => true, 'msg' => 'Vehicle not found in inventory.'];
        }

        // $status_row = mysqli_fetch_assoc($res);
        // if (intval($status_row['status']) != $this->commonConfig['common_statuses']['inventory']['booked']) {
        //     return ['status' => false, 'msg' => 'Vehicle is not in booked status.'];
        // }

        // Step 3: Update status to ready for sale
        $update_query = "UPDATE inventory SET status = $vehicle_status WHERE id = $inventory_id";
        $update_res = mysqli_query($this->connection, $update_query);

        if (!$update_res) {
            logSqlError(mysqli_error($this->connection), $update_query, 'changeVehicleStatus-update');
            return ['status' => false, 'msg' => 'Failed to update vehicle status.'];
        }

        return ['status' => true, 'msg' => 'Vehicle status updated successfully.'];
    }

    public function isVehicleBooked($id = 0, $buylead_id = 0)
    {
      $id = (int) $id;
      if($this->status == $this->common_status['status']['booked']) //Booked
      {
         $query = "UPDATE inventory SET 
                   status = " . $this->commonConfig['common_statuses']['inventory']['booked']. ",
                   buylead_id = '".mysqli_real_escape_string($this->connection,$buylead_id)."',
                   updated_on = '".date('Y-m-d H:i:s')."',  
                   updated_by = '".$this->logged_user_id."'  
                   WHERE id = $id";
         $res = mysqli_query($this->connection,$query);
         if (!$res) 
         {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-updatevehiclestatus');
            return ['status' => false,'msg' => 'Failed to update booked vehicle status']; 
         }
        logTableInsertion('inventory', $id);
        $history_query = "INSERT INTO inventory_history SET 
                    inventory_id = $id,
                    status = '".$this->commonConfig['common_statuses']['inventory']['booked']."',
                    action = 'Vehicle is Booked',                                                          
                    booked_date = '".date('Y-m-d')."',                                        
                    created = '".date('Y-m-d H:i:s')."',
                    created_by = '".$this->logged_user_id."'
            "; 
        $result = mysqli_query($this->connection,$history_query);
        if (!$result) 
        {
            logSqlError(mysqli_error($this->connection), $history_query, 'buyleads-updatevehiclestatus');
            return ['status' => false,'msg' => 'Failed to update booked vehicle status']; 
        }          
        return ['status' => true,'msg' => 'Booked vehicle status updated in my stock successfully']; 
      }
   }

   public function isVehicleSold($stock_id = 0,$buylead_id)
   {
      $stock_id = (int) $stock_id;
      if($this->status == $this->common_status['status']['sold']) //Sold 
      {
         $id = (int) mysqli_real_escape_string($this->connection,$this->id);

        //  Get mmv data

         $mmv_query = "SELECT make,model,variant,mileage,reg_num FROM inventory
                       WHERE id = $stock_id";

         $mmv_res = mysqli_query($this->connection,$mmv_query);
         if (!$mmv_res) 
         {
            logSqlError(mysqli_error($this->connection), $mmv_query, 'get-vehciledetails');
            return ['status' => false,'msg' => 'Failed to get sold vehicle mmv data']; 
         }

         $mmv_row = mysqli_fetch_assoc($mmv_res);

         $make = $mmv_row['make'] ?? 0; 
         $model = $mmv_row['model'] ?? 0; 
         $variant = $mmv_row['variant'] ?? 0; 
         $mileage = $mmv_row['mileage'] ?? 0; 
         $reg_num = $mmv_row['reg_num'] ?? '0'; 

         $query = "INSERT INTO invoices (
                    stock_id,buylead_id,dealer,branch,customer_name,customer_mobile,customer_email,
                    customer_address,customer_pin_code,customer_city,customer_state,make,model,
                    variant,mileage,registration_no,order_id,created_by
                   )
                   SELECT 
                    $stock_id,id,dealer,branch,first_name,mobile,email,address,pin_code,city,state,
                    $make,$model,$variant,$mileage,'$reg_num','$this->order_id',created_by
                   FROM buyleads WHERE id =  $id";

         $res = mysqli_query($this->connection,$query);
         if (!$res) 
         {
            logSqlError(mysqli_error($this->connection), $query, 'invoice-addvehciles');
            return ['status' => false,'msg' => 'Failed to add sold vehicle into invoice']; 
         }

         $last_insert_id = mysqli_insert_id($this->connection);

         logTableInsertion('invoices', $last_insert_id);

        // Update Vehicle status in inventory

        $inv_query = "UPDATE inventory SET 
                      is_sold = 'y', 
                      status = " . $this->commonConfig['common_statuses']['inventory']['sold']. ", 
                      buylead_id = '".mysqli_real_escape_string($this->connection,$buylead_id)."',
                      updated_on = '" . date('Y-m-d H:i:s') . "',
                      updated_by = '".$this->logged_user_id."' 
                      WHERE id = $stock_id";
        $inv_res = mysqli_query($this->connection,$inv_query);
        if(!$inv_res)
        {
            logSqlError(mysqli_error($this->connection), $inv_query, 'inventory-updatevehcilesoldflag');
            return ['status' => false,'msg' => 'Failed to update sold vehicle status in inventory']; 
        }

        //Inventory log table insertion 
        logTableInsertion('inventory', $stock_id);
        $history_query = "INSERT INTO inventory_history SET 
                    inventory_id = $stock_id,
                    status = '".$this->commonConfig['common_statuses']['inventory']['sold']."',
                    action = 'Vehicle is Sold',                                                          
                    sold_date = '".date('Y-m-d')."',                                        
                    created = '".date('Y-m-d H:i:s')."',
                    created_by = '".$this->logged_user_id."'
            "; 
        $result = mysqli_query($this->connection,$history_query);
        if (!$result) 
        {
            logSqlError(mysqli_error($this->connection), $history_query, 'buyleads-updatevehiclestatus');
            return ['status' => false,'msg' => 'Failed to update booked vehicle status']; 
        }          

         return ['status' => true,'msg' => 'Sold vehicle added to the invoice']; 
      }
   }

    public function updateExecutive($data)
    {
        $lead_id = (int) mysqli_real_escape_string($this->connection, $data['id']);   
        $logged_branch_id = isset($data['branch']) ? (int) mysqli_real_escape_string($this->connection, $data['branch']) : 0;
        $exec_id = isset($data['executive']) ? (int) mysqli_real_escape_string($this->connection, $data['executive']) : 0;

        // Validate executive before assignment
        $validation = validateExecutiveActive($exec_id, $this->connection);
        if (!$validation['valid']) {
            return ['status' => false, 'message' => $validation['message']];
        }

        if ($lead_id > 0) // Only require valid lead ID
        {
            $query = "UPDATE buyleads 
                    SET executive=$exec_id, 
                        branch=$logged_branch_id, 
                        updated='" . date('Y-m-d H:i:s') . "', 
                        updated_by=" . (int)$this->logged_user_id . "
                    WHERE id = $lead_id";

            $res = mysqli_query($this->connection, $query); 

            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'buyleads-assignExecutive', true);
            }
            logTableInsertion('buyleads', $lead_id);

            return mysqli_affected_rows($this->connection) > 0;
        }

        return false;
   }

   public function getStatuses()
   {
        $statuses = [];
        $query = "SELECT id as status_id,name as label FROM master_buylead_statuses WHERE status = 1";
        $res = mysqli_query($this->connection,$query); 
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-getstatuses');
        }
        if( mysqli_num_rows($res)>0 )
        {
            while( $row = mysqli_fetch_assoc($res) )
            {
                $key = strtolower(str_replace(' ','-',$row['label']));
                $statuses[$key] = $row;
            }
        }
        return $statuses;    
    }

    public function testDriveVehicle($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return [];
        }

        $query = "SELECT * FROM buyleads_test_drive_vehicles WHERE id = $id LIMIT 1";

        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-testDriveVehicle');
            return [];
        }

        $row = mysqli_fetch_assoc($res);
        return $row ?: [];
    }

    public function getTestDriveVehicles($id)
    {
        $testdrive_vehicles = [];

        $id = intval($id);
        if ($id <= 0) {
            return [];
        }

        if (!$this->ownerCheck($id)) {
            return null;
        }

        $query = "
            SELECT 
                td.id as testdrive_id,
                IFNULL(NULLIF(td.inventory_id, 0), '') AS inventory_id,
                IFNULL(NULLIF(td.test_drive_place, 0), '') AS test_drive_place,
                " . buildSqlCaseFromConfig('td.test_drive_place', $this->commonConfig['testdrive_place']) . " AS test_drive_place_name,
                IFNULL(NULLIF(td.test_drive_status, 0), '') AS test_drive_status,
                " . buildSqlCaseFromConfig('td.test_drive_status', $this->commonConfig['testdrive_status']) . " AS test_drive_status_name,
                td.scheduled_date,
                td.completed_date,
                td.form_doc
            FROM buyleads_test_drive_vehicles td
            WHERE td.buylead_id = $id
        ";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-getTestDriveVehicles');
            return [];
        }

        $idList = [];
        $testdriveData = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $inventoryId = intval($row['inventory_id']);
            if ($inventoryId > 0) {
                $idList[] = $inventoryId;
                $testdriveData[$inventoryId] = $row;
            }
        }

        if (empty($idList)) { return []; }
        $idStr = implode(',', array_map('intval', $idList));
        if (empty($idStr)) return [];

        $vehicle_query = "
            SELECT 
                i.id,
                " . buildSqlCaseFromConfig('i.status', $this->commonConfig['inventory_lead_statuses']) . " AS status_name,
                IFNULL(NULLIF(i.make, 0), '') AS make,
                IFNULL(NULLIF(i.model, 0), '') AS model,
                IFNULL(NULLIF(i.variant, 0), '') AS variant,
                IFNULL(mv.make, '') AS make_name,
                IFNULL(mv.model, '') AS model_name,
                IFNULL(mv.variant, '') AS variant_name
            FROM inventory i
            LEFT JOIN master_variants_new mv ON i.variant = mv.id
            WHERE i.id IN ($idStr)
        ";

        $vehicle_res = mysqli_query($this->connection, $vehicle_query);
        if (!$vehicle_res) {
            logSqlError(mysqli_error($this->connection), $vehicle_query, 'buyleads-getTestDriveVehicles');
            return [];
        }

        while ($vrow = mysqli_fetch_assoc($vehicle_res)) {
            $invId = intval($vrow['id']);
            $data = $testdriveData[$invId] ?? [];

            // Build document URLs using processDocuments()
            $docs = $this->processDocuments($data);
            $form_doc_url = $docs['form_doc']['url'] ?? '';

            $testdrive_vehicles[] = [
                'row_id'                => data_encrypt($data['testdrive_id']),
                'vehicle_id'            => $vrow['id'],
                'label'                 => '#INV'.$vrow['id'],
                'vehicle_status'        => $vrow['status_name'] ?? '',
                'make_name'             => $vrow['make_name'] ?? '',
                'model_name'            => $vrow['model_name'] ?? '',
                'variant_name'          => $vrow['variant_name'] ?? '',
                'test_drive_place'      => $data['test_drive_place'] ?? '',
                'test_drive_place_name' => $data['test_drive_place_name'] ?? '',
                'test_drive_status'     => $data['test_drive_status'] ?? '',
                'test_drive_status_name'=> $data['test_drive_status_name'] ?? '',
                'scheduled_date'        => $data['scheduled_date'] ?? '',
                'completed_date'        => $data['completed_date'] ?? '',
                'form_doc'              => $form_doc_url
            ];
        }

        return $testdrive_vehicles;
    }


    public function addTestDriveVehicle()
    {
        $buylead_id   = intval($this->id ?? 0);
        $inventory_id = intval($this->test_drive_vehicle ?? 0);
        $place        = intval($this->test_drive_place ?? 0);
        $status       = intval($this->test_drive_status ?? 1);
        $scheduled    = mysqli_real_escape_string($this->connection, trim($this->test_drive_date ?? ''));
        $completed = mysqli_real_escape_string($this->connection, trim($this->test_drive_completed_date ?? ''));
        $form_doc  = mysqli_real_escape_string($this->connection, trim($this->form_doc ?? ''));

        if ($buylead_id <= 0 || $inventory_id <= 0) {
            return ['success' => false, 'msg' => 'Invalid buylead or inventory ID'];
        }

        if (empty($scheduled)) {
            return ['success' => false, 'msg' => 'Scheduled date is required'];
        }

        $query = "INSERT INTO buyleads_test_drive_vehicles 
                (buylead_id, inventory_id, test_drive_place, test_drive_status, scheduled_date, completed_date, form_doc)
                VALUES ($buylead_id, $inventory_id, $place, $status, '$scheduled', '$completed', '$form_doc')";

        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-addTestDriveVehicle');
            return ['success' => false, 'msg' => 'Insert failed'];
        }

        return ['success' => true, 'id' => mysqli_insert_id($this->connection)];
    }

    public function updateTestDriveVehicle($row_id)
    {
        $buylead_id   = intval($this->id ?? 0);
        $row_id = intval($row_id);

        if ($buylead_id <= 0 ) {
            return ['success' => false, 'msg' => 'Invalid buylead'];
        }

        $place     = intval($this->test_drive_place ?? 0);
        $status    = intval($this->test_drive_status ?? 0);
        $scheduled = mysqli_real_escape_string($this->connection, trim($this->test_drive_date ?? ''));
        $completed = mysqli_real_escape_string($this->connection, trim($this->test_drive_completed_date ?? ''));
        $form_doc = mysqli_real_escape_string($this->connection, trim($this->form_doc ?? ''));

        // Build dynamic update set
        $updates = [];
        $updates[] = "test_drive_place = $place";
        $updates[] = "test_drive_status = $status";
        if (!empty($scheduled)) $updates[] = "scheduled_date = '$scheduled'";
        if (!empty($completed)) $updates[] = "completed_date = '$completed'";
        if (!empty($form_doc)) $updates[] = "form_doc = '$form_doc'";
        $update_sql = implode(', ', $updates);

        $query = "
            UPDATE buyleads_test_drive_vehicles
            SET $update_sql
            WHERE buylead_id = $buylead_id AND id = $row_id
        ";

        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-updateTestDriveVehicle');
            return ['success' => false, 'msg' => 'Update failed'];
        }

        if (mysqli_affected_rows($this->connection) === 0) {
            return ['success' => false, 'msg' => 'No fields are updated'];
        }

        return ['success' => true];
    }


    public function getLeadHistory(int $lead_id = 0)
    {
        if($lead_id <= 0) return [];
        
        $lead_id = (int) $lead_id;

        $hist_query = "SELECT h.id,
            CONCAT('INV', h.id) AS formatted_id,
            IFNULL(h.status,'') AS status,
            " . buildSqlCaseFromConfig('h.status', $this->commonConfig['sm_status']) . " AS status_name,
            IFNULL(h.sub_status,'') as sub_status,
            " . buildSubStatusSqlCase('h.status', 'h.sub_status', $this->commonConfig['sm_sub_status']) . " AS sub_status_name,
            IFNULL(h.followup_date, '') AS followup_date,
            IFNULL(h.booked_vehicle,'') AS booked_vehicle,
            IFNULL(h.sold_vehicle,'') AS stock_id,
            IFNULL(h.sold_by,'') AS sold_by,
            IFNULL(u1.name,'') AS sold_by_name,
            IFNULL(h.price_customer,'') AS price_customer,
            IFNULL(h.price_quote,'') AS price_quote,
            IFNULL(h.price_agreed,'') AS price_agreed,
            IFNULL(h.price_indicative,'') AS price_indicative,
            IFNULL(h.price_sold,'') AS price_sold,
            IFNULL(h.price_margin,'') AS price_margin,
            IFNULL(h.sold_date,'') AS sold_date,
            IFNULL(h.remarks,'') AS remarks,
            IFNULL(h.created,'') AS updated_date,
            IFNULL(u2.name, '') AS updated_by
        FROM buyleads_history h
        LEFT JOIN users u1 ON h.sold_by = u1.id
        LEFT JOIN users u2 ON h.created_by = u2.id
        WHERE h.buylead_id = $lead_id
        ORDER BY h.id DESC
        LIMIT 20";

        $hist_rows = [];
        if ($resHist = mysqli_query($this->connection, $hist_query)) {
            while ($r = mysqli_fetch_assoc($resHist)) 
            {
                if (!empty($r['booked_vehicle'])) {
                    $res = $this->inventoryCount($r['booked_vehicle']);
                    $table = ($res > 0 ) ? 'inventory' : "inventory_sold"; 

                    $booked_vehicle_details = getLeadDataById($r['booked_vehicle'], $table);
                    if (!empty($booked_vehicle_details[0])) {
                        $r['booked_vehicle_name'] =
                            trim(
                                ($booked_vehicle_details[0]['make_name'] ?? '') . ' ' .
                                ($booked_vehicle_details[0]['model_name'] ?? '') . ' ' .
                                ($booked_vehicle_details[0]['variant_name'] ?? '')
                            );
                    }
                }
                if (!empty($r['stock_id'])) {
                    $res = $this->inventoryCount($r['stock_id']);
                    $table = ($res > 0 ) ? 'inventory' : "inventory_sold"; 

                    $sold_vehicle_details = getLeadDataById($r['stock_id'], $table);
                    if (!empty($sold_vehicle_details[0])) {
                        $r['sold_vehicle_name'] =
                            trim(
                                ($sold_vehicle_details[0]['make_name'] ?? '') . ' ' .
                                ($sold_vehicle_details[0]['model_name'] ?? '') . ' ' .
                                ($sold_vehicle_details[0]['variant_name'] ?? '')
                            );
                    }
                }
                $hist_rows[] = $r; 
            }
        }
        else
        {
            logSqlError(mysqli_error($this->connection), $hist_query, 'buy-getlead-history');
        }
        return $hist_rows;
    }
    
    // Get single lead
    public function getLead(int $lead_id)
    {
        $lead_id = (int) $lead_id;

        if($lead_id <= 0) return null;

        // Check ownership
        if(!$this->ownerCheck($lead_id)){
            return null;
        }

        $lead_details = [];
        if( $lead_id > 0 )
        {
            $lead_id = mysqli_real_escape_string($this->connection,$lead_id);
            $query = $this->getLeadBaseQuery(true,false) . "
                WHERE a.id = $lead_id
            " . $this->executiveCondition();
 
            $res = mysqli_query($this->connection,$query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'buyleads-getlead');
            }
            if( mysqli_num_rows($res) > 0 )
            {
                $lead_details = mysqli_fetch_assoc($res); 
            }
            if(empty($lead_details)){
                return $lead_details;
            }

            // Store numeric ID for display purposes and convert main id to encrypted
            if (isset($lead_details['id']) && !empty($lead_details['id']) && is_numeric($lead_details['id'])) {
                $lead['id'] = data_encrypt($lead_details['id']);
                $lead['detail'] = $lead_details;
                $lead['detail']['numeric_id'] = $lead_details['id'];
                $lead['detail']['formatted_id'] = "SM{$lead_details['id']}";
                $lead['detail']['id'] = $lead['id'];

                // Process documents 
                $lead['documents'] = (object)$this->processDocuments($lead_details);
                  if (!empty($lead['documents'])) {
                    foreach ($lead['documents'] as $key => $doc) {
                        if (!empty($doc['url'])) {
                            $lead['detail'][$key] = $doc['url'];
                        }
                    }
                }
            }
        }
    
        // Add ematches as a separate key 
        $lead['shortlisted_vehicles'] = $this->getShortListedVehicles($lead_id);
        $lead['interested_vehicles'] = $this->getInterestedVehicles($lead_id);
        
        if (!empty($lead_details['booked_vehicle'])) 
        {
            $res = $this->inventoryCount($lead_details['booked_vehicle']);
            
            $table = ($res > 0 ) ? 'inventory' : "inventory_sold"; 

            $vehicle_details = getLeadDataById($lead_details['booked_vehicle'], $table);
            if (!empty($vehicle_details[0])) 
            {
                $lead_details['booked_vehicle_details'] = $vehicle_details[0];
                $lead_details['booked_vehicle_details']['mmv'] =
                    trim(
                        ($vehicle_details[0]['make_name'] ?? '') . ' ' .
                        ($vehicle_details[0]['model_name'] ?? '') . ' ' .
                        ($vehicle_details[0]['variant_name'] ?? '')
                    );
            }
        }
        
        $lead['history'] = $this->getLeadHistory($lead_id); 
        $lead['testdrive_vehicles'] = $this->getTestDriveVehicles($lead_id);
        $lead_details['test_drive_done'] = 'n';
       
        return $lead;
    }


    private function buildMatchWhereCondition($make, $model, $year)
    {
        $conditions = [];

        if (intval($make) > 0)  $conditions[] = "a.make = " . intval($make);
        if (intval($model) > 0) $conditions[] = "a.model = " . intval($model);
        if (!empty($year))      $conditions[] = "a.mfg_year = '" . mysqli_real_escape_string($this->connection, $year) . "'";

        return !empty($conditions) ? implode(' AND ', $conditions) : '1';
    }

    private function getExcludedIds($buylead_id)
    {
        $shortlisted = ['inventory' => [], 'sellleads' => []];

        $query = "
            SELECT type, type_id 
            FROM buyleads_shortlist_vehicles 
            WHERE buylead_id = " . intval($buylead_id);

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-getExcludedIds');
            return $shortlisted;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $type = strtolower(trim($row['type']));
            $id   = intval($row['type_id']);
            if ($type === 'inventory') {
                $shortlisted['inventory'][] = $id;
            } elseif ($type === 'sellleads') {
                $shortlisted['sellleads'][] = $id;
            }
        }

        return $shortlisted;
    }

    public function getMatchCounts($make, $model, $year, $min_budget, $max_budget, $buylead_id)
    {
        $dealerId = (int)$this->logged_dealer_id;
        $counts = [
            'inventory'           => 0,
            'sellleads'           => 0,
            'codealer_inventory'  => 0,
            'codealer_sellleads'  => 0
        ];

        $invWhereSql = $this->buildMatchWhereCondition($make, $model, $year);
        $pmWhereSql  = $this->buildMatchWhereCondition($make, $model, $year);
        $exclude     = $this->getExcludedIds($buylead_id);

        // Exclude shortlisted items
        if (!empty($exclude['inventory'])) {
            $invWhereSql .= " AND a.id NOT IN (" . implode(',', $exclude['inventory']) . ")";
        }
        if (!empty($exclude['sellleads'])) {
            $pmWhereSql .= " AND a.id NOT IN (" . implode(',', $exclude['sellleads']) . ")";
        }

        if ($min_budget > 0){ 
            $invWhereSql .= " AND a.listing_price >= $min_budget" ; 
            $pmWhereSql .= " AND a.price_selling >= $min_budget" ; 
        }
        if ($max_budget > 0){ 
            $invWhereSql .= " AND a.listing_price <= $max_budget" ; 
            $pmWhereSql .= " AND a.price_selling <= $max_budget" ; 
        }

        // 1️⃣ My Stock
        $inv_query = "
            SELECT COUNT(*) AS cnt 
            FROM inventory a 
            WHERE a.status NOT IN (" . $this->commonConfig['common_statuses']['inventory']['booked'] . ")
            AND a.dealer = $dealerId
            AND $invWhereSql 
        ";
        $inv_res = mysqli_query($this->connection, $inv_query);
        if ($inv_res) {
            $row = mysqli_fetch_assoc($inv_res);
            $counts['inventory'] = intval($row['cnt'] ?? 0);
        } else {
            logSqlError(mysqli_error($this->connection), $inv_query, 'buyleads-getMatchCounts-inventory');
        }

        // 2️⃣ Co Dealer Inventory
        $codealer_inv_query = "
            SELECT COUNT(*) AS cnt 
            FROM inventory a 
            WHERE a.status NOT IN (" . $this->commonConfig['common_statuses']['inventory']['booked'] . ")
            AND a.dealer != $dealerId
            AND $invWhereSql 
        ";
        $codealer_inv_res = mysqli_query($this->connection, $codealer_inv_query);
        if ($codealer_inv_res) {
            $row = mysqli_fetch_assoc($codealer_inv_res);
            $counts['codealer_inventory'] = intval($row['cnt'] ?? 0);
        } else {
            logSqlError(mysqli_error($this->connection), $codealer_inv_query, 'buyleads-getMatchCounts-codealer_inventory');
        }

        // 3️⃣ Purchase Master (same dealer)
        $pm_query = "
            SELECT COUNT(*) AS cnt 
            FROM sellleads a 
            WHERE a.status NOT IN (" .
                $this->commonConfig['common_statuses']['pm']['status']['purchased'] . "," .
                $this->commonConfig['common_statuses']['pm']['status']['lost'] . ")
            AND a.dealer = $dealerId
            AND $pmWhereSql 
        ";
        $pm_res = mysqli_query($this->connection, $pm_query);
        if ($pm_res) {
            $row = mysqli_fetch_assoc($pm_res);
            $counts['sellleads'] = intval($row['cnt'] ?? 0);
        } else {
            logSqlError(mysqli_error($this->connection), $pm_query, 'buyleads-getMatchCounts-sellleads');
        }

        // 4️⃣ Co Dealer Purchase Master
        $codealer_pm_query = "
            SELECT COUNT(*) AS cnt 
            FROM sellleads a 
            WHERE a.status NOT IN (" .
                $this->commonConfig['common_statuses']['pm']['status']['purchased'] . "," .
                $this->commonConfig['common_statuses']['pm']['status']['lost'] . ")
            AND a.dealer != $dealerId
            AND $pmWhereSql 
        ";
        $codealer_pm_res = mysqli_query($this->connection, $codealer_pm_query);
        if ($codealer_pm_res) {
            $row = mysqli_fetch_assoc($codealer_pm_res);
            $counts['codealer_sellleads'] = intval($row['cnt'] ?? 0);
        } else {
            logSqlError(mysqli_error($this->connection), $codealer_pm_query, 'buyleads-getMatchCounts-codealer_sellleads');
        }

        // ✅ Return unified format (always label + count + type)
        return [
            ['label' => 'My Stock',                  'count' => $counts['inventory'],          'type' => 'inventory'],
            ['label' => 'Purchase Master',           'count' => $counts['sellleads'],          'type' => 'sellleads'],
            ['label' => 'Co Dealer My Stock',        'count' => $counts['codealer_inventory'], 'type' => 'codealer_inventory'],
            ['label' => 'Co Dealer Purchase Master', 'count' => $counts['codealer_sellleads'], 'type' => 'codealer_sellleads'],
        ];
    }


    public function getInventoryMatches($make, $model, $year, $min_budget, $max_budget, $buylead_id, $isCoDealer = false)
    {
        $inventory = [];
        $whereSql = $this->buildMatchWhereCondition($make, $model, $year);
        $exclude  = $this->getExcludedIds($buylead_id);

        $bookedStatus = (int)($this->commonConfig['common_statuses']['inventory']['booked'] ?? 5);

        // Exclude shortlisted inventory 
        if (!empty($exclude['inventory'])) {
            $whereSql .= " AND a.id NOT IN (" . implode(',', array_map('intval', $exclude['inventory'])) . ")";
        }
        if ($min_budget > 0){ $whereSql .= "AND a.listing_price >= $min_budget" ; }
        if ($max_budget > 0){ $whereSql .= "AND a.listing_price <= $max_budget" ; }

        // Dealer filtering
        $dealerFilter = $isCoDealer
            ? "a.dealer != $this->logged_dealer_id "
            : "a.dealer = $this->logged_dealer_id";

        $inv_query = "
            SELECT 
                a.id,
                CONCAT('INV', a.id) AS formatted_id,
                a.listing_price,
                a.owners,
                " . buildSqlCaseFromConfig('a.owners', $this->commonConfig['owners']) . " AS owners_name,
                a.mfg_year,
                a.mileage,
                " . buildSqlCaseFromConfig('a.reg_type', $this->commonConfig['reg_type']) . " AS reg_type_name,
                a.make,
                a.model,
                a.variant,
                a.color,
                a.status,
                " . buildSqlCaseFromConfig('a.status', $this->commonConfig['inventory_lead_statuses']) . " AS status_name,
                a.added_on AS created,
                c.make AS make_name,
                c.model AS model_name,
                c.variant AS variant_name,
                d.color AS color_name,
                e.cw_state AS state_name,
                e.cw_city  AS city_name,
                dg.name AS dealer_name
            FROM inventory a
            LEFT JOIN master_variants_new c ON a.variant = c.id
            LEFT JOIN master_colors d ON a.color = d.id
            LEFT JOIN sellleads b ON a.selllead_id = b.id
            LEFT JOIN master_states_areaslist e ON b.pin_code = e.cw_zip
            LEFT JOIN dealer_groups dg ON a.dealer = dg.id
            WHERE a.status NOT IN ($bookedStatus)
            AND $dealerFilter
            AND ($whereSql)
        ";

        $res = mysqli_query($this->connection, $inv_query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $inv_query, 'buyleads-getInventoryMatches');
            return [];
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = data_encrypt($row['id']);
            if (!$isCoDealer) { $row['dealer_name'] = ''; }
            $inventory[] = $row;
        }

        return $inventory;
    }


        // 4. Get full inventory and selllead matches
    public function getSellleadMatches($make, $model, $year, $min_budget, $max_budget, $buylead_id, $isCoDealer = false)
    {
        $sellleads = [];
        $whereSql = $this->buildMatchWhereCondition($make, $model, $year);
        $exclude  = $this->getExcludedIds($buylead_id);

         if (!empty($exclude['sellleads'])) {
            $whereSql .= " AND a.id NOT IN (" . implode(',', array_map('intval', $exclude['sellleads'])) . ")";
        }
        if ($min_budget > 0){ $whereSql .= "AND a.price_selling >= $min_budget" ; }
        if ($max_budget > 0){ $whereSql .= "AND a.price_selling <= $max_budget" ; }

        $statusPurchased = (int)($this->commonConfig['common_statuses']['pm']['status']['purchased'] ?? 1);
        $statusLost = (int)($this->commonConfig['common_statuses']['pm']['status']['lost'] ?? 2);

        // Dealer filtering
        $dealerFilter = $isCoDealer
            ? "a.dealer != $this->logged_dealer_id "
            : "a.dealer = $this->logged_dealer_id";

        $pm_query = "
            SELECT 
                a.id,
                CONCAT('PM', a.id) AS formatted_id,
                a.make,
                a.model,
                a.variant,
                a.mfg_year,
                a.mileage,
                IFNULL(a.price_selling, '') AS listing_price,
                a.status,
                " . buildSqlCaseFromConfig('a.status', $this->commonConfig['pm_status']) . " AS status_name,
                a.created,
                c.make AS make_name,
                c.model AS model_name,
                c.variant AS variant_name,
                d.color AS color_name,
                e.cw_state as state_name,
                e.cw_city as city_name,
                dg.name AS dealer_name
                FROM sellleads a
                LEFT JOIN master_variants_new c ON (a.variant = c.id)
                LEFT JOIN master_colors d ON (a.color = d.id)
                LEFT JOIN master_states_areaslist e ON (a.pin_code = e.cw_zip)
                LEFT JOIN dealer_groups dg ON a.dealer = dg.id
                WHERE a.status NOT IN ($statusPurchased, $statusLost)
            AND $dealerFilter
            AND ($whereSql)
        ";

        $res = mysqli_query($this->connection, $pm_query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $pm_query, 'buyleads-getSellleadMatches');
            return [];
        } 
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = data_encrypt($row['id']);
            if (!$isCoDealer) { $row['dealer_name'] = ''; }
            $sellleads[] = $row;
        }

        return $sellleads;
    }

    // ==============================
    // 🔹 Get Interested Vehicles + Counts + Data
    // ==============================
    public function getInterestedVehicles($buylead_id = 0)
    {
        $buylead_id = intval($buylead_id);
        if ($buylead_id <= 0) return [];

        $vehicle_query = "
            SELECT 
                bv.id AS row_id,
                IFNULL(NULLIF(bv.make, 0), '') AS make_id,
                IFNULL(NULLIF(bv.model, 0), '') AS model_id,
                IFNULL(NULLIF(bv.mfg_year, 0), '') AS mfg_year,
                bv.min_budget,
                bv.max_budget,
                CASE
                    WHEN bv.make > 0 THEN COALESCE((
                        SELECT mv.make 
                        FROM master_variants_new mv 
                        WHERE mv.make_id = bv.make 
                        AND (mv.model_id = bv.model OR bv.model = 0)
                        ORDER BY mv.model_id 
                        LIMIT 1
                    ), (
                        SELECT mv.make 
                        FROM master_variants_new mv 
                        WHERE mv.make_id = bv.make 
                        ORDER BY mv.model_id 
                        LIMIT 1
                    ))
                    ELSE ''
                END AS make_name,
                CASE
                    WHEN bv.model > 0 THEN (
                        SELECT mv.model 
                        FROM master_variants_new mv 
                        WHERE mv.model_id = bv.model
                        ORDER BY mv.make_id 
                        LIMIT 1
                    )
                    ELSE ''
                END AS model_name
            FROM buyleads_vehicles bv
            WHERE bv.buylead = $buylead_id
        ";

        $vehicle_res = mysqli_query($this->connection, $vehicle_query);
        if (!$vehicle_res) {
            logSqlError(mysqli_error($this->connection), $vehicle_query, 'buyleads-getInterestedVehicles');
            return [];
        }

        $existing_ematches = [];

        while ($vehicle = mysqli_fetch_assoc($vehicle_res)) {
            $make_id  = $vehicle['make_id'];
            $model_id = $vehicle['model_id'];
            $year     = $vehicle['mfg_year'] ?? '';
            $min_budget = $vehicle['min_budget'] ?? 0;
            $max_budget = $vehicle['max_budget'] ?? 0;

            $budget_range = '';
            if (!empty($min_budget) || !empty($max_budget)) {
                $budget_range = "{$min_budget} - {$max_budget}";
            }

            $budget_name = '';
            if (!empty($budget_range) && isset($this->commonConfig['vehicle_budget_range'][$budget_range])) {
                $budget_name = $this->commonConfig['vehicle_budget_range'][$budget_range];
            }
            $existing_ematches[] = [
                'row_id'       => data_encrypt($vehicle['row_id']),
                'make_id'      => $make_id,
                'model_id'     => $model_id,
                'make_name'    => $vehicle['make_name'] ?? '',
                'model_name'   => $vehicle['model_name'] ?? '',
                'year'         => $year,
                'budget'       => $budget_range,
                'budget_name'  => $budget_name,
                'match_counts' => $this->getMatchCounts(
                    $make_id,
                    $model_id,
                    $year,
                    $min_budget,
                    $max_budget,
                    $buylead_id
                ),
            ];
        }

        return $existing_ematches;
    }

    private function processDocuments(array $lead): array 
    {
        $documents = [];
        $baseUrl = rtrim($this->commonConfig['document_base_url'] ?? '', '/') . '/';

        foreach (['file_doc1', 'file_doc2', 'form_doc'] as $key) {
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
        return $documents; 
    }

    public function ownerCheck($leadId)
    {
        if( $leadId >0 )
        {
            $lead_id = (int) mysqli_real_escape_string($this->connection,$leadId);
            $dealer_id = (int) mysqli_real_escape_string($this->connection,$this->logged_dealer_id);
            $query = "select count(*) cnt from buyleads as a where a.dealer = $dealer_id and a.id = $lead_id";
            $query .= $this->executiveCondition();
           
            $res = mysqli_query($this->connection,$query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'buyleads-getlead');
            }
            $row = mysqli_fetch_assoc($res); 
            if($row['cnt']>0) return true;
            else return false;
        }
        return false;
    }


    public function getShortListedVehicles($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return [];
        }
        if (!$this->ownerCheck($id)) {
            return null;
        }

        $logged_dealer_id = intval($this->logged_dealer_id);

        // Step 1: Fetch shortlist entries
        $query = "SELECT id as row_id, type, type_id FROM buyleads_shortlist_vehicles WHERE buylead_id = $id";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-getShortListedVehicles');
            return [];
        }

        $shortlist = [
            'sellleads' => [],
            'inventory' => [],
        ];
        $rowIdsMap = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $type = trim($row['type']);
            $typeId = intval($row['type_id']);
            if ($type && $typeId > 0) {
                $shortlist[$type][] = $typeId;
                $rowIdsMap[$type][$typeId] = intval($row['row_id']); // map type_id → row_id
            }
        }

        // --- Initialize grouped results
        $result = [
            'inventory' => [],
            'sellleads' => [],
            'co_dealer_inventory' => [],
            'co_dealer_sellleads' => [],
        ];

        // --- Helper for processing result sets
        $processResult = function ($rows, $type) use (&$result, $logged_dealer_id, $rowIdsMap) {
            foreach ($rows as $r) {
                $isSelfDealer = (intval($r['dealer_id']) == $logged_dealer_id);
                $idVal = intval($r['id']);
                $rowId = $rowIdsMap[$type][$idVal] ?? 0;

                $item = [
                    'row_id'       => data_encrypt($rowId),
                    'id'           => data_encrypt($r['id']),
                    'label'        => ($type === 'sellleads' ? '#PM' : '#INV') . $idVal,
                    'fuel_name'    => $r['fuel_name'],
                    'mfg_year'     => $r['mfg_year'],
                    'mileage'      => $r['mileage'],
                    'make_name'    => $r['make_name'],
                    'model_name'   => $r['model_name'],
                    'variant_name' => $r['variant_name'],
                    'color_name'   => $r['color_name'],
                    'dealer_name'  => $isSelfDealer ? '' : $r['dealer_name'],
                    'branch_name'  => $r['branch_name'],
                    'listing_price'=> $r['listing_price'],
                    'status_name' => $r['status_name'],
                    'image' => Files::imageLink($r['image'], '600x800'),
                    'owners' => $r['owners'],
                ];

                if ($isSelfDealer) {
                    $result[$type][] = $item;
                } else {
                    $result["co_dealer_{$type}"][] = $item;
                }
            }
        };

        // --- Step 2: Fetch Selllead Details
        if (!empty($shortlist['sellleads'])) {
            $idList = implode(',', array_map('intval', $shortlist['sellleads']));

            $selllead_query = "
                SELECT 
                    s.id,
                    s.dealer AS dealer_id,
                    s.branch AS branch_id,
                    s.status,
                    " . buildSqlCaseFromConfig('s.status', $this->commonConfig['pm_status']) . " AS status_name,
                    " . buildSqlCaseFromConfig('s.fuel', $this->commonConfig['fuel']) . " AS fuel_name,
                    s.price_selling AS listing_price,
                    IFNULL(NULLIF(s.mfg_year, ''), '') AS mfg_year,
                    IFNULL(NULLIF(s.mileage, ''), '') AS mileage,
                    IFNULL(NULLIF(s.color, ''), '') AS color,
                    IFNULL(NULLIF(s.owners, ''), '') AS owners,
                    IFNULL(v.make, '') AS make_name,
                    IFNULL(v.model, '') AS model_name,
                    IFNULL(v.variant, '') AS variant_name,
                    IFNULL(col.color, '') AS color_name,
                    IFNULL(dg.name, '') AS dealer_name,
                    IFNULL(db.name, '') AS branch_name,
                    si.image
                FROM sellleads s
                LEFT JOIN master_variants_new v ON s.variant = v.id
                LEFT JOIN master_colors col ON s.color = col.id
                LEFT JOIN dealer_groups dg ON s.dealer = dg.id
                LEFT JOIN dealer_branches db ON s.branch = db.id
                LEFT JOIN sellleads_images si ON (si.selllead_id = s.id AND image_tag = 'front')
                WHERE s.id IN ($idList)
            ";

            $selllead_res = mysqli_query($this->connection, $selllead_query);
            if ($selllead_res) {
                $rows = [];
                while ($r = mysqli_fetch_assoc($selllead_res)) {
                    $rows[$r['id']] = $r;
                }
                $processResult(array_values($rows), 'sellleads');
            }
        }

        // --- Step 3: Fetch Inventory + Inventory Sold Details
        if (!empty($shortlist['inventory'])) {
            $idList = implode(',', array_map('intval', $shortlist['inventory']));

            $inventory_query = "
                (SELECT 
                    i.id,
                    i.dealer AS dealer_id,
                    i.branch AS branch_id,
                    i.listing_price,
                    " . buildSqlCaseFromConfig('i.status', $this->commonConfig['inventory_lead_statuses']) . " AS status_name,
                    " . buildSqlCaseFromConfig('i.fuel', $this->commonConfig['fuel']) . " AS fuel_name,
                    IFNULL(NULLIF(i.mfg_year, ''), '') AS mfg_year,
                    IFNULL(NULLIF(i.mileage, ''), '') AS mileage,
                    IFNULL(NULLIF(i.color, ''), '') AS color,
                    IFNULL(NULLIF(i.owners, ''), '') AS owners,
                    IFNULL(v.make, '') AS make_name,
                    IFNULL(v.model, '') AS model_name,
                    IFNULL(v.variant, '') AS variant_name,
                    IFNULL(col.color, '') AS color_name,
                    IFNULL(dg.name, '') AS dealer_name,
                    IFNULL(db.name, '') AS branch_name,
                    ii.image
                FROM inventory i
                LEFT JOIN master_variants_new v ON i.variant = v.id
                LEFT JOIN master_colors col ON i.color = col.id
                LEFT JOIN dealer_groups dg ON i.dealer = dg.id
                LEFT JOIN dealer_branches db ON i.branch = db.id
                LEFT JOIN inventory_images ii ON ( ii.inventory_id = i.id AND image_tag = 'front' )
                WHERE i.id IN ($idList))
            ";

            $inventory_res = mysqli_query($this->connection, $inventory_query);
            if ($inventory_res) {
                $rows = [];
                while ($r = mysqli_fetch_assoc($inventory_res)) {
                    $rows[$r['id']] = $r;
                }
                $processResult(array_values($rows), 'inventory');
            }
        }

        // --- Step 4: Return structured data
        $data =  [
            [
                'type'  => 'inventory',
                'label' => 'My Stock',
                'list'  => $result['inventory'] ?? [],
            ],
            [
                'type'  => 'purchase-master',
                'label' => 'Purchase Master',
                'list'  => $result['sellleads'] ?? [],
            ],
            [
                'type'  => 'inventory',
                'label' => 'Co Dealers - My Stock',
                'list'  => $result['co_dealer_inventory'] ?? [],
            ],
            [
                'type'  => 'purchase-master',
                'label' => 'Co Dealers - Purchase Master',
                'list'  => $result['co_dealer_sellleads'] ?? [],
            ],
        ];
        $data = array_values(array_filter($data, function ($item) {
            return !empty($item['list']);
        }));

        return $data;
    }

    // Add Intrested, vehicles
    public function saveExactMatches($id = 0, $list = [])
    {
        $buylead_id = (int)$id;
        $this->created = date('Y-m-d H:i:s');

        if ($buylead_id <= 0 || empty($list)) return false;

        // Normalize single record into array
        if (isset($list['make'])) {
            $list = [$list];
        }

        foreach ($list as $row) {
            if (empty($row) || !is_array($row)) continue;

            $make  = isset($row['make']) ? mysqli_real_escape_string($this->connection, $row['make']) : null;
            $model = isset($row['model']) ? mysqli_real_escape_string($this->connection, $row['model']) : null;
            $year  = isset($row['mfg_year']) ? intval($row['mfg_year']) : null;

            // --- Extract budgets from input ---
            $min_budget = null;
            $max_budget = null;
            if (!empty($row['budget'])) {
                $budgetParts = explode('-', $row['budget']);
                $min_budget = isset($budgetParts[0]) ? trim($budgetParts[0]) : null;
                $max_budget = isset($budgetParts[1]) ? trim($budgetParts[1]) : null;
            }

            // Make is mandatory, skip if missing
            if ($make === null) continue;

            // --- Check if record exists ---
            $conditions = ["buylead = '$buylead_id'", "make = '$make'"];
            if ($model !== null) $conditions[] = "model = '$model'";
            if ($year !== null) $conditions[] = "mfg_year = '$year'";
            if ($min_budget !== null) $conditions[] = "min_budget = '$min_budget'";
            if ($max_budget !== null) $conditions[] = "max_budget = '$max_budget'";
            $where = implode(" AND ", $conditions);

            $cnt_query = "SELECT id FROM buyleads_vehicles WHERE $where LIMIT 1";
            $cnt_res = mysqli_query($this->connection, $cnt_query);

            if (!$cnt_res) {
                logSqlError(mysqli_error($this->connection), $cnt_query, 'buyleads-saveematches');
                continue;
            }

            $row_existing = mysqli_fetch_assoc($cnt_res);

            if ($row_existing) {
                // --- Update existing record ---
                $update_fields = [];
                if ($model !== null) $update_fields[] = "model = '$model'";
                if ($year !== null) $update_fields[] = "mfg_year = '$year'";
                if ($min_budget !== null) $update_fields[] = "min_budget = '$min_budget'";
                if ($max_budget !== null) $update_fields[] = "max_budget = '$max_budget'";

                if (!empty($update_fields)) {
                    $update_query = "UPDATE buyleads_vehicles SET " . implode(", ", $update_fields) . " WHERE id = '{$row_existing['id']}'";
                    mysqli_query($this->connection, $update_query);
                }
            } else {
                // --- Insert new record ---
                $insert_query = "INSERT INTO buyleads_vehicles 
                    (buylead, dealer, make, model, mfg_year, min_budget, max_budget, created, created_by)
                    VALUES (
                        '$buylead_id',
                        '{$this->logged_dealer_id}',
                        '$make',
                        " . ($model !== null ? "'$model'" : "NULL") . ",
                        " . ($year !== null ? "'$year'" : "NULL") . ",
                        " . ($min_budget !== null ? "'$min_budget'" : "NULL") . ",
                        " . ($max_budget !== null ? "'$max_budget'" : "NULL") . ",
                        '$this->created',
                        '{$this->logged_user_id}'
                    )";

                mysqli_query($this->connection, $insert_query);
            }
        }

        return true;
    }


    public function addShortListItem($buylead_id, $id, $type)
    {
        $buylead_id = intval($buylead_id);
        $type_id    = intval($id);
        $type       = trim($type);

        if ($buylead_id <= 0 || $type_id <= 0 || empty($type)) return false;

        $checkQuery = "
            SELECT id FROM buyleads_shortlist_vehicles 
            WHERE buylead_id = $buylead_id AND type = '" . mysqli_real_escape_string($this->connection, $type) . "' 
            AND type_id = $type_id 
            LIMIT 1
        ";
        $checkRes = mysqli_query($this->connection, $checkQuery);
        if ($checkRes && mysqli_num_rows($checkRes) > 0) {
            return true;
        }

        // Insert new record
        $query = "
            INSERT INTO buyleads_shortlist_vehicles (buylead_id, type, type_id, created_by)
            VALUES ($buylead_id, '" . mysqli_real_escape_string($this->connection, $type) . "', $type_id, $this->logged_user_id)
        ";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-shortlist-insert');
            return false;
        }

        return true;
    }

    public function deleteShortListItem($id)
    {
        $id = intval($id);
        if ($id <= 0) return false;

        $query = "DELETE FROM buyleads_shortlist_vehicles WHERE id = $id LIMIT 1";
        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-shortlist-delete');
            return false;
        }

        return true;
    }


    public function deleteIntrestedVehicle($id)
    {
        $id = intval($id);
        if ($id <= 0) return false;
        $id = mysqli_real_escape_string($this->connection, $id);
        $query = "DELETE FROM buyleads_vehicles WHERE id = '$id' LIMIT 1";
        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-deleteematch');
            return false;
        }

        return true;
    }


    public function getReadyForSaleVehiclesList($id = 0)
    {
        $vehicles_list = [];
        $query = "SELECT
                    a.id AS value,
                    CONCAT(
                        'INV', a.id, ' - ',
                        TRIM(CONCAT_WS(' ', b.make, b.model, b.variant))
                    ) AS label
                FROM inventory a
                LEFT JOIN master_variants_new b ON a.variant = b.id
                WHERE ";

        if ($id > 0) {
            $id = mysqli_real_escape_string($this->connection, $id);
            $query .= "(a.status = 4 OR (a.status = 5 AND a.buylead_id = '$id'))";
        } else {
            $query .= "a.status = 4";
        }

        // Optional DISTINCT or GROUP BY if duplicates exist
        $query .= " GROUP BY a.id";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-getvehicleslist');
            return $vehicles_list;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $vehicles_list[] = $row;
        }

        return $vehicles_list;
    }

    public function insertBuyleadsHistory(): bool
    {
        if (empty($this->id) || $this->id <= 0) {
            logSqlError('Invalid or missing lead ID', '', 'buyleads-insertBuyleadsHistory', true);
            return false;
        }

        $leadId = (int)$this->id;

        // Fields copied from buyleads table
        $fieldsFromBuyleads = [
            'status',
            'sub_status',
            'followup_date',
            'sold_vehicle',
            'sold_by',
            'price_sold',
            'price_customer',
            'price_quote',
            'price_agreed',
            'price_indicative',
            'price_margin',
            'token_amount',
            'sold_date',
            'delivery_date',
            'booking_date',
            'booked_vehicle',
            'test_drive_done',
            'remarks'
        ];

        //Base history table fields (metadata)
        $historyMetaFields = ['buylead_id', 'created', 'created_by'];

        // Build final INSERT column list
        $allFields = array_merge($historyMetaFields, $fieldsFromBuyleads);
        $allFieldsSql = implode(', ', array_map(fn($f) => "`$f`", $allFields));

        // Build SELECT values list (same order as fields)
        $selectParts = [];

        // Static values for metadata
        $selectParts[] = $leadId;
        $selectParts[] = "'" . date('Y-m-d H:i:s') . "'";
        $selectParts[] = (int)$this->logged_user_id;

        // buyleads table columns (aliased as b)
        foreach ($fieldsFromBuyleads as $f) {
            $selectParts[] = "b.`$f`";
        }

        $selectSql = implode(', ', $selectParts);

        // Build query
        $query = "
            INSERT INTO buyleads_history ($allFieldsSql)
            SELECT $selectSql
            FROM buyleads b
            WHERE b.id = $leadId
            LIMIT 1
        ";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-insertBuyleadsHistory', true);
            return false;
        }

        if (mysqli_affected_rows($this->connection) === 0) {
            logSqlError("No lead found for ID: $leadId", $query, 'buyleads-insertBuyleadsHistory', true);
            return false;
        }

        return true;
    }



//  export functionality not in use
         public function exportBuyleads($filters = []){
        $main_headers = [
            ['name' => 'Lead Info', 'colspan' => 2],
            ['name' => 'Customer Details',  'colspan' => 3],
            // ['name' => 'Vehicle Details',    'colspan' => 14],
            // ['name' => 'Other Details',  'colspan' => 10],
        ];

        $headers = [
            ['name' => 'ID', 'type' => 'number', 'value' => 'id'],
            ['name' => 'Status', 'type' => 'string', 'value' => 'status_name'],
            ['name' => 'Customer Name', 'type' => 'string', 'value' => 'customer_name'],
            ['name' => 'Customer Mobile', 'type' => 'string', 'value' => 'mobile'],
            ['name' => 'Customer Email', 'type' => 'string', 'value' => 'email'],
            // ['name' => 'Registration Type', 'type' => 'string', 'value' => 'reg_type', 'config' => 'reg_type'],
            // ['name' => 'Registration Number', 'type' => 'string', 'value' => 'reg_num'],
            // ['name' => 'Registration Date', 'type' => 'datetime', 'value' => 'reg_date'],
            // ['name' => 'Car Type', 'type' => 'string', 'value' => 'car_type', 'config' => 'car_type'],
            // ['name' => 'Make', 'type' => 'string', 'value' => 'make_name'],
            // ['name' => 'Model', 'type' => 'string', 'value' => 'model_name'],
            // ['name' => 'Variant', 'type' => 'string', 'value' => 'variant_name'],
            // ['name' => 'Maufacturing Year', 'type' => 'number', 'value' => 'mfg_year'],
            // ['name' => 'Manufacturing Month', 'type' => 'number', 'value' => 'mfg_month', 'config' => 'months'],
            // ['name' => 'Chassis Number', 'type' => 'string', 'value' => 'chassis'],
            // ['name' => 'Transmission', 'type' => 'string', 'value' => 'transmission', 'config' => 'transmission'],
            // ['name' => 'Fuel Type', 'type' => 'string', 'value' => 'fuel', 'config' => 'fuel'],
            // ['name' => 'Mileage', 'type' => 'number', 'value' => 'mileage'],
            // ['name' => 'Color', 'type' => 'string', 'value' => 'color', 'config' => 'colors'],
            ['name' => 'PinCode', 'type' => 'string', 'value' => 'pin_code'],
            ['name' => 'City', 'type' => 'string', 'value' => 'city_name'],
            ['name' => 'State', 'type' => 'string', 'value' => 'state_name'],
            // ['name' => 'Address', 'type' => 'string', 'value' => 'address'],
            // ['name' => 'Hypothetication', 'type' => 'boolean', 'value' => 'hypothecation'],
            // ['name' => 'Insurance Type', 'type' => 'string', 'value' => 'insurance_type', 'config' => 'insurance_type'],
            // ['name' => 'Insurance Expiry Date', 'type' => 'datetime', 'value' => 'insurance_exp_date'],
            ['name' => 'Source', 'type' => 'string', 'value' => 'source_name'],
            ['name' => 'Sub Source', 'type' => 'string', 'value' => 'source_sub_name']
        ];

        $where = "WHERE (executive = " . $this->logged_user_id . " OR user = " . $this->logged_user_id . ") ";

        foreach ($filters as $key => $val) {
            $val = mysqli_real_escape_string($this->connection, $val);
            if ($val !== '') {
                if($key == 'id')
                {
                    $numericId = intval(preg_replace('/\D/', '', $val));
                    $where .= " AND a.id LIKE '%" . $numericId . "%'";
                    continue;
                }
                // Special handling for make / model filters
                if ($key === 'make') {
                    $where .= " AND EXISTS (SELECT 1 FROM buyleads_vehicles v WHERE v.buylead = a.id AND v.make = '".intval($val)."')";
                    continue;
                }

                if ($key === 'model') {
                    $where .= " AND EXISTS (SELECT 1 FROM buyleads_vehicles v WHERE v.buylead = a.id AND v.model = '".intval($val)."')";
                    continue;
                }
                $where .= " AND a.`" . mysqli_real_escape_string($this->connection, $key) . "` LIKE '%$val%'";
            }
        }

        $query = "SELECT DISTINCT a.*, 
            CONCAT_WS(' ', a.title, a.first_name, a.last_name) AS customer_name,
            IFNULL(st.name, '') AS status_name,
            IFNULL(v.make, '') AS make_name,
            IFNULL(v.model, '') AS model_name,
            IFNULL(msa.cw_city, '') AS city_name,
            IFNULL(msa.cw_state, '') AS state_name,
            IFNULL(src.source, '') AS source_name,
            IFNULL(srcs.sub_source, '') AS source_sub_name
            FROM buyleads a
            LEFT JOIN buyleads_vehicles bv ON bv.buylead = a.id
            LEFT JOIN master_variants_new v ON bv.make = v.make_id AND bv.model = v.model_id 
            LEFT JOIN master_selllead_statuses st ON a.status = st.id
            LEFT JOIN master_states_areaslist msa ON a.pin_code = msa.cw_zip
            LEFT JOIN master_sources src ON a.source = src.id
            LEFT JOIN master_sources_sub srcs ON a.source_sub = srcs.id
            $where
            ORDER BY a.updated DESC";
    
        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'buyleads-export', true);
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
        $filename = "buyleads_".date('Ymd_His').".xlsx";
        $url = exportExcelFile($headers, $data, $filename, $main_headers);
        return ['file_url' => $url];
    }

   
    
    public function buildUrl($id = 0,$module)
    {
        $encrypted_id = data_encrypt($id);
        $slug = (!empty($module) && $module == 'sellleads') ? '/purchase-master/detail/' : '/my-stock/detail/';
        $link = $this->commonConfig['base_url'] . $slug . $encrypted_id . '/overview';
        return $link;
    }

    public function inventoryCount($id = 0)
    {
        $id = (int) $id;
        $inv_query = "SELECT COUNT(*) AS cnt FROM inventory WHERE id = $id";
        $inv_res = mysqli_query($this->connection,$inv_query);
        if(!$inv_res)
        {
            logSqlError(mysqli_error($this->connection), $inv_query, 'buyleads-inventorycount');
        }

        $cnt_res = mysqli_fetch_assoc($inv_res);
        $cnt = $cnt_res['cnt'];

        return $cnt;
    }

}

?>