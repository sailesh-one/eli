<?php
    class Locations
    {

        // Database & config
        public $connection;
        public $commonConfig;

        public function __construct($id = 0) {
            global $connection;
            $this->connection = $connection;
            $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
            if ($id > 0) {
                $this->id = $id;
                // optionally: load lead data from DB into properties
            }
        }

        public function getList($filters = [], $current_page = 1, $per_page = 10)
        {
            // print_r($_POST['cw_state']);
            // print_r($_POST['cw_city']);
            // print_r($_POST['cw_zip']);exit;
            // print_r('currentpage', $_POST['current_page']);
            // print_r('perPage', $_POST['perPage']);exit;
            $current_date = date('Y-m-d');

            // Ensure proper integer pagination values
            $current_page = max(1, (int)$current_page);
            $per_page = max(1, (int)$per_page);
            $offset = max(0, ($current_page - 1) * $per_page);

            // --- Dynamic filters ---
            $listFilters = [];
            foreach ($filters as $key => $val) {
                if ($val === '' || $val === null) continue;
                $val = mysqli_real_escape_string($this->connection, $val);
                switch ($key) {
                    case 'cw_state':
                        $listFilters[] = "cw_state_id = '$val'";
                        break;
                    case 'cw_city':
                        $listFilters[] = "cw_city_id = '$val'";
                        break;
                    case 'cw_zip':
                        $listFilters[] = "cw_zip = '$val'";
                        break;
                }
            }

            // Build WHERE clause only if filters exist
            $whereClause = '';
            if (count($listFilters) > 0) {
                $whereClause = 'WHERE ' . implode(' AND ', $listFilters);
            }

            // --- Count total for pagination ---
            $countSql = "SELECT COUNT(*) AS total FROM `master_states_areaslist` $whereClause";
            // return $countSql;
            $resCount = mysqli_query($this->connection, $countSql);
            $filtered_total = 0;
            if ($resCount && $r = mysqli_fetch_assoc($resCount)) {
                $filtered_total = (int)$r['total'];
            }

            $pages = ($filtered_total > 0) ? ceil($filtered_total / $per_page) : 0;

            // --- List query ---
            // Use same whereClause; ensure safe integer usage for LIMIT
            $listQuery = "SELECT * FROM `master_states_areaslist` $whereClause
                        ORDER BY `cw_state` ASC
                        LIMIT " . (int)$offset . ", " . (int)$per_page;

            $rows = mysqli_query($this->connection, $listQuery);
            $lists = [];
            if ($rows) {
                $serialNumber = 1; // start S.No from 1 for each page
                while ($row = mysqli_fetch_assoc($rows)) {
                    $row['sno'] = $serialNumber++; // add serial number
                    $lists[] = $row;
                }
            } else {

            }

            return [
                'pagination' => [
                    'total' => $filtered_total,
                    'pages' => $pages,
                    'per_page' => $per_page,
                    'current_page' => (int)$current_page,
                    'start_count' => $filtered_total ? $offset + 1 : 0,
                    'end_count' => min($offset + $per_page, $filtered_total)
                ],
                'list' => $lists
            ];
        }


       
    }
?>