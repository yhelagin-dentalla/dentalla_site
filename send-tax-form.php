<?php
// Обработчик формы заявки на справку для налогового вычета.
// Защита от спама: honeypot-поле (website) + временная метка (_ts).

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// --- Антиспам ---
if (!empty($_POST['website'])) {
    header('Location: index.html');
    exit;
}
$ts = isset($_POST['_ts']) ? intval($_POST['_ts']) : 0;
if ($ts > 0 && (time() - $ts) < 3) {
    header('Location: index.html');
    exit;
}
// --- /Антиспам ---

$to = 'dentalla@inbox.ru';

function tax_field($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

$fio            = tax_field('ФИО');
$inn            = tax_field('ИНН');
$phone          = tax_field('Телефон');
$year           = tax_field('Год');
$email          = tax_field('Email');
$comment        = tax_field('Комментарий');
$patientType    = tax_field('ТипПациента', 'взрослый');

// Паспорт (взрослый)
$passportSeries  = tax_field('ПаспортСерия');
$passportNumber  = tax_field('ПаспортНомер');
$passportIssued  = tax_field('ПаспортВыдан');
$passportDate    = tax_field('ПаспортДатаВыдачи');

// Свидетельство о рождении (ребёнок)
$birthSeries     = tax_field('СвидетельствоСерия');
$birthNumber     = tax_field('СвидетельствоНомер');
$birthDate       = tax_field('СвидетельствоДатаВыдачи');

if ($fio === '' && $phone === '') {
    header('Location: index.html');
    exit;
}

$subject = 'Заявка на справку для налогового вычета';

$body  = "Новая заявка с сайта dentalla.ru\r\n";
$body .= "Тема: Справка для налогового вычета\r\n\r\n";

$body .= "--- Пациент ---\r\n";
$body .= "ФИО: " . ($fio !== '' ? $fio : '(не указано)') . "\r\n";
$body .= "Тип: " . $patientType . "\r\n";

if ($patientType === 'взрослый') {
    $body .= "ИНН: " . ($inn !== '' ? $inn : '(не указан)') . "\r\n";
    $body .= "\r\n--- Паспорт ---\r\n";
    $body .= "Серия: " . ($passportSeries !== '' ? $passportSeries : '(не указана)') . "\r\n";
    $body .= "Номер: " . ($passportNumber !== '' ? $passportNumber : '(не указан)') . "\r\n";
    $body .= "Кем выдан: " . ($passportIssued !== '' ? $passportIssued : '(не указано)') . "\r\n";
    $body .= "Дата выдачи: " . ($passportDate !== '' ? $passportDate : '(не указана)') . "\r\n";
} else {
    $body .= "\r\n--- Свидетельство о рождении ---\r\n";
    $body .= "Серия: " . ($birthSeries !== '' ? $birthSeries : '(не указана)') . "\r\n";
    $body .= "Номер: " . ($birthNumber !== '' ? $birthNumber : '(не указан)') . "\r\n";
    $body .= "Дата выдачи: " . ($birthDate !== '' ? $birthDate : '(не указана)') . "\r\n";
}

$body .= "\r\n--- Контакты ---\r\n";
$body .= "Телефон: " . ($phone !== '' ? $phone : '(не указан)') . "\r\n";
if ($email !== '') {
    $body .= "Email: " . $email . "\r\n";
}
if ($year !== '') {
    $body .= "Год(ы) лечения: " . $year . "\r\n";
}
if ($comment !== '') {
    $body .= "\r\nКомментарий: " . $comment . "\r\n";
}
$body .= "\r\nДата заявки: " . date('d.m.Y H:i:s') . "\r\n";

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
