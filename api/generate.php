<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php'; require_once ROOT_PATH.'/_private/lib/credits.php'; require_once ROOT_PATH.'/_private/lib/ai.php'; require_once ROOT_PATH.'/_private/lib/ai_pricing.php';
$u=require_auth(); require_csrf(); rate_limit('generate',20,60);
$input=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$type=$input['type']??'text'; $prompt=trim((string)($input['prompt']??'')); $project=(int)($input['project_id']??0);
if($prompt==='' || strlen($prompt)>30000) json_out(['error'=>'Invalid prompt'],422);
$map=['text'=>'text-default','image'=>'image-default','voice'=>'voice-default','video'=>'video-default','research'=>'research-default','document'=>'document-default'];
if(!isset($map[$type])) json_out(['error'=>'Unsupported type'],422);
$requestedModel=trim((string)($input['model']??'')); $slug=$requestedModel!==''?$requestedModel:$map[$type];
$s=$pdo->prepare("SELECT * FROM ai_models WHERE slug=? AND enabled=1"); $s->execute([$slug]); $model=$s->fetch();
if(!$model) json_out(['error'=>'Model unavailable'],503);
try {
  $pdo->beginTransaction();
  $pdo->prepare("INSERT INTO generations(user_id,project_id,model_id,type,prompt,status,credits_spent) VALUES(?,?,?,?,?,'processing',?)")
      ->execute([$u['id'],$project?:null,$model['id'],$type,$prompt,round(ai_generation_quote_usd($model,$input),6)]);
  $gid=(int)$pdo->lastInsertId(); $pdo->commit();
  $charged_cost=round(ai_generation_quote_usd($model,$input),6);
  spend_credits((int)$u['id'],$charged_cost,"Generation: ".$type,'generation',$gid);
  $text=null; $path=null;
  if($type==='text' || $type==='research' || $type==='document') $text=generate_text($prompt);
  elseif($type==='image') {
    $outs=generate_image($prompt,$slug,$input); $paths=[];
    foreach($outs as $out){
      if(str_starts_with($out,'data:')) $out=substr($out,strpos($out,',')+1);
      if(str_starts_with($out,'http://') || str_starts_with($out,'https://')) { $paths[]=$out; continue; }
      if(preg_match('/^[A-Za-z0-9+\/=\r\n]+$/',$out) && strlen($out)>100) {
        $fn='media/generated/img_'.$gid.'_'.bin2hex(random_bytes(5)).'.png';
        $full=ROOT_PATH.'/'.$fn;
        if (file_put_contents($full,base64_decode($out)) === false) throw new RuntimeException('Unable to save generated image');
        $paths[]='/'.$fn;
      }
    }
    $path=$paths[0]??null;
    $text=json_encode(['paths'=>$paths],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  } else $text='Задание поставлено в очередь. Подключите production worker/provider для этого типа.';
  $pdo->prepare("UPDATE generations SET status='completed',result_text=?,result_path=? WHERE id=?")->execute([$text,$path,$gid]);
  audit('generation_completed',(int)$u['id'],['generation_id'=>$gid,'type'=>$type]);
  json_out(['ok'=>true,'id'=>$gid,'type'=>$type,'result_text'=>$text,'result_path'=>$path,'result_paths'=>($type==='image' ? ($paths??[]) : []),'spent'=>$charged_cost]);
} catch(Throwable $e) {
  if($pdo->inTransaction()) $pdo->rollBack();
  if(isset($gid)) $pdo->prepare("UPDATE generations SET status='failed',error_message=? WHERE id=?")->execute([$e->getMessage(),$gid]);
  json_out(['error'=>$e->getMessage()],500);
}
