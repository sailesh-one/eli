<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_curl.php';

function api_response($status_code = 400, $status = 'ok', $msg = '', $data = [], $data_hide = [], $errors = [], $errors_hide = []) {
    global $env_server,$GLOBALS;
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    $response = [
        'status' => $status,
        'msg'    => $msg,
        'errors' => (object)$errors,
        'data'   => (object)$data
    ];
    if ($env_server === 'dev') {
        $data_hide['device_id'] = $_REQUEST['device_id'] ?? '';
        $data_hide['device_type'] = $_REQUEST['device_type'] ?? '';
        $data_hide['device_version'] = $_REQUEST['device_version'] ?? '';
        $response['errors_optional'] = (object)$errors_hide;
        $response['data_optional']   = (object)$data_hide;
    }
    //Log updated
    //Req-Resp longs inserting code start
    req_response_logs($status_code,$_REQUEST,$response);
    echo rtrim(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    exit;
}

function logSqlError($errorMessage, $query, $from = '') {
    $message = "[SQL ERROR] " . $errorMessage .
               " | Query: " . $query .
               " | From: " . $from .
               " | Time: " . date('Y-m-d H:i:s');
    error_log($message);
    api_response(500, 'fail', 'Internal server error. Please try again later.');
}

function generateNumericOtp($length = 6)
{

    global $env_server;
    if($env_server !== 'prod'){ 
        return str_pad('123456', $length, '0', STR_PAD_LEFT);
    }

    // Enforce min/max limit for safety (e.g., between 4–10 digits)
    $length = max(4, min((int)$length, 10));

    $min = 0;
    $max = pow(10, $length) - 1;

    // Generate random number
    $number = random_int($min, $max);

    // Pad with leading zeros if needed
    return str_pad((string)$number, $length, '0', STR_PAD_LEFT);
}


function generate_nonce($length = 16) {
       return bin2hex(random_bytes($length));
}

function loadDeviceId()
{
    $expectedHeaders = [
        'device_id'   => 'x-device-id',
        'device_type' => 'x-device-type',
    ];

    $found = [];

    // Normalize header name for consistent lookup
    $normalize = function ($header) {
        return strtoupper(str_replace('-', '_', $header));
    };

    // Step 1: Check in $_SERVER (covers Apache + Nginx + FastCGI)
    foreach ($expectedHeaders as $key => $headerName) {
        $serverKey = 'HTTP_' . $normalize($headerName);
        if (isset($_SERVER[$serverKey]) && $_SERVER[$serverKey] !== '') {
            $found[$key] = $_SERVER[$serverKey];
        }
    }

    // Step 2: apache_request_headers (works only in Apache)
    if (count($found) < count($expectedHeaders) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if ($headers) {
            $headers = array_change_key_case($headers, CASE_LOWER);
            foreach ($expectedHeaders as $key => $headerName) {
                $h = strtolower($headerName);
                if (empty($found[$key]) && isset($headers[$h]) && $headers[$h] !== '') {
                    $found[$key] = $headers[$h];
                }
            }
        }
    }

    // Step 3: getallheaders (works in most PHP-FPM / CGI)
    if (count($found) < count($expectedHeaders) && function_exists('getallheaders')) {
        $headers = getallheaders();
        if ($headers) {
            $headers = array_change_key_case($headers, CASE_LOWER);
            foreach ($expectedHeaders as $key => $headerName) {
                $h = strtolower($headerName);
                if (empty($found[$key]) && isset($headers[$h]) && $headers[$h] !== '') {
                    $found[$key] = $headers[$h];
                }
            }
        }
    }

    // Step 4: PHP-FPM/Nginx "REDIRECT_HTTP_" fallback (sometimes used)
    foreach ($expectedHeaders as $key => $headerName) {
        if (empty($found[$key])) {
            $serverKey = 'REDIRECT_HTTP_' . $normalize($headerName);
            if (isset($_SERVER[$serverKey]) && $_SERVER[$serverKey] !== '') {
                $found[$key] = $_SERVER[$serverKey];
            }
        }
    }

    // Save to $_REQUEST and validate
    foreach ($expectedHeaders as $key => $headerName) {
        $_REQUEST[$key] = $found[$key] ?? null;

        if (empty($_REQUEST[$key])) {
            api_response(
                400,
                'fail',
                ucfirst(str_replace('_', ' ', $key)) . " is required.",
                [],
                []
            );
        }
    }
}



// Generate a random captcha code (5 alphanumeric chars)
    function generate_captcha_code($length = 5) {
        // Step 1: Generate random code
        $randomStr = "ABCDEFGHIJ0123456789KLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQR0123456789STUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $codeArray = [];
        for ($i = 0; $i < $length; $i++) {
            $codeArray[] = substr($randomStr, rand(0, strlen($randomStr) - 1), 1);
        }
        $captchaCode = implode('', $codeArray);

        // Step 2: Create image (increased width to prevent cutoff with rotation)
        $width = 200;
        $height = 60;
        $image = imagecreatetruecolor($width, $height);
        imageantialias($image, true);

        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 64, 64, 64);
        $lineColor = imagecolorallocate($image, 64, 64, 64);
        $noiseColor = imagecolorallocate($image, 64, 64, 64);

        imagefill($image, 0, 0, $backgroundColor);

        // Add noise
        for ($i = 0; $i < 100; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
        }

        // Add lines
        for ($i = 0; $i < 3; $i++) {
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        // Add text (centered positioning with proper margins)
        $fontPath = $_SERVER['DOCUMENT_ROOT'] . "/assets/fonts/bpg-arial-webfont.ttf";
        // X position: 30-50 (left margin), Y position: 38-42 (vertical center)
        imagettftext($image, 20, rand(-10, 10), rand(30, 50), rand(38, 42), $textColor, $fontPath, $captchaCode);

        // Step 3: Capture image data
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        // Step 4: Return code and base64 image
        return [
            'captcha_code' => $captchaCode,
            'captcha_image' => 'data:image/png;base64,' . base64_encode($imageData)
        ];
    }



function safe_json_decode($json, $assoc = true) {
    if (!is_string($json) || trim($json) === '') return [];
    $data = json_decode($json, $assoc);
    return is_array($data) ? $data : [];
}


function format_time($timestamp, $format = 'Y-m-d H:i:s') {
    return date($format, $timestamp);
}

function getBearerToken() {
    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_LOWER);
    //print_pre($headers);
    if (!isset($headers['authorization'])) {
        return null;
    }
    $authHeader = $headers['authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return null;
}

function logTableInsertion($table_name, $id_list, $data = [])
{
    global $connection;
    $log_table_name = $table_name . "_log";
    // --- CASE 1: No extra data (just copy from main table to log table) ---
    if (empty($data)) {
        $sql_query = "
            INSERT INTO `$log_table_name`
            SELECT NULL, a.* FROM `$table_name` AS a WHERE id IN ($id_list)";
        
        try {
            if (!mysqli_query($connection, $sql_query)) {
                logSqlError(mysqli_error($connection), $sql_query, 'logger');
                return false;
            }
            return true;
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Internal Logger Error: ' . $e->getMessage());
        }
    }
    // --- CASE 2: Data provided (extra columns in log table) ---
    $columns = array_keys($data);
    $values  = array_map(function ($value) use ($connection) {
    if (is_null($value)) {
        return "NULL"; // Insert real NULL
        }
        return "'" . mysqli_real_escape_string($connection, (string)$value) . "'";
    }, array_values($data));

    $sql_query = "INSERT INTO `$log_table_name` (" . implode(", ", $columns) . ")
                  VALUES (" . implode(", ", $values) . ")";

    try {
        if (!mysqli_query($connection, $sql_query)) {
            logSqlError(mysqli_error($connection), $sql_query, 'logger');
            return false;
        }
        return true;
    } catch (Throwable $e) {
        api_response(400, 'fail', 'Internal Logger Error: ' . $e->getMessage());
    }
}


function prepareEmailBody(string $type, array $params = []): string
{
    $user = $params['userName'] ?? '';
    $greeting = $user ? "Hello {$user}," : "Hello,";

    switch ($type) {
        case 'otp':
            $otp = $params['otp'] ?? 'XXXXXX';
            $content = "<p>{$greeting} <br><br>Your OTP is <strong style='color:#000000;'>{$otp}</strong>. Valid for 10 minutes. Do not share it.<br><br>JLR UDMS Team</p>";
            break;

        case 'welcome':
            $content = "<p>{$greeting} Welcome to JLR UDMS! We are glad to have you.<br>JLR UDMS Team</p>";
            break;

        case 'password_reset':
            $link = $params['resetLink'] ?? '#';
            $content = "<p>{$greeting} Reset your password: <a href='{$link}' style='color:#2d89ef; text-decoration:none;'>Click here</a>.<br>JLR UDMS Team</p>";
            break;

        default:
            $content = "<p>Invalid email type.</p>";
            break;
    }

    return "<html><body style='font-family:Arial,sans-serif;font-size:14px;color:#333;'>{$content}</body></html>";
}

function getVahanFlagStatus()
{
    global $connection;
    $query = "SELECT value FROM feature_flags WHERE flag_name = 'vahan_api'";
    $res = mysqli_query($connection,$query);
    if(!$res)
    {
        logSqlError(mysqli_error($connection), $query, 'vahan_api');
    }

    $status = mysqli_fetch_assoc($res);
    return $status['value'];
}

function validate_configdata($config_data, $post_data) {
    $errors = [];

    foreach ($config_data as $fieldKey => $fieldDef) {
        $label = $fieldDef['label'] ?? $fieldKey;
        $required = $fieldDef['validation']['required'] ?? false;
        $patterns = $fieldDef['validation']['patterns'] ?? [];

        $value = trim($post_data[$fieldKey] ?? '');

        // --- Conditional Required Check ---
        if (isset($fieldDef['conditional']['required'])) {
            $cond = $fieldDef['conditional']['required'];
            $relatedVal = trim($post_data[$cond['field']] ?? '');

            // only mark as required if condition matches
            if (isset($cond['equals']) && $relatedVal == $cond['equals']) {
                $required = true;
            } elseif (isset($cond['not_equals']) && $relatedVal != $cond['not_equals']) {
                $required = true;
            } else {
                $required = false;
            }
        }

        // Required check
        if ($required && $value === '') {
            $errors[$fieldKey] = "$label is required.";
            continue;
        }

        // Pattern checks
        if ($value !== '' && !empty($patterns)) {
            foreach ($patterns as $rule) {
                $regex = $rule['regex'] ?? '';
                $message = $rule['message'] ?? "$label is not valid.";

                if ($regex && !validate_field_regex($regex, $value)) {
                    $errors[$fieldKey] = $message;
                    break; // stop at first failed rule
                }
            }
        }
    }

    return $errors;

}

function validate_addconfig($addConfig, $postData) {
    $addConfig = json_decode(json_encode($addConfig), true);

    $errors = [];
    $allowedData = [];

    foreach ($addConfig['fields'] as $formBlock) {
        foreach ($formBlock['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $fieldKey = $field['fieldKey'];
                $value = $postData[$fieldKey] ?? '';

                // required check
                if (!empty($field['isRequired']) && $field['isRequired'] === true) {
                    if ($value === '' || $value === null) {
                        $errors[$fieldKey] = $field['validation']['errorMessageRequired'] 
                            ?? ($field['fieldLabel'] . " is required");
                        continue;
                    }
                }

                // regex validation
                $pattern = $field['validation']['validationPattern'] ?? '';
                if (!empty($pattern) && !empty($value) && !validate_field_regex($pattern, $value)) {
                    $errors[$fieldKey] = $field['validation']['errorMessageInvalid'] 
                        ?? ($field['fieldLabel'] . " is invalid");
                    continue;
                }

                if (array_key_exists($fieldKey, $postData)) {
                    // Convert comma separator to pipe for contact_method field
                    if ($fieldKey === 'contact_method' && !empty($value)) {
                        $value = str_replace(',', '|', $value);
                    }
                    $allowedData[$fieldKey] = $value;
                }

                // handle conditional fields
                if (!empty($field['conditionalFields']) && isset($postData[$fieldKey])) {
                    $conditionValue = $postData[$fieldKey];
                    if (isset($field['conditionalFields'][$conditionValue])) {
                        foreach ($field['conditionalFields'][$conditionValue] as $condField) {
                            $condKey = $condField['fieldKey'];
                            $condValue = $postData[$condKey] ?? '';

                            if (!empty($condField['isRequired']) && ($condValue === '' || $condValue === null)) {
                                $errors[$condKey] = $condField['validation']['errorMessageRequired'] 
                                    ?? ($condField['fieldLabel'] . " is required");
                                continue;
                            }

                            $condPattern = $condField['validation']['validationPattern'] ?? '';
                            if (!empty($condPattern) && !empty($condValue) && !validate_field_regex($condPattern, $condValue)) {
                                $errors[$condKey] = $condField['validation']['errorMessageInvalid'] 
                                    ?? ($condField['fieldLabel'] . " is invalid");
                                continue;
                            }

                            if (array_key_exists($condKey, $postData)) {
                                $allowedData[$condKey] = $condValue;
                            }
                        }
                    }
                }
            }
        }
    }

    return [
        'errors' => $errors,
        'data'   => $errors ? [] : $allowedData
    ];
}


function validate_statusconfig($config, $inputData) {
    $errors = [];

    // Get current main status value
    $statusValue = $inputData['status'] ?? null;
    $statusStr = (string)$statusValue;

    // --------- Get the main status field globally ---------
    $mainStatusField = null;
    foreach ($config->fields as $formBlock) {
        foreach ($formBlock['sections'] as $section) {
            foreach ($section['fields'] as $f) {
                if ($f['fieldKey'] === 'status') {
                    $mainStatusField = $f;
                    break 3; // exit all loops
                }
            }
        }
    }
    $mainConditional = $mainStatusField['conditionalApply'] ?? [];

    // --------- Loop through all sections and fields ---------
    foreach ($config->fields as $formBlock) {
        foreach ($formBlock['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $fieldKey = $field['fieldKey'];
                $value = $inputData[$fieldKey] ?? null;

                if (is_string($value) && strpos($value, ',') !== false) {
                    $value = array_map('trim', explode(',', $value));
                }

                // Skip main status itself (it has its own isRequired)
                if ($fieldKey !== 'status') {

                    // ---------- HIDDEN LOGIC ----------
                    $hiddenRules = $mainConditional['isHidden'] ?? [];
                    $isHidden = false;
                    foreach ($hiddenRules as $rule) {
                        if ($rule['fieldKey'] === $fieldKey) {
                            $ruleEqual    = array_map('strval', $rule['equal'] ?? []);
                            $ruleNotEqual = array_map('strval', $rule['not_equal'] ?? []);

                            $cond = (isset($rule['equal']) && in_array($statusStr, $ruleEqual, true)) ||
                                    (isset($rule['not_equal']) && !in_array($statusStr, $ruleNotEqual, true));

                            if ($cond) {
                                $isHidden = true;
                                break;
                            }
                        }
                    }
                    if ($isHidden) continue; // Skip validation for hidden fields

                    // ---------- REQUIRED LOGIC ----------
                    $requiredRules = $mainConditional['isRequired'] ?? [];
                    $isRequired = false;
                    foreach ($requiredRules as $rule) {
                        if ($rule['fieldKey'] === $fieldKey) {
                            $ruleEqual    = array_map('strval', $rule['equal'] ?? []);
                            $ruleNotEqual = array_map('strval', $rule['not_equal'] ?? []);

                            $cond = (isset($rule['equal']) && in_array($statusStr, $ruleEqual, true)) ||
                                    (isset($rule['not_equal']) && !in_array($statusStr, $ruleNotEqual, true));

                            if ($cond) {
                                $isRequired = true;
                                break;
                            }
                        }
                    }

                    if ($isRequired && (empty($value) && $value !== "0")) {
                        $errors[$fieldKey] = $field['validation']['errorMessageRequired'] ?? "$fieldKey is required";
                        continue;
                    }
                }

                // ---------- BASE REQUIRED FOR STATUS ----------
                if ($fieldKey === 'status' && !empty($field['isRequired']) && (empty($value) && $value !== "0")) {
                    $errors[$fieldKey] = $field['validation']['errorMessageRequired'] ?? "Status is required";
                    continue;
                }

                // ---------- ALLOWED OPTIONS ----------
                if (!empty($field['fieldOptionIds']) && !empty($value)) {
                    $validOptions = array_map(function($opt) {
                        return is_array($opt) && isset($opt['value']) ? $opt['value'] : $opt;
                    }, $field['fieldOptionIds']);

                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (!in_array((string)$v, $validOptions, true)) {
                                $errors[$fieldKey] = $field['validation']['errorMessageInvalid'] ?? "$fieldKey has invalid option(s)";
                                break;
                            }
                        }
                    } else {
                        if (!in_array((string)$value, $validOptions, true)) {
                            $errors[$fieldKey] = $field['validation']['errorMessageInvalid'] ?? "$fieldKey is invalid";
                        }
                    }
                }

                // ---------- PATTERN VALIDATION ----------
                if (!empty($value) && !empty($field['validation']['validationPattern'])) {
                    $pattern = $field['validation']['validationPattern'];
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (!preg_match("/{$pattern}/", $v)) {
                                $errors[$fieldKey] = $field['validation']['errorMessageInvalid'] ?? "$fieldKey is invalid";
                                break;
                            }
                        }
                    } else {
                        if (!preg_match("/{$pattern}/", $value)) {
                            $errors[$fieldKey] = $field['validation']['errorMessageInvalid'] ?? "$fieldKey is invalid";
                        }
                    }
                }

                // ---------- MAX LENGTH ----------
                if (!empty($field['maxLength']) && !empty($value)) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (strlen($v) > $field['maxLength']) {
                                $errors[$fieldKey] = "$fieldKey exceeds maximum length of {$field['maxLength']}";
                                break;
                            }
                        }
                    } else {
                        if (strlen($value) > $field['maxLength']) {
                            $errors[$fieldKey] = "$fieldKey exceeds maximum length of {$field['maxLength']}";
                        }
                    }
                }
            }
        }
    }

    return [
        'status' => empty($errors),
        'errors' => $errors
    ];
}



    function combineDateTime($date, $time) {
    if (empty($date) || empty($time)) {
        return null; 
    }

    $dt = DateTime::createFromFormat('Y-m-d H:i:s', "$date $time");
    
    return $dt ? $dt->format('Y-m-d H:i:s') : null;
}

function getUserNameById($userId) {
    global $connection;
    
    if (empty($userId)) {
        return 'Unknown User';
    }
    
    $userId = intval($userId);
    $query = "SELECT name FROM users WHERE id = $userId LIMIT 1";
    
    $result = mysqli_query($connection, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return 'Unknown User';
    }
    
    $user = mysqli_fetch_assoc($result);
    return $user['name'];
}

function getUsersByBranchIds($branchIds) {
    global $connection;
    $resultData = [];

    if (empty($branchIds)) { return $resultData; }

    if (is_string($branchIds)) {
        $branchIds = json_decode($branchIds, true);
    }

    if (!is_array($branchIds)) { return $resultData; }

    $branchIds = array_map('intval', $branchIds);

    foreach ($branchIds as $bid) {
        $branchQuery = "SELECT id, name FROM dealer_branches WHERE id = $bid LIMIT 1";
        $branchRes = mysqli_query($connection, $branchQuery);

        if (!$branchRes || mysqli_num_rows($branchRes) === 0) {
            continue;
        }

        $branchRow = mysqli_fetch_assoc($branchRes);

        $userQuery = "SELECT u.id, u.name, u.role_id, r.role_name FROM users u
            JOIN config_roles r ON u.role_id = r.id
            WHERE JSON_CONTAINS(branch_id, '\"$bid\"')
              AND u.active = 'y'
              AND r.active = 'y'";
        $userRes = mysqli_query($connection, $userQuery);

        $executives = [];
        if ($userRes) {
            while ($u = mysqli_fetch_assoc($userRes)) {
                $executives[] = [
                    "id" => $u['id'],
                    "name" => $u['name'],
                    "role_id" => $u['role_id'],
                    "role_name" => $u['role_name']
                ];
            }
        }

        $resultData[] = [
            "branch_id" => $branchRow['id'],
            "branch_name" => $branchRow['name'],
            "executives" => $executives
        ];
    }

    return $resultData;
}



function validateExecutiveActive($executiveId, $connection = null) {
    if ($connection === null) {
        global $connection;
    }
    $conn = $connection;

    // Allow NULL or 0 (no executive assigned)
    if (empty($executiveId)) {
        return ['valid' => true, 'message' => ''];
    }

    $executiveId = (int)$executiveId;

    // Check if executive exists, is active, and their role is active
    $query = "SELECT u.id, u.name, u.active AS user_active, 
                     r.id AS role_id, r.role_name, r.active AS role_active
              FROM users u
              JOIN config_roles r ON u.role_id = r.id
              WHERE u.id = $executiveId
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        logSqlError(mysqli_error($conn), $query, 'common-validateExecutiveActive', true);
        return ['valid' => false, 'message' => 'Database error while validating executive'];
    }

    $executive = mysqli_fetch_assoc($result);

    // Executive not found
    if (!$executive) {
        return ['valid' => false, 'message' => 'Selected executive does not exist'];
    }

    // Check if user is inactive
    if ($executive['user_active'] !== 'y') {
        return ['valid' => false, 'message' => 'Selected executive "' . $executive['name'] . '" is inactive'];
    }

    // Check if role is inactive
    if ($executive['role_active'] !== 'y') {
        return ['valid' => false, 'message' => 'Selected executive "' . $executive['name'] . '" has an inactive role "' . $executive['role_name'] . '"'];
    }

    return ['valid' => true, 'message' => ''];
}


function exportExcelFile(array $headers, array $rows, string $filename, array $mainHeaders = []) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $startRow = 1;
    // ===== If main headers exist =====
    if (!empty($mainHeaders)) {
        $col = 'A';
        $idx = 0;
        foreach ($mainHeaders as $main) {
            $mergeFrom = $col;
            $colSpan = $main['colspan'] ?? 1;

            // calculate merge target
            $mergeTo = $col;
            for ($i = 1; $i < $colSpan; $i++) {
                $mergeTo++;
            }

            // Merge & set value
            $mergeRange = "{$mergeFrom}{$startRow}:{$mergeTo}{$startRow}";
            $sheet->mergeCells("{$mergeFrom}{$startRow}:{$mergeTo}{$startRow}");
            $sheet->setCellValue($mergeFrom . $startRow, $main['name']);
            $sheet->getStyle($mergeFrom . $startRow)->getFont()->setBold(true);
            $sheet->getStyle($mergeFrom . $startRow)->getAlignment()->setHorizontal('center');

            // Alternate light-gray background for main headers
            if ($idx % 2 === 0) {
                $sheet->getStyle($mergeRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            }

            // move pointer
            for ($i = 0; $i < $colSpan; $i++) {
                $col++;
            }
            $idx++;
        }

        $startRow++; // sub headers in next row
    }

    // ===== Headers =====
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $startRow , $header['name']);
        $sheet->getStyle($col . $startRow)->getFont()->setBold(true);
        $col++;
    }

    // ===== Rows =====
    $rowNum = $startRow + 1;
    foreach ($rows as $row) {
        $col = 'A';
        $i = 0; // index for header type
        foreach ($row as $cell) {
            $headerType = $headers[$i]['type'] ?? 'string';

            switch ($headerType) {
                case 'date':
                case 'datetime':
                    if (!empty($cell) && $cell !== '0000-00-00' && $cell !== '0000-00-00 00:00:00') {
                        $formattedDate = date('d-M-Y', strtotime($cell));
                        $sheet->setCellValue($col . $rowNum, $formattedDate);
                    } else {
                        $sheet->setCellValue($col . $rowNum, '');
                    }
                    break;
                case 'boolean':
                    if ($cell == 1 || strtolower($cell) === 'y') {
                        $sheet->setCellValue($col . $rowNum, 'Yes');
                    } elseif ($cell == 0 || strtolower($cell) === 'n') {
                        $sheet->setCellValue($col . $rowNum, 'No');
                    } else {
                        $sheet->setCellValue($col . $rowNum, $cell);
                    }
                    break;

                default: // string, number, etc.
                    $sheet->setCellValue($col . $rowNum, $cell);
                    break;
            }

            $col++;
            $i++;
        }
        $rowNum++;
    }

    // ===== Autosize columns =====
    $colCount = count($headers);
    $colLetter = 'A';
    for ($i = 0; $i < $colCount; $i++) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        $colLetter++;
    }

    // ===== Ensure temp directory exists =====
    $tempDir = __DIR__ . '../../export_temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    $filePath = rtrim($tempDir, '/') . '/' . $filename;

    // Save
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    // Public URL
    $publicUrl = '/export_temp/' . $filename;
    return $publicUrl;
}

function buildSqlCaseFromConfig($column_name, $mapping) {
    if (empty($mapping) || !is_array($mapping) || is_array(reset($mapping))) {
        return $column_name;
    }
    
    $cases = [];
    foreach ($mapping as $key => $value) {
        if (is_scalar($value)) {
            
            $escaped_value = str_replace("'", "''", $value);
            
            $cases[] = "WHEN $column_name = '$key' THEN '$escaped_value'";
        }
    }
    
    return empty($cases) ? $column_name : "CASE " . implode(' ', $cases) . " ELSE '' END";
}

function buildMultiSelectCase($column_name, $mapping) {
    if (empty($mapping) || !is_array($mapping)) {
        return $column_name;
    }

    $parts = [];
    foreach ($mapping as $key => $value) {
        // Escape single quotes for SQL safety
        $escaped_value = str_replace("'", "''", $value);
        $parts[] = "IF(FIND_IN_SET('$key', $column_name), '$escaped_value', NULL)";
    }

    // CONCAT_WS skips NULLs and joins all matched names with comma+space
    return "CONCAT_WS(', ', " . implode(', ', $parts) . ")";
}

function convertCommaStringToArray($value)
{
    if (empty($value)) {
        return [];
    }

    // Split by comma, trim spaces, and cast to int (optional)
    $array = array_map('trim', explode(',', $value));

    // If values are numeric, convert to integers
    if (ctype_digit(str_replace(',', '', $value))) {
        $array = array_map('intval', $array);
    }

    return $array;
}


function buildSubStatusSqlCase($status_column, $substatus_column, $lead_statuses_config) {
    
    if (empty($lead_statuses_config) || !is_array($lead_statuses_config)) {
        return "''";
    }
    
    $cases = [];
    foreach ($lead_statuses_config as $status_id => $sub_statuses) {
        if (!empty($sub_statuses) && is_array($sub_statuses)) {
            foreach ($sub_statuses as $sub_status_id => $sub_status_name) {
                $escaped_name = str_replace("'", "''", $sub_status_name);
                $cases[] = "WHEN $status_column = '$status_id' AND $substatus_column = '$sub_status_id' THEN '$escaped_name'";
            }
        }
    }
    return empty($cases) ? "''" : "CASE " . implode(' ', $cases) . " ELSE '' END";
}

function data_encrypt($pass)
{
  try {
    if (empty($pass)) {
      return '';
    }

    global $config;

    $secret_key = $config['encryption']['secret_key'];
    $crypt_type = $config['encryption']['crypt_type'];
    $iv         = $config['encryption']['iv'];

    // Convert to string to ensure consistent encryption
    $str_pass = (string)$pass;

    // Perform encryption (same as before)
    $encrypted_raw = @openssl_encrypt($str_pass, $crypt_type, $secret_key, 0, $iv);

    // Base64 encode for safe storage/transfer
    return $encrypted_raw ? base64_encode($encrypted_raw) : '';
  } catch (Exception $e) {
    // Return original string if any error occurs
    return (string)$pass;
  }
}

function data_decrypt($pass)
{
  try {
    if (empty($pass)) {
      return '';
    }

    global $config;

    $secret_key = $config['encryption']['secret_key'];
    $crypt_type = $config['encryption']['crypt_type'];
    $iv         = $config['encryption']['iv'];

    // Decode from Base64 (same as before)
    $decoded = base64_decode((string)$pass, true); // strict = true

    if ($decoded !== false && $decoded !== '') {
      $decrypted = @openssl_decrypt($decoded, $crypt_type, $secret_key, 0, $iv);

      // Return decrypted only if successful and not empty
      if ($decrypted !== false && $decrypted !== '') {
        return $decrypted;
      }
    }

    // If decoding/decryption fails, return original input
    return (string)$pass;
  } catch (Exception $e) {
    return (string)$pass;
  }
}


function get_cw_indicative_price($params)
{
    global $connection,$config;

    if(empty($params['make']))
    {
        return ["status" => false,"msg"=>"Make value is required."];
    }
    else if(empty($params['model']))
    {
        return ["status" => false,"msg"=>"Model value is required."];
    }
    else if(empty($params['variant']))
    {
        return ["status" => false,"msg"=>"Variant value is required."];
    }
    else if(empty($params['mfg_year']))
    {
        return ["status" => false,"msg"=>"Mfg year value is required."];
    }
    else if(empty($params['city']))
    {
        return ["status" => false,"msg"=>"city value is required."];
    } 
    else if(empty($params['owners']))
    {
        return ["status" => false,"msg"=>"No.of owners value is required."];
    }
    else if(empty($params['mileage']))
    {
        return ["status" => false,"msg"=>"Mileage value is required."];
    }

	$query_map = "SELECT cw_variant_id FROM master_cw_cte_mmv_mapping WHERE 
                  cte_make = '" . mysqli_real_escape_string($connection, $params['make']) . "' AND 
                  cte_model = '" . mysqli_real_escape_string($connection, $params['model']) . "' AND 
                  cte_variant = '" . mysqli_real_escape_string($connection, $params['variant']) . "' AND 
                  mfgyear = '" . mysqli_real_escape_string($connection, $params['mfg_year'])."'
                  ORDER BY mfgyear DESC, cte_variant_id DESC LIMIT 1";

    $res_map           = mysqli_query($connection,$query_map);
	$row_map           = mysqli_fetch_assoc($res_map);
	if(!mysqli_num_rows($res_map)){
		$l_l = 0;
		$u_l = 0;
		return ['l_l'=>$l_l,'u_l'=>$u_l];
	}
	else 
    {
		$params['versionId']    = $row_map['cw_variant_id'];
	}
    
    $final_url = $config['cw_price_api_url'] . "?makeYear=" . $params["mfg_year"] . "&cityId=" . $params["city"] . "&owners=" . $params["owners"] . "&kilometers=" . $params["mileage"] . "&entryYear=" . $params["mfg_year"] . "&versionId=" . $params["versionId"] . "&valuationType=2";
	
    $request = new CurlRequest($final_url);
    $headers = ["Referer"=>".carwale.com"];
	$request->headers = $headers;
	$request_res = $request->post("", "GET"); 
	
    $price_resp = json_decode($request_res['body'],true);
	
    $l_l = $price_resp['unFormattedFairConditionPrice'];
	$u_l = $price_resp['unFormattedGoodConditionPrice'];

	return ['l_l'=>$l_l,'u_l'=>$u_l];
}


function getLeadDataById($id = 0,$table = "")
{
    global $connection;
    $data = [];
    if(!empty($id) && !empty($table))
    {
        $query = "SELECT 
                  a.id,
                  a.make,
                  a.model,
                  a.variant,
                  b.make as make_name,
                  b.model as model_name,
                  b.variant as variant_name 
                  FROM $table as a
                  LEFT JOIN master_variants_new b on a.variant = b.id
                  WHERE a.id = $id";
        $res = mysqli_query($connection,$query);
        if(!$res)
        {
            logSqlError(mysqli_error($connection), $query, 'getlead-data');
            return $data;
        }
        while($row = mysqli_fetch_assoc($res))
        {
            $data[] = $row;
        }
        return $data;
    }
    else
    {
        return $data;
    }
}
function viewdocLink($path)
{
    global $config;
    return $config['base_url']."/viewdoc/".$path;
}
function isFieldHidden($field) {
    // Convert object to array if needed
    if (is_object($field)) {
        $field = json_decode(json_encode($field), true);
    }
    
    // If field doesn't have role_main flag, show it by default
    if (!isset($field['role_main'])) {
        return false;
    }
    
    // Get current user from global context
    if (empty($GLOBALS['api_user'])) {
        error_log("isFieldHidden: No api_user in GLOBALS");
        return false; // No user context, show field by default
    }
    
    // Get dealership info which contains role details
    $dealership = $GLOBALS['dealership'] ?? [];
    
    if (empty($dealership)) {
        error_log("isFieldHidden: No dealership in GLOBALS");
        return false; // No dealership context, show field by default
    }
    
    // Check user's role_main value
    $userRoleMain = $dealership['role_main'] ?? 'y';
    $fieldRoleMain = $field['role_main'] ?? 'n';
    $fieldKey = $field['fieldKey'] ?? $field['key'] ?? 'unknown';
    
    error_log("isFieldHidden: Field '$fieldKey' has role_main='$fieldRoleMain', User has role_main='$userRoleMain'");
    
    // If user has role_main='n' (sub-role) AND field requires role_main='y' (main role only), hide it
    if ($userRoleMain === 'n' && $fieldRoleMain === 'y') {
        error_log("isFieldHidden: HIDING field '$fieldKey' from user with role_main='n'");
        return true; // Hide field from sub-role users
    }
    
    error_log("isFieldHidden: SHOWING field '$fieldKey'");
    return false; // Show field (user is main role OR field is accessible to sub-roles)
}

/**
 * Filter module config based on current user's role_main
 * 
 * Processes the entire config (array or object):
 * 1. Adds 'isHidden' property to searchConfig fields
 * 2. Removes restricted columns from columns data array
 * 
 * @param array|object $config The raw module config (array or stdClass)
 * @return array|object The filtered config
 */
function filterConfig($config) {
    error_log("filterConfig START - Type: " . gettype($config));
    
    // Return early if config is null or empty
    if (empty($config)) {
        error_log("filterConfig: config is empty");
        return $config;
    }
    
    // Remember if we need to convert back to object
    $wasObject = is_object($config);
    
    // ALWAYS convert to full array (handles both top-level objects AND nested objects)
    $config = json_decode(json_encode($config), true);
    
    error_log("filterConfig: After conversion, type = " . gettype($config) . ", Has 'grid'? " . (isset($config['grid']) ? 'yes' : 'no'));
    
    // Ensure config is now an array
    if (!is_array($config)) {
        error_log("filterConfig: config is NOT an array after conversion! Type: " . gettype($config));
        return $config;
    }
    
    // Check grid type after conversion
    if (isset($config['grid'])) {
        error_log("filterConfig: grid type = " . gettype($config['grid']));
    }
    
    // Process searchConfig fields - add isHidden property
    if (isset($config['grid']) && is_array($config['grid'])) {
        error_log("filterConfig: grid exists and is array");
        
        if (isset($config['grid']['searchConfig']) && is_array($config['grid']['searchConfig'])) {
            error_log("filterConfig: searchConfig exists and is array");
            
            if (isset($config['grid']['searchConfig']['fields']) && is_array($config['grid']['searchConfig']['fields'])) {
                error_log("filterConfig: Processing " . count($config['grid']['searchConfig']['fields']) . " fields");
                
                foreach ($config['grid']['searchConfig']['fields'] as $key => $field) {
                    $field['isHidden'] = isFieldHidden($field);
                    $config['grid']['searchConfig']['fields'][$key] = $field;
                }
            }
        }
        
        // Process columns data - remove restricted items entirely
        if (isset($config['grid']['columns']) && is_array($config['grid']['columns'])) {
            error_log("filterConfig: columns exists and is array");
            error_log("filterConfig: columns keys = " . implode(', ', array_keys($config['grid']['columns'])));
            
            if (isset($config['grid']['columns']['data']) && is_array($config['grid']['columns']['data'])) {
                error_log("filterConfig: Processing " . count($config['grid']['columns']['data']) . " columns");
                
                $filteredColumns = [];
                foreach ($config['grid']['columns']['data'] as $column) {
                    if (!isFieldHidden($column)) {
                        $filteredColumns[] = $column;
                    }
                }
                $config['grid']['columns']['data'] = $filteredColumns;
            } else {
                error_log("filterConfig: columns['data'] " . (isset($config['grid']['columns']['data']) ? "is NOT array, type=" . gettype($config['grid']['columns']['data']) : "does NOT exist"));
            }
        }
    }
    
    // Convert back to object if input was object
    if ($wasObject) {
        error_log("filterConfig: Converting back to object");
        $config = json_decode(json_encode($config));
    }
    
    error_log("filterConfig END");
    return $config;
}
/*Function  - req_response_logs
Arguments   - $req, $res
Description - Storing API all request and response
*/
function req_response_logs($status,$req=[],$res=[]){
        
        global $connection,$config,$GLOBALS;
        
        $rawUser = $GLOBALS['user'] ?? [];
        if (is_object($rawUser)) {
            $userArr = json_decode(json_encode($rawUser), true);
        } elseif (is_array($rawUser)) {
            $userArr = $rawUser;
        } else {
            $userArr = [];
        }

        $user_id = isset($userArr['uid']) ? $userArr['uid'] : 0;
        $version_code = $userArr['device_version'] ?? '';
        $action = $req['action']?? '';
        $original_request = $req ?? [];
        $device_id = $userArr['device_id'] ?? '';
        $device_type = $userArr['device_type'] ?? '';
        $htp_host = $_SERVER['HTTP_HOST'] ?? '';
        $htp_usr_agnt = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $req_uri = $_SERVER['REQUEST_URI'] ?? '';
        $mthd = $_SERVER['REQUEST_METHOD'] ?? '';
        $server = php_uname('n');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $original_response = $res ?? [];
        $status = $status ?? '';
        
        $table = "api_req_resp_logs_".date("Y_m_d");
        $retVal = 0;
        $chk_qry = "SELECT table_name FROM information_schema.tables WHERE table_schema ='".$config['db_name']."' AND table_name ='".$table."'";
        $record = mysqli_query($connection, $chk_qry);
        if (!$record) {
            // Avoid die() in library — log and return
            error_log("req_response_logs: Query failed: " . mysqli_error($connection));
            return 0;
        }
        $row = mysqli_fetch_assoc($record);
        if (isset($row['table_name'])) {
            
            $log_q = "INSERT INTO ".$table."(
                    user_id,
                    ip,
                    version,
                    device_id,
                    device,
                    domain,
                    url,
                    user_agent,
                    server,
                    method,
                    action,
                    original_request,
                    original_response,
                    status,
                    c_date
                ) VALUES (
                    '".mysqli_real_escape_string($connection, $user_id)."',
                    '".mysqli_real_escape_string($connection, $ip)."' ,
                    '".mysqli_real_escape_string($connection, $version_code)."' ,
                    '".mysqli_real_escape_string($connection, $device_id)."',
                    '".mysqli_real_escape_string($connection, $device_type)."',
                    '".mysqli_real_escape_string($connection, $htp_host)."',
                    '".mysqli_real_escape_string($connection, $req_uri)."',
                    '".mysqli_real_escape_string($connection, $htp_usr_agnt)."',
                    '".mysqli_real_escape_string($connection, $server)."',
                    '".mysqli_real_escape_string($connection, $mthd)."',
                    '".mysqli_real_escape_string($connection, $action)."',
                    '".mysqli_real_escape_string($connection, json_encode($original_request))."',
                    '".mysqli_real_escape_string($connection, json_encode($original_response))."',
                    '".mysqli_real_escape_string($connection, $status)."',
                    '".date('Y-m-d H:i:s')."'
                )";
            $log_res = mysqli_query($connection, $log_q);
            if (!$log_res) {
                error_log("req_response_logs: Insert failed: " . mysqli_error($connection) . " | Query: $log_q");
                $retVal = 0;
            } else {
                $retVal = 1;
            }
     
        } else { 
            $cquery = "CREATE TABLE IF NOT EXISTS ".$table." (
                    log_id int NOT NULL AUTO_INCREMENT,
                    user_id int NOT NULL,
                    ip varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    version varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    device_id varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    device varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    domain varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    url varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    user_agent varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    server varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    method varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    action varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    original_request longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    original_response longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                    status int NOT NULL,
                    blocked int NOT NULL DEFAULT 0,
                    c_date datetime NOT NULL, 
                    PRIMARY KEY (log_id)
                    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
            if (mysqli_query($connection, $cquery)) {
                $retVal = 1;
            } else {
                error_log("req_response_logs: Error creating table: " . mysqli_error($connection));
                $retVal = 0;
            }
        }
        return $retVal;
}
function logMail(string $subject,string $body,array $params = [],string $response = '',string $ip = ''): void {
    global $connection;
    $plainText = trim(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $body)));
    $mail_subject = mysqli_real_escape_string($connection, $subject);
    $mail_content = mysqli_real_escape_string($connection, $plainText);
    $params_json  = mysqli_real_escape_string($connection, json_encode($params));
    $response     = mysqli_real_escape_string($connection, $response);
    $ip           = mysqli_real_escape_string($connection, $ip ?: ($_SERVER['REMOTE_ADDR'] ?? ''));
    $datetime     = date('Y-m-d H:i:s');

    $query = "
        INSERT INTO users_email_logs
        (mail_subject, params, mail_content, response, ip, datetime)
        VALUES (
            '$mail_subject',
            '$params_json',
            '$mail_content',
            '$response',
            '$ip',
            '$datetime'
        )
    ";
    mysqli_query($connection, $query);
}

?>