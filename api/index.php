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
 if (!in_array($key,['identity','contact','navigation','homepage','layout'],true)) respond(['error'=>'Invalid setting'],400);
 $value=$v['value']??null;if (!is_array($value)) respond(['error'=>'Invalid setting value'],400);
 if ($key==='navigation') {
  if(count($value)>40) respond(['error'=>'Too many menu items'],400);
  $links=[];foreach($value as $link){
   if(!is_array($link)) continue;
   $label=trim((string)($link['label']??''));
   $url=trim((string)($link['url']??''));
   $group=trim((string)($link['group']??''));
   if($label===''||mb_strlen($label)>70||mb_strlen($group)>70||!preg_match('~^/(?:[a-z0-9-]+)?$~i',$url))respond(['error'=>'Invalid menu item or URL'],400);
   $links[]=['label'=>$label,'url'=>$url,'group'=>$group,'active'=>!empty($link['active'])];
  }$value=$links;
 } elseif ($key==='identity') {
  $name=trim((string)($value['name']??''));$logo=trim((string)($value['logo_url']??''));
  if ($name===''||mb_strlen($name)>150) respond(['error'=>'Invalid college name'],400);
  if ($logo!=='' && (!filter_var($logo,FILTER_VALIDATE_URL)||parse_url($logo,PHP_URL_SCHEME)!=='https')) respond(['error'=>'HTTPS logo URL required'],400);
  $value=['name'=>$name,'logo_url'=>$logo];
 } elseif ($key==='contact') {
  $value=['address'=>mb_substr(trim((string)($value['address']??'')),0,300),'email'=>mb_substr(trim((string)($value['email']??'')),0,190),'phone'=>mb_substr(trim((string)($value['phone']??'')),0,80)];
  } elseif ($key==='layout') {
  $allowed=['header_notice','header_notice_active','footer_about','footer_copyright','footer_email','footer_phone','footer_links_title','footer_logo','top_motto','top_eiin','top_language','topbar_active','footer_links','top_college_code','social_facebook','social_instagram','social_youtube','facebook_embed_code','admission_button_label','admission_button_url','admission_button_active','urgent_title','urgent_active'];
  $clean=[];foreach($allowed as $field){
   if(!array_key_exists($field,$value))continue;
   $raw=$value[$field];
   if($field==='header_notice_active'||$field==='topbar_active'||$field==='admission_button_active'||$field==='urgent_active'){$clean[$field]=!empty($raw);continue;}
   if($field==='footer_links'){
    if(!is_array($raw)||count($raw)>20)respond(['error'=>'Invalid footer links'],400);
    $links=[];foreach($raw as $item){
     if(!is_array($item))respond(['error'=>'Invalid link'],400);
     $label=trim((string)($item['label']??''));$url=trim((string)($item['url']??''));
     if($label===''||mb_strlen($label)>80||!preg_match('~^/(?:[a-zA-Z0-9/_#-]*)$~',$url))respond(['error'=>'Use a label and a local /page URL'],400);
     $links[]=['label'=>$label,'url'=>$url,'active'=>!empty($item['active'])];
    }$clean[$field]=$links;continue;
   }
   if($field==='facebook_embed_code'){
    if(!is_string($raw)||mb_strlen($raw)>10000)respond(['error'=>'Facebook embed code must be text under 10,000 characters'],400);
    $clean[$field]=$raw;continue;
   }
   if(in_array($field,['social_facebook','social_instagram','social_youtube'],true)&&$raw!==''){
    if(!is_string($raw)||!filter_var($raw,FILTER_VALIDATE_URL)||strtolower((string)parse_url($raw,PHP_URL_SCHEME))!=='https')respond(['error'=>'Social media URL must use HTTPS'],400);
    $host=strtolower((string)parse_url($raw,PHP_URL_HOST));
    $allowedHosts=['social_facebook'=>['facebook.com','fb.com','fb.me'],'social_instagram'=>['instagram.com'],'social_youtube'=>['youtube.com','youtu.be']];
    $valid=false;foreach($allowedHosts[$field] as $domain){if($host===$domain||substr($host,-strlen('.'.$domain))==='.'. $domain){$valid=true;break;}}
    if(!$valid)respond(['error'=>'Please use the matching official social platform URL'],400);
   }
   if($field==='admission_button_url' && $raw!=='' && (!is_string($raw)||!filter_var($raw,FILTER_VALIDATE_URL)||parse_url($raw,PHP_URL_SCHEME)!=='https'))respond(['error'=>'Online Admission URL must be HTTPS'],400);
   if(!is_string($raw)||mb_strlen($raw)>500)respond(['error'=>'Invalid header/footer field'],400);
   $clean[$field]=trim($raw);
  }$value=$clean;
 } elseif ($key==='homepage') {
  if (count($value)>40) respond(['error'=>'Too many homepage fields'],400);
  $clean=[];foreach($value as $field=>$text){
   if (!is_string($field)||!preg_match('/^[a-z][a-z0-9_]{0,49}$/',$field)||!is_string($text)||mb_strlen($text)>1500) respond(['error'=>'Invalid homepage field'],400);
   $clean[$field]=trim($text);
  }$value=$clean;
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
if ($action==='upload_image' && $method==='POST') {
 csrf();
 try {
  if(!isset($_FILES['image'])) respond(['error'=>'No image received. Check post_max_size.'],400);
  $file=$_FILES['image'];$err=(int)($file['error']??UPLOAD_ERR_NO_FILE);
  if($err!==UPLOAD_ERR_OK) respond(['error'=>'Upload error '.$err.'; check PHP upload limits'],400);
  if(($file['size']??0)<1||$file['size']>5242880)respond(['error'=>'Image must be under 5 MB'],400);
  $tmp=$file['tmp_name']??'';
  if(!is_uploaded_file($tmp))respond(['error'=>'Invalid uploaded file'],400);
  $info=@getimagesize($tmp);
  $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$info['mime']??'']??null;
  if(!$ext)respond(['error'=>'Use JPG, PNG or WebP'],400);
  $dir=dirname(__DIR__).'/uploads/media';
  if(!is_dir($dir)&&!@mkdir($dir,0755,true)&&!is_dir($dir))respond(['error'=>'Media folder cannot be created'],500);
  if(!is_writable($dir))respond(['error'=>'Media folder is not writable'],500);
  $name=bin2hex(random_bytes(16)).'.'.$ext;
  if(!@move_uploaded_file($tmp,$dir.'/'.$name))respond(['error'=>'Image could not be saved'],500);
  respond(['ok'=>true,'url'=>'https://demraidealcollege.edu.bd/uploads/media/'.$name]);
 }catch(Throwable $e){error_log('DIC media upload: '.$e->getMessage());respond(['error'=>'Media upload server error'],500);}
}
if ($action==='upload_logo' && $method==='POST') {
 csrf();if (($user['role']??'')!=='super_admin') respond(['error'=>'Super Admin required'],403);
 try {
  if (!isset($_FILES['logo'])) {
   $max=ini_get('post_max_size');
   respond(['error'=>'No logo received. Check PHP upload_max_filesize and post_max_size (current post_max_size: '.$max.').'],400);
  }
  $file=$_FILES['logo'];
  if (!is_array($file)) respond(['error'=>'Invalid upload'],400);
  $err=(int)($file['error']??UPLOAD_ERR_NO_FILE);
  if ($err!==UPLOAD_ERR_OK) {
   $errors=[UPLOAD_ERR_INI_SIZE=>'File exceeds server upload limit',UPLOAD_ERR_FORM_SIZE=>'File exceeds form upload limit',UPLOAD_ERR_PARTIAL=>'File was only partially uploaded',UPLOAD_ERR_NO_FILE=>'Please select a logo',UPLOAD_ERR_NO_TMP_DIR=>'Server temporary folder missing',UPLOAD_ERR_CANT_WRITE=>'Server cannot write uploaded file',UPLOAD_ERR_EXTENSION=>'Server blocked the upload'];
   respond(['error'=>$errors[$err]??'Upload failed'],400);
  }
  if (($file['size']??0)>2097152||($file['size']??0)<1) respond(['error'=>'Logo must be under 2 MB'],400);
  $tmp=(string)($file['tmp_name']??'');
  if (!$tmp||!is_uploaded_file($tmp)) respond(['error'=>'Invalid uploaded file'],400);
  $info=@getimagesize($tmp);
  if (!$info) respond(['error'=>'Please upload a valid image'],400);
  $mime=(string)($info['mime']??'');
  $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
  if (!$ext) respond(['error'=>'Only PNG, JPG or WebP images are allowed'],400);
  $dir=dirname(__DIR__).'/uploads/logos';
  if (!is_dir($dir)&&!@mkdir($dir,0755,true)&&!is_dir($dir)) respond(['error'=>'Unable to create uploads/logos. Check folder permissions.'],500);
  if (!is_writable($dir)) respond(['error'=>'uploads/logos folder is not writable'],500);
  $name='dic-logo-'.bin2hex(random_bytes(12)).'.'.$ext;
  if (!@move_uploaded_file($tmp,$dir.'/'.$name)) respond(['error'=>'Could not save logo. Check server permissions.'],500);
  respond(['ok'=>true,'url'=>'https://demraidealcollege.edu.bd/uploads/logos/'.$name]);
 } catch (Throwable $e) {
  error_log('DIC logo upload error: '.$e->getMessage());
  respond(['error'=>'Logo upload failed on server. Check PHP error log.'],500);
 }
}
if ($action==='delete' && $method==='POST') {
 csrf();$v=input();$id=filter_var($v['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 if (!$id) respond(['error'=>'Invalid ID'],400);
 $q=$db->prepare('DELETE FROM content WHERE id=?');$q->execute([$id]);respond(['ok'=>true]);
}
respond(['error'=>'Not found'],404);
