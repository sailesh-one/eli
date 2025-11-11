<?php
class Features
{
    public $dealer_id;
    public $connection;
    public $module_name;

    public function __construct() {
        global $connection, $config, $redis;        
        $this->module_name = "feature-flags";
        $this->connection = $connection;
    }
    public function get_feature_flags() 
    {
        // Fetch paginated data
        $query = "SELECT * FROM feature_flags";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-feature-flags', true);
        } 

        while ($row = mysqli_fetch_assoc($result)) {
            $flags[] = $row;
        }

        return [
            "flags" => $flags ?? [],
        ];
    }

    public function add_feature_flag(){
        $flag_name = mysqli_real_escape_string($this->connection, $_POST['flag_name'] ?? '');
        $description = mysqli_real_escape_string($this->connection, $_POST['description'] ?? '');
        $flag_type = mysqli_real_escape_string($this->connection, $_POST['flag_type'] ?? '');
        $value = mysqli_real_escape_string($this->connection, $_POST['value'] ?? '');
        $added_on = date('Y-m-d H:i:s');
        $added_by = $GLOBALS['api_user']['uid'] ?? null;

        $query = "INSERT INTO feature_flags (flag_name, description, flag_type, value, added_by, added_on) 
                    VALUES ('$flag_name', '$description', '$flag_type', '$value', '$added_by', '$added_on')";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'add-feature-flag', true);
            return false;
        }
        $flag_id = mysqli_insert_id($this->connection);
        if ($flag_id) {
            $new_flag = $this->get_feature_flags();
            return true;
        }
    }

    public function update_feature_flag() {
        $id = mysqli_real_escape_string($this->connection, $_POST['id'] ?? '');
        $flag_name = mysqli_real_escape_string($this->connection, $_POST['flag_name'] ?? '');
        $description = mysqli_real_escape_string($this->connection, $_POST['description'] ?? '');
        $flag_type = mysqli_real_escape_string($this->connection, $_POST['flag_type'] ?? '');
        $value = mysqli_real_escape_string($this->connection, $_POST['value'] ?? '');
        $updated_on = date('Y-m-d H:i:s');
        $updated_by = $GLOBALS['api_user']['uid'] ?? null;

        $query = "UPDATE feature_flags SET flag_name='$flag_name', description='$description', flag_type='$flag_type', 
                    value='$value', updated_by='$updated_by', updated_on='$updated_on' WHERE id='$id'";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'update-feature-flag', true);
            return false;
        }
        return true;
    }

    public function delete_feature_flag(){
        $id = mysqli_real_escape_string($this->connection, $_POST['id'] ?? '');
        $query = "DELETE FROM feature_flags WHERE id = $id";
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'delete-feature-flag', true);
            return false;
        }
        return true; 
    }

    public function checkFeature() {
        $flag_name = mysqli_real_escape_string($this->connection, $_POST['flag_name'] ?? '');
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

        $query = "SELECT COUNT(*) as cnt FROM feature_flags WHERE flag_name = '$flag_name'";
        if ($id) {
            $query .= " AND id != $id";
        }

        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'check-feature-flag', true);
            return false;
        }

        $row = mysqli_fetch_assoc($result);
        return ($row['cnt'] > 0);
    }

    public function getFeature($name) {
        $flag_name = mysqli_real_escape_string($this->connection, $name ?? '');

        $query = "SELECT * FROM feature_flags WHERE flag_name = '$flag_name' LIMIT 1";
        $result = mysqli_query($this->connection, $query);

        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'get-feature-flag', true);
            return false;
        }

        $row = mysqli_fetch_assoc($result);
        return $row ?: null;
    }

}
