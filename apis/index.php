<?php
// --- Only allow POST requests ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'fail', 'msg' => 'Method Not Allowed']);
    exit;
}
// --- Common includes and globals ---
include($_SERVER['DOCUMENT_ROOT'] . '/common/control_config.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/apis/routes.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_auth.php';
loadDeviceId();


global $auth;
$auth = new Auth();

// Always merge JSON and POST into $_REQUEST for unified input
$input = file_get_contents('php://input');
$json = safe_json_decode($input);
if (is_array($json)) {
    $_REQUEST = array_merge($_REQUEST, $json);
}
if (!empty($_POST)) {
    $_REQUEST = array_merge($_REQUEST, $_POST);
}

// --- Dynamic Routing & Protection ---
$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$apiBase = 'apis/';
$path = (strpos($requestUri, $apiBase) === 0) ? substr($requestUri, strlen($apiBase)) : $requestUri;
$segments = explode('/', $path);
$verRoute = $segments[0] ?? '';
$mainRoute = $segments[1] ?? '';
$subRoute = $_REQUEST['action'] ?? '';

// Determine protection and allowed roles
$routeConfig = $config['routes'][$mainRoute] ?? null;
$subConfig = ($routeConfig['subroutes'][$subRoute] ?? null);
$isProtected = $subConfig['protected'] ?? $routeConfig['protected'] ?? false;
$allowedRoles = $subConfig['roles'] ?? $routeConfig['roles'] ?? [];

// Centralized token and role check for protected routes
if ($isProtected) {    
    $access_token = getBearerToken();
    if (!$access_token) {
        api_response(401, 'fail', 'Unauthorized', [], []);
    }
    $device_id = $_REQUEST['device_id'] ?? '';    
    $user = $auth->verifyAccessToken($access_token, $device_id);
    if (!$user) {
        api_response(401, 'fail', 'Unauthorized', [], []);
    }
    // Check if user has required role
    /*$userRole = is_object($user) ? ($user->role ?? null) : ($user['role'] ?? null);
    if ($userRole === null || !in_array($userRole, $allowedRoles, true)) {
        api_response(403, 'fail', 'Forbidden: insufficient role', [], []);
    }*/
    $GLOBALS['api_user'] = $user;
    $GLOBALS['dealership'] = [];
    if( $user['auth_user'] == "dealer" )
    {
        $GLOBALS['dealership'] = $auth->getDealership();
    }
}
// Route to subfolder index.php if exists
$routeFile = $_SERVER['DOCUMENT_ROOT'] . "/apis/$path/index.php";
if (is_file($routeFile)) {
    require $routeFile;
    exit;
}
api_response(404, 'fail', 'Not Found', [], [], [], []);