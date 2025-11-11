<?php
$connection = @mysqli_init();
//global $cte_m2_connection;
@mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
mysqli_report(MYSQLI_REPORT_OFF);
$connection_status = @mysqli_real_connect( $connection,  $config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'],$config['db_port']);
if (!$connection_status) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => mysqli_connect_error()
    ]);
    exit;
}
mysqli_set_charset($connection, "utf8mb4");
?>