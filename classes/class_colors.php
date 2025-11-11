<?php
class Color
{
    public $con;
    
    public function __construct()
    {
        global $connection,$auth;
        $this->con = $connection;
    }
    public function getmakes()
    {
        $query = "SELECT id, make FROM master_makes WHERE is_brand_group = 'y' ORDER BY id ASC";
        $res = mysqli_query($this->con, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->con), $query, 'getmakes', true);
        }
        
        $makes = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $makes[] = $row;
        }
        
        $makes[] = ['id' => 0, 'make' => 'Other'];
        
        api_response(200, "ok", "Makes list fetched successfully.", ["makes" => $makes]);
    }
    
    public function getInteriorColors($page = 1, $perPage = 10)
    {
        $page = max(1, intval($page));
        $perPage = max(1, intval($perPage));
        
        $count_query = "SELECT COUNT(*) as total FROM interior_colors";
        $count_res = mysqli_query($this->con, $count_query);
        if (!$count_res) {
            logSqlError(mysqli_error($this->con), $count_query, 'Interior-Colors-count', true);
        }
        $count_row = mysqli_fetch_assoc($count_res);
        $total = intval($count_row['total'] ?? 0);
        
        $start = ($page - 1) * $perPage;
        $start = max(0, $start);
        
        $q = "SELECT * FROM interior_colors ORDER BY id DESC LIMIT $start, $perPage";
        $res = mysqli_query($this->con, $q);
        if (!$res) {
            logSqlError(mysqli_error($this->con), $q, 'Interior-Colors-list', true);
        }
        
        $list = [];
        while ($row = mysqli_fetch_assoc($res)) {
            if (!isset($row['make_id']) || intval($row['make_id']) === 0) {
                $row['make_id'] = 0;
                $row['make'] = 'Other';
            }
            $list[] = $row;
        }
        
        if (empty($list)) {
            api_response(200, "empty", "Empty interior colors list.");
        }
        
        api_response(200, "ok", "Interior Colors list fetched successfully.", [
            "interior_colors" => $list,
            "start_count" => ($total > 0 ? $start + 1 : 0),
            "end_count" => min($start + $perPage, $total),
            "total" => $total
        ]);
    }
    public function addInteriorColor()
    {
        $make_id_raw = $_POST['make'] ?? '';
        $make_id = intval($make_id_raw);
        $interior_color = trim($_POST['interior_color'] ?? '');
        $base_color = trim($_POST['base_color'] ?? '');
        $active = trim($_POST['active'] ?? 'y');
        $created_by = $GLOBALS['user']['uid'] ?? 0;

        // print_r($GLOBALS);exit;
        
        // basic validation
        $errors = [];
        if ($interior_color === '') $errors['interior_color'] = 'Interior color is required.';
        if ($base_color === '') $errors['base_color'] = 'Base color is required.';
        if ($make_id_raw === '' && $make_id_raw !== '0') $errors['make'] = 'Make is required.'; // allow '0'
        
        if (!empty($errors)) {
            api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
            return false;
        }
        
        // determine make name
        if ($make_id > 0) {
            $mq = "SELECT make FROM master_makes WHERE id = '".mysqli_real_escape_string($this->con, $make_id)."'";
            $mres = mysqli_query($this->con, $mq);
            $mrow = $mres ? mysqli_fetch_assoc($mres) : null;
            $make_name = $mrow['make'] ?? 'Other';
        } else {
            // treat any non-positive id as Other; store make_id=0
            $make_id = 0;
            $make_name = 'Other';
        }
        
        // duplicate check (make + interior_color) OR base_color duplicate across same make
        $dup_q = "SELECT COUNT(*) as cnt FROM interior_colors
                  WHERE make = '".mysqli_real_escape_string($this->con, $make_name)."'
                  AND (interior_color = '".mysqli_real_escape_string($this->con, $interior_color)."'
                       AND base_color = '".mysqli_real_escape_string($this->con, $base_color)."')";
        $dup_res = mysqli_query($this->con, $dup_q);
        if (!$dup_res) { logSqlError(mysqli_error($this->con), $dup_q, 'addInterior-dup', true); }
        $dup_row = mysqli_fetch_assoc($dup_res);
        if (intval($dup_row['cnt'] ?? 0) > 0) {
            api_response(409, 'fail', 'Duplicate entry.');
            return false;
        }
        
        // insert
        $ins_q = "INSERT INTO interior_colors (make_id, make, interior_color, base_color, active, created_by)
                  VALUES (
                    '".mysqli_real_escape_string($this->con, $make_id)."',
                    '".mysqli_real_escape_string($this->con, $make_name)."',
                    '".mysqli_real_escape_string($this->con, $interior_color)."',
                    '".mysqli_real_escape_string($this->con, $base_color)."',
                    '".mysqli_real_escape_string($this->con, $active)."',
                    '".mysqli_real_escape_string($this->con, $created_by)."'
                  )";
        $ins_res = mysqli_query($this->con, $ins_q);
        if (!$ins_res) {
            logSqlError(mysqli_error($this->con), $ins_q, 'addInterior-insert', true);
            api_response(500, 'fail', 'Failed to save interior color.');
            return false;
        }
        
        $insert_id = mysqli_insert_id($this->con);
        $sel = mysqli_query($this->con, "SELECT * FROM interior_colors WHERE id = $insert_id");
        $row = $sel ? mysqli_fetch_assoc($sel) : null;
        
        api_response(200, 'ok', 'Interior color added successfully.', ['color' => $row]);
    }
    
    public function updateInteriorColor()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            api_response(422, 'fail', 'Invalid id.');
            return false;
        }
        
        $make_id_raw = $_POST['make'] ?? '';
        $make_id = intval($make_id_raw);
        $interior_color = trim($_POST['interior_color'] ?? '');
        $base_color = trim($_POST['base_color'] ?? '');
        $active = trim($_POST['active'] ?? 'y');
        $updated_by = $GLOBALS['user']['uid'] ?? 0;

        
        $errors = [];
        if ($interior_color === '') $errors['interior_color'] = 'Interior color is required.';
        if ($base_color === '') $errors['base_color'] = 'Base color is required.';
        if ($make_id_raw === '' && $make_id_raw !== '0') $errors['make'] = 'Make is required.'; // allow '0'
        
        if (!empty($errors)) {
            api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
            return false;
        }
        
        // --- Check if make exists ---
        if ($make_id > 0) {
            $mq = "SELECT make FROM master_makes WHERE id = '" . mysqli_real_escape_string($this->con, $make_id) . "'";
            $mres = mysqli_query($this->con, $mq);
            $mrow = $mres ? mysqli_fetch_assoc($mres) : null;
            $make_name = $mrow['make'] ?? 'Other';
        } else {
            $make_id = 0;
            $make_name = 'Other';
        }
        
        // --- Duplicate check (ignore same ID) ---
        // $dup_q = "SELECT id FROM interior_colors 
        //       WHERE make_id = '" . mysqli_real_escape_string($this->con, $make_id) . "'
        //       AND interior_color = '" . mysqli_real_escape_string($this->con, $interior_color) . "'
        //     AND base_color = '" . mysqli_real_escape_string($this->con, $base_color) . "'

        //     //   AND id != $id";
        // $dup_res = mysqli_query($this->con, $dup_q);
        
        // if ($dup_res && mysqli_num_rows($dup_res) > 0) {
        //     api_response(409, 'fail', 'Duplicate entry.');
        //     return false;
        // }


             // duplicate check (make + interior_color) OR base_color duplicate across same make
        $dup_q = "SELECT * FROM interior_colors
                  WHERE make = '".mysqli_real_escape_string($this->con, $make_name)."'
                  AND (interior_color = '".mysqli_real_escape_string($this->con, $interior_color)."'
                       AND base_color = '".mysqli_real_escape_string($this->con, $base_color)."')";
        $dup_res = mysqli_query($this->con, $dup_q);

        if (!$dup_res) { logSqlError(mysqli_error($this->con), $dup_q, 'addInterior-dup', true); }
        $dup_row = mysqli_fetch_assoc($dup_res);
        $dup_row = !is_array($dup_row) ? [] : $dup_row;
        if(count($dup_row) > 0 && $dup_row['id'] != $id) {
            api_response(409, 'fail', 'Duplicate entry.');
            return false;
        }
        
        // --- Update record ---
        $upd_q = "UPDATE interior_colors
              SET make_id = '" . mysqli_real_escape_string($this->con, $make_id) . "',
                  make = '" . mysqli_real_escape_string($this->con, $make_name) . "',
                  interior_color = '" . mysqli_real_escape_string($this->con, $interior_color) . "',
                  base_color = '" . mysqli_real_escape_string($this->con, $base_color) . "',
                  active = '" . mysqli_real_escape_string($this->con, $active) . "',
                  updated_by = '".mysqli_real_escape_string($this->con, $updated_by)."'

              WHERE id = $id";
        
        $upd_res = mysqli_query($this->con, $upd_q);
        if (!$upd_res) {
            logSqlError(mysqli_error($this->con), $upd_q, 'updateInteriorColor', true);
            api_response(500, 'fail', 'Failed to update interior color.');
            return false;
        }
        
        // --- Fetch updated row ---
        $sel = mysqli_query($this->con, "SELECT * FROM interior_colors WHERE id = $id");
        $row = $sel ? mysqli_fetch_assoc($sel) : null;
        
        api_response(200, 'ok', 'Interior color updated successfully.', ['color' => $row]);
    }
    
    
    public function getExteriorColors($page = 1, $perPage = 10)
    {
        $page = max(1, intval($page));
        $perPage = max(1, intval($perPage));
        
        $count_query = "SELECT COUNT(*) as total FROM exterior_colors";
        $count_res = mysqli_query($this->con, $count_query);
        if (!$count_res) { logSqlError(mysqli_error($this->con), $count_query, 'Exterior-Colors-count', true); }
        $total = intval(mysqli_fetch_assoc($count_res)['total'] ?? 0);
        
        $start = ($page - 1) * $perPage;
        $q = "SELECT * FROM exterior_colors ORDER BY id DESC LIMIT $start, $perPage";
        $res = mysqli_query($this->con, $q);
        if (!$res) { logSqlError(mysqli_error($this->con), $q, 'Exterior-Colors-list', true); }
        
        $list = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $list[] = $row;
        }
        
        if (empty($list)) {
            api_response(200, "empty", "No exterior colors found.");
        }
        
        api_response(200, "ok", "Exterior Colors list fetched successfully.", [
            "exterior_colors" => $list,
            "start_count" => ($total > 0 ? $start + 1 : 0),
            "end_count" => min($start + $perPage, $total),
            "total" => $total
        ]);
    }
    
    public function addExteriorColor()
    {
        $make_id_raw = trim($_POST['make'] ?? '');
        $make_id = intval($make_id_raw);
        $exterior_color = trim($_POST['exterior_color'] ?? '');
        $base_color = trim($_POST['base_color'] ?? '');
        $active = trim($_POST['active'] ?? 'y');
        $created_by = $GLOBALS['user']['uid'] ?? 0;

        
        $errors = [];
        if ($make_id_raw === '') $errors['make'] = 'Make is required.';
        if ($exterior_color === '') $errors['exterior_color'] = 'Exterior color is required.';
        if ($base_color === '') $errors['base_color'] = 'Base color is required.';
        
        if (!empty($errors)) {
            api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
            return false;
        }
        
        if ($make_id > 0) {
            $mq = "SELECT make FROM master_makes WHERE id = '".mysqli_real_escape_string($this->con, $make_id)."'";
            $mres = mysqli_query($this->con, $mq);
            $mrow = $mres ? mysqli_fetch_assoc($mres) : null;
            $make_name = $mrow['make'] ?? 'Other';
        } else {
            $make_id = 0;
            $make_name = 'Other';
        }

        // print_r($make_name);exit;
        
        // --- DUPLICATE CHECK ---
        //     $dup_q = "
        //     SELECT id FROM exterior_colors 
        //     WHERE make_id = '".mysqli_real_escape_string($this->con, $make_id)."' 
        //       AND LOWER(exterior_color) = LOWER('".mysqli_real_escape_string($this->con, $exterior_color)."')
        // ";
        //     $dup_res = mysqli_query($this->con, $dup_q);
        //     if ($dup_res && mysqli_num_rows($dup_res) > 0) {
        //         api_response(409, 'fail', 'Duplicate entry. Exterior color already exists for this make.', [], []);
        //         return false;
        //     }

        // duplicate check (make + interior_color) OR base_color duplicate across same make
        $dup_q = "SELECT COUNT(*) as cnt FROM exterior_colors
                  WHERE make = '".mysqli_real_escape_string($this->con, $make_name)."'
                  AND (exterior_color = '".mysqli_real_escape_string($this->con, $exterior_color)."'
                       AND base_color = '".mysqli_real_escape_string($this->con, $base_color)."')";
        $dup_res = mysqli_query($this->con, $dup_q);
        if (!$dup_res) { logSqlError(mysqli_error($this->con), $dup_q, 'addExterior-dup', true); }
        $dup_row = mysqli_fetch_assoc($dup_res);
        // print_r($dup_row);exit;
        if (intval($dup_row['cnt'] ?? 0) > 0) {
            api_response(409, 'fail', 'Duplicate entry.');
            return false;
        }


        
        // --- INSERT QUERY ---
        $ins_q = "
        INSERT INTO exterior_colors (make_id, make, exterior_color, base_color, active,created_by)
        VALUES (
            '".mysqli_real_escape_string($this->con, $make_id)."',
            '".mysqli_real_escape_string($this->con, $make_name)."',
            '".mysqli_real_escape_string($this->con, $exterior_color)."',
            '".mysqli_real_escape_string($this->con, $base_color)."',
            '".mysqli_real_escape_string($this->con, $active)."',
            '".mysqli_real_escape_string($this->con, $created_by)."'
        )
         ";
        $ins_res = mysqli_query($this->con, $ins_q);
        
        if (!$ins_res) {
            logSqlError(mysqli_error($this->con), $ins_q, 'addExteriorColor', true);
            api_response(500, 'fail', 'Failed to add exterior color.');
            return false;
        }
        
        // --- FETCH INSERTED RECORD ---
        $id = mysqli_insert_id($this->con);
        $sel = mysqli_query($this->con, "SELECT * FROM exterior_colors WHERE id = $id");
        $row = $sel ? mysqli_fetch_assoc($sel) : null;
        
        api_response(200, 'ok', 'Exterior color added successfully.', ['color' => $row]);
    }
    
    
    public function updateExteriorColor()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) { api_response(422, 'fail', 'Invalid id.'); return false; }
        $make_id_raw = $_POST['make'] ?? '';
        $make_id = intval($make_id_raw);
        $exterior_color = trim($_POST['exterior_color'] ?? '');
        $base_color = trim($_POST['base_color'] ?? '');
        $active = trim($_POST['active'] ?? 'y');
        $updated_by = $GLOBALS['user']['uid'] ?? 0;

        
        $errors = [];
        if ($make_id_raw === '' && $make_id_raw !== '0') $errors['make'] = 'Make is required.'; // allow '0'
        if ($exterior_color === '') $errors['exterior_color'] = 'Exterior color required.';
        if ($base_color === '') $errors['base_color'] = 'Base color required.';
        
        if (!empty($errors)) { api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []); return false; }
        


         if ($make_id > 0) {
            $mq = "SELECT make FROM master_makes WHERE id = '" . mysqli_real_escape_string($this->con, $make_id) . "'";
            $mres = mysqli_query($this->con, $mq);
            $mrow = $mres ? mysqli_fetch_assoc($mres) : null;
            $make_name = $mrow['make'] ?? 'Other';
        } else {
            $make_id = 0;
            $make_name = 'Other';
        }
        

         $dup_q = "SELECT * FROM exterior_colors
                  WHERE make = '".mysqli_real_escape_string($this->con, $make_name)."'
                  AND (exterior_color = '".mysqli_real_escape_string($this->con, $exterior_color)."'
                       AND base_color = '".mysqli_real_escape_string($this->con, $base_color)."')";
        $dup_res = mysqli_query($this->con, $dup_q);

        if (!$dup_res) { logSqlError(mysqli_error($this->con), $dup_q, 'addInterior-dup', true); }
        $dup_row = mysqli_fetch_assoc($dup_res);
        $dup_row = !is_array($dup_row) ? [] : $dup_row;
        if(count($dup_row) > 0 && $dup_row['id'] != $id) {
            api_response(409, 'fail', 'Duplicate entry.');
            return false;
        }
        // --- Update record ---
        $upd_q = "UPDATE exterior_colors
              SET make_id = '" . mysqli_real_escape_string($this->con, $make_id) . "',
                  make = '" . mysqli_real_escape_string($this->con, $make_name) . "',
                  exterior_color = '" . mysqli_real_escape_string($this->con, $exterior_color) . "',
                  base_color = '" . mysqli_real_escape_string($this->con, $base_color) . "',
                  active = '" . mysqli_real_escape_string($this->con, $active) . "',
                  updated_by = '".mysqli_real_escape_string($this->con, $updated_by)."'

              WHERE id = $id";
        
        $upd_res = mysqli_query($this->con, $upd_q);
        if (!$upd_res) {
            logSqlError(mysqli_error($this->con), $upd_q, 'updateInteriorColor', true);
            api_response(500, 'fail', 'Failed to update interior color.');
            return false;
        }
        
        $sel = mysqli_query($this->con, "SELECT * FROM exterior_colors WHERE id = $id");
        $row = $sel ? mysqli_fetch_assoc($sel) : null;
        api_response(200, 'ok', 'Exterior color updated successfully.', ['color' => $row]);
    }
}
?>