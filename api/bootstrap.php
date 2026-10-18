<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
 http_response_code(403); echo json_encode(['error'=>'HTTPS required']); exit;
}
$configFile = '/home/demraedubd/dic-private/config.php';
if (!is_readable($configFile)) {
 http_response_code(503); echo json_encode(['error'=>'Database not configured']); exit;
}
$config = require $configFile;
if (!is_array($config) || !isset($config['db_host'],$config['db_name'],$config['db_user'],$config['db_pass'])) {
 http_response_code(503); echo json_encode(['error'=>'Invalid configuration']); exit;
}
try {
 $db = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES=>false
 ]);
} catch (Throwable $e) {
 error_log('DIC database connection failed: '.$e->getMessage());
 http_response_code(503); echo json_encode(['error'=>'Service unavailable']); exit;
}
session_name('dic_admin');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
function respond(array $data,int $code=200): never {http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function input(): array {
 $raw=file_get_contents('php://input');
 if (strlen($raw)>1048576) respond(['error'=>'Payload too large'],413);
 $data=json_decode($raw,true);
 if (!is_array($data)) respond(['error'=>'Invalid JSON'],400);
 return $data;
}
function admin(): array {
 if (empty($_SESSION['admin_id'])) respond(['error'=>'Unauthorized'],401);
 global $db;
 $q=$db->prepare('SELECT id,email,role FROM admins WHERE id=? AND active=1');
 $q->execute([$_SESSION['admin_id']]);
 $user=$q->fetch();
 if (!$user) {session_unset();respond(['error'=>'Unauthorized'],401);}
 return $user;
}
function csrf(): void {
 $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
 if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$token)) respond(['error'=>'Invalid CSRF token'],403);
 $origin=$_SERVER['HTTP_ORIGIN']??'';
 if ($origin) {
  $parts=parse_url($origin);
  if (!$parts || !in_array(strtolower((string)($parts['host']??'')),['demraidealcollege.edu.bd','www.demraidealcollege.edu.bd'],true) || ($parts['scheme']??'')!=='https') respond(['error'=>'Invalid origin'],403);
 }
}
