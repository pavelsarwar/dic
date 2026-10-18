<?php
declare(strict_types=1);
/**
 * DIC one-click deploy. Install this file once at public_html/admin/deploy.php.
 * Source: /home/demraedubd/repositories/dic (updated through cPanel Git).
 * Never deploy the entire repository or copy secrets into public_html.
 */
require dirname(__DIR__).'/api/bootstrap.php';
$user=admin();
if (($user['role']??'')!=='super_admin') {
    http_response_code(403);
    exit('Super Admin access required.');
}
$source='/home/demraedubd/repositories/dic';
$destination='/home/demraedubd/public_html';
$pages=['index.html','faculty.html','about.html','principal.html','administration.html',
'admission.html','academic.html','routine.html','exam.html','results.html',
'notices.html','news.html','events.html','gallery.html','contact.html','policies.html'];
$files=array_merge($pages,[
'assets/shared.css','assets/cms.js','assets/cms-config.js','assets/pages.css','assets/pages.js',
'admin/panel.php','admin/index.php','admin/.htaccess',
'api/index.php','api/bootstrap.php','api/.htaccess'
]);
$report=[];
$error='';
$success=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf();
    try {
        if (!is_dir($source) || !is_dir($destination)) {
            throw new RuntimeException('Source repository or destination directory is missing.');
        }
        if (realpath($source)===realpath($destination)) {
            throw new RuntimeException('Source and destination cannot be the same.');
        }
        // Preflight every allowlisted file before changing anything.
        foreach ($files as $relative) {
            $from=$source.'/'.$relative;
            $to=$destination.'/'.$relative;
            if (!is_file($from) || !is_readable($from) || is_link($from)) {
                throw new RuntimeException('Missing or unsafe source file: '.$relative);
            }
            if (is_link($to)) {
                throw new RuntimeException('Destination symlink not allowed: '.$relative);
            }
        }
        foreach ($files as $relative) {
            $from=$source.'/'.$relative;
            $to=$destination.'/'.$relative;
            $dir=dirname($to);
            if (is_link($dir)) throw new RuntimeException('Destination directory symlink not allowed: '.$relative);
            if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) {
                throw new RuntimeException('Cannot create directory: '.dirname($relative));
            }
            $tmp=tempnam($dir,'.dic-');
            if ($tmp===false) throw new RuntimeException('Cannot create temporary file.');
            try {
                if (!copy($from,$tmp)) throw new RuntimeException('Copy failed: '.$relative);
                chmod($tmp,0644);
                if (!rename($tmp,$to)) throw new RuntimeException('Install failed: '.$relative);
            } finally {
                if (is_file($tmp)) unlink($tmp);
            }
            $report[]=$relative;
        }
        $success=true;
        error_log('DIC deploy succeeded by admin ID '.$user['id'].'; files: '.count($report));
    } catch (Throwable $e) {
        $error=$e->getMessage();
        error_log('DIC deploy failed: '.$error);
    }
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
function h(string $s): string {return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$csrfToken=(string)($_SESSION['csrf']??'');
?>
<!doctype html>
<html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Deploy Website | DIC Admin</title>
<style>body{font:16px/1.6 system-ui,sans-serif;background:#f4f8f3;color:#20472f;margin:0}
main{max-width:740px;margin:48px auto;padding:28px;background:white;border:1px solid #d8e5d7;border-radius:14px}
button{background:#27733d;color:white;border:0;border-radius:8px;padding:13px 22px;font:inherit;cursor:pointer}
a{color:#226d38}.notice{padding:13px;border-radius:8px;background:#f1f5ec;margin:18px 0}
.ok{background:#e5f4e7}.err{background:#fff0e8;color:#842b20}code{word-break:break-all}
</style></head><body><main>
<h1>ডেমরা আইডিয়াল কলেজ — Deploy</h1>
<p><a href="panel.php">← Admin Panel</a></p>
<p>প্রথমে cPanel Git Version Control থেকে <strong>Update from Remote</strong> করুন। তারপর নিচের বাটনে ক্লিক করলে অনুমোদিত Website ফাইলগুলো Repository থেকে <code>public_html</code>-এ Copy হবে।</p>
<div class="notice">এই প্রক্রিয়ায় Database, Admin Account, Uploaded Logo, <code>dic-private/config.php</code> বা অন্যান্য Private File পরিবর্তন হবে না। মোট <?=count($files)?>টি ফাইল Deploy হবে।</div>
<?php if($success): ?>
<div class="notice ok"><strong>Deploy successful!</strong> <?=count($report)?>টি ফাইল আপডেট হয়েছে। <a href="../index.html">Website দেখুন</a></div>
<?php elseif($error!==''): ?>
<div class="notice err"><strong>Deploy failed:</strong> <?=h($error)?>। <?=count($report)?>টি ফাইল ইতিমধ্যে Copy হয়েছে; সমস্যাটি সমাধান করে আবার Deploy করুন।</div>
<?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>">
<button type="submit" onclick="return confirm('Repository থেকে Live Website আপডেট করবেন?')">Deploy Website Now</button></form>
<p><small>নিরাপত্তার জন্য শুধুমাত্র Logged-in Super Admin এই কাজ করতে পারবেন। এটি GitHub থেকে Pull করে না; cPanel-এ Update from Remote আগে করতে হবে।</small></p>
</main></body></html>
