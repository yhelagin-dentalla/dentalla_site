<?php
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
echo "PHP_OK\n";
echo 'PHP: '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION."\n";
echo 'JSON: '.(function_exists('json_encode')?'yes':'no')."\n";
echo 'cURL: '.(function_exists('curl_init')?'yes':'no')."\n";
$p=dirname(__DIR__).'/dentalla-smartcaptcha.php';
echo 'Config exists: '.(@is_file($p)?'yes':'no')."\n";
echo 'Config readable: '.(@is_readable($p)?'yes':'no')."\n";
echo 'open_basedir: '.(ini_get('open_basedir')?'restricted':'not set')."\n";
