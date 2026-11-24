<?php
// The real configuration is outside public_html and outside Git.
function dentalla_captcha_config() {
    $path = dirname(__DIR__) . '/dentalla-smartcaptcha.php';
    if (!is_file($path)) return [];
    $config = require $path;
    return is_array($config) ? $config : [];
}
function dentalla_captcha_valid($token) {
    $config = dentalla_captcha_config();
    if (!is_string($token) || $token === '' || strlen($token) > 8192 ||
        empty($config['server_key']) || !function_exists('curl_init')) return false;
    $ch = curl_init('https://smartcaptcha.cloud.yandex.ru/validate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => $config['server_key'], 'token' => $token,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8, CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = is_string($response) ? json_decode($response, true) : null;
    return $status === 200 && is_array($data) &&
        ($data['status'] ?? '') === 'ok' &&
        in_array(strtolower($data['host'] ?? ''), ['kvrachu.info', 'www.kvrachu.info'], true);
}
