<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
$u=require_auth(); require_csrf(); rate_limit('creator_guard',12,60);
if($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);
$in=json_decode((string)file_get_contents('php://input'),true);
if(!is_array($in)) json_out(['error'=>'Invalid JSON'],400);
$image=(string)($in['image']??''); $title=trim((string)($in['title']??''));
if($image===''||!preg_match('#^data:image/(jpeg|jpg|webp|png);base64,#i',$image)) json_out(['error'=>'Invalid image'],422);
if(strlen($image)>180000) json_out(['error'=>'Frame too large'],413);
$raw=base64_decode(preg_replace('#^data:image/[^;]+;base64,#i','',$image),true);
if($raw===false||strlen($raw)>130000) json_out(['error'=>'Frame too large'],413);
$info=@getimagesizefromstring($raw); if(!$info||($info[0]??0)<80||($info[1]??0)<60) json_out(['error'=>'Invalid frame'],422);
$slug=(string)envv('CREATOR_VISION_MODEL',envv('CREATOR_AI_MODEL',envv('AI_DEFAULT_MODEL','aitunnel-auto')));
try{
  $m=ai_model_record($slug); $provider=strtolower((string)($m['provider']??''));
  $cfg=ai_provider_config($provider); if(($cfg['kind']??'')!=='openai') json_out(['error'=>'Для Guard нужен OpenAI-compatible vision model.'],503);
  $cost=max(0.0,(float)($m['credit_cost']??0)); if($cost>0) wallet_spend((int)$u['id'],$cost,'Creator Guard: vision','creator_guard',null);
  try{
    $context=$title!==''?'Контент заявлен как: '.mb_substr($title,0,120).'. ':' '; 
    $system='You are Evruni Creator Guard. Analyze one low-resolution livestream frame for content safety. '.$context.'Return ONLY JSON: {"boxes":[{"x":0,"y":0,"w":0,"h":0,"action":"blur|cover"}],"beep":false,"reason":"short"}. Coordinates are normalized 0..1000. Flag only clearly visible nudity/sexual imagery, graphic gore, or extreme disturbing imagery. Do not flag ordinary action, bloodless violence, swimsuits, faces, food, games, cartoons, or normal movie scenes. Max 3 boxes. If uncertain return empty boxes. beep=true only when the frame clearly indicates a moment that should also be silenced.';
    $messages=[['role'=>'system','content'=>$system],['role'=>'user','content'=>[['type'=>'text','text'=>'Analyze this frame now.'],['type'=>'image_url','image_url'=>['url'=>$image,'detail'=>'low']]]]];
    $r=ai_call_model(['model'=>(string)($m['provider_model']??$slug),'messages'=>$messages,'temperature'=>0,'max_tokens'=>220],$slug);
    $out=(string)($r['choices'][0]['message']['content']??'');
    $out=preg_replace('/^```(?:json)?\s*|\s*```$/i','',trim($out)); $j=json_decode($out,true);
    if(!is_array($j)) throw new RuntimeException('Invalid Guard response');
    $boxes=[]; foreach((array)($j['boxes']??[]) as $b){if(!is_array($b))continue;$x=max(0,min(1000,(int)($b['x']??0)));$y=max(0,min(1000,(int)($b['y']??0)));$w=max(0,min(1000-$x,(int)($b['w']??0)));$h=max(0,min(1000-$y,(int)($b['h']??0)));if($w>=30&&$h>=30)$boxes[]=['x'=>$x,'y'=>$y,'w'=>$w,'h'=>$h,'action'=>($b['action']??'blur')==='cover'?'cover':'blur'];if(count($boxes)>=3)break;}
    json_out(['ok'=>true,'boxes'=>$boxes,'beep'=>!empty($j['beep']),'reason'=>mb_substr((string)($j['reason']??''),0,160)]);
  }catch(Throwable $e){if($cost>0)wallet_add((int)$u['id'],$cost,'USD','Creator Guard refund','creator_guard_refund',null);throw $e;}
}catch(Throwable $e){$msg=$e->getMessage();$status=str_contains(mb_strtolower($msg),'недостаточно')?402:500;json_out(['error'=>$status===402?'Недостаточно средств.':'Creator Guard temporarily unavailable.'],$status);}
