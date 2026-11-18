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

$to = 'dentalla@inbox.ru';

function dentalla_field($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

$subject = dentalla_field('_subject', 'Заявка с сайта dentalla.ru');
$name    = dentalla_field('Имя');
$phone   = dentalla_field('Телефон');
$doctor  = dentalla_field('Врач');
$comment = dentalla_field('Комментарий');

if ($name === '' && $phone === '' && $comment === '') {
    header('Location: index.html');
    exit;
}

$body  = "Новая заявка с сайта dentalla.ru\r\n\r\n";
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
if ($phone !== '') {
    $headers .= "Reply-To: " . $phone . "\r\n";
}

@mail($to, $encodedSubject, $body, $headers);

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if ($referer !== '') {
    $refererPath = preg_replace('/[?#].*$/', '', $referer);
    $next = $refererPath . '?sent=1';
} else {
    $next = dentalla_field('_next', 'index.html?sent=1');
}
header('Location: ' . $next);
exit;
