<?php
declare(strict_types=1);
// ONE-TIME BOOTSTRAP. Delete this file immediately after use.
// This file is intentionally excluded from .cpanel.yml deployments.
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; form-action \'self\'; base-uri \'none\'; frame-ancestors \'none\'');
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { http_response_code(403); exit('HTTPS required'); }
$configPath='/home/demraedubd/dic-private/config.php';
if (!is_readable($configPath)) { http_response_code(503); exit('Private configuration missing'); }
$cfg=require $configPath;
if (!is_array($cfg) || !isset($cfg['setup_token']) || !is_string($cfg['setup_token']) || strlen($cfg['setup_token'])<32) {http_response_code(503);exit('Set a random setup_token of at least 32 characters in private config.php first');}
try {
 $db=new PDO('mysql:host='.$cfg['db_host'].';dbname='.$cfg['db_name'].';charset=utf8mb4',$cfg['db_user'],$cfg['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
 $count=(int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
 if ($count>0) {http_response_code(403);exit('Admin already exists. Setup disabled. Delete this script.');}
} catch (Throwable $e) {error_log('DIC setup database error: '.$e->getMessage());http_response_code(503);exit('Database unavailable');}
session_name('dic_setup');session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict','path'=>'/']);session_start();
$_SESSION['csrf']??=bin2hex(random_bytes(32));
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 $csrf=(string)($_POST['csrf']??'');
 if (!hash_equals($_SESSION['csrf'],$csrf)) {http_response_code(403);exit('Invalid form token');}
 $origin=$_SERVER['HTTP_ORIGIN']??'';
 if ($origin && (parse_url($origin,PHP_URL_HOST)!==($_SERVER['HTTP_HOST']??'') || parse_url($origin,PHP_URL_SCHEME)!=='https')) {http_response_code(403);exit('Invalid origin');}
 $token=(string)($_POST['setup_token']??'');
 $email=filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL);
 $password=(string)($_POST['password']??'');
 if (!hash_equals($cfg['setup_token'],$token)) {$error='Invalid setup token';sleep(2);}
 elseif (!$email || strlen($email)>190) $error='Valid email required';
 elseif (strlen($password)<14 || strlen($password)>256) $error='Use a password between 14 and 256 characters';
 else {
  try {
   // Guard against concurrent setup requests: unique account bootstrap is serialized.
   $db->beginTransaction();
   $db->query('SELECT id FROM admins FOR UPDATE')->fetchAll();
   $check=(int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
   if ($check>0) throw new RuntimeException('Setup already completed');
   $q=$db->prepare("INSERT INTO admins(email,password_hash,role,active) VALUES(?,?,'super_admin',1)");
   $q->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);
   $db->commit();
   unset($_SESSION['csrf']);session_destroy();
   header('Content-Type: text/plain; charset=utf-8');
   echo "Super Admin created. DELETE public_html/setup-admin.php NOW, remove setup_token from private config.php, then open /admin/panel.php.";
   exit;
  } catch (Throwable $e) {
   if ($db->inTransaction()) $db->rollBack();
   error_log('DIC setup failed: '.$e->getMessage());
   $error='Setup failed. Check server logs or whether an admin already exists.';
  }
 }
}
header('Content-Type: text/html; charset=utf-8');
function h(string $s): string {return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DIC One-Time Admin Setup</title><style>body{font:16px system-ui;background:#f2f6f2;margin:0;padding:30px;color:#173e25}.card{max-width:490px;margin:5vh auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 5px 25px #0001}label{display:block;margin:14px 0}input{display:block;width:100%;box-sizing:border-box;padding:11px;margin-top:6px}button{background:#287741;color:#fff;border:0;padding:12px 20px;border-radius:7px;cursor:pointer}.error{color:#a22}</style></head><body><main class="card"><h1>One-time Super Admin Setup</h1><p>Only run this on HTTPS. Use the private setup token, not your database password.</p><?php if($error):?><p class="error" role="alert"><?=h($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label>Setup token<input name="setup_token" type="password" required autocomplete="off"></label><label>Admin email<input name="email" type="email" required autocomplete="email"></label><label>Admin password (14+ characters)<input name="password" type="password" minlength="14" required autocomplete="new-password"></label><button type="submit">Create Super Admin</button></form><p><strong>After success:</strong> delete setup-admin.php and remove setup_token from private config.php.</p></main></body></html>
