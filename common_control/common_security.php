<?php
require dirname($_SERVER['DOCUMENT_ROOT']) . '/eli_config.php';

function getUserIPAddress(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getRealIP(): string {
    static $realIP = null;
    if ($realIP !== null) return $realIP;

    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',      
        'HTTP_X_REAL_IP',             
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
    ];

    foreach ($headers as $header) {
        if (empty($_SERVER[$header])) continue;
        $ips = array_map('trim', explode(',', $_SERVER[$header]));
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $realIP = $ip;
            }
        }
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $realIP = $ip;
            }
        }
    }
    return $realIP = ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
$_SERVER['REMOTE_ADDR'] = getRealIP();

$config['display_errors_mode']         = $config['display_errors_mode'] ?? '0';
$config['display_startup_errors_mode'] = $config['display_startup_errors_mode'] ?? '0';
$config['error_reporting']             = $config['error_reporting'] ?? E_ALL & ~E_NOTICE;

ini_set('display_errors', $config['display_errors_mode']);
ini_set('display_startup_errors', $config['display_startup_errors_mode']);
error_reporting($config['error_reporting']);
$error_page = $_SERVER['DOCUMENT_ROOT'] . '/pages/page_400.php';

$headerPatterns = [
    'HTTP_X_FORWARDED_FOR' => '/^[0-9a-fA-F:.,;\-\s]+$/', 
    'HTTP_HOST'            => '/^[a-z0-9.\-]+$/i',       
];

foreach ($headerPatterns as $header => $pattern) {
    if (!empty($_SERVER[$header]) && !preg_match($pattern, $_SERVER[$header])) {
        http_response_code(400);
        include $error_page;
        exit;
    }
}

$origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($origin && !preg_match('/^https?:\/\/[a-z0-9.\-]+(:[0-9]+)?(\/.*)?$/i', $origin)) {
    http_response_code(400);
    include $error_page;
    exit;
}
if ($referer && !preg_match('/^https?:\/\/[a-z0-9.\-]+(:[0-9]+)?(\/.*)?$/i', $referer)) {
    http_response_code(400);
    include $error_page;
    exit;
}

// Validate Origin/Referer if present
// $o = $_SERVER['HTTP_ORIGIN'] ?? ''; $r = $_SERVER['HTTP_REFERER'] ?? '';
// if ($o || $r) {
//     foreach ($config['config_trusted_hosts'] as $host) {
//         if (stripos($o, $host) !== false || stripos($r, $host) !== false) return;
//     }
//     http_response_code(400); include $error_page; exit;
// }

// Initialize connection
global $env_server,  $connection;
$env_server = $config['env_server'];


// global $redis;
// $redis = new Redis();
// $connection_redis = true;
// $redis_ip_blocked = false;
// try {
//     $connected = $redis->connect($config['redis_host'], $config['redis_port'], 1.5);
//     if (!$connected) {
//         echo "Internal Redis Error";
//         exit;
//         throw new Exception("Could not connect");
//     }
//     $redis->select($config['redis_db']);
// } catch (Exception $e) {
//     $connection_redis = false;
//     $rediserror = sprintf(
//         "Redis Connection ERROR - %s%s; Filename=%s; Errornote: Cache server unavailable for %s:%s; Exception: %s",
//         $_SERVER['HTTP_HOST'] ?? '',
//         $_SERVER['REQUEST_URI'] ?? '',
//         basename(__FILE__),
//         $config['redis_host'],
//         $config['redis_port'],
//         $e->getMessage()
//     );
//     error_log($rediserror);
//     header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
//     echo '500 Internal Server Error';
//     exit;
// }

// // Block IP if in Redis blacklist
// $blocked_ips = $redis->hGetAll('blk_ip');
// $client_ip = $_SERVER['REMOTE_ADDR'];
// if (is_array($blocked_ips) && ($blocked_ips[$client_ip] ?? null) === 'y') {
//     header("HTTP/1.1 400 Bad Request");
//     echo "Blocked IP";
//     exit;
// }



if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'json') !== false) {
    $data = file_get_contents('php://input') ?: '';
    $_POST = json_decode($data, true) ?: [];
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(sprintf(
            "JSON parse error at %s: %s; Input: %s",
            $_SERVER['REQUEST_URI'] ?? '',
            json_last_error_msg(),
            substr($data, 0, 500)
        ));
        http_response_code(400);
        header('Content-Type: application/json');
        header('X-Reason: JSON Parse Error');
        echo json_encode([
            "status" => "fail",
            "error"  => "JSON Parse Error: " . json_last_error_msg()
        ]);
        exit;
    }
}

function escapeData(mysqli $conn, string $data = ''): string {
    return $conn->real_escape_string(trim((string)$data));
}
function hsc($data): string {
    return htmlspecialchars((string)$data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

    require($_SERVER['DOCUMENT_ROOT'] . "common_control/common_security_class.php");
	$sec_obj = new SECURITY();
	//$sec_obj->con_redis_security = $redis;
	$sec_obj->filter( $_POST );
	$sec_obj->filter( $_GET );
	$sec_obj->filter( $_REQUEST );
	$sec_obj->filter( $_FILES );
	
	if( isset($_GET['action']) && $_GET['action'] == "clear_block" ){
		$sec_obj->redis_ip_block("clear");
	}
    $config_enable_ip_blocking = false;
	if( $config_enable_ip_blocking ){
		$sec_obj->redis_ip_block();
	}
	$sec_obj->fileUpload();
	$sec_obj->logtable();
	if( $sec_obj->block ){
		header("HTTP/1.1 400 Bad Request");
		header("Reason: DATA Validation Errors");
		include($error_page);
		exit;
	}
   $ver = date('YmdHis');
