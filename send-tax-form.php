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
$inn     = tax_field('ИНН');
$phone   = tax_field('Телефон');
$year    = tax_field('Год');
$email   = tax_field('Email');
$forWho  = tax_field('ВычетЗа', 'за себя');
$comment = tax_field('Комментарий');

if ($name === '' && $phone === '') {
    header('Location: index.html');
    exit;
}

// Пациенты (если вычет за другого)
$patientFios = isset($_POST['ПациентФИО']) && is_array($_POST['ПациентФИО'])
    ? $_POST['ПациентФИО'] : array();
$patientInns = isset($_POST['ПациентИНН']) && is_array($_POST['ПациентИНН'])
    ? $_POST['ПациентИНН'] : array();

$subject = 'Заявка на справку для налогового вычета';

$body  = "Новая заявка с сайта dentalla.ru\r\n";
$body .= "Тема: Справка для налогового вычета\r\n\r\n";
$body .= "--- Получатель вычета ---\r\n";
$body .= "Имя: " . ($name !== '' ? $name : '(не указано)') . "\r\n";
$body .= "ИНН: " . ($inn !== '' ? $inn : '(не указан)') . "\r\n";
$body .= "Телефон: " . ($phone !== '' ? $phone : '(не указан)') . "\r\n";
if ($email !== '') {
    $body .= "Email: " . $email . "\r\n";
}
$body .= "Вычет оформляется: " . $forWho . "\r\n";
if ($year !== '') {
    $body .= "Год(ы) лечения: " . $year . "\r\n";
}

// Блок пациентов (если вычет за другого)
if ($forWho === 'за другого' && count($patientFios) > 0) {
    $body .= "\r\n--- Пациенты, за которых получается вычет ---\r\n";
    foreach ($patientFios as $i => $fio) {
        $fio = trim($fio);
        $pInn = isset($patientInns[$i]) ? trim($patientInns[$i]) : '';
        if ($fio !== '' || $pInn !== '') {
            $num = $i + 1;
            $body .= "Пациент " . $num . ": " . ($fio !== '' ? $fio : '(ФИО не указано)');
            if ($pInn !== '') {
                $body .= ", ИНН: " . $pInn;
            }
            $body .= "\r\n";
        }
    }
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
