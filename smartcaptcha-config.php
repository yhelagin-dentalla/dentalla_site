<?php
require_once __DIR__ . '/smartcaptcha-server.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$config = dentalla_captcha_config();
if (empty($config['client_key']) || empty($config['server_key'])) {
    http_response_code(503);
    echo json_encode(['error' => 'unavailable']);
    exit;
}
echo json_encode(['sitekey' => $config['client_key']]);
