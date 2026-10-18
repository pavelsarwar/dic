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
if ($action==='delete' && $method==='POST') {
 csrf();$v=input();$id=filter_var($v['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 if (!$id) respond(['error'=>'Invalid ID'],400);
 $q=$db->prepare('DELETE FROM content WHERE id=?');$q->execute([$id]);respond(['ok'=>true]);
}
respond(['error'=>'Not found'],404);
