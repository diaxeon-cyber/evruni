<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth();
if($_SERVER['REQUEST_METHOD']==='GET'){
  $project=(int)($_GET['project_id']??0);
  if($project){$s=$pdo->prepare('SELECT * FROM presets WHERE user_id=? AND (project_id=? OR project_id IS NULL) ORDER BY project_id DESC,id DESC');$s->execute([$u['id'],$project]);}
  else{$s=$pdo->prepare('SELECT * FROM presets WHERE user_id=? ORDER BY id DESC');$s->execute([$u['id']]);}
  json_out(['presets'=>$s->fetchAll()]);
}
require_csrf(); $in=json_decode(file_get_contents('php://input'),true)?:$_POST;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim((string)($in['name']??''));$model=trim((string)($in['model_slug']??''));$project=(int)($in['project_id']??0);
  if(!$name||!$model)json_out(['error'=>'Name and model required'],422);
  if($project){$q=$pdo->prepare('SELECT id FROM projects WHERE id=? AND user_id=?');$q->execute([$project,$u['id']]);if(!$q->fetchColumn())json_out(['error'=>'Project not found'],404);}
  $pdo->prepare('INSERT INTO presets(user_id,project_id,name,model_slug,system_prompt,temperature,max_tokens) VALUES(?,?,?,?,?,?,?)')->execute([$u['id'],$project?:null,$name,$model,$in['system_prompt']??null,(float)($in['temperature']??.7),!empty($in['max_tokens'])?(int)$in['max_tokens']:null]);
  json_out(['ok'=>true]);
}
if($_SERVER['REQUEST_METHOD']==='DELETE'){ $id=(int)($_GET['id']??0);$pdo->prepare('DELETE FROM presets WHERE id=? AND user_id=?')->execute([$id,$u['id']]);json_out(['ok'=>true]);}json_out(['error'=>'Method not allowed'],405);
