<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
ini_set('display_errors','0');
$path=dirname(__DIR__).'/dentalla-smartcaptcha.php';
ob_start();
function out($data,$code=200){if(ob_get_length()!==false)ob_clean();http_response_code($code);$json=json_encode($data,JSON_UNESCAPED_UNICODE);echo $json===false?' {"status":"json_encoding_error"}':$json;exit;}
register_shutdown_function(function(){ $e=error_get_last(); if($e && in_array($e['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR),true)){out(array('status'=>'php_fatal','type'=>$e['type'],'source'=>basename($e['file'])==='dentalla-smartcaptcha.php'?'config':'diagnostic','line'=>$e['line']),500);} });
if (!is_readable($path)) out(['status'=>'config_unreadable'],503);
try {$cfg=require $path;} catch(Throwable $e){out(['status'=>'config_invalid'],503);}
if(!is_array($cfg)) out(['status'=>'config_not_array'],503);
if(!is_array($cfg)||empty($cfg['client_key'])||empty($cfg['server_key'])) out(['status'=>'keys_missing'],503);
if($_SERVER['REQUEST_METHOD']==='GET') out(['status'=>'ready','sitekey'=>$cfg['client_key'],'curl'=>function_exists('curl_init')]);
if($_SERVER['REQUEST_METHOD']!=='POST') out(['status'=>'method'],405);
$token=$_POST['smart-token']??null;
if(!is_string($token)||$token===''||strlen($token)>8192) out(['status'=>'missing_token'],403);
if(!function_exists('curl_init')) out(['status'=>'curl_unavailable'],503);
$ch=curl_init('https://smartcaptcha.cloud.yandex.ru/validate');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['secret'=>$cfg['server_key'],'token'=>$token,'ip'=>$_SERVER['REMOTE_ADDR']??'']),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);
$body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
if($body===false||$code!==200) out(['status'=>'upstream_error'],503);
$d=json_decode($body,true);
if(!is_array($d)||($d['status']??'')!=='ok') out(['status'=>'rejected'],403);
if(!in_array(strtolower($d['host']??''),['kvrachu.info','www.kvrachu.info'],true)) out(['status'=>'host_mismatch'],403);
out(['status'=>'verified']);
