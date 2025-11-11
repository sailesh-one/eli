<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class Users
{
    public $dealer_id;
    public $connection;
    public $module_name;
    public $login_user_id;

    public function __construct() {
        global $connection;        
        $this->module_name = "dealer_management";
        $this->connection = $connection;
        $this->login_user_id = $GLOBALS['api_user']['uid'];
    }


    
    public function getdealers($filters = [], $page = 1, $perPage = 10) 
    {
        $leads = [];
        $where = "WHERE 1";

        if (!empty($filters['search_data'])) {
            $search = mysqli_real_escape_string($this->connection, $filters['search_data']);
            $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.mobile LIKE '%$search%')";
        }

        if (!empty($filters['role'])) {
            $role = mysqli_real_escape_string($this->connection, $filters['role']);
            $where .= " AND u.role_id = '$role'";
        }
        if (!empty($filters['dealership'])) {
            $dealership = mysqli_real_escape_string($this->connection, $filters['dealership']);
            $where .= " AND u.dealership_id = '$dealership'";
        }

        // Count query
        $query_cnt = "SELECT COUNT(*) as cnt FROM users u $where";
        $res_cnt = mysqli_query($this->connection, $query_cnt);
        if (!$res_cnt) {
            logSqlError(mysqli_error($this->connection), $query_cnt, 'users-getdealers');
        }
        $row_cnt = mysqli_fetch_assoc($res_cnt);
        $total = (int)$row_cnt['cnt'];

        // Pagination
        $start = ($page - 1) * $perPage;
        $end_count = min($start + $perPage, $total);
        $start_count = $total > 0 ? ($start + 1) : 0;

        // Fetch paginated data
        $query = "
            SELECT u.*, r.role_name, d.name AS dealership_name,
                b.id AS branch_id, b.name AS branch_name, b.city AS branch_city
            FROM users u
            LEFT JOIN config_roles r ON u.role_id = r.id
            LEFT JOIN dealer_groups d ON u.dealership_id = d.id
            LEFT JOIN dealer_branches b 
                ON JSON_CONTAINS(u.branch_id, CONCAT('\"', b.id, '\"'))
            $where
            ORDER BY CASE WHEN u.active = 'y' THEN 0 ELSE 1 END
            LIMIT $start, $perPage ";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getdealers');
        }

        $users = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $userId = $row['id'];

            if (!isset($users[$userId])) {
                $users[$userId] = $row;
                $users[$userId]['branches'] = [];
            }

            if (!empty($row['branch_id'])) {
                $users[$userId]['branches'][] = [
                    'id' => $row['branch_id'],
                    'branch_name' => $row['branch_name'],
                    'branch_city' => $row['branch_city'],
                ];
            }

            unset($users[$userId]['branch_id'], $users[$userId]['branch_name'], $users[$userId]['branch_city']);
        }

        $leads = array_values($users);

        $dealership_query = "SELECT distinct d.name as dealership_name FROM `dealer_groups` d left join users u on d.id = u.dealership_id where u.id = $this->login_user_id";
        $dealership_res = mysqli_query($this->connection,$dealership_query);
        if(!$dealership_res)
        {
            logSqlError(mysqli_error($this->connection), $dealership_query, 'users-getdealers');
        }

        $dealership_name_res = mysqli_fetch_assoc($dealership_res);
        $dealership_name = $dealership_name_res['dealership_name'];

        return [
            "total" => $total,
            "start_count" => $start_count,
            "end_count" => $end_count,
            "leads" => $leads,
            "dealership_name" => $dealership_name
        ];
    }

    public function getDealersList($filters = [], $page = 1, $perPage = 10) 
    {
        $leads = [];
        $where = "WHERE 1";

        $dealership_query = "SELECT  dealership_id as dealership_id FROM `users` where id = $this->login_user_id";
        $dealership_res = mysqli_query($this->connection,$dealership_query);
        if(!$dealership_res)
        {
            logSqlError(mysqli_error($this->connection), $dealership_query, 'users-getdealers');
        }

        $dealership_id_res = mysqli_fetch_assoc($dealership_res);
        $dealership_id = $dealership_id_res['dealership_id'];

        if (!empty($filters['search_data'])) {
            $search = mysqli_real_escape_string($this->connection, $filters['search_data']);
            $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.mobile LIKE '%$search%')";
        }

        if (!empty($filters['role'])) {
            $role = mysqli_real_escape_string($this->connection, $filters['role']);
            $where .= " AND u.role_id = '$role'";
        }

        // Count query
        $query_cnt = "SELECT COUNT(*) as cnt FROM users u WHERE dealership_id = $dealership_id";
        $res_cnt = mysqli_query($this->connection, $query_cnt);
        if (!$res_cnt) {
            logSqlError(mysqli_error($this->connection), $query_cnt, 'users-getdealers');
        }
        $row_cnt = mysqli_fetch_assoc($res_cnt);
        $total = (int)$row_cnt['cnt'];

        // Pagination
        $start = ($page - 1) * $perPage;
        $end_count = min($start + $perPage, $total);
        $start_count = $total > 0 ? ($start + 1) : 0;

        //Fetch paginated data
        
        $query = "SELECT u.*,r.role_name FROM users u LEFT JOIN config_roles r ON u.role_id = r.id $where AND u.dealership_id = $dealership_id ORDER BY CASE WHEN u.active = 'y' THEN 0 ELSE 1 END
        LIMIT $start, $perPage";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getdealers');
        } 

        while ($row = mysqli_fetch_assoc($result)) {
            $leads[] = $row;
        }

        return [
            "total" => $total,
            "start_count" => $start_count,
            "end_count" => $end_count,
            "leads" => $leads,
            "dealership_id" => $dealership_id,
        ];
    }

    public function getroles($is_dealer = 0) {
        $roles = [];
        $query = "SELECT * FROM config_roles WHERE role_type = " . (int)$is_dealer ."
                    ORDER BY CASE WHEN active = 'y' THEN 0 ELSE 1 END "; 
        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getroles');
        } 
        while ($row = mysqli_fetch_assoc($res)) {
            $roles[] = $row;
        }
        return [
            "roles" => $roles,
        ];
    }

public function getdealerships($filters = [], $page = 1, $perPage = 0) 
{
    $dealers = [];
    $where = "";

    if (!empty($filters['search_data'])) {
        $search = mysqli_real_escape_string($this->connection, $filters['search_data']);
        $where .= " WHERE (name LIKE '%$search%')";
    }

    // Count total dealers
    $query_cnt = "SELECT COUNT(*) as cnt FROM dealer_groups $where";
    $res_cnt = mysqli_query($this->connection, $query_cnt);
    if (!$res_cnt) {
        logSqlError(mysqli_error($this->connection), $query_cnt, 'users-getdealergroups');
    }
    $row_cnt = mysqli_fetch_assoc($res_cnt);
    $total = (int)$row_cnt['cnt'];

    // Pagination
    $start = ($page - 1) * $perPage;
    $end_count = min($start + $perPage, $total);
    $start_count = $total > 0 ? ($start + 1) : 0;

    // Fetch dealer groups
    $query = "SELECT * FROM dealer_groups $where 
              ORDER BY CASE WHEN active = 'y' THEN 0 ELSE 1 END ";
    if($perPage > 0){ 
        $query .= " LIMIT $start, $perPage"; 
    }

    $result = mysqli_query($this->connection, $query);
    if (!$result) {
        logSqlError(mysqli_error($this->connection), $query, 'users-getdealerships');
    } 

    $dealer_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dealer_ids[] = (int)$row['id'];
        $dealers[$row['id']] = $row; // use dealer_id as key for easy mapping
        $dealers[$row['id']]['branches'] = []; // initialize
    }
    $config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
    // If we have dealers, fetch all branches in one query
    if (!empty($dealer_ids)) {
        $ids_str = implode(",", $dealer_ids);
        $branch_query = "SELECT b.*,
                        IFNULL(msa.cw_city, '') AS city_name,
                        IFNULL(msa.cw_state, '') AS state_name
                        FROM dealer_branches b
                        LEFT JOIN master_states_areaslist msa ON b.pin_code = msa.cw_zip
                        WHERE b.dealer_group_id IN ($ids_str)";
        $branch_result = mysqli_query($this->connection, $branch_query);

        if ($branch_result) {
            while ($branch = mysqli_fetch_assoc($branch_result)) {
                $labels = [];
                $selected = explode(',', $branch['franchise_type']);
                foreach ($selected as $val) {
                    $val = trim($val);
                    if (isset($config['franchise_type'][$val])) {
                        $labels[] = $config['franchise_type'][$val];
                    }
                }
                $branch['franchise_type_name'] = implode(', ', $labels);
                $branch['invoicing_enabled_value'] = $config['active_type'][$branch['invoicing_enabled']] ?? null;
                $branch['main_branch_value'] = $config['active_type'][$branch['main_branch']] ?? null;

                $dealer_id = (int)$branch['dealer_group_id'];
                if (isset($dealers[$dealer_id])) {
                    $dealers[$dealer_id]['branches'][] = $branch;
                }
            }
        } else {
            logSqlError(mysqli_error($this->connection), $branch_query, 'users-getdealerbranches');
        }
    }

    // Re-index array (remove dealer_id keys)
    $dealers = array_values($dealers);

    return [
        "total" => $total,
        "start_count" => $start_count,
        "end_count" => $end_count,
        "dealers" => $dealers
    ];
}


    public function addDealerBranch($dealer_group_id, $data = []) {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        $data['dealer_group_id'] = intval($dealer_group_id);
        $data['created_by'] = $this->login_user_id;

        $fields = [];
        $values = [];

        foreach ($data as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val);
            }
            $fields[] = "`" . mysqli_real_escape_string($this->connection, $key) . "`";
            $values[] = "'" . mysqli_real_escape_string($this->connection, (string)$val) . "'";
        }

        $fields[] = "`created`";
        $values[]  = "'" . date('Y-m-d H:i:s') . "'";

        $fields_str = implode(",", $fields);
        $values_str = implode(",", $values);

        $query = "INSERT INTO dealer_branches ($fields_str) VALUES ($values_str)";
        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-adddealerbranch');
            return false;
        }

        $insert_id = mysqli_insert_id($this->connection);

        $result = logTableInsertion("dealer_branches", $insert_id);

        return $result ? true : false;
    }


    public function editDealerBranch($branch_id, $data = []) {
        if (empty($branch_id) || empty($data) || !is_array($data)) {
            return false;
        }
        $updates = [];
        $data['updated_by'] = $this->login_user_id;
        foreach ($data as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val);
            }
            $updates[] = "`" . mysqli_real_escape_string($this->connection, $key) . "` = '" . 
                        mysqli_real_escape_string($this->connection, (string)$val) . "'";
        }
        $updates[] = "`updated` = '" . date('Y-m-d H:i:s') . "'";

        $updates_str = implode(", ", $updates);

        $query = "UPDATE dealer_branches SET $updates_str 
                  WHERE id = '" . mysqli_real_escape_string($this->connection, (string)$branch_id) . "'";

        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-editdealer_branch');
            return false;
        }

       $result = logTableInsertion("dealer_branches", $branch_id);
       
       if($result) { return true; }
       else { return false;}
    }

    public function deletedealership($dealership_id,$active) {
        $dealership_id = intval($dealership_id);
        $query = "UPDATE dealer_groups SET active = '$active' WHERE id = $dealership_id";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-dealership-delete');
            return false;
        }

       $result = logTableInsertion("dealer_groups", $dealership_id);
       
       if($result)
       {
         return true; 
       }
       else
       {
        return false;
       }
    }

    public function getdealership($dealership_id) {
        $dealership_id = intval($dealership_id);
        $query = "SELECT * FROM dealer_groups WHERE id = $dealership_id";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getdealership');
        } 
        return mysqli_fetch_assoc($result);
    }

    public function getBranchData($branch_id){
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        $branch_id = intval($branch_id);
        $branches = [];
        $query = "SELECT b.*, 
                    IFNULL(msa.cw_city, '') AS city_name,
                    IFNULL(msa.cw_state, '') AS state_name
                    FROM dealer_branches b
                    LEFT JOIN master_states_areaslist msa ON b.pin_code = msa.cw_zip
                    WHERE b.id = $branch_id
                    ORDER BY 
                    CASE WHEN main_branch = 'y' THEN 0 ELSE 1 END " ;
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getBranchData');
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $selected = explode(',', $row['franchise_type']);
            $labels = [];
            foreach ($selected as $val) {
                $val = trim($val);
                if (isset($config['franchise_type'][$val])) {
                    $labels[] = $config['franchise_type'][$val];
                }
            }
            $row['franchise_type_name'] = implode(', ', $labels);
            $row['invoicing_enabled_value'] = $config['active_type'][$row['invoicing_enabled']] ?? null;
            $row['main_branch_value'] = $config['active_type'][$row['main_branch']] ?? null;
            $branches[] = $row;
        }
        return $branches;
    }

    public function addDealerGroup(){
        $name = mysqli_real_escape_string($this->connection, $_POST['name']);
        $short_name = mysqli_real_escape_string($this->connection, $_POST['short_name']);
        $website_url = mysqli_real_escape_string($this->connection, $_POST['website_url']);

        // Duplicate check
        $dup_query = "SELECT COUNT(*) AS cnt FROM dealer_groups WHERE name = '$name'";
        $dup_res = mysqli_query($this->connection,$dup_query);
        if(!$dup_res)
        {
           logSqlError(mysqli_error($this->connection),$dup_query,'add-dealergroup');
        }
        $dup_row = mysqli_fetch_assoc($dup_res);
        if ($dup_row && intval($dup_row['cnt']) > 0)
        {
            api_response(409,"fail","This dealership already exists.",[],[]);
        }

        $query = "INSERT INTO dealer_groups (name, short_name, website_url, created_by) VALUES ('$name', '$short_name', '$website_url', '$this->login_user_id')";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-adddealergroup');
            return false;
        }
        $insert_id = mysqli_insert_id($this->connection);
        $result = logTableInsertion("dealer_groups", $insert_id);
        if($result) { return true; }
        else { return false; }
    }

    public function editDealerGroup(){
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($this->connection, $_POST['name']);
        $short_name = mysqli_real_escape_string($this->connection, $_POST['short_name']);
        $website_url = mysqli_real_escape_string($this->connection, $_POST['website_url']);

        // Duplicate check
        $dup_query = "SELECT COUNT(*) AS cnt FROM dealer_groups WHERE name = '$name' and id != $id";
        $dup_res = mysqli_query($this->connection,$dup_query);
        if(!$dup_res)
        {
           logSqlError(mysqli_error($this->connection),$dup_query,'add-dealergroup');
        }
        $row = mysqli_fetch_assoc($dup_res);

        if ($row && $row['cnt'] > 0) {
            api_response(409,"fail","This dealership already exists.",[],[],$errors);
        }

        $query = "UPDATE dealer_groups SET name='$name', short_name='$short_name', website_url='$website_url', updated_by = '$this->login_user_id' WHERE id= $id";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-editdealergroup');
            return false;
        }

       $result = logTableInsertion("dealer_groups", $id);
       
       if($result) { return true; }
       else { return false;}
    }

    public function adddealer($data = []) {
        $name = mysqli_real_escape_string($this->connection, $data['name']);
        $email = mysqli_real_escape_string($this->connection, $data['email']);
        $mobile = mysqli_real_escape_string($this->connection, $data['mobile']);
        $role_id = mysqli_real_escape_string($this->connection, $data['role_id']);
        $dealership_id = mysqli_real_escape_string($this->connection, $data['dealership_id']);
        $active = mysqli_real_escape_string($this->connection, $data['active'] ?? 'y');

        $branch_ids = !empty($data['branch_ids']) ? json_encode($data['branch_ids']) : '[]';
        $branch_id = mysqli_real_escape_string($this->connection, $branch_ids);

        // Duplicate check
        $dup_query = "SELECT COUNT(*) AS cnt FROM users WHERE (email = '$email' OR mobile = $mobile)";
        $dup_res = mysqli_query($this->connection,$dup_query);
        $errors = [];
        if(!$dup_res)
        {
           logSqlError(mysqli_error($this->connection),$dup_query,'add-dealer');
        }
        $count = mysqli_fetch_assoc($dup_res);

        $email_query = "SELECT email FROM users";
        $email_res = mysqli_query($this->connection,$email_query);
        if(!$email_res)
        {
           logSqlError(mysqli_error($this->connection),$email_query,'add-dealer');
        }

        $user_emails = [];
        while($row = mysqli_fetch_assoc($email_res))
        {
            $user_emails[] = $row['email'];
        }

        if(in_array($email,$user_emails))
        {
            $errors['email'] = "This email already exists with another user";
        }

        $mobile_query = "SELECT mobile FROM users";
        $mobile_res = mysqli_query($this->connection,$mobile_query);
        if(!$mobile_res)
        {
           logSqlError(mysqli_error($this->connection),$mobile_query,'add-dealer');
        }

        $mobile_numbers = [];
        while($row = mysqli_fetch_assoc($mobile_res))
        {
            $mobile_numbers[] = $row['mobile'];
        }

        if(in_array($mobile,$mobile_numbers))
        {
            $errors['mobile'] = "This mobile number already exists for another user";
        }


        if($count['cnt'] > 0 && count($errors) > 0)
        {
            api_response(409,"fail","Duplicate entry.",[],[],$errors);
        }

        $branch_ids = !empty($data['branch_ids']) ? json_encode($data['branch_ids']) : '[]';

        $query = "INSERT INTO users (name, email, mobile, role_id, dealership_id, branch_id, active,added_by) VALUES ('$name', '$email', '$mobile', '$role_id', '$dealership_id', '$branch_id', '$active','$this->login_user_id')";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-adddealer');
            return false;
        }
        $insert_id = mysqli_insert_id($this->connection);
        $dealer = [];
        $res = mysqli_query($this->connection, "SELECT * FROM users WHERE id = $insert_id");
        if ($res) {
            $dealer = mysqli_fetch_assoc($res);
        }

        $result = logTableInsertion("users",$insert_id);

        if($result)
        {
          return $dealer; 
        }
        else
        {
            return false;
        }
    }

       public function addExecutive($data = []) {
        $name = mysqli_real_escape_string($this->connection, $data['name']);
        $email = mysqli_real_escape_string($this->connection, $data['email']);
        $mobile = mysqli_real_escape_string($this->connection, $data['mobile']);
        $role_id = mysqli_real_escape_string($this->connection, $data['role_id']);
        $dealership_id = mysqli_real_escape_string($this->connection, $data['dealership_id']);
        $outlet_code = mysqli_real_escape_string($this->connection, $data['outlet_code']);
        $active = mysqli_real_escape_string($this->connection, $data['active'] ?? 'y');

        // Duplicate check
        $dup_query = "SELECT COUNT(*) AS cnt FROM users WHERE (email = '$email' OR mobile = $mobile)";
        $dup_res = mysqli_query($this->connection,$dup_query);
        $errors = [];
        if(!$dup_res)
        {
           logSqlError(mysqli_error($this->connection),$dup_query,'add-dealer');
        }
        $count = mysqli_fetch_assoc($dup_res);

        $email_query = "SELECT email FROM users";
        $email_res = mysqli_query($this->connection,$email_query);
        if(!$email_res)
        {
           logSqlError(mysqli_error($this->connection),$email_query,'add-dealer');
        }

        $user_emails = [];
        while($row = mysqli_fetch_assoc($email_res))
        {
            $user_emails[] = $row['email'];
        }

        if(in_array($email,$user_emails))
        {
            $errors['email'] = "This email already exists for another user";
        }

        $mobile_query = "SELECT mobile FROM users";
        $mobile_res = mysqli_query($this->connection,$mobile_query);
        if(!$mobile_res)
        {
           logSqlError(mysqli_error($this->connection),$mobile_query,'add-dealer');
        }

        $mobile_numbers = [];
        while($row = mysqli_fetch_assoc($mobile_res))
        {
            $mobile_numbers[] = $row['mobile'];
        }

        if(in_array($mobile,$mobile_numbers))
        {
            $errors['mobile'] = "This mobile number already exists for another user";
        }


        if($count['cnt'] > 0 && count($errors) > 0)
        {
            api_response(409,"fail","Duplicate entry.",[],[],$errors);
        }

        $query = "INSERT INTO users (name, email, mobile, role_id, dealership_id, outlet_code, active, added_by) VALUES ('$name', '$email', '$mobile', '$role_id', '$dealership_id', '$outlet_code', '$active','$this->login_user_id')";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-adddealer');
            return false;
        }
        $insert_id = mysqli_insert_id($this->connection);
        $dealer = [];
        $res = mysqli_query($this->connection, "SELECT * FROM users WHERE id = $insert_id");
        if ($res) {
            $dealer = mysqli_fetch_assoc($res);
        }

        $result = logTableInsertion("users", $insert_id);

        if($result)
        {
          return $dealer; 
        }
        else
        {
            return false;
        }
    }

    public function editdealer($data = []) {
        $id = (int)$data['id'];
        $name = mysqli_real_escape_string($this->connection, $data['name']);
        $email = mysqli_real_escape_string($this->connection, $data['email']);
        $mobile = mysqli_real_escape_string($this->connection, $data['mobile']);
        $role_id = mysqli_real_escape_string($this->connection, $data['role_id']);
        $dealership_id = mysqli_real_escape_string($this->connection, $data['dealership_id']);
        $active = mysqli_real_escape_string($this->connection, $data['active']);
        $branch_ids = !empty($data['branch_ids']) ? json_encode($data['branch_ids']) : '[]';
        $branch_id = mysqli_real_escape_string($this->connection, $branch_ids);
        $errors = [];
        // Duplicate check
        $update_query = "SELECT COUNT(*) as cnt FROM users WHERE (email = '$email' OR mobile = $mobile) AND id != $id";
        $update_query_res = mysqli_query($this->connection,$update_query);
        if(!$update_query_res)
        {
            logSqlError(mysqli_error($this->connection), $update_query, 'users-editdealer');
        }
        
        $count = mysqli_fetch_assoc($update_query_res);
        
        $email_query = "SELECT email FROM users";
        $email_res = mysqli_query($this->connection,$email_query);
        if(!$email_res)
        {
           logSqlError(mysqli_error($this->connection),$email_query,'add-dealer');
        }

        $user_emails = [];
        while($row = mysqli_fetch_assoc($email_res))
        {
            $user_emails[] = $row['email'];
        }

        if(in_array($email,$user_emails))
        {
            $errors['email'] = "This email already exists for another user";
        }

        $mobile_query = "SELECT mobile FROM users";
        $mobile_res = mysqli_query($this->connection,$mobile_query);
        if(!$mobile_res)
        {
           logSqlError(mysqli_error($this->connection),$mobile_query,'add-dealer');
        }

        $mobile_numbers = [];
        while($row = mysqli_fetch_assoc($mobile_res))
        {
            $mobile_numbers[] = $row['mobile'];
        }

        if(in_array($mobile,$mobile_numbers))
        {
            $errors['mobile'] = "This mobile number already exists for another user";
        }

        if($count['cnt'] > 0 && count($errors) > 0)
        {
            api_response(409,"fail","Duplicate entry.",[],[],$errors);
        }

        $query = "UPDATE users SET name='$name', email='$email', mobile='$mobile', role_id='$role_id', dealership_id = '$dealership_id', branch_id = '$branch_id', active='$active' WHERE id=$id";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-editdealer');
            return false;
        }
        $dealer = [];
        $res = mysqli_query($this->connection, "SELECT * FROM users WHERE id = $id");
        if ($res) {
            $dealer = mysqli_fetch_assoc($res);
        }

        $result = logTableInsertion("users",$id);

        if($result)
        {
          return $dealer; 
        }
        else
        {
            return false;
        }
    }
    
     public function editExecutive($data = []) {
        $id = (int)$data['id'];
        $name = mysqli_real_escape_string($this->connection, $data['name']);
        $email = mysqli_real_escape_string($this->connection, $data['email']);
        $mobile = mysqli_real_escape_string($this->connection, $data['mobile']);
        $role_id = mysqli_real_escape_string($this->connection, $data['role_id']);
        $dealership_id = mysqli_real_escape_string($this->connection, $data['dealership_id']);
        $outlet_code = mysqli_real_escape_string($this->connection, $data['outlet_code']);
        $active = mysqli_real_escape_string($this->connection, $data['active']);
        $errors = [];
        // Duplicate check
        $update_query = "SELECT COUNT(*) as cnt FROM users WHERE (email = '$email' OR mobile = $mobile) AND id != $id";
        $update_query_res = mysqli_query($this->connection,$update_query);
        if(!$update_query_res)
        {
            logSqlError(mysqli_error($this->connection), $update_query, 'users-editdealer');
        }
        
        $count = mysqli_fetch_assoc($update_query_res);

        $email_query = "SELECT email FROM users";
        $email_res = mysqli_query($this->connection,$email_query);
        if(!$email_res)
        {
           logSqlError(mysqli_error($this->connection),$email_query,'edit-dealer');
        }

        $user_emails = [];
        while($row = mysqli_fetch_assoc($email_res))
        {
            $user_emails[] = $row['email'];
        }

        if(in_array($email,$user_emails))
        {
            $errors['email'] = "This email already exists for another user";
        }

        $mobile_query = "SELECT mobile FROM users";
        $mobile_res = mysqli_query($this->connection,$mobile_query);
        if(!$mobile_res)
        {
           logSqlError(mysqli_error($this->connection),$mobile_query,'edit-dealer');
        }

        $mobile_numbers = [];
        while($row = mysqli_fetch_assoc($mobile_res))
        {
            $mobile_numbers[] = $row['mobile'];
        }

        if(in_array($mobile,$mobile_numbers))
        {
            $errors['mobile'] = "This mobile number already exists for another user";
        }

        if($count['cnt'] > 0 && count($errors) > 0)
        {
            api_response(409,"fail","Duplicate entry.",[],[],$errors);
        }

        $query = "UPDATE users SET name='$name', email='$email', mobile='$mobile', role_id='$role_id', dealership_id = '$dealership_id', outlet_code = '$outlet_code', active='$active' WHERE id=$id";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-editdealer');
            return false;
        }
        $dealer = [];
        $res = mysqli_query($this->connection, "SELECT * FROM users WHERE id = $id");
        if ($res) {
            $dealer = mysqli_fetch_assoc($res);
        }

        $result = logTableInsertion("users",$id);

        if($result)
        {
          return $dealer; 
        }
        else
        {
            return false;
        }
    }


    public function getmodules($is_dealer = 0) {
        $modules = [];
        $query = "SELECT 
                    m.id as module_id, m.module_name, m.url as module_url, m.category_name, m.is_visible, m.icon, m.active,
                    s.id as submodule_id, s.submodule_name, s.action as submodule_url, s.active as sub_active
                FROM config_modules m 
                LEFT JOIN config_submodules s ON s.module_id = m.id 
                WHERE 1=1 AND m.module_type = " . intval($is_dealer) . " ORDER BY m.category_name ASC,m.module_name ASC";

        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getmodules');
            return ["modules" => []];
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $module_id = $row['module_id'];

            if (!isset($modules[$module_id])) {
                $modules[$module_id] = [
                    'module_id' => $row['module_id'],
                    'module_name' => $row['module_name'],
                    'module_url' => $row['module_url'],
                    'category_name'=> !empty($row['category_name']) ? $row['category_name'] : 'Others',
                    'is_visible' => $row['is_visible'],
                    'icon' => $row['icon'],
                    'active' => $row['active'], 
                    'submodules' => []
                ];
            }

            if (!empty($row['submodule_name']) && !empty($row['submodule_url'])) {
                $modules[$module_id]['submodules'][] = [
                    'submodule_id' => $row['submodule_id'],
                    'submodule_name' => $row['submodule_name'],
                    'submodule_url' => $row['submodule_url'],
                    'active' => $row['sub_active']
                ];
            }
        }
        return [
            "modules" => array_values($modules)
        ];
    }

    public function getsubmodules($module_id){
        $submodules = [];
        $query = "SELECT * FROM `config_sub_submodules` WHERE module_id =" . $module_id;
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getsubmodules');
        } 

        while ($row = mysqli_fetch_assoc($result)) {
            $submodules[] = $row;
        }

        return [
            "submodules" => $submodules
        ];
    }

    public function addmodule($name, $url, $category_name = '', $is_visible = '0', $is_dealer = 0, $icon = '') {
        $name = mysqli_real_escape_string($this->connection, $name);
        $url = mysqli_real_escape_string($this->connection, $url);
        $is_dealer = intval($is_dealer);
        $category_name = mysqli_real_escape_string($this->connection, $category_name);
        $is_visible = mysqli_real_escape_string($this->connection, $is_visible);
        $icon = mysqli_real_escape_string($this->connection, $icon);
        $created_at = date('Y-m-d H:i:s');
        $created_by = (int)($this->login_user_id);

        // Duplicate check start

        $dup_ins_query = "SELECT COUNT(*) as total FROM `config_modules` WHERE module_type = '".$is_dealer."' AND (module_name = '".$name."' OR url = '".$url."') AND active = 'y'";
        $dup_ins_query_res = mysqli_query($this->connection,$dup_ins_query);
        if(!$dup_ins_query_res)
        {
           logSqlError(mysqli_error($this->connection), $dup_ins_query, 'duplicate module');
        }
        $dup_count = mysqli_fetch_assoc($dup_ins_query_res);

        if($dup_count['total'] > 0)
        {
           api_response(409,'fail','Duplicate entry.');
        }

        // Duplicate check end

        $query = "INSERT INTO config_modules (module_name, url, module_type,category_name,is_visible,icon,created_at,created_by) VALUES ('$name', '$url', $is_dealer, '$category_name', '$is_visible', '$icon','$created_at','$created_by')";
        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'users-addmodule');
            return ["success" => false, "message" => "Failed to add module"];
        }
        $insert_id = mysqli_insert_id($this->connection);
        $result = logTableInsertion("config_modules",$insert_id);
        
        // modules_log table insertion end

        if($result)
        {
           return true;
        } 
    }

    public function updatemodule($name, $url, $module_id, $active,$category_name,$is_visible,$is_dealer = 0,$icon = '') {
        $name = mysqli_real_escape_string($this->connection, $name);
        $url = mysqli_real_escape_string($this->connection, $url);
        $module_id = intval($module_id);
        $is_dealer = intval($is_dealer);
        $active = mysqli_real_escape_string($this->connection, $active);
        $icon = mysqli_real_escape_string($this->connection, $icon);
        $updated_at = date('Y-m-d H:i:s');
        $updated_by = (int)($this->login_user_id);
        // Duplicate check start
        
        $dup_update_query = "SELECT COUNT(*) as total FROM `config_modules` WHERE (module_type = '".$is_dealer."' AND (module_name = '".$name."' OR url = '".$url."')) AND id != '".$module_id."' AND active = 'y'";
        $dup_update_query_res = mysqli_query($this->connection,$dup_update_query);
        if(!$dup_update_query_res)
        {
           logSqlError(mysqli_error($this->connection), $dup_update_query, 'duplicate module');
        }
        $dup_update_count = mysqli_fetch_assoc($dup_update_query_res);

        if($dup_update_count['total'] > 0)
        {
           api_response(409,'fail','Duplicate entry.');
        }
 
        // Duplicate check end

        $query = "UPDATE config_modules 
                SET module_name = '$name', url = '$url', module_type = $is_dealer , active = '$active', category_name = '$category_name', is_visible = '$is_visible', icon = '$icon',updated_at = '$updated_at',updated_by = '$updated_by'
                WHERE id = $module_id";

        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'users-updatemodule');
            return ["success" => false, "message" => "Failed to update module"];
        }

        $result = logTableInsertion("config_modules",$module_id);
        
        if($result)
        {
           return ["success" => true, "message" => "Module updated successfully"];
        } 
    }

    public function addsubmodule($name, $url, $module_id, $is_dealer = 0) {
        $name = mysqli_real_escape_string($this->connection, $name);
        $action = mysqli_real_escape_string($this->connection, $url);
        $module_id = intval($module_id);
        $is_dealer = intval($is_dealer);
        $created_at = date('Y-m-d H:i:s');
        $created_by = (int)($this->login_user_id);
 
        // Duplicate check start        
        $dup_sub_ins_query = "SELECT COUNT(*) as total FROM `config_submodules` WHERE module_id = '". $module_id ."' AND submodule_type = '".$is_dealer."' AND (submodule_name = '".$name."' OR action = '".$url."' ) AND active = 'y'";
        $dup_sub_ins_query_res = mysqli_query($this->connection,$dup_sub_ins_query);
        if(!$dup_sub_ins_query_res)
        {
           logSqlError(mysqli_error($this->connection), $dup_sub_ins_query, 'duplicate sub module');
        }
        $dup_total = mysqli_fetch_assoc($dup_sub_ins_query_res);

        if($dup_total['total'] > 0)
        {
           api_response(409,'fail','Duplicate entry.');
        }

        // Duplicate check end


        $query = "INSERT INTO config_submodules (submodule_name, action, submodule_type, module_id,created_at,created_by) VALUES ('$name', '$action', $is_dealer, '$module_id','$created_at','$created_by')";

        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'users-addsubmodule');
            return ["success" => false, "message" => "Failed to add module"];
        }
        $insert_id = mysqli_insert_id($this->connection);

        $result = logTableInsertion("config_submodules",$insert_id);
        
        // sub_modules_log table insertion end

        if($result)
        {
           return true;
        } 
    }


    public function updatesubmodule($name, $url, $module_id, $submodule_id, $active, $is_dealer = 0) {
        $name = mysqli_real_escape_string($this->connection, $name);
        $action = mysqli_real_escape_string($this->connection, $url);
        $active = mysqli_real_escape_string($this->connection, $active);
        $module_id = intval($module_id);
        $submodule_id = intval($submodule_id);
        $is_dealer = intval($is_dealer);
        $updated_at = date('Y-m-d H:i:s');
        $updated_by = (int)($this->login_user_id);
        
        // Duplicate check start

        $dup_sub_update_query = "SELECT COUNT(*) as total FROM `config_submodules` WHERE module_id = '". $module_id ."' AND submodule_type = '".$is_dealer."' AND active = 'y' AND (submodule_name = '".$name."' OR action = '".$action."' ) AND id != '".$submodule_id."'";
        $dup_sub_update_query_res = mysqli_query($this->connection,$dup_sub_update_query);
        if(!$dup_sub_update_query_res)
        {
           logSqlError(mysqli_error($this->connection), $dup_sub_update_query, 'duplicate sub module');
        }
        $dup_update_total = mysqli_fetch_assoc($dup_sub_update_query_res);

        if($dup_update_total['total'] > 0)
        {
           api_response(409,'fail','Duplicate entry.');
        }

        // Duplicate check end


        $query = "UPDATE config_submodules 
                SET submodule_name = '$name', action = '$action', submodule_type = $is_dealer , module_id = '$module_id', active = '$active',updated_at = '$updated_at',updated_by = '$updated_by'
                WHERE id = $submodule_id";

        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'users-updatesubmodule');
            return ["success" => false, "message" => "Failed to update module"];
        }

        $result = logTableInsertion("config_submodules",$submodule_id);
        if($result)
        {
           return ["success" => true, "message" => "Module updated successfully"];
        }
    }

    public function getrolepermissions() {
        $permissions = [];
        $query = "SELECT * FROM config_role_permissions";
        // return $query;
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'users-getrolepermissions');
        } 

        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row;
        }

        return ["permissions" => $permissions];
    }
    
    

   public function addrolepermission($role_id, $module_id, $submodule_id = null) {
        $role_id   = intval($role_id);
        $module_id = intval($module_id);
        $submodule_value = ($submodule_id !== null && $submodule_id !== '') 
                            ? intval($submodule_id) 
                            : "NULL";
        $created_at = date('Y-m-d H:i:s');
        $created_by = (int)($this->login_user_id);
        
        $query = "INSERT INTO config_role_permissions (role_id, module_id, submodule_id,created_at,created_by)
                VALUES ($role_id, $module_id, $submodule_value,'$created_at',$created_by)";

        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'users-addrolepermission');
            return ["success" => false, "message" => "Failed to add role permission"];
        }

        $master_id = mysqli_insert_id($this->connection); 
        $result = logTableInsertion("config_role_permissions", $master_id);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }


    public function removerolepermission($role_id, $module_id, $submodule_id = NULL) {
        $role_id   = intval($role_id);
        $module_id = intval($module_id);
        $submodule = $submodule_id !== NULL ? intval($submodule_id) : NULL;

        // 🔹 Get permission_id BEFORE deletion (for logging)
        $q = "SELECT id 
            FROM config_role_permissions 
            WHERE role_id = $role_id AND module_id = $module_id" 
            . ($submodule !== null ? " AND submodule_id = $submodule" : " LIMIT 1");


        $res = mysqli_query($this->connection, $q);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $q, 'users-removerolepermission-select');
            return ["success" => false, "message" => "Failed to fetch permission before deletion"];
        }

        $row = mysqli_fetch_assoc($res);

        $permission_id = $row['id'] ?? null;

        // 🔹 Delete role permission
        $deleteQuery = "DELETE FROM config_role_permissions 
                        WHERE role_id = $role_id AND module_id = $module_id" 
                        . ($submodule !== null ? " AND submodule_id = $submodule" : "");

        if (!mysqli_query($this->connection, $deleteQuery)) {
            logSqlError(mysqli_error($this->connection), $deleteQuery, 'users-removerolepermission-delete');
            return ["success" => false, "message" => "Failed to remove role permission"];
        }

        $data = [
            "id"              => $permission_id,
            "role_id"         => $role_id,
            "module_id"       => $module_id,
            "submodule_id"    => $submodule,
            "updated_by"      => $this->login_user_id,
            "created_at"      => date('Y-m-d H:i:s'),
            "updated_at"      => date('Y-m-d H:i:s')
        ];

        $result = logTableInsertion("config_role_permissions", $permission_id, $data);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }


    public function addRole($role_name,$description,$is_active,$role_type, $role_main)
    {
        $created_at = date('Y-m-d H:i:s');
        $created_by = (int)($this->login_user_id);
        $dup_count_query = "SELECT count(*) as total from config_roles where role_name = '" . mysqli_real_escape_string($this->connection,$role_name)."'";
        $count_res = mysqli_query($this->connection,$dup_count_query);
        if(!$count_res)
        {
            logSqlError(mysqli_error($this->connection), $dup_count_query, 'duplicate role');
        }
        $count = mysqli_fetch_assoc($count_res);
        
        if($count['total'] > 0)
        {
            api_response(409,"fail","A user with this role name already exists.");
        }
        
        if(($count['total'] == 0))
        {
            $role_insQuery = "INSERT INTO config_roles (role_name,description,active,role_type,role_main,created_at,created_by) VALUES ('".mysqli_real_escape_string($this->connection,$role_name)."','".mysqli_real_escape_string($this->connection,$description)."',
                            '".mysqli_real_escape_string($this->connection,$is_active)."','".mysqli_real_escape_string($this->connection,$role_type)."', '".mysqli_real_escape_string($this->connection,$role_main)."', '".mysqli_real_escape_string($this->connection,$created_at)."', '".mysqli_real_escape_string($this->connection,$created_by)."')";
            $role_insQueryRes = mysqli_query($this->connection, $role_insQuery);
            if(!$role_insQueryRes) 
            { 
                logSqlError(mysqli_error($this->connection),$role_insQuery, 'add role');
            }     
            else
            {
                $master_id = mysqli_insert_id($this->connection); 
                $result = logTableInsertion("config_roles",$master_id);
                if($result)
                {
                    api_response(200,"ok","Role added successfully.");             
                }
                else
                {
                    api_response(500,"fail","Filed to add role.");             
                }
            }       
        }
   }
      // Add role end

       // Edit role start
    public function editRole($role_name,$is_active,$role_type,$description, $role_main)
    {
        $updated_at = date('Y-m-d H:i:s');
        $updated_by = (int)($this->login_user_id);
        $id_query = "SELECT id FROM config_roles WHERE role_name = '".mysqli_real_escape_string($this->connection,$role_name)."'";
        $id_res = mysqli_query($this->connection,$id_query);
        if(!$id_res) 
         {
           logSqlError(mysqli_error($this->connection), $id_query, 'fetch role_id');
         }
         $id_row = mysqli_fetch_assoc($id_res);
         $id = $id_row['id'];
        $dup_count_query = "SELECT count(*) as total from config_roles where role_name = '" . mysqli_real_escape_string($this->connection,$role_name)."' and id != '".mysqli_real_escape_string($this->connection,$id)."'";
        $count_res = mysqli_query($this->connection,$dup_count_query);
        if(!$count_res)
        {
          logSqlError(mysqli_error($this->connection), $dup_count_query, 'duplicate role');
        }
        $count = mysqli_fetch_assoc($count_res);
        
        if($count['total'] > 0)
        {
          api_response(409,"fail","A user with this role name already exists.");
        }
       
        if(($count['total'] == 0))
        {
           $role_updateQuery = "UPDATE config_roles set role_name = '".mysqli_real_escape_string($this->connection,$role_name)."',active= '".mysqli_real_escape_string($this->connection,$is_active)."', 
                    description = '".mysqli_real_escape_string($this->connection,$description)."', role_main = '".mysqli_real_escape_string($this->connection,$role_main)."', updated_at = '".mysqli_real_escape_string($this->connection,$updated_at)."', updated_by = '".mysqli_real_escape_string($this->connection,$updated_by)."' where id = '".mysqli_real_escape_string($this->connection,$id)."'";
           $role_updateQueryRes = mysqli_query($this->connection, $role_updateQuery);
           if(!$role_updateQueryRes) 
           { 
             logSqlError(mysqli_error($this->connection),$role_updateQuery, 'edit role');
           }     
           else
           {
               $result = logTableInsertion("config_roles",$id);
               if($result)
               {
                  api_response(200,"ok","Role edited successfully.");             
               }
               else
               {
                  api_response(500,"fail","Filed to edit role.");             
               }
           }       
        }
    }
        // Edit role end

public function exportDealerships($fields, $filters = []) {
    $where = "WHERE 1=1";
    // if (!empty($filters['search_data'])) {
    //     $search = mysqli_real_escape_string($this->connection, $filters['search_data']);
    //     $where .= " AND (name LIKE '%$search%')";
    // }
    $config = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
    $query = "SELECT d.id as dealer_id, d.name as dealer_name,d.short_name,d.website_url,d.active as dealer_active,
                IFNULL(msa.cw_city, '') AS city_name,
                IFNULL(msa.cw_state, '') AS state_name,
                b.* FROM dealer_groups d
                LEFT JOIN dealer_branches b ON b.dealer_group_id = d.id 
                LEFT JOIN master_states_areaslist msa ON b.pin_code = msa.cw_zip
                $where ORDER BY d.id, b.id DESC";
    $result = mysqli_query($this->connection, $query);

    if (!$result) {
        logSqlError(mysqli_error($this->connection), $query, 'users-exportDealerships');
        return false;
    }

    // Main Headers
    $main_headers = [
        ['name' => 'Dealer Info', 'colspan' => 5],
        ['name' => 'Basic Details',  'colspan' => 12],
        ['name' => 'Contact Details', 'colspan' => 8],
        ['name' => 'Legal Details', 'colspan' => 8],
        ['name' => 'Paymen Details', 'colspan' => 8],
    ];

    // Headers
    $headers = [
        ['name' => 'Dealer ID', 'type' => 'string'],
        ['name' => 'Dealer Name', 'type' => 'string'],
        ['name' => 'Short Name', 'type' => 'string'],
        ['name' => 'Website URL', 'type' => 'string'],
        ['name' => 'Active', 'type' => 'boolean'],
        ['name' => 'Outlet Code', 'type' => 'string'],
        ['name' => 'Branch ID', 'type' => 'string'],
        ['name' => 'Branch Name', 'type' => 'string'],
        ['name' => 'Address', 'type' => 'string'],
        ['name' => 'City', 'type' => 'string'],
        ['name' => 'State', 'type' => 'string'],
        ['name' => 'Pin Code', 'type' => 'string'],
        ['name' => 'Country', 'type' => 'string'],
        ['name' => 'Google Map Link', 'type' => 'string'],
        ['name' => 'Franchise Type', 'type' => 'string'],
        ['name' => 'Invoicing Enabled', 'type' => 'boolean'],
        ['name' => 'Main Branch' , 'type' => 'boolean'],
        ['name' => 'Contact Name', 'type' => 'string'],
        ['name' => 'Contact Country Code', 'type' => 'string'],
        ['name' => 'Contact Mobile', 'type' => 'string'],
        ['name' => 'Contact Country Code 2', 'type' => 'string'],
        ['name' => 'Contact Mobile 2', 'type' => 'string'],
        ['name' => 'Contact Telephone', 'type' => 'string'],
        ['name' => 'Contact Email', 'type' => 'string'],
        ['name' => 'Contact Email 2', 'type' => 'string'],
        ['name' => 'Registered Name', 'type' => 'string'],
        ['name' => 'GSTIN', 'type' => 'string'],
        ['name' => 'PAN', 'type' => 'string'],
        ['name' => 'TAN', 'type' => 'string'],
        ['name' => 'Payment Terms', 'type' => 'string'],
        ['name' => 'General Terms', 'type' => 'string'],
        ['name' => "EoE Terms",  "type"  => "string"],
        ['name' => "Jurisdiction Terms",  "type"  => "string"],
        ['name' => "Bank Name 1",  "type"  => "string"],
        ['name' => "Bank Account Number 1",  "type"  => "string"],
        ['name' => "Bank IFSC Code 1",  "type"  => "string"],
        ['name' => "Bank UPI ID 1",  "type"  => "string"],
        ['name' => "Bank Name 2",  "type"  => "string"],
        ['name' => "Bank Account Number 2",  "type"  => "string"],
        ['name' => "Bank IFSC Code 2",  "type"  => "string"],
        ['name' => "Bank UPI ID 2",  "type"  => "string"],
        // ['name' => 'Created', 'type' => 'datetime'],
        // ['name' => 'Updated', 'type' => 'datetime'],
        // ['name' => 'Created By', 'type' => 'string'],
        // ['name' => 'Updated By', 'type' => 'string']

    ];

    // Rows
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            $row['dealer_id'],
            $row['dealer_name'],
            $row['short_name'],
            $row['website_url'],
            $row['dealer_active'],
            $row['outlet_code'],
            $row['id'],
            $row['name'],
            $row['address'],
            $row['city_name'],
            $row['state_name'],
            $row['pin_code'],
            $row['country'],
            $row['google_map_link'],
            $config['franchise_type'][$row['franchise_type']],
            $row['invoicing_enabled'],
            $row['main_branch'],
            $row['contact_name'],
            $row['contact_country_code'],
            $row['contact_mobile'],
            $row['contact_country_code2'],
            $row['contact_mobile2'],
            $row['contact_telephone'],
            $row['contact_email'],
            $row['contact_email2'],
            $row['registered_name'],
            $row['gstin'],
            $row['pan'],
            $row['tan'],
            $row['payment_terms'],
            $row['general_terms'],
            $row['eoe_terms'],
            $row['jurisdiction_terms'],
            $row['bank_name1'],
            $row['bank_account_number1'],
            $row['bank_ifsc_code1'],
            $row['bank_upi_id1'],
            $row['bank_name2'],
            $row['bank_account_number2'],
            $row['bank_ifsc_code2'],
            $row['bank_upi_id2'],
            // $row['created'],
            // $row['updated'],
            // $row['created_by'],
            // $row['updated_by']

        ];
    }

    $filename = 'dealerships_' . time() . '.xlsx';

    // Call common function
    $publicUrl = exportExcelFile($headers, $rows, $filename, $main_headers);
    return ["file_url" => $publicUrl];
}




}
?>