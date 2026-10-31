<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth(); $id=(int)($_GET['id']??0); $q=trim((string)($_GET['q']??'')); $project=(int)($_GET['project_id']??0);

if($_SERVER['REQUEST_METHOD']==='GET'){
  if($id){
    $s=$pdo->prepare('SELECT * FROM chat_threads WHERE id=? AND user_id=?');$s->execute([$id,$u['id']]);$t=$s->fetch();
    if(!$t)json_out(['error'=>'Not found'],404);
    $m=$pdo->prepare('SELECT role,content,model_slug,created_at FROM chat_messages WHERE thread_id=? ORDER BY id');$m->execute([$id]);
    json_out(['thread'=>$t,'messages'=>$m->fetchAll()]);
  }
  $where=['t.user_id=?','t.is_archived=0'];$args=[$u['id']];
  if($project){$where[]='t.project_id=?';$args[]=$project;}
  if($q!==''){$where[]='t.title LIKE ?';$args[]='%'.$q.'%';}
  $sql='SELECT t.id,t.title,t.model_slug,t.project_id,t.is_pinned,t.updated_at,p.name AS project_name FROM chat_threads t LEFT JOIN projects p ON p.id=t.project_id AND p.user_id=t.user_id WHERE '.implode(' AND ',$where).' ORDER BY t.is_pinned DESC,t.updated_at DESC LIMIT 100';
  $s=$pdo->prepare($sql);$s->execute($args);json_out(['threads'=>$s->fetchAll()]);
}
require_csrf();
$in=json_decode(file_get_contents('php://input'),true)?:$_POST;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=(string)($in['action']??'');
  if($action==='rename'){
    $title=trim((string)($in['title']??''));if(!$id||$title===''||mb_strlen($title)>180)json_out(['error'=>'Invalid title'],422);
    $s=$pdo->prepare('UPDATE chat_threads SET title=? WHERE id=? AND user_id=?');$s->execute([$title,$id,$u['id']]);json_out(['ok'=>true]);
  }
  if($action==='pin'||$action==='archive'){
    $field=$action==='pin'?'is_pinned':'is_archived';$value=(int)!!($in['value']??true);
    $s=$pdo->prepare("UPDATE chat_threads SET $field=? WHERE id=? AND user_id=?");$s->execute([$value,$id,$u['id']]);json_out(['ok'=>true]);
  }
  if($action==='project'){
    $pid=(int)($in['project_id']??0);
    if($pid){$p=$pdo->prepare('SELECT id FROM projects WHERE id=? AND user_id=?');$p->execute([$pid,$u['id']]);if(!$p->fetchColumn())json_out(['error'=>'Project not found'],404);}
    $s=$pdo->prepare('UPDATE chat_threads SET project_id=? WHERE id=? AND user_id=?');$s->execute([$pid?:null,$id,$u['id']]);json_out(['ok'=>true]);
  }
}
if($_SERVER['REQUEST_METHOD']==='DELETE'){
  $pdo->prepare('DELETE FROM chat_threads WHERE id=? AND user_id=?')->execute([$id,$u['id']]);json_out(['ok'=>true]);
}
json_out(['error'=>'Method not allowed'],405);
