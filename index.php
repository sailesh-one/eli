<?php
ob_start();
try {
    if (!is_file(__DIR__ . '/pages/page_main.php')) {
        throw new RuntimeException('Main page not found.');
    }
    require __DIR__ . '/pages/page_main.php';
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '<h2>Something went wrong.</h2><p>Please try again later.</p>';
} finally {
    ob_end_flush();
}