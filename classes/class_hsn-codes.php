<?php
class HSN
{
    public function __construct() {
        global $connection;        
        $this->module_name = "HSN Codes";
        $this->connection = $connection;
        $this->login_user_id = $GLOBALS['api_user']['uid'];
    }
    public function getAllHsnCodes($page = 1,$perPage = 10)
    {
        $count_query = "SELECT COUNT(*) as total FROM hsn_codes";
        $count_res = mysqli_query($this->connection,$count_query);
        if(!$count_res)
        {
            logSqlError(mysqli_error($this->connection), $count_query, 'admin-hsn-codes', true);
        }
        $count_cnt = mysqli_fetch_assoc($count_res);
        
        $count = $count_cnt['total'];

        $start = ($page - 1) * $perPage;
        $start_count = $count > 0 ? ($start + 1) : 0;
        $end_count = min($start + $perPage, $count);

        $hsn_query = "SELECT * FROM `hsn_codes` WHERE 1 LIMIT $start , $perPage"; 
        $hsn_res = mysqli_query($this->connection,$hsn_query);
        if(!$hsn_res)
        {
            logSqlError(mysqli_error($this->connection), $hsn_query, 'admin-hsn-codes', true);
        }
        $hsn_list = [];
        while($row = mysqli_fetch_assoc($hsn_res))
        {
            $hsn_list[] = $row;
        }

        if(empty($hsn_list))
        {
            api_response(200,"empty","Empty HSN Code list.");
        }
        api_response(200,"ok","HSN list fetched successsfully.",["hsn_list" => $hsn_list,"start_count" => $start_count,"end_count" => $end_count,"total" => $count]);
    }
    public function addHsnCodes(){

        $hsn_code = isset($_POST['hsn_code'])? mysqli_real_escape_string($this->connection, $_POST['hsn_code']):'';
        $description = isset($_POST['description'])?mysqli_real_escape_string($this->connection, $_POST['description']):'';
        $cgst =isset($_POST['cgst'])? mysqli_real_escape_string($this->connection, $_POST['cgst']):0;
        $sgst = isset($_POST['sgst'])?mysqli_real_escape_string($this->connection, $_POST['sgst']):0;
        $igst = isset($_POST['igst'])? mysqli_real_escape_string($this->connection, $_POST['igst']):0;

        $checkQuery = "SELECT id FROM hsn_codes WHERE hsn_code = '$hsn_code'";
        $checkResult = mysqli_query($this->connection, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing Variant: " . mysqli_error($this->connection));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(200,"fail","HSN code already exists");
        }
        $query = "INSERT INTO hsn_codes(hsn_code,`description`,cgst,sgst,igst,active) 
                  VALUES ('$hsn_code','$description',$cgst,$sgst,$igst,1)";
        
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            throw new Exception("Failed to add HSN Code: " . mysqli_error($this->connection));
        }
        api_response(200,"ok","HSN code added successsfully.");

    }
    public function updateHsnCodes($id){

        $hsn_code = isset($_POST['hsn_code'])? mysqli_real_escape_string($this->connection, $_POST['hsn_code']):'';
        $description = isset($_POST['description'])?mysqli_real_escape_string($this->connection, $_POST['description']):'';
        $cgst =isset($_POST['cgst'])? mysqli_real_escape_string($this->connection, $_POST['cgst']):0;
        $sgst = isset($_POST['sgst'])?mysqli_real_escape_string($this->connection, $_POST['sgst']):0;
        $igst = isset($_POST['igst'])? mysqli_real_escape_string($this->connection, $_POST['igst']):0;
        $active=isset($_POST['active'])? mysqli_real_escape_string($this->connection, $_POST['active']):0;
        $edit_id=isset($id)?$id:0;
      
        $checkQuery = "SELECT id FROM hsn_codes WHERE hsn_code = '$hsn_code' and id!=".$edit_id;
        $checkResult = mysqli_query($this->connection, $checkQuery);
        if (!$checkResult) {
            
            throw new Exception("Failed to check existing HSN Code: " . mysqli_error($this->connection));
        }
        if (mysqli_num_rows($checkResult) > 0) {
            api_response(200,"fail","HSN code already exists");
        }
        $query = "update hsn_codes set hsn_code='$hsn_code',`description`='$description',cgst=$cgst,sgst=$sgst,igst=$igst,active=$active where id=".$edit_id;
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            throw new Exception("Failed to add HSN Code: " . mysqli_error($this->connection));
        }
        api_response(200,"ok","HSN code updated successsfully.");
    }

    public function getHsnCodes($hsn = ''){
        $hsn_query = "SELECT * FROM `hsn_codes` WHERE hsn_code = $hsn"; 
        $hsn_res = mysqli_query($this->connection,$hsn_query);
        if(!$hsn_res)
        {
            logSqlError(mysqli_error($this->connection), $hsn_query, 'admin-hsn-codes', true);
        }
        $options = [];
        while ($row = mysqli_fetch_assoc($hsn_res)) {
            $code = trim($row['hsn_code'] ?? '');
            $desc = trim($row['description'] ?? '');
            if ($code !== '') {
                $options[] = [
                    'value' => $code,
                    'label' => $desc !== '' ? "{$code} - {$desc}" : $code
                ];
            }
        }

        if (empty($options)) {
            api_response(200, "empty", "Empty HSN Code list.");
        }

        return $options;

    }
}
?>