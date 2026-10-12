<?php
// Обработчик формы заявки на справку для налогового вычета.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$to = 'dentalla@inbox.ru';

function tax_field($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

$name    = tax_field('Имя');
$phone   = tax_field('Телефон');
$year    = tax_field('Год');
$email   = tax_field('Email');
$comment = tax_field('Комментарий');

if ($name === '' && $phone === '') {
    header('Location: index.html');
    exit;
}

$subject = 'Заявка на справку для налогового вычета';

$body  = "Новая заявка с сайта dentalla.ru\r\n";
$body .= "Тема: Справка для налогового вычета\r\n\r\n";
$body .= "Имя: " . ($name !== '' ? $name : '(не указано)') . "\r\n";
$body .= "Телефон: " . ($phone !== '' ? $phone : '(не указан)') . "\r\n";
if ($year !== '') {
    $body .= "Год(ы) лечения: " . $year . "\r\n";
}
if ($email !== '') {
    $body .= "Email пациента: " . $email . "\r\n";
}
if ($comment !== '') {
    $body .= "Комментарий: " . $comment . "\r\n";
}
$body .= "Дата заявки: " . date('d.m.Y H:i:s') . "\r\n";

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
    $next = 'index.html?sent=1';
}
header('Location: ' . $next);
exit;
