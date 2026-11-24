<?php
// Обработчик формы записи на приём.
// Защита от спама: honeypot-поле (website) + временная метка (_ts).

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// --- Антиспам ---
// 1. Honeypot: поле "website" должно быть пустым (боты его заполняют)
if (!empty($_POST['website'])) {
    header('Location: index.html');
    exit;
}
// 2. Временная метка: между загрузкой формы и отправкой должно пройти не менее 3 секунд
$ts = isset($_POST['_ts']) ? intval($_POST['_ts']) : 0;
if ($ts > 0 && (time() - $ts) < 3) {
    header('Location: index.html');
    exit;
}
// --- /Антиспам ---
require_once __DIR__ . '/smartcaptcha-server.php';
if (!dentalla_captcha_valid($_POST['appointment-captcha-token'] ?? null)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Проверка безопасности</title><p>Заявка не отправлена: проверка безопасности не пройдена. Вернитесь к форме и попробуйте снова или позвоните в клинику.</p><a href="index.html">На сайт</a></html>';
    exit;
}


$to = 'dentalla@inbox.ru';

function dentalla_field($key, $default = '') {
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

$subject = dentalla_field('_subject', 'Заявка с сайта kvrachu.info');
$name    = dentalla_field('Имя');
$phone   = dentalla_field('Телефон');
$doctor  = dentalla_field('Врач');
$comment = dentalla_field('Комментарий');

if ($name === '' && $phone === '' && $comment === '') {
    header('Location: index.html');
    exit;
}

$body  = "Новая заявка с сайта kvrachu.info\r\n\r\n";
$body .= "Имя: " . ($name !== '' ? $name : '(не указано)') . "\r\n";
$body .= "Телефон: " . ($phone !== '' ? $phone : '(не указан)') . "\r\n";
if ($doctor !== '') {
    $body .= "Врач: " . $doctor . "\r\n";
}
if ($comment !== '') {
    $body .= "Комментарий: " . $comment . "\r\n";
}
$body .= "Дата: " . date('d.m.Y H:i:s') . "\r\n";

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: Сайт Денталла <no-reply@kvrachu.info>\r\n";

if (!mail($to, $encodedSubject, $body, $headers)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Не удалось отправить заявку. Попробуйте позже или позвоните в клинику.';
    exit;
}
header('Location: /contacts.html?sent=1', true, 303);
exit;
