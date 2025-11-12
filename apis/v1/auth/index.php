<?php
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_mailer.php';

$action = strtolower($_REQUEST['action'] ?? '');


try {
    switch ($action) {

       case 'captcha':
            try {
                $auth->getCaptcha('auth');
            } catch (Throwable $e) {
                api_response(400, 'fail', 'Captcha error', [], [], ['error' => $e->getMessage()]);
            }
            break;

        case 'login':
            try {
                // $captcha_code = $_REQUEST['captcha_code'] ?? '';
                // $captcha_token = $_REQUEST['captcha_token'] ?? '';
                $auth_user = $_REQUEST['auth_user'] ?? 'dealer';
                $mobile = trim($_REQUEST['mobile'] ?? '');
                $errors = [];
            
                if (empty($mobile)) $errors['mobile'] = 'Mobile is required.';
                $regexErrors = validate_fields_regex(['mobile' => $mobile], ['mobile']);
                $errors = array_merge($errors, $regexErrors);
                if (!empty($errors)){ api_response(403, 'fail', 'Validation failed.', [], [], $errors); }
                    $auth->login('auth', [
                        'mobile' => $mobile,
                        // 'captcha_code' => $captcha_code,
                        // 'captcha_token' => $captcha_token,
                        'auth_user' => $auth_user,
                    ]);
            } catch (Throwable $e) {
                api_response(403, 'Login error: ' . $e, [], []);
            }
            break;

        case 'otpverify':
            try {
                $mobile = trim($_REQUEST['mobile'] ?? '');
                $otp = trim($_REQUEST['otp'] ?? '');
                $otp_token = $_REQUEST['otp_token'] ?? '';
                $device_id = $_REQUEST['device_id'] ?? '';
                $device_type = $_REQUEST['device_type'] ?? '';
                $device_version = $_REQUEST['device_version'] ?? '';
                $errors = [];
                if (empty($mobile)) $errors['mobile'] = 'Mobile is required.';
                if (empty($otp)) $errors['otp'] = 'OTP is required.';
                if (empty($otp_token)) $errors['otp_token'] = 'OTP token is required.';
                $regexErrors = validate_fields_regex([
                    'mobile' => $mobile,
                    'otp' => $otp
                ], ['mobile', 'otp']);
                $errors = array_merge($errors, $regexErrors);
                if (!empty($errors)) api_response(400, 'fail', 'Validation failed.', [],[],$errors);
                $auth->otpVerify([
                    'mobile' => $mobile,
                    'otp' => $otp,
                    'otp_token' => $otp_token,
                    'device_id' => $device_id,
                    'device_type' => $device_type,
                    'device_version' => $device_version
                ]);
            } catch (Throwable $e) {
                api_response(400, 'fail', 'OTP verify error: ' . $e->getMessage(), [], []);
            }
            break;
        case 'otpresend':
            try {
                $email = trim($_REQUEST['email'] ?? '');
                $otp_token = $_REQUEST['otp_token'] ?? '';
                $errors = [];
                if (empty($email)) $errors['email'] = 'Email address is required.';
                if (empty($otp_token)) $errors['otp_token'] = 'OTP token is required.';
                $regexErrors = validate_fields_regex([
                    'email' => $email
                ], ['email']);
                $errors = array_merge($errors, $regexErrors);
                if (!empty($errors)) api_response(400, 'fail', 'Validation failed.',[], [], $errors);
                $auth->otpResend([
                    'email' => $email,
                    'otp_token' => $otp_token
                ]);
            } catch (Throwable $e) {
                api_response(400, 'fail', 'OTP resend error: ' . $e->getMessage(), [], []);
            }
            break;
        case 'getaccesstoken':
            try {
                $access_token = getBearerToken();                                               
                $refresh_token = $_REQUEST['refresh_token'] ?? '';
                $device_id = $_REQUEST['device_id'] ?? '';
                $device_type = $_REQUEST['device_type'] ?? '';
                $device_version = $_REQUEST['device_version'] ?? '';
                
                $errors = [];
                if (empty($access_token)) $errors['token'] = 'Access Token is required.';
                if (empty($refresh_token)) $errors['refresh_token'] = 'Refresh Token is required.';
                if (empty($device_id)) $errors['device_id'] = 'Device ID is required.';
                if (!empty($errors)) api_response(400, 'Validation failed.', [],[], $errors);
                $auth->getAccessToken([
                    'access_token' => $access_token,
                    'refresh_token' => $refresh_token,
                    'device_id' => $device_id,
                    'device_version' => $device_version
                ]);
            } catch (Throwable $e) {
                api_response(400, 'Get access token error: ' . $e->getMessage(), [], []);
            }
            break;
        case 'getuser':

            try {  
                if (!isset($GLOBALS['api_user']) || empty($GLOBALS['api_user'])) {
                    api_response(403, 'fail', 'Unauthorized', [], []);
                } 
                $auth->getUser( $_REQUEST );                                
            } catch (Throwable $e) {               
                api_response(403, 'fail', 'Get user error: ' . $e, [], []);
            }
            break;
        case 'logout':
            try {               
                $access_token = getBearerToken();
                $device_id = $_REQUEST['device_id'] ?? '';
                $errors = [];
                if (empty($access_token)) $errors['token'] = 'Token is required.';
                if (!empty($errors)) api_response(400, 'Validation failed.', [], $errors);
                $auth->logout([
                    'token' => $access_token,
                    'device_id' => $device_id
                ]);
            } catch (Throwable $e) {
                api_response(400,'fail', 'Logout error: ' . $e->getMessage(), [], []);
            }
            break;
        case 'verifycaptcha':
            try {
                $captcha_code = $_REQUEST['captcha_code'] ?? '';
                $captcha_token = $_REQUEST['captcha_token'] ?? '';
                $errors = [];
                if (empty($captcha_code)) $errors['captcha_code'] = 'Captcha code is required.';
                if (empty($captcha_token)) $errors['captcha_token'] = 'Captcha token is required.';
                if (!empty($errors)) api_response(400, 'Validation failed.',[], [], $errors);
                $auth->verifyCaptcha([
                    'captcha_code' => $captcha_code,
                    'captcha_token' => $captcha_token
                ]);
            } catch (Throwable $e) {
                api_response(400, 'Verify captcha error: ' . $e->getMessage(), [], []);
            }
            break;
        default:
            api_response(400, 'fail', 'Invalid action for auth', [], []);
            break;
    }
} catch (Throwable $e) {
    api_response(403, 'fail', 'Internal server error: ' . $e->getMessage(), [], []);
}
