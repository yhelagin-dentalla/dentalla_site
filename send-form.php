<?php
// Собственный обработчик формы записи на приём.
// Заменяет formsubmit.co (сторонний сервис, оказавшийся ненадёжным/недоступным)
// на прямую отправку письма с сервера — так же, как это устроено на dentalla.ru.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$to = 'dentalla@inbox.ru';

function dentalla_field($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

$subject = dentalla_field('_subject', 'Заявка с сайта kvrachu.info');
$name    = dentalla_field('Имя');
$phone   = dentalla_field('Телефон');
$doctor  = dentalla_field('Врач');
$comment = dentalla_field('Комментарий');

// Простая защита от пустой отправки и от ботов (honeypot-поле, если добавим позже)
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
$body .= "\r\nСтраница: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '—') . "\r\n";
$body .= "Дата: " . date('d.m.Y H:i:s') . "\r\n";

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: Сайт Денталла <no-reply@kvrachu.info>\r\n";
if ($phone !== '') {
    $headers .= "Reply-To: " . $phone . "\r\n";
}

@mail($to, $encodedSubject, $body, $headers);

// Возвращаем пользователя на ту же страницу, откуда была отправка формы
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if ($referer !== '') {
    $refererPath = preg_replace('/[?#].*$/', '', $referer);
    $next = $refererPath . '?sent=1';
} else {
    $next = dentalla_field('_next', 'index.html?sent=1');
}
header('Location: ' . $next);
exit;
