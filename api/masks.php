<?php
require_once __DIR__.'/../_private/config/config.php';require_once ROOT_PATH.'/_private/lib/security.php';$u=require_auth();
if($_SERVER['REQUEST_METHOD']==='GET'){ $s=$pdo->prepare('SELECT id,name,pattern_text,replacement,enabled,created_at FROM masks WHERE user_id=? ORDER BY id DESC');$s->execute([$u['id']]);json_out(['masks'=>$s->fetchAll()]); }
require_csrf();$in=json_decode(file_get_contents('php://input'),true)?:$_POST;
if($_SERVER['REQUEST_METHOD']==='POST'){ $name=trim((string)($in['name']??''));$pattern=trim((string)($in['pattern_text']??''));$rep=(string)($in['replacement']??'[REDACTED]');if(!$name||!$pattern)json_out(['error'=>'Name and regex are required'],422);if(@preg_match($pattern,'')===false)json_out(['error'=>'Invalid regular expression'],422);$pdo->prepare('INSERT INTO masks(user_id,name,pattern_text,replacement) VALUES(?,?,?,?)')->execute([$u['id'],$name,$pattern,$rep]);json_out(['ok'=>true]);}
if($_SERVER['REQUEST_METHOD']==='DELETE'){ $id=(int)($_GET['id']??0);$pdo->prepare('DELETE FROM masks WHERE id=? AND user_id=?')->execute([$id,$u['id']]);json_out(['ok'=>true]);}json_out(['error'=>'Method not allowed'],405);
