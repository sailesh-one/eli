<?php
// --- Only allow POST requests ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status'=>'fail', 'msg' => 'Method Not Allowed']);
    exit;
}
// --- Common includes and globals ---
include($_SERVER['DOCUMENT_ROOT'] . '/common/control_config.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/apis/routes.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_auth.php';
// loadDeviceId();


$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$apiBase = 'services/';
$path = (strpos($requestUri, $apiBase) === 0) ? substr($requestUri, strlen($apiBase)) : $requestUri;
$segments = explode('/', $path);
$verRoute = $segments[0] ?? '';
$mainRoute = $segments[1] ?? '';
$subRoute = $_REQUEST['action'] ?? '';


// Route to subfolder index.php if exists
$routeFile = $_SERVER['DOCUMENT_ROOT'] . "/services/$path/index.php";
if (is_file($routeFile)) {
    require $routeFile;
    exit;
}
api_response(404, 'fail', 'Not Found', [], [], [], []);