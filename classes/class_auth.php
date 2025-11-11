<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {

    private $jwtSecret;
    private $refreshSecret;
    private $issuer;
    private $audience;
    private $jwtExpiry;
    private $refreshExpiry;
    private $sql;
    private $setup;
    private $deviceType;
    private $deviceVersion;

    public function __construct() {
        global $connection, $config;
        $this->sql = $connection;
        $this->jwtSecret = $config['jwt_secret'];
        $this->refreshSecret = $config['refresh_secret'];
        $this->issuer = $config['jwt_issuer'];
        $this->audience = $config['jwt_audience'];
        $this->jwtExpiry = 900; // 15 min

        $this->deviceType = strtolower($_SERVER['HTTP_X_DEVICE_TYPE']) ?? 'web';
        $this->deviceVersion = $_SERVER['HTTP_X_DEVICE_VERSION'] ?? '';
        $this->refreshExpiry = $config['type_of_device'][$this->deviceType]['expiry'] ?? $config['type_of_device']['web']['expiry'];

        $this->setup = [
            'login_attempts' => 4,
            'email_block_time' => 300, // 5 min block
            'otp_expiry' => 300,
            'otp_attempts' => 5,
            'otp_resend' => 2,
            'token_nonce_expiry' => 600
        ];
    }

    // --- Helper: error response ---
    public function errorResponse($message, $errors = [], $code = 400, $redirect = null) {
        $response = [
            "status" => "error",
            "message" => $message,
            "code" => $code,
            "errors" => $errors ?: []
        ];
        if ($redirect) $response["redirect"] = $redirect;
        api_response(403, 'fail', $message, [], [], $errors);
        exit;
    }


    private function JWTEncode(array $payload)
    {
        try {
            $now = time();
            if (!isset($payload['iat'])) { $payload['iat'] = $now; }
            if (!isset($payload['device_id']) && !empty($_REQUEST['device_id'])) {
                $payload['device_id'] = trim($_REQUEST['device_id']);
            }
            if (!isset($payload['nonce'])) {
                $payload['nonce'] = bin2hex(random_bytes(8));
            }
            return JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function JWTDecode(string $token)
    {        

        if (empty($token) || !is_string($token) || strlen(trim($token)) < 5) {
            return false;
        }

        try {
            $decoded = (array) JWT::decode($token, new \Firebase\JWT\Key($this->jwtSecret, 'HS256'));             
            $currentDeviceId = trim($_REQUEST['device_id'] ?? '');
            if (!empty($currentDeviceId) && isset($decoded['device_id']) && $decoded['device_id'] !== $currentDeviceId) {
                return false;
            }
            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    private function getUserTable($authUser) {
        return $authUser === "admin" ? "users_admin" : "users";
    }
    private function getUserRoleType($authUser) {
        return $authUser === "admin" ? 0 : 1;
    }
    private function getUserRoleRoute($authUser) {
        return $authUser === "admin" ? "admin" : "";
    }
    
    // --- Captcha ---
    public function getCaptcha($captcha_id='') {
        // Use common helpers for captcha code
        $captcha = generate_captcha_code(5);
        $now = time();
        $noncePayload = [
            'exp' => $now + 300, // 5 min expiry
            'captcha_id' => $captcha_id,
            'captcha_code' => $captcha['captcha_code'],
        ];
        $jwtNonce = $this->JWTEncode($noncePayload);
        api_response(200, 'ok', 'Captcha token generated successfully.',
            [
                "captcha_token" => $jwtNonce,
                "captcha_image" => $captcha['captcha_image'],
            ],
            ["captcha_code" => $captcha['captcha_code']]
        );
    }

    // --- Verify Captcha ---
    public function verifyCaptcha($captchaId, $request) {
        $errors = [];
        $captchaCode = $request['captcha_code'] ?? '';
        $captchaToken = $request['captcha_token'] ?? '';

        if (empty($captchaCode)) { $errors['captcha_code'] = "Captcha code is required."; }
        if (empty($captchaToken)) { $errors['captcha_token'] = "Captcha token is required."; }
        if ($errors) { api_response(403, 'fail', 'Failed to verify captcha.', [], [], $errors); }

        try {
            $decoded = $this->JWTDecode($captchaToken);
            if (!$decoded) {
                api_response(400, 'fail', 'Invalid captcha token.');
            }
            if (isset($decoded['exp']) && $decoded['exp'] < time()) {
                api_response(400, 'fail', 'Captcha token has expired.');
            }
            if (!isset($decoded['captcha_id']) || $decoded['captcha_id'] !== $captchaId) {
                api_response(400, 'fail', 'Captcha ID mismatch.');
            }
            if (!isset($decoded['captcha_code']) || $decoded['captcha_code'] !== $captchaCode) {
                api_response(400, 'fail', 'Captcha code mismatch.');
            }
        } catch (\Exception $e) {
            api_response(400, 'fail', 'Failed to Verify Captcha.');
        }
        return true;
    }

    public function login($captchaId, $request)
    {
        // Step 0: Verify captcha
        if (!$this->verifyCaptcha($captchaId, $request)) {
            api_response(400, 'fail', 'Captcha verification failed.');
        }
        // Step 1: Validate inputs
        $email   = trim($request['email'] ?? '');
        $errors   = [];

        if ($email === '') {
            $errors['email'] = "Email address is required.";
        } elseif (!$this->validateEmail($email)) {
            $errors['email'] = "Invalid email address.";
        }
        if (!empty($errors)) {
            api_response(400, 'fail', 'Validation failed.', [], [], $errors);
        }
       
        // Step 2: Identify auth user type
        $authUser = ($request['auth_user'] ?? '') === 'admin' ? 'admin' : 'dealer';
        $u_table = $this->getUserTable($authUser);
        $r_table = $this->getUserRoleType($authUser);

        
        // Step 3: Escape and prepare variables
        $now        = time();
        $otpExpiry  = $this->setup['otp_expiry'] ?? 300; // default 5 min
        $fiveMinAgo = $now - $otpExpiry;

        $emailEsc   = mysqli_real_escape_string($this->sql, $email);
        $deviceIdEsc = mysqli_real_escape_string($this->sql, $_REQUEST['device_id']);
        $ipEsc       = mysqli_real_escape_string($this->sql, $_SERVER['REMOTE_ADDR']);
        // Step 4: Check user exists & active
        $query = "SELECT u.id,u.name FROM $u_table as u  JOIN config_roles as r ON (u.role_id=r.id) WHERE u.email = '$emailEsc' AND u.active = 'y' AND u.role_id != '' AND r.role_type=$r_table AND r.active = 'y'  LIMIT 1";
        $res   = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-login-check-user', true);
        }

        $user = mysqli_fetch_assoc($res);
        if (!$user) {
            api_response(404, 'fail', 'User not found or inactive.');
        }
        $userId = (int)$user['id'];
        // Step 5: Count recent login attempts
        $query = "SELECT COUNT(*) AS cnt 
                FROM users_login_log
                WHERE email = '$emailEsc'
                    AND auth_user = '$authUser'
                    AND device_id = '$deviceIdEsc'
                    AND created_at >= $fiveMinAgo";
        $res = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-login-count-attempts', true);
        }

        $row = mysqli_fetch_assoc($res);
        $attemptsCount = (int)($row['cnt'] ?? 0);
        $maxAttempts   = (int)($this->setup['login_attempts'] ?? 5);

        if ($attemptsCount >= $maxAttempts) 
        {                    
            api_response(429, 'fail', 'Too many attempts. Please try again later.');
        }

        // Step 6: Generate OTP & sign token
        $otp = generateNumericOtp();
        $nonce = bin2hex(random_bytes(8));
        $otpTokenData = [
            'exp'        => $now + $otpExpiry,
            'user_id'    => $userId,
            'auth_user'  => $authUser,
            'otp'        => $otp,
            'nonce'      => $nonce
        ];
        $otpToken = $this->JWTEncode($otpTokenData);

        // Step 7: Record login attempt
        $insert = "INSERT INTO users_login_log
                (email, auth_user, user_id, device_id, device_type, device_version, ip, otp, otp_token_nonce, created_at, updated_at)
                VALUES 
                ('$emailEsc', '$authUser', $userId, '$deviceIdEsc', '{$this->deviceType}', '{$this->deviceVersion}', '$ipEsc', '$otp', '$nonce', $now, $now)";
        if (!mysqli_query($this->sql, $insert)) { 
            logSqlError(mysqli_error($this->sql), $insert, 'auth-login-insert-attempt');
        }

        // Step 8: Send Email (implement Email sending method)
        $mailer = new Mailer();
        $mailbody = prepareEmailBody('otp', ['userName' => $user['name'], 'otp' => $otp]);
        $sent = $mailer->send($emailEsc, "Your OTP for JLR UDMS", $mailbody, true);

        api_response(200, 'ok', 'OTP sent successfully.', [
            'otp_token' => $otpToken
        ], [
            'otp' => $otp
        ]);
    }


    public function otpVerify($request) 
    {

        $email = trim($request['email'] ?? '');
        $otp = trim($request['otp'] ?? '');
        $otpToken = $request['otp_token'] ?? '';
        $deviceId = mysqli_real_escape_string($this->sql, $request['device_id'] ?? '');       
        $errors = [];
        if (empty($email)) $errors[] = "Email address is required.";
        elseif (!$this->validateEmail($email)) $errors[] = "Invalid email address.";
        if (empty($otp)) $errors[] = "OTP is required.";
        if (empty($otpToken)) $errors[] = "OTP token is required.";
        if ($errors) $this->errorResponse("Validation errors.", $errors);  
        

        $tokenData = $this->JWTDecode($otpToken);        
        if (!$tokenData) {
            $this->errorResponse("Invalid OTP token.", [], 400, "login");
        } 
        elseif (isset($tokenData['exp']) && $tokenData['exp'] < time()) {           
            $this->errorResponse("OTP token has expired.", [], 400);
        }
        elseif (!isset($tokenData['otp']) || $tokenData['otp'] !== $otp) {
            $this->errorResponse("Invalid OTP.", [], 400);
        }
        $userId = (int)($tokenData['user_id'] ?? 0);
        $nonce = $tokenData['nonce'] ?? '';
        $query = "SELECT id,user_id,auth_user 
                FROM users_login_log 
                WHERE email = '$email'                    
                    AND device_id = '$deviceId'
                    AND user_id = '$userId'
                    AND otp_token_nonce = '$nonce'
                    AND otp = $otp
                    AND otp_verified=0 order by id desc limit 1";
        //echo $query;        
        $res = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-otp-verify-check', true);
        }
        if( mysqli_num_rows($res) >0 )
        {
            $row = mysqli_fetch_assoc($res);
            $userId = $row['user_id'] ?? null;
            $authUser = $row['auth_user'] ?? null;
            if (!$userId || !$authUser) {
                $this->errorResponse("Invalid session data.", [], 401);
            }
            $token = $this->generateAccessToken([
                'id' => $userId,
                'username' => $authUser
            ], $request['device_id'] ?? '', $request['device_type'] ?? '', $request['device_version'] ?? '');

            // Mark OTP as verified
            $update = "UPDATE users_login_log 
                    SET otp_verified = 1, updated_at = " . time() . "
                    WHERE id = " . (int)$row['id'];
            mysqli_query($this->sql, $update);
            if (mysqli_affected_rows($this->sql) <= 0) {
                $this->errorResponse("Failed to mark OTP as verified.", [], 500);
            }            
            api_response(200, 'ok','OTP verified successfully.', $token);
        }
        else {
            $this->errorResponse("Invalid OTP or session expired.", [], 400, "login");
        }
       
    }


    // --- Generate Access Token ---
    public function generateAccessToken($user, $device_id, $device_type, $device_version) {
        $nonce = bin2hex(random_bytes(8));
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->jwtExpiry,
            'nonce' => $nonce,
            'auth_user' => $user['username'],
            'uid' => $user['id'],
            'device_id' => $device_id,
            'device_type' => $device_type,
            'device_version' => $device_version,
            'iss' => $this->issuer,
            'aud' => $this->audience
        ];
        $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');
        $refresh_token  = bin2hex(random_bytes(16));
        $this->storeToken($user['id'], $device_id,$refresh_token, $nonce);
        return [
            "access_token" => $jwt,
            "refresh_token" => $refresh_token,
            "expires_in" => $this->jwtExpiry,
            "auth_user" => $user['username']
        ];
    }

    public function fetchUserById($userId, $authUser) {
        $u_table = $this->getUserTable($authUser);
        $r_table = $this->getUserRoleType($authUser);

        $sql = "SELECT u.id, u.name, u.email, u.mobile, u.active, u.role_id,
                    r.role_name,r.role_main,r.description";
                    
        if ($authUser !== "admin") {
            $sql .= ", d.id AS dealer_id, d.name AS dealer_name ";
        }

        $sql .= " FROM $u_table AS u
                JOIN config_roles AS r ON (u.role_id = r.id)";

        if ($authUser !== "admin") {
            $sql .= " LEFT JOIN dealer_groups AS d ON u.dealership_id = d.id";
        }

        $sql .= " WHERE u.id = '" . mysqli_real_escape_string($this->sql, $userId) . "' AND u.active = 'y' AND u.role_id != '' AND r.role_type = $r_table AND r.active = 'y' LIMIT 1";

        $res = mysqli_query($this->sql, $sql);
        $user = $res ? mysqli_fetch_assoc($res) : null;

        if (!$user) {
            api_response(404, 'fail', 'User not found or inactive.');
        }

        $user['info'] = [
            "id"   => $user['dealer_id'] ?? null,
            "name" => $user['dealer_name'] ?? null
        ];
        unset($user['dealer_id'], $user['dealer_name']);

        $user['route'] = $this->getUserRoleRoute($authUser);
        $modules = $this->rolePermissions($user['role_id'] ?? 0, $authUser);

        return ["user_details" => $user, "modules" => $modules];
    }


    public function rolePermissions($roleId, $authUser)
    {
        $deviceType = $GLOBALS['api_user']['device_type'] ?? null;
        $permissions = [];

        $moduleType = ($authUser === "dealer") ? 1 : (($authUser === "admin") ? 0 : null);
        if ($moduleType === null) {
            return $permissions;
        }

        // Base query
        $query = "
            SELECT 
                b.module_name,
                b.category_name,
                b.is_visible,
                b.is_app,
                b.is_default,
                b.url AS module_url,
                b.icon,
                c.submodule_name,
                c.action AS submodule_url
            FROM config_role_permissions AS a
            LEFT JOIN config_modules AS b ON a.module_id = b.id
            LEFT JOIN config_submodules AS c ON c.id = a.submodule_id
            WHERE b.module_type = $moduleType
            AND b.active = 'y'
            AND (c.id IS NULL OR c.active = 'y')
            AND a.role_id = $roleId
        ";

        if ($deviceType !== 'web') {
            $query .= " AND b.is_app = 'y'";
        }

        $query .= " ORDER BY b.category_name, b.module_name";

        $res = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-role-permissions', true);
            return $permissions;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            if (!isset($permissions[$row['module_url']])) {
                $permissions[$row['module_url']] = [
                    'name'       => $row['module_name'],
                    'category'   => $row['category_name'],
                    'visible'    => $row['is_visible'],
                    'icon'       => $row['icon'],
                    'app'        => $row['is_app'],
                    'default'    => $row['is_default'],
                    'submodules' => []
                ];
            }

            if (!empty($row['submodule_url'])) {
                $permissions[$row['module_url']]['submodules'][$row['submodule_url']] = [
                    'name' => $row['submodule_name']
                ];
            }
        }

        return $permissions;
    }


    public function otpResend($request) {
        global $env_server;
        $email = trim($request['email'] ?? '');
        $otpToken = $request['otp_token'] ?? '';
        $errors = [];
        if (empty($email)) {
            $errors['email'] = "Email address is required.";
        } 
        elseif (!$this->validateEmail($email)) {
            $errors['email'] = "Invalid email address.";
        }
        if (empty($otpToken)) {
            $errors['otp_token'] = "OTP token is required.";
        }
        if (!empty($errors)) {           
            api_response(400, 'fail', 'Validation failed.',[], [], $errors);
        }
       
        $otpTokenData = $this->JWTDecode($otpToken);
        
        if (!$otpTokenData) {
            $this->errorResponse("OTP session expired. Please initiate login again.", [], 400, "login");
        }
        $fiveMinAgo = time() - $this->setup['otp_expiry'];
        $query = "SELECT COUNT(*) AS cnt 
                FROM users_login_log 
                WHERE email = '". mysqli_real_escape_string($this->sql, $email) . "'
                    AND user_id = '" . intval($otpTokenData['user_id']) . "'
                    AND device_id = '".mysqli_real_escape_string($this->sql,$otpTokenData['device_id'])."'
                    AND created_at >= $fiveMinAgo";
        //echo $query;
        $res = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-resend otp-count-attempts', true);
        }
        $row = mysqli_fetch_assoc($res);
        $attemptsCount = (int)($row['cnt'] ?? 0);
        $maxAttempts   = (int)($this->setup['otp_resend'] ?? 2);

        if ($attemptsCount >= $maxAttempts) 
        {                    
            api_response(429, 'fail', 'Too many attempts. Please try again later.');
        }

        $query = "SELECT id, user_id, auth_user FROM users_login_log 
                WHERE email = '" . mysqli_real_escape_string($this->sql, $email) . "'
                AND user_id = '" . intval($otpTokenData['user_id']) . "'
                AND otp_token_nonce = '" . mysqli_real_escape_string($this->sql, $otpTokenData['nonce']) . "'
                AND device_id = '" . mysqli_real_escape_string($this->sql, $otpTokenData['device_id']) . "'
                AND otp = '" . mysqli_real_escape_string($this->sql, $otpTokenData['otp']) . "' 
                AND otp_verified = 0 
                ORDER BY id DESC LIMIT 1";
        //echo $query;exit;
        $res = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-otp-resend-check', true);
        }
        if (mysqli_num_rows($res) === 0) {
            $this->errorResponse("Invalid OTP session. Please initiate login again.", [], 400, "login");
        }
        $row = mysqli_fetch_assoc($res);

        $up_que = "UPDATE users_login_log 
                SET otp_verified = 2, updated_at = " . time() . "
                WHERE id = " . intval($row['id']);
        mysqli_query($this->sql, $up_que);

        $otp = generateNumericOtp();
        $now = time();
        $otpExpiry = $this->setup['otp_expiry'] ?? 300; // default 5 min
        $userId = (int)($row['user_id'] ?? 0);
        $authUser = $row['auth_user'] ?? 'dealer';
        $nonce = bin2hex(random_bytes(8));
        $otpData = [
            'exp'        => $now + $otpExpiry,
            'user_id'    => $userId,
            'auth_user'  => $authUser,
            'otp'        => $otp,
            'nonce'      => $nonce
        ];
        $otpToken = $this->JWTEncode($otpData);

        $emailEsc   = mysqli_real_escape_string($this->sql, $email);
        $deviceIdEsc = mysqli_real_escape_string($this->sql, $otpTokenData['device_id'] ?? '');
        $insert = "INSERT INTO users_login_log 
                (email, auth_user, user_id, device_id,device_type, device_version, ip, otp, otp_token_nonce, created_at, updated_at)
                VALUES 
                ('$emailEsc', '$authUser', $userId, '$deviceIdEsc', '{$this->deviceType}', '{$this->deviceVersion}', '".$_SERVER['REMOTE_ADDR']."', '$otp','$nonce', $now, $now)";
        //echo $insert;
        if (!mysqli_query($this->sql, $insert)) { 
            logSqlError(mysqli_error($this->sql), $insert, 'auth-login-insert-attempt');
        }
        api_response(200, 'ok', 'OTP sent successfully.', [
            'otp_token' => $otpToken
        ], [
            'otp' => $otp
        ]);
    }
    
    public static function validateExpiredAccessToken($expiredAccessToken) 
    {              
        $parts = explode('.', $expiredAccessToken);
        if (count($parts) !== 3) {
            //throw new Exception('Invalid JWT token');
            return null;
        }
        // Base64 decode the payload part
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($payload === false) {
            //throw new Exception('Failed to decode payload');
            return null;
        }       
        return json_decode($payload, true);

    }

    // --- Token/Session ---
    public function getAccessToken($request) {
        $token = $request['access_token'] ?? '';
        $token2 = $request['refresh_token'] ?? '';
        $device_id = $request['device_id'] ?? '';
        $device_version = $request['device_version'] ?? '';
        
        if (!$token || !$token2) {
            $this->errorResponse("Access Token or Refresh Token is missing.", [], 401);
        }
        $decoded = $this->validateExpiredAccessToken($token);
        if (!$decoded) {
            $this->errorResponse("Invalid token structure.", [], 401);
        }

        //echo '<pre>'; print_r($decoded); echo '<pre>';exit;
        $query = "SELECT * FROM users_tokens WHERE 
            refresh_token = '" . mysqli_real_escape_string($this->sql, $token2) . "' 
            AND device_id = '" . mysqli_real_escape_string($this->sql, $device_id) . "' 
            AND nonce = '" . mysqli_real_escape_string($this->sql, $decoded['nonce']) . "'
            AND expires_at > " . time() . "
            AND user_id = '" . intval($decoded['uid']) . "' AND is_revoked = 0 LIMIT 1";
        
        $res = mysqli_query($this->sql, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->sql), $query, 'auth-get-access-token-check', true);
        }
        if (mysqli_num_rows($res) === 0) {
            $this->errorResponse("Invalid or expired refresh token.", [], 401);
        }
        $row = mysqli_fetch_assoc($res);
        $up_que = "UPDATE users_tokens SET 
            is_revoked = 1, 
            updated_at = '".date('Y-m-d H:i:s')."' 
            WHERE id = " . intval($row['id']);
        mysqli_query($this->sql, $up_que);
        $table = $this->getUserTable($decoded['auth_user']);
        $que = "SELECT id FROM $table WHERE id = '" . intval($row['user_id']) . "' and active = 'y' LIMIT 1";
        $result = mysqli_query($this->sql, $que);
        if (!$result) {
            logSqlError(mysqli_error($this->sql), $que, 'auth-get-access-token-user-check', true);
        }
        if (mysqli_num_rows($result) === 0) {
            $this->errorResponse("User not found or inactive.", [], 404);
        }
        $authUser = mysqli_fetch_assoc($result)['roles'] ?? '';
        
        $accessToken = $this->generateAccessToken([
            'id' => $row['user_id'],
            'username' => $decoded['auth_user']
        ], $device_id, $row['device_type'], $device_version);
        api_response(200, 'ok','Access token generated successfully.', $accessToken);
    }

    public function getAppSyncStatus($sync_time = '')
    {
        $query = "SELECT value, updated_on 
                FROM feature_flags 
                WHERE flag_name = 'appsync' 
                LIMIT 1";

        $result = mysqli_query($this->sql, $query);
        $flag = mysqli_fetch_assoc($result);

        if (empty($flag)) {
            return [
                'sync' => 'n',
                'sync_time' => date('Y-m-d H:i:s'),
                'message' => 'Appsync flag not found'
            ];
        }
        $needsSync = 'n';
        if (empty($sync_time)) {
            $needsSync = 'y'; 
        } elseif (!empty($flag['updated_on'])) {
            $needsSync = (strtotime($flag['updated_on']) > strtotime($sync_time)) ? 'y' : 'n';
        }

        return [
            'sync'       => $needsSync,
            'sync_time'  => $flag['updated_on'],
        ];
    }

    public function getUser($request)
    {
        $authUser = $GLOBALS['api_user']['auth_user'] ?? '';
        $userId = $GLOBALS['api_user']['uid'] ?? null;
        $deviceType = $GLOBALS['api_user']['device_type'] ?? null;

        if (!$userId || !$authUser) {
            $this->errorResponse("Unauthorized access.", [], 403);
        }

        // Fetch user details
        $user = $this->fetchUserById($userId, $authUser);
        if (empty($user['user_details'])) {
            $this->errorResponse("User not found.", [], 404);
        }

        // Get sync_time from request (if app already synced before)
        $sync_time = $request['sync_time'] ?? '';
        //  Use getAppSyncStatus() to determine if sync is needed
        $syncStatus = $this->getAppSyncStatus($sync_time);

        //  Merge device info
        $user['device_info'] = [
            'type'        => $deviceType,
            'version'     => $this->deviceVersion,
            'sync'        => $syncStatus['sync'],
            'sync_time'   => $syncStatus['sync_time'],
        ];

        //  Return API response
        api_response(200, 'ok', 'User fetched successfully', $user);
    }


    public function logout($request) {
        $token = $request['token'] ?? '';
        if (empty($token)) {
            $this->errorResponse("Token is required.", [], 401);
        }
        $verify = $this->verifyAccessToken($token, $request['device_id'] ?? '');
        if (!$verify) {
            $this->errorResponse("Invalid token.", [], 401);
        }
        $query = "UPDATE users_tokens SET is_revoked = 1, updated_at = '".date('Y-m-d H:i:s')."' 
                WHERE nonce = '" . mysqli_real_escape_string($this->sql, $verify['nonce']) . "' 
                AND user_id = '" . intval($verify['uid']) . "'
                AND device_id = '" . mysqli_real_escape_string($this->sql, $request['device_id'] ?? '') . "'";
        mysqli_query($this->sql, $query);
        if (mysqli_affected_rows($this->sql) <= 0) {
            $this->errorResponse("Failed to revoke token.", [], 500);
        }
        api_response(200,'ok', 'Logged out successfully.');
    }

    public function generateRefreshToken() {
        $now = time();
        $nonce = bin2hex(random_bytes(16));
        $payload = [           
            'exp' => $now + $this->refreshExpiry
        ];
        $jwt = JWT::encode($payload, $this->refreshSecret, 'HS256');       
        return $jwt;
    }

    private function storeToken($user_id, $device_id, $token, $nonce) {
        $now = time();
        $user_id = intval($user_id);
        $token_esc = mysqli_real_escape_string($this->sql, $token);
        $nonce_esc = mysqli_real_escape_string($this->sql, $nonce);
        $device_id_esc = $device_id ?  mysqli_real_escape_string($this->sql, $device_id) : 'NULL';       
        $expiry = $now + $this->refreshExpiry;
        $query = "INSERT INTO users_tokens (user_id, device_id, device_type, device_version, refresh_token, nonce, ip, user_agent, expires_at, created_at)
                VALUES ($user_id, '$device_id_esc', '{$this->deviceType}', '{$this->deviceVersion}', '$token_esc', '$nonce_esc', '".$_SERVER["REMOTE_ADDR"]."', '".$_SERVER['HTTP_USER_AGENT']."', '$expiry','".date('Y-m-d H:i:s')."' )";
        // echo $query;exit;
        mysqli_query($this->sql, $query);
    }

    public function verifyAccessToken($token, $device_id) {
        try {
            $decoded = $this->JWTDecode($token);
            if (!$decoded) return false;                   
            if ($decoded['iss'] !== $this->issuer || $decoded['aud'] !== $this->audience) return false;
            if ($decoded['device_id'] !== $device_id) return false;
            if ($decoded['device_type'] == '') return false;
            $nonce_esc = mysqli_real_escape_string($this->sql, $decoded['nonce'] ?? '');
            $device_id_esc = mysqli_real_escape_string($this->sql, $decoded['device_id'] );
            $user_id = intval($decoded['uid']);
        
            $sql = "SELECT COUNT(*) FROM users_tokens 
                    WHERE nonce='$nonce_esc' AND user_id='$user_id' AND is_revoked=0 
                    AND device_id='$device_id_esc'";
            
            $res = mysqli_query($this->sql, $sql);
            $row = mysqli_fetch_row($res);
            if (!$row[0]) return false;
            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    

    public function revokeToken($token, $type, $device_id) {
        $token_esc = mysqli_real_escape_string($this->sql, $token);
        $type_esc = mysqli_real_escape_string($this->sql, $type);
        $device_id_esc = mysqli_real_escape_string($this->sql, $device_id);
        $sql = "UPDATE users_tokens SET is_revoked=1 WHERE token='$token_esc' AND type='$type_esc' AND device_id='$device_id_esc'";
        mysqli_query($this->sql, $sql);
    }

    public function revokeAllTokensForDevice($user_id, $device_id) {
        $user_id = intval($user_id);
        $device_id_esc = mysqli_real_escape_string($this->sql, $device_id);
        $sql = "UPDATE users_tokens SET is_revoked=1 WHERE user_id=$user_id AND device_id='$device_id_esc' AND is_revoked=0";
        mysqli_query($this->sql, $sql);
    }

    public function revokeAllTokensForUser($user_id) {
        $user_id = intval($user_id);
        $sql = "UPDATE users_tokens SET is_revoked=1 WHERE user_id=$user_id AND is_revoked=0";
        mysqli_query($this->sql, $sql);
    }
    public function checkModuleAccess($module,$submodule=null,$permission='can_view')
    {   

        $authUser = $GLOBALS['api_user']['auth_user'] ?? '';
        $userId = $GLOBALS['api_user']['uid'] ?? null;
        $user = $this->fetchUserById($userId,$authUser);
       
        //echo $module."<br>".$submodule;
        if( empty($user['modules'][$module]) )
        {            
            return false;
        }
        $modules_list = $user['modules'][$module];
       
        if( !empty($submodule) )
        {
            if(empty($modules_list['submodules']) || !isset($modules_list['submodules'][$submodule]))
            {
                return false;
            }

            if ( !empty($modules_list['submodules']) && $modules_list['submodules'][$submodule][$permission] )
            {
                return true;
            }
            else
            {
                return false;
            }
        }        
        if( !empty($user['modules'][$module]) )
        {
            return true;
        }
    }
    public function getDealership()
    {
        $auth_user = $GLOBALS['api_user']['auth_user'];
        $user_id = $GLOBALS['api_user']['uid'];
        $dealership = [];
        if( $auth_user == "dealer" && $user_id >0 )
        {
            $query = "SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.mobile,
                        u.role_id,
                        u.branch_id,
                        d.id AS dealership_id,
                        d.name AS dealership_name,
                        r.role_name,
                        r.role_main
                    FROM users AS u
                    JOIN dealer_groups AS d 
                        ON (u.dealership_id = d.id)
                    JOIN config_roles AS r 
                        ON (u.role_id = r.id) 
                    WHERE u.id = $user_id";
            $res = mysqli_query($this->sql,$query);
            if (!$res) {
                logSqlError(mysqli_error($this->sql), $query, 'auth-dealerships', true);
            }            
            $dealership = mysqli_fetch_assoc($res);  

            // Handle branch_id JSON
            $branchIds = [];
            if (!empty($dealership['branch_id'])) {
                $branchIds = json_decode($dealership['branch_id'], true);
                if (!is_array($branchIds)) {
                    $branchIds = [$dealership['branch_id']];
                }
            }

            // Get branch details if branchIds exist
            if (!empty($branchIds)) {
                $ids = implode(",", array_map('intval', $branchIds));

                $branchQuery = "SELECT id AS branch_id, name AS branch_name, city AS branch_city 
                                FROM dealer_branches 
                                WHERE id IN ($ids)";
                $branchRes = mysqli_query($this->sql, $branchQuery);

                if ($branchRes) {
                    $branches = [];
                    while ($row = mysqli_fetch_assoc($branchRes)) {
                        $branches[] = $row;
                    }
                    $dealership['branches'] = $branches;
                } else {
                    logSqlError(mysqli_error($this->sql), $branchQuery, 'auth-dealership-branches', true);
                }
            } else {
                $dealership['branches'] = [];
            }
        }
        return $dealership;
    }
}