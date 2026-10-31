<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth();
$method=$_SERVER['REQUEST_METHOD'];
$id=(int)($_GET['id']??0);

if($method==='GET'){
  if($id){
    $s=$pdo->prepare("SELECT * FROM projects WHERE id=? AND user_id=?");
    $s->execute([$id,$u['id']]); $p=$s->fetch();
    if(!$p) json_out(['error'=>'Project not found'],404);
    $t=$pdo->prepare("SELECT id,title,model_slug,updated_at,is_pinned,is_archived FROM chat_threads WHERE user_id=? AND project_id=? ORDER BY is_pinned DESC,updated_at DESC");
    $t->execute([$u['id'],$id]);
    $f=$pdo->prepare('SELECT id,name,mime_type,size_bytes,created_at,is_knowledge FROM documents WHERE user_id=? AND project_id=? ORDER BY id DESC');$f->execute([$u['id'],$id]);
    $ps=$pdo->prepare('SELECT id,name,model_slug,system_prompt,temperature,max_tokens FROM presets WHERE user_id=? AND (project_id=? OR project_id IS NULL) ORDER BY project_id DESC,name');$ps->execute([$u['id'],$id]);
    $mem=$pdo->prepare('SELECT content FROM project_memory WHERE project_id=? AND user_id=? LIMIT 1');$mem->execute([$id,$u['id']]);$memory=(string)($mem->fetchColumn()??'');
    $p['memory']=$memory;
    json_out(['project'=>$p,'threads'=>$t->fetchAll(),'files'=>$f->fetchAll(),'presets'=>$ps->fetchAll()]);
  }
  $s=$pdo->prepare("SELECT p.*,COUNT(t.id) thread_count FROM projects p LEFT JOIN chat_threads t ON t.project_id=p.id AND t.user_id=p.user_id WHERE p.user_id=? GROUP BY p.id ORDER BY p.updated_at DESC,p.id DESC");
  $s->execute([$u['id']]); json_out(['projects'=>$s->fetchAll()]);
}

require_csrf();
$in=json_decode(file_get_contents('php://input'),true)?:$_POST;

if($method==='POST'){
  $name=trim((string)($in['name']??''));
  $desc=trim((string)($in['description']??''));
  $instructions=trim((string)($in['instructions']??''));
  $memory=trim((string)($in['memory']??''));
  $defaultModel=trim((string)($in['default_model_slug']??''));
  $defaultPreset=(int)($in['default_preset_id']??0);
  $color=preg_match('/^[a-z0-9_-]{1,20}$/i',(string)($in['color']??'purple'))?(string)$in['color']:'purple';
  if($name===''||mb_strlen($name)>180) json_out(['error'=>'Invalid project name'],422);
  if($id){
    if($defaultPreset){
      $q=$pdo->prepare('SELECT id FROM presets WHERE id=? AND user_id=? AND (project_id=? OR project_id IS NULL)');
      $q->execute([$defaultPreset,$u['id'],$id]); if(!$q->fetchColumn()) $defaultPreset=0;
    }
    $s=$pdo->prepare("UPDATE projects SET name=?,description=?,instructions=?,memory=?,color=?,default_model_slug=?,default_preset_id=? WHERE id=? AND user_id=?");
    $s->execute([$name,$desc,$instructions,$memory,$color,$defaultModel?:null,$defaultPreset?:null,$id,$u['id']]);
    if($memory!==''){
      $q=$pdo->prepare('INSERT INTO project_memory(project_id,user_id,content) VALUES(?,?,?) ON DUPLICATE KEY UPDATE content=VALUES(content),updated_at=CURRENT_TIMESTAMP');
      $q->execute([$id,$u['id'],$memory]);
    }else{$pdo->prepare('DELETE FROM project_memory WHERE project_id=? AND user_id=?')->execute([$id,$u['id']]);}
    json_out(['ok'=>true,'id'=>$id]);
  }
  $s=$pdo->prepare("INSERT INTO projects(user_id,name,description,color) VALUES(?,?,?,?)");
  $s->execute([$u['id'],$name,$desc,$color]); json_out(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
}
if($method==='DELETE'){
  if(!$id) json_out(['error'=>'Project id required'],422);
  $s=$pdo->prepare("DELETE FROM projects WHERE id=? AND user_id=?");$s->execute([$id,$u['id']]);
  json_out(['ok'=>true]);
}
json_out(['error'=>'Method not allowed'],405);
