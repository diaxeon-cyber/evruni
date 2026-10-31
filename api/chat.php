<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
$u=require_auth(); require_csrf(); rate_limit('chat',30,60);
$in=json_decode(file_get_contents('php://input'),true)?:$_POST;
$thread=(int)($in['thread_id']??0); $project=(int)($in['project_id']??0); $documentIds=is_array($in['document_ids']??null)?array_map('intval',$in['document_ids']):[]; $model=trim((string)($in['model']??envv('AI_DEFAULT_MODEL','aitunnel-auto'))); $prompt=trim((string)($in['message']??'')); $system=trim((string)($in['system_prompt']??''));
if($prompt===''||strlen($prompt)>30000) json_out(['error'=>'Invalid message'],422);
try{
  $m=ai_model_record($model);
  $cost=(float)$m['credit_cost'];
  if((float)$u['credits']<$cost) json_out(['error'=>'Недостаточно средств'],402);
  if($project){
    $p=$pdo->prepare('SELECT id FROM projects WHERE id=? AND user_id=?');
    $p->execute([$project,$u['id']]);
    if(!$p->fetchColumn()) json_out(['error'=>'Project not found'],404);
  }
  if(!$thread){
    $s=$pdo->prepare("INSERT INTO chat_threads(user_id,project_id,title,model_slug,system_prompt) VALUES(?,?,?,?,?)");
    $s->execute([$u['id'],$project?:null,mb_substr($prompt,0,80),$model,$system?:null]);
    $thread=(int)$pdo->lastInsertId();
  }
  $own=$pdo->prepare('SELECT id FROM chat_threads WHERE id=? AND user_id=?');
  $own->execute([$thread,$u['id']]);
  if(!$own->fetchColumn()) json_out(['error'=>'Thread not found'],404);
  $pdo->prepare("INSERT INTO chat_messages(thread_id,user_id,role,content,model_slug) VALUES(?,?,?,?,?)")->execute([$thread,$u['id'],'user',$prompt,$model]);
  $q=$pdo->prepare('SELECT role,content FROM chat_messages WHERE thread_id=? ORDER BY id ASC LIMIT 40');
  $q->execute([$thread]);
  $messages=$q->fetchAll();
  if($documentIds){
    $documentIds=array_values(array_unique(array_filter($documentIds,fn($v)=>$v>0)));
    if(count($documentIds)>8)$documentIds=array_slice($documentIds,0,8);
    if($documentIds){
      $marks=implode(',',array_fill(0,count($documentIds),'?'));
      $args=array_merge([$u['id']],$documentIds);
      $ds=$pdo->prepare("SELECT name,mime_type,extracted_text FROM documents WHERE user_id=? AND id IN ($marks)");
      $ds->execute($args);
      $ctx=[];
      foreach($ds->fetchAll() as $d){
        if(trim((string)$d['extracted_text'])!=='') $ctx[]="FILE: ".$d['name']."
".$d['extracted_text'];
      }
      if($ctx) $messages[]=['role'=>'system','content'=>"The user attached these files. Use their contents when relevant.

".implode("

---

",$ctx)];
    }
  }
  $projectSystem='';
  if($project){
    $ps=$pdo->prepare('SELECT p.instructions,p.memory,p.default_model_slug,p.default_preset_id FROM projects p WHERE p.id=? AND p.user_id=?');
    $ps->execute([$project,$u['id']]); $proj=$ps->fetch() ?: [];
    if(!$system && !empty($proj['default_model_slug'])) $model=(string)$proj['default_model_slug'];
    if(!$system && !empty($proj['default_preset_id'])){
      $pp=$pdo->prepare('SELECT system_prompt,model_slug,temperature,max_tokens FROM presets WHERE id=? AND user_id=?');
      $pp->execute([(int)$proj['default_preset_id'],$u['id']]); $preset=$pp->fetch();
      if($preset){
        if(!empty($preset['model_slug'])) $model=(string)$preset['model_slug'];
        if(trim((string)$preset['system_prompt'])!=='') $projectSystem=trim((string)$preset['system_prompt']);
      }
    }
    if(trim((string)($proj['instructions']??''))!=='') $projectSystem.="\n\nProject instructions:\n".trim((string)$proj['instructions']);
    if(trim((string)($proj['memory']??''))!=='') $projectSystem.="\n\nProject memory:\n".trim((string)$proj['memory']);
    if($projectSystem!=='') $messages[]=['role'=>'system','content'=>trim($projectSystem)];
    $k=$pdo->prepare('SELECT name,extracted_text FROM documents WHERE user_id=? AND project_id=? AND is_knowledge=1 AND extracted_text IS NOT NULL AND extracted_text<>"" ORDER BY id DESC LIMIT 12');
    $k->execute([$u['id'],$project]); $knowledge=[];
    foreach($k->fetchAll() as $d){$txt=trim((string)$d['extracted_text']); if($txt!=='') $knowledge[]="KNOWLEDGE FILE: ".$d['name']."\n".$txt;}
    if($knowledge) $messages[]=['role'=>'system','content'=>"Project knowledge base. Use these files when relevant:\n\n".implode("\n\n---\n\n",$knowledge)];
  }
  if($system) array_unshift($messages,['role'=>'system','content'=>$system]);
  $r=ai_chat($messages,$model,.7);
  $text=(string)($r['choices'][0]['message']['content']??'');
  if($text==='') throw new RuntimeException('Provider returned an empty response.');
  $usage=$r['usage']??[];
  $inTok=(int)($usage['prompt_tokens']??0);
  $outTok=(int)($usage['completion_tokens']??0);
  spend_credits((int)$u['id'],$cost,'AI chat '.$model,'chat_thread',$thread);
  $pdo->prepare("INSERT INTO chat_messages(thread_id,user_id,role,content,model_slug,input_tokens,output_tokens) VALUES(?,?,?,?,?,?,?)")->execute([$thread,$u['id'],'assistant',$text,$model,$inTok,$outTok]);
  $pdo->prepare('UPDATE chat_threads SET model_slug=?,project_id=COALESCE(?,project_id),updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$model,$project?:null,$thread]);
  audit('chat_completed',(int)$u['id'],['thread_id'=>$thread,'model'=>$model]);
  json_out(['ok'=>true,'thread_id'=>$thread,'message'=>$text,'usage'=>$usage,'charged'=>$cost]);
}catch(Throwable $e){
  json_out(['error'=>$e->getMessage()],500);
}
