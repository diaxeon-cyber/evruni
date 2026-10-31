<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth();
$q=trim((string)($_GET['q']??''));
if($q===''||mb_strlen($q)<2) json_out(['results'=>[]]);
$like='%'.$q.'%'; $results=[];
$s=$pdo->prepare('SELECT id,name,description FROM projects WHERE user_id=? AND (name LIKE ? OR description LIKE ?) ORDER BY updated_at DESC LIMIT 8');$s->execute([$u['id'],$like,$like]);
foreach($s->fetchAll() as $r)$results[]=['type'=>'project','id'=>(int)$r['id'],'title'=>$r['name'],'meta'=>$r['description']?:'Project'];
$s=$pdo->prepare('SELECT id,title,model_slug,project_id FROM chat_threads WHERE user_id=? AND is_archived=0 AND title LIKE ? ORDER BY updated_at DESC LIMIT 8');$s->execute([$u['id'],$like]);
foreach($s->fetchAll() as $r)$results[]=['type'=>'chat','id'=>(int)$r['id'],'title'=>$r['title'],'meta'=>$r['model_slug']];
$s=$pdo->prepare('SELECT DISTINCT d.id,d.name,d.mime_type,d.project_id FROM documents d WHERE d.user_id=? AND (d.name LIKE ? OR d.extracted_text LIKE ?) ORDER BY d.created_at DESC LIMIT 8');$s->execute([$u['id'],$like,$like]);
foreach($s->fetchAll() as $r)$results[]=['type'=>'file','id'=>(int)$r['id'],'title'=>$r['name'],'meta'=>$r['mime_type']];
$s=$pdo->prepare('SELECT DISTINCT t.id,t.title,t.model_slug,t.project_id FROM chat_threads t JOIN chat_messages m ON m.thread_id=t.id WHERE t.user_id=? AND m.role IN ("user","assistant") AND m.content LIKE ? ORDER BY t.updated_at DESC LIMIT 8');$s->execute([$u['id'],$like]);
foreach($s->fetchAll() as $r)$results[]=['type'=>'message','id'=>(int)$r['id'],'title'=>$r['title'],'meta'=>$r['model_slug']];
json_out(['results'=>array_slice($results,0,24)]);
