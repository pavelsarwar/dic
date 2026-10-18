<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$method=$_SERVER['REQUEST_METHOD'];
$action=(string)($_GET['action']??'');
$types=['teacher','notice','slide','page','news','event','gallery','admission','academic'];
if ($action==='session' && $method==='GET') {
 if (empty($_SESSION['admin_id'])) respond(['authenticated'=>false]);
 $user=admin(); $_SESSION['csrf']??=bin2hex(random_bytes(32));
 respond(['authenticated'=>true,'user'=>$user,'csrf'=>$_SESSION['csrf']]);
}
if ($action==='login' && $method==='POST') {
 $body=input();$email=filter_var($body['email']??'',FILTER_VALIDATE_EMAIL);$password=(string)($body['password']??'');
 if (!$email || $password==='') respond(['error'=>'Invalid credentials'],401);
 // Rate limit by PHP session; production should also add server-side IP-based throttling.
 $attempts=$_SESSION['login_attempts']??0;
 if ($attempts>=5 && time()-($_SESSION['login_since']??0)<900) respond(['error'=>'Too many attempts. Try later.'],429);
 if (time()-($_SESSION['login_since']??0)>=900) {$_SESSION['login_attempts']=0;$_SESSION['login_since']=time();}
 $q=$db->prepare('SELECT id,email,role,password_hash FROM admins WHERE email=? AND active=1 LIMIT 1');
 $q->execute([$email]);$user=$q->fetch();
 if (!$user || !password_verify($password,$user['password_hash'])) {
  $_SESSION['login_attempts']=($_SESSION['login_attempts']??0)+1;
  respond(['error'=>'Invalid credentials'],401);
 }
 session_regenerate_id(true);
 $_SESSION['admin_id']=$user['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['login_attempts']=0;
 unset($user['password_hash']);
 respond(['authenticated'=>true,'user'=>$user,'csrf'=>$_SESSION['csrf']]);
}
if ($action==='logout' && $method==='POST') {csrf();session_unset();session_destroy();respond(['ok'=>true]);}
if ($action==='public' && $method==='GET') {
 $type=(string)($_GET['type']??'');
 if (!in_array($type,$types,true)) respond(['error'=>'Invalid type'],400);
 $q=$db->prepare('SELECT id,type,title,slug,body,image_url,subject,designation,department,attachment_url,sort_order,created_at FROM content WHERE type=? AND published=1 ORDER BY sort_order ASC,created_at DESC LIMIT 200');
 $q->execute([$type]);respond(['items'=>$q->fetchAll()]);
}
if ($action==='settings' && $method==='GET') {
 $q=$db->query('SELECT setting_key,value_json FROM site_settings');
 $settings=[];foreach($q as $r){$settings[$r['setting_key']]=json_decode($r['value_json'],true);}
 respond(['settings'=>$settings]);
}
$user=admin();
if ($action==='list' && $method==='GET') {
 $type=(string)($_GET['type']??'');
 if (!in_array($type,$types,true)) respond(['error'=>'Invalid type'],400);
 $q=$db->prepare('SELECT * FROM content WHERE type=? ORDER BY sort_order ASC,created_at DESC LIMIT 300');
 $q->execute([$type]);respond(['items'=>$q->fetchAll()]);
}
if ($action==='save' && $method==='POST') {
 csrf();$v=input();$type=(string)($v['type']??'');
 if (!in_array($type,$types,true)) respond(['error'=>'Invalid content type'],400);
 $title=trim((string)($v['title']??''));
 if ($title==='' || mb_strlen($title)>255) respond(['error'=>'Title required (max 255 characters)'],400);
 $fields=['slug'=>190,'body'=>50000,'image_url'=>1000,'subject'=>190,'designation'=>190,'department'=>190,'attachment_url'=>1000];
 $clean=[];foreach($fields as $key=>$max){$val=trim((string)($v[$key]??''));if(mb_strlen($val)>$max)respond(['error'=>'Field too long: '.$key],400);$clean[$key]=$val?:null;}
 foreach(['image_url','attachment_url'] as $key){if($clean[$key] && (!filter_var($clean[$key],FILTER_VALIDATE_URL)||parse_url($clean[$key],PHP_URL_SCHEME)!=='https'))respond(['error'=>'HTTPS URL required for '.$key],400);}
 $order=max(-100000,min(100000,(int)($v['sort_order']??0)));$published=empty($v['published'])?0:1;
 $id=filter_var($v['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 $params=[$type,$title,$clean['slug'],$clean['body'],$clean['image_url'],$clean['subject'],$clean['designation'],$clean['department'],$clean['attachment_url'],$order,$published];
 try{
 if($id){
  $q=$db->prepare('UPDATE content SET type=?,title=?,slug=?,body=?,image_url=?,subject=?,designation=?,department=?,attachment_url=?,sort_order=?,published=? WHERE id=?');
  $q->execute([...$params,$id]);
 }else{
  $q=$db->prepare('INSERT INTO content(type,title,slug,body,image_url,subject,designation,department,attachment_url,sort_order,published) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
  $q->execute($params);$id=(int)$db->lastInsertId();
 }
 }catch(PDOException $e){error_log($e->getMessage());respond(['error'=>'Unable to save. Check unique slug and values.'],409);}
 respond(['ok'=>true,'id'=>$id]);
}
if ($action==='save_settings' && $method==='POST') {
 csrf();if (($user['role']??'')!=='super_admin') respond(['error'=>'Super Admin required'],403);
 $v=input();$key=(string)($v['key']??'');
 if (!in_array($key,['identity','contact','navigation'],true)) respond(['error'=>'Invalid setting'],400);
 $value=$v['value']??null;if (!is_array($value)) respond(['error'=>'Invalid setting value'],400);
 if ($key==='identity') {
  $name=trim((string)($value['name']??''));$logo=trim((string)($value['logo_url']??''));
  if ($name===''||mb_strlen($name)>150) respond(['error'=>'Invalid college name'],400);
  if ($logo!=='' && (!filter_var($logo,FILTER_VALIDATE_URL)||parse_url($logo,PHP_URL_SCHEME)!=='https')) respond(['error'=>'HTTPS logo URL required'],400);
  $value=['name'=>$name,'logo_url'=>$logo];
 } elseif ($key==='contact') {
  $value=['address'=>mb_substr(trim((string)($value['address']??'')),0,300),'email'=>mb_substr(trim((string)($value['email']??'')),0,190),'phone'=>mb_substr(trim((string)($value['phone']??'')),0,80)];
 } else {
  if (count($value)>30) respond(['error'=>'Too many links'],400);
  $links=[];foreach($value as $link){
   if (!is_array($link)) continue;
   $label=trim((string)($link['label']??''));$url=trim((string)($link['url']??''));
   if ($label===''||mb_strlen($label)>70||strlen($url)>200||!preg_match('~^(?:[a-z0-9-]+\\.html(?:[?#][a-z0-9_-]*)?|index\\.html(?:#[a-z0-9_-]+)?)$~i',$url)) respond(['error'=>'Invalid navigation link'],400);
   $links[]=['label'=>$label,'url'=>$url];
  }$value=$links;
 }
 $q=$db->prepare('INSERT INTO site_settings(setting_key,value_json) VALUES(?,?) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)');
 $q->execute([$key,json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
 respond(['ok'=>true]);
}
if ($action==='upload_logo' && $method==='POST') {
 csrf();if (($user['role']??'')!=='super_admin') respond(['error'=>'Super Admin required'],403);
 if (!isset($_FILES['logo'])||$_FILES['logo']['error']!==UPLOAD_ERR_OK) respond(['error'=>'Upload failed'],400);
 $file=$_FILES['logo'];if ($file['size']>2097152||$file['size']<1) respond(['error'=>'Maximum logo size 2 MB'],400);
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
 $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
 if (!$ext||!getimagesize($file['tmp_name'])) respond(['error'=>'Use PNG, JPG or WebP'],400);
 $dir=dirname(__DIR__).'/uploads/logos';if (!is_dir($dir)&&!mkdir($dir,0755,true)) respond(['error'=>'Upload directory unavailable'],500);
 $name='dic-logo-'.bin2hex(random_bytes(12)).'.'.$ext;
 if (!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) respond(['error'=>'Could not save logo'],500);
 $url='https://demraidealcollege.edu.bd/uploads/logos/'.$name;
 respond(['ok'=>true,'url'=>$url]);
}
if ($action==='delete' && $method==='POST') {
 csrf();$v=input();$id=filter_var($v['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 if (!$id) respond(['error'=>'Invalid ID'],400);
 $q=$db->prepare('DELETE FROM content WHERE id=?');$q->execute([$id]);respond(['ok'=>true]);
}
respond(['error'=>'Not found'],404);
