<?php
class Dashboard
{
    public $dealer_id;
    public $connection;
    public $module_name;
    public $config;

    public function __construct() {
        global $connection, $config, $redis;        
        $this->module_name = "dashboard";
        $this->connection = $connection;
        $this->config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
    }

    public function pm_source() 
    {
        $query = "SELECT s.source AS s_id, ms.source AS source_name, COUNT(*) AS total 
                    FROM sellleads s 
                    JOIN master_sources ms ON ms.id = s.source 
                    GROUP BY s.source, ms.source";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-pm-source', true);
        } 

        $pm_source = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $pm_source[] = $row;
        }

        return [
            "pm_sources" => $pm_source,
        ];
    }

    public function sm_source() {
        $query = "SELECT b.source AS s_id, ms.source AS source_name, COUNT(*) AS total 
                    FROM buyleads b 
                    JOIN master_sources ms ON ms.id = b.source 
                    GROUP BY b.source, ms.source";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-sm-source', true);
        }

        $sm_source = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sm_source[] = $row;
        }

        return [
            "sm_sources" => $sm_source,
        ];
    }

    public function pm_status() {
        $query = "SELECT s.status AS status_id, mss.name AS status_name, COUNT(*) AS total 
                    FROM sellleads s 
                    JOIN master_selllead_statuses mss ON mss.id = s.status 
                    GROUP BY s.status, mss.name";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-pm-status', true);
        }

        $pm_status = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $pm_status[] = $row;
        }

        return [
            "pm_statuses" => $pm_status,
        ];
    }

    public function sm_status() {
        $query = "SELECT b.status AS status_id, mbs.name AS status_name, COUNT(*) AS total 
                    FROM buyleads b 
                    JOIN master_buylead_statuses mbs ON mbs.id = b.status 
                    GROUP BY b.status, mbs.name";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-sm-status', true);
        }

        $sm_status = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sm_status[] = $row;
        }

        return [
            "sm_statuses" => $sm_status,
        ];
    }

    public function sales_achievement(){
        $query = "select  ";
    }


    public function kra_target_by_month($year = 2025) {
        $query = "
            SELECT 
                month,
                SUM(evaluation) AS total_evaluation,
                SUM(trade_in) AS total_trade_in,
                SUM(purchase) AS total_purchase,
                SUM(sales) AS total_sales,
                SUM(overall_sales) AS total_overall_sales
            FROM kra_targets
            WHERE year = $year
            GROUP BY month
            ORDER BY month ASC
        ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-kra-targets', true);
            return [];
        }

        $targets = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $targets[$row['month']] = [
                'evaluation' => (int)$row['total_evaluation'],
                'trade_in' => (int)$row['total_trade_in'],
                'purchase' => (int)$row['total_purchase'],
                'sales' => (int)$row['total_sales'],
                'overall_sales' => (int)$row['total_overall_sales'],
            ];
        }
        return [
            "kra_targets" => $targets
        ];
    }

    public function evaluation_data($year = 2025) {
        $year = intval($year);

        $status = intval($this->config['common_statuses']['pm']['status']['evaluated'] ?? 0);
        if ($status <= 0) {
            error_log("dashboard:evaluation_data - missing config common_statuses.pm.status.evaluated");
            return ['eval_data' => []];
        }

        $query = "
            SELECT 
                MONTH(evaluation_date) AS month,
                COUNT(*) AS total_evaluations
            FROM sellleads
            WHERE status = " . $status . " 
            AND YEAR(evaluation_date) = $year
            GROUP BY MONTH(evaluation_date)
            ORDER BY MONTH(evaluation_date) ASC
        ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-eval-achieve', true);
            return ['eval_data' => []];
        }

        $evalData = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $evalData[$row['month']] = (int)$row['total_evaluations'];
        }

        return ['eval_data' => $evalData];
    }
    public function purchased_data($year = 2025) {
        $year = intval($year);
        // need to change with purchased_date 
        $query = "
            SELECT 
                MONTH(evaluation_date) AS month,
                COUNT(*) AS total_purchases
            FROM sellleads
            WHERE status = ". $this->config['common_statuses']['pm']['status']['purchased']. " 
            AND YEAR(evaluation_date) = $year
            GROUP BY MONTH(evaluation_date)
            ORDER BY MONTH(evaluation_date) ASC "; 

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-purchase-achieve', true);
            return [];
        }

        $purchase_data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $purchase_data[$row['month']] = (int)$row['total_purchases'];
        }

        return ['purchase_data' => $purchase_data];
    }

    public function sold_data($year = 2025) {
        $year = intval($year);
        // need to change with sold_date 
        $query = "
            SELECT 
                MONTH(booking_date) AS month,
                COUNT(*) AS total_sold
            FROM buyleads
            WHERE status = ". $this->config['common_statuses']['sm']['status']['sold']. " 
            AND YEAR(booking_date) = $year
            GROUP BY MONTH(booking_date)
            ORDER BY MONTH(booking_date) ASC "; 

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-sold-achieve', true);
            return [];
        }

        $soldData = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $soldData[$row['month']] = (int)$row['total_sold'];
        }

        return ['sold_data' => $soldData];
    }


}?>