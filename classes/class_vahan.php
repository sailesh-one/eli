<?php

class Vahan
{
   public $token;
   public $end_point;
   public $reg_number;
   public $statusCode;
   public $client;
   public $user_id;
   public $api_response_headers;
   public $api_response_codes;

   	public function __construct()
   	{
		global $config;
	  	$this->reg_number = isset($_POST['reg_number']) ? trim($_POST['reg_number']) : '';
		$this->user_id = $GLOBALS['dealership']['dealership_id'];      		
		$this->end_point = $config['vahan_end_point'] ?? '';
		$this->token = $config['vahan_key'] ?? '';
		$this->client= $config['vahan_client'] ?? '';
		$this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';

		
      	$this->api_response_headers = array(
			"400" => "Bad Request",
		    "401" => "Unauthorized Access",
		    "402" => "Insufficient Credits",
		    "500" => "Internal Server Error",
		    "503" => "Service Unavailable",
		    "504" => "Endpoint Request Timeout"
		);

      	$this->api_response_codes = array(
	        "101" => "Valid Authentication",
	        "102" => "Invalid ID number or combination of inputs",
	        "103" => "No Records Found for the given ID or combination of inputs",
	        "104" => "Max retries exceeded",
	        "105" => "Missing Consent",
	        "106" => "Multiple Records Exist",
	        "107" => "Not Supported",
	        "108" => "Internal Resource Unavailable",
	        "109" => "Too many Records Found",
	        "110" => "Invalid Registration Number"
	    );
   	}
		private function sanitizeField($value, $fieldType = 'address')
		{
			if (!$value) return '';

			return trim(preg_replace(
				$fieldType === 'chassis'
					? '/[^a-zA-Z0-9]/'
					: '/[^a-zA-Z0-9.,@\-\/ ]/',
				'',
				$value
			));
		}

	// public function fetchVahanDetails()
	// {		
	// 	$post_fields = [
	// 		'client' => $this->client,
	// 		'regnum' => $this->reg_number,
	// 		'dealer' => $GLOBALS['dealership']['dealership_name'] ?? ''
	// 	];

	// 	$enc_data = $this->encryptData(json_encode($post_fields));
	// 	$headers = [
	// 		"Content-Type: application/json",
	// 		"vtoken: $enc_data"
	// 	];

	// 	$request = new CurlRequest($this->end_point);
	// 	$request->headers = $headers;
	// 	$response = $request->post(json_encode($post_fields));

	// 	$this->saveResponse($this->reg_number, $response['body'] ?? '');

	// 	$final_response = [];

	// 	if (($response['status_code'] ?? 0) == 200) {
	// 		$final_response = json_decode($response['body'], true);

	// 		if (json_last_error() !== JSON_ERROR_NONE) {
	// 			return ["status" => false, "msg" => "Invalid JSON response.", "data" => []];
	// 		}

	// 		if (!empty($final_response['status']) && $final_response['status'] === "success") {
	// 			$ownerName = $final_response['result']['ownerName'] ?? '';
	// 			$ownerSerialNumber = $final_response['result']['ownerSerialNumber'] ?? '';
	// 			$chassisNumber = $final_response['result']['chassisNumber'];
	// 			$first_name = $ownerName;
	// 			$owners = '';
	// 			$financier = $final_response['result']['financier'] ?? '';
	// 			$fuel = $final_response['result']['fuelDescription'] ?? '';
	// 			$last_name = '';
	// 			$mfg_year = "";
	// 			$mfg_month = "";


	// 			if (!empty($ownerName) && preg_match('/\s/', $ownerName)) {
	// 				$parts = preg_split('/\s+/', trim($ownerName));
	// 				$first_name = $parts[0] ?? '';
	// 				$last_name  = $parts[1] ?? '';
	// 			}

	// 			$reg_date = '';
	// 			if (!empty($final_response['result']['registrationDate'])) {
	// 				$reg_date = date('Y-m-d', strtotime($final_response['result']['registrationDate']));
	// 			}

	// 			if (!empty($final_response['result']['ownerSerialNumber'])) {
	// 				$owners = array_search($ownerSerialNumber, $this->commonConfig['owners'], true);
	// 				if (array_key_exists($ownerSerialNumber, $this->commonConfig['owners'])) {
	// 					$owners = $ownerSerialNumber;
	// 				}
	// 			}

	// 			if (!empty($final_response['result']['manufacturedMonthYear'])) {
    // 				$mfgData = explode("/", $final_response['result']['manufacturedMonthYear']);
	// 				if (count($mfgData) == 2 && strlen($mfgData[0]) == 2 && strlen($mfgData[1]) == 4) {
	// 					$mfg_month = $mfgData[0];
	// 					$mfg_year  = $mfgData[1]; 
	// 				}
	// 			}

	// 			$insurance_exp_date = '';
	// 			if (!empty($final_response['result']['insuranceUpto'])) {
	// 				$insurance_exp_date = date('Y-m-d', strtotime($final_response['result']['insuranceUpto']));	
	// 			}

	// 			$chassis = '';
	// 			if (!empty($chassisNumber) && preg_match('/\d+$/', $chassisNumber)) {
	// 				$chassis = $chassisNumber;
	// 			}

	// 			if (!empty($fuel) && $fuel !='') {
	// 				$fuel_type = strtolower($fuel);
	// 				$fuel_type = ucfirst($fuel_type);
	// 				$fuel = array_search($fuel_type, $this->commonConfig['fuel']);
	// 			}

	// 			if($first_name != ""){
	// 				$data['first_name'] = $first_name;	
	// 			}
	// 			if($last_name != ""){
	// 				$data['last_name'] = $last_name;	
	// 			}
	// 			if($reg_date != ""){
	// 				$data['reg_date'] = $reg_date;	
	// 			}
	// 			if($chassis != ""){
	// 				$data['chassis'] = $chassis;	
	// 			}if($final_response['result']['fitnessUpto'] != ""){
	// 				$data['fitness_upto'] = $final_response['result']['fitnessUpto'];	
	// 			}
	// 			if($insurance_exp_date != ""){
	// 				$data['insurance_exp_date'] = $insurance_exp_date;	
	// 			}
	// 			if($owners != ""){
	// 				$data['owners'] = $owners;	
	// 			}
	// 			if($financier != ""){
	// 				$data['financier'] = $financier;	
	// 			}
	// 			if($fuel != ""){
	// 				$data['fuel'] = $fuel;	
	// 			}
	// 			if($mfg_month != ""){
	// 				$data['mfg_month'] = $mfg_month;	
	// 			}
	// 			if($mfg_year != ""){
	// 				$data['mfg_year'] = $mfg_year;	
	// 			}

	// 			return ["status" => true, "msg" => "success", "data" => $data];
	// 		} else {
	// 			return ["status" => false, "msg" => "Vahan data not found", "data" => []];
	// 		}
	// 	} else {
	// 		return ["status" => false, "msg" => "Invalid response from Vahan.", "data" => []];
	// 	}
	// }


	public function fetchVahanDetailsComplete()
	{		
		$post_fields = [
			'client' => $this->client,
			'regnum' => $this->reg_number,
			'dealer' => $GLOBALS['dealership']['dealership_name'] ?? ''
		];

		$enc_data = $this->encryptData(json_encode($post_fields));
		$headers = [
			"Content-Type: application/json",
			"vtoken: $enc_data"
		];

		$request = new CurlRequest($this->end_point);
		$request->headers = $headers;
		$response = $request->post(json_encode($post_fields));

		$this->saveResponse($this->reg_number, $response['body'] ?? '');

		$final_response = [];

		if (($response['status_code'] ?? 0) == 200) {
			$final_response = json_decode($response['body'], true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				return ["status" => false, "msg" => "Invalid JSON response.", "data" => []];
			}

			if (!empty($final_response['status']) && $final_response['status'] === "success") {
				// Return complete Vahan response with processed fields for backward compatibility
				$result = $final_response['result'];
				$res =[];

				// echo "<pre>";
				// print_r($result);
				// echo "</pre>";
				// exit;
				
				// Process some fields for backward compatibility
				$ownerName = $result['ownerName'] ?? '';
				$first_name = $ownerName;
				$last_name = '';
				
				if (!empty($ownerName) && preg_match('/\s/', $ownerName)) {
					$parts = preg_split('/\s+/', trim($ownerName));
					$first_name = $parts[0] ?? '';
					$last_name = $parts[1] ?? '';
				}

				$reg_date = '';
				if (!empty($result['registrationDate'])) {
					$reg_date = date('Y-m-d', strtotime($result['registrationDate']));
				}

				$insurance_exp_date = '';
				if (!empty($result['insuranceUpto'])) {
					$insurance_exp_date = date('Y-m-d', strtotime($result['insuranceUpto']));	
				}

			$mfg_year = "";
			$mfg_month = "";
			if (!empty($result['manufacturedMonthYear'])) {
				$mfgData = explode("/", $result['manufacturedMonthYear']);
				if (count($mfgData) == 2 && strlen($mfgData[0]) == 2 && strlen($mfgData[1]) == 4) {
					$mfg_month = ltrim($mfgData[0], '0'); // Remove leading zero to match config format (1-12)
					$mfg_year = $mfgData[1]; 
				}
			}				// Add processed fields to the result
				$res['first_name'] = $first_name;
				$res['last_name'] = $last_name;
				$res['reg_date'] = $reg_date;
				$res['insurance_exp_date'] = $insurance_exp_date;
				$res['mfg_year'] = $mfg_year;
				$res['mfg_month'] = $mfg_month;

				// Map some fields for consistency with sanitization
				$res['chassis'] = $this->sanitizeField($result['chassisNumber'] ?? '', 'chassis');
				$res['owners'] = $result['ownerSerialNumber'] ?? '';
				$res['fuel'] = $result['fuelDescription'] ?? '';
				$res['rc_address'] = $this->sanitizeField($result['presentAddress'] ?? '', 'address'); 

				return ["status" => true, "msg" => "success", "data" => $res];
			} else {
				return ["status" => false, "msg" => "Vahan data not found", "data" => []];
			}
		} else {
			return ["status" => false, "msg" => "Invalid response from Vahan.", "data" => []];
		}
	}
    public function regNumberValidation()
    {
		$reg_no = strtoupper(preg_replace('/[\s\-]/', '',$this->reg_number));		
		$reg_no_length = strlen($reg_no);
		$check_bh = ($reg_no_length == 10) ? substr($reg_no, 2, -6) : substr($reg_no, 2, -5);
		$check_delhi = substr($reg_no,0, 2);

		if(isset($check_bh) && $check_bh =='BH')
        {
			if($reg_no !='' && validate_field_regex('bharat_reg_number',$reg_no)) return true;           
            else return false;
           
		}
        else if(isset($check_delhi) && $check_delhi =='DL')
        {
			if($reg_no !='' && validate_field_regex('delhi_reg_number',$reg_no)) return true;           
            else return false;           
		}
        else if($reg_no_length > 7)
        {
			if($reg_no !='' && validate_field_regex('reg_num',$reg_no)) return true;           
            else return false;          
		}
        else
        {
			if($reg_no !='' && validate_field_regex('old_reg_number',$reg_no)) return true;          
            else return false;
		}		
	}
    
    public function encryptData($data)
    {
		$result = hash_hmac('sha256', $data, $this->token);
		return $result;
	}

    public function saveResponse($reg_number, $response)
	{
		global $connection;
		$resp = json_decode($response, true);
		$api_response = mysqli_real_escape_string($connection, json_encode($resp));
		$status_code = $resp['statusCode'] ?? '';
		$created_at = date('Y-m-d H:i:s');

		
		$user_id    = (int)$this->user_id;
		$reg_number = mysqli_real_escape_string($connection, $reg_number);
		$status_code = mysqli_real_escape_string($connection, $status_code);
		$created_at = mysqli_real_escape_string($connection, $created_at);

		$query = "
			INSERT INTO vahan_api_log 
				(user_id, reg_num, response, status_code, created_at) 
			VALUES 
				('$user_id', '$reg_number', '$api_response', '$status_code', '$created_at')
		";

		$res = mysqli_query($connection, $query);

		if (!$res) {
			logSqlError(mysqli_error($connection), $query, 'vahan-api');
		}
	}

}

?>