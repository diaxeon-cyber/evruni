<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth(); require_csrf();
if(empty($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) json_out(['error'=>'Upload failed'],400);
$f=$_FILES['file']; $max=(int)$GLOBALS['config']['upload_max_mb']*1024*1024;
if($f['size']>$max) json_out(['error'=>'File too large'],413);
$fi=new finfo(FILEINFO_MIME_TYPE); $mime=$fi->file($f['tmp_name']);
$allowed=['image/png','image/jpeg','image/webp','application/pdf','text/plain','text/csv','application/json','application/zip'];
if(!in_array($mime,$allowed,true)) json_out(['error'=>'File type not allowed'],415);
$ext=pathinfo($f['name'],PATHINFO_EXTENSION); $stored=bin2hex(random_bytes(18)).($ext?'.'.preg_replace('/[^a-zA-Z0-9]/','',$ext):'');
$dir=ROOT_PATH.'/storage/uploads/'.$u['id']; if(!is_dir($dir)) mkdir($dir,0750,true);
move_uploaded_file($f['tmp_name'],$dir.'/'.$stored);
$s=$pdo->prepare("INSERT INTO uploads(user_id,original_name,stored_name,mime_type,size_bytes) VALUES(?,?,?,?,?)");
$s->execute([$u['id'],substr($f['name'],0,255),$stored,$mime,$f['size']]);
json_out(['ok'=>true,'id'=>$pdo->lastInsertId(),'name'=>$f['name']]);
