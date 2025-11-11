<?php

class KRA
{
    public $dealer_id;
    public $connection;
    public $module_name;
    public $login_user_id;

    public function __construct() {
        global $connection;        
        $this->module_name = "kra_targets";
        $this->connection = $connection;
        $this->login_user_id = $GLOBALS['api_user']['uid'];
    }

    public function getTargets(){
        $kra_year = mysqli_real_escape_string($this->connection, $_POST['kra_year']);
        $kra_month = mysqli_real_escape_string($this->connection, $_POST['kra_month']);
        $query = "SELECT 
                        dg.id AS dealer_group_id,
                        dg.name AS dealer_group_name,
                        b.id AS branch_id,
                        b.name AS branch_name,
                        IFNULL(msa.cw_city, '') AS city_name,
                        kt.*
                    FROM dealer_groups dg
                    LEFT JOIN dealer_branches b  ON b.dealer_group_id = dg.id
                    LEFT JOIN master_states_areaslist msa ON b.pin_code = msa.cw_zip
                    LEFT JOIN kra_targets kt  
                        ON kt.branch = b.id  
                        AND kt.year = $kra_year  
                        AND kt.month = $kra_month
                    ORDER BY dg.id, b.id, kt.id; ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get_kra_targets');
        }
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $dgId = $row['dealer_group_id'];
            $branchId = $row['branch_id'];

            if (!isset($data[$dgId])) {
                $data[$dgId] = [
                    'dealer_group_id' => $dgId,
                    'dealer_group_name' => $row['dealer_group_name'],
                    'branches' => []
                ];
            }

            if ($branchId && !isset($data[$dgId]['branches'][$branchId])) {
                $data[$dgId]['branches'][$branchId] = [
                    'branch_id' => $branchId,
                    'branch_name' => $row['branch_name'],
                    'branch_city' => $row['city_name'],
                    'kra_targets' => []
                ];
            }

            if (!empty($row['id'])) {
                $data[$dgId]['branches'][$branchId]['kra_targets'][] = [
                    'id' => $row['id'],
                    'year' => $row['year'],
                    'month' => $row['month'],
                    'evaluation' => $row['evaluation'],
                    'trade_in' => $row['trade_in'],
                    'purchase' => $row['purchase'],
                    'sales' => $row['sales'],
                    'overall_sales' => $row['overall_sales']
                ];
            }
        }

        foreach ($data as &$dg) {
            $dg['branches'] = array_values($dg['branches']);
        }

        return array_values($data);
    }

    public function checkAvailability() {
        $dealer = mysqli_real_escape_string($this->connection, $_POST['dealer']);
        $branch = mysqli_real_escape_string($this->connection, $_POST['branch']);
        $year   = mysqli_real_escape_string($this->connection, $_POST['year']);
        $month  = mysqli_real_escape_string($this->connection, $_POST['month']);

        $query = "SELECT id FROM kra_targets
                  WHERE dealer = '$dealer' AND branch = '$branch' AND year = '$year' AND month = '$month' 
                  LIMIT 1";
        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'check_kra_target');
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        return $row ? (int)$row['id'] : null;
    }

    public function addKraTarget() {
        $dealer = mysqli_real_escape_string($this->connection, $_POST['dealer']);
        $branch = mysqli_real_escape_string($this->connection, $_POST['branch']);
        $year   = mysqli_real_escape_string($this->connection, $_POST['year']);
        $month  = mysqli_real_escape_string($this->connection, $_POST['month']);
        $field  = mysqli_real_escape_string($this->connection, $_POST['field']);
        $value  = mysqli_real_escape_string($this->connection, $_POST['value']);

        $query = "INSERT INTO kra_targets (dealer, branch, year, month, `$field`, created_by, created) 
                  VALUES ('$dealer', '$branch', '$year', '$month', '$value', '{$this->login_user_id}', NOW())";

        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'add_kra_target');
            return false;
        }
        return true;
    }

    public function updateKraTarget($id) {
        $id    = mysqli_real_escape_string($this->connection, $id);
        $field  = mysqli_real_escape_string($this->connection, $_POST['field']);
        $value  = mysqli_real_escape_string($this->connection, $_POST['value']);

        $query = "UPDATE kra_targets 
                  SET `$field` = '$value', updated_by = '{$this->login_user_id}', updated = NOW()
                  WHERE id = '$id'";

        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'update_kra_target');
            return false;
        }
        return true;
    }
}

?>