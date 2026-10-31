<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
require_once ROOT_PATH.'/_private/lib/credits.php';

$u=require_auth();
require_csrf();
rate_limit('creator_tools',20,60);

if($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);

$input=json_decode((string)file_get_contents('php://input'),true);
if(!is_array($input)) json_out(['error'=>'Invalid JSON'],400);
$action=trim((string)($input['action']??''));
$text=trim((string)($input['text']??''));
$text=mb_substr($text,0,12000);

$allowed=['moderate','chatbot','summary','clip_note'];
if(!in_array($action,$allowed,true)) json_out(['error'=>'Unknown creator tool'],422);

if($action==='clip_note'){
    json_out(['ok'=>true,'action'=>$action,'queued'=>true,'message'=>'Момент отмечен. Тяжёлая обработка запускается отдельно.']);
}

if($text==='') json_out(['error'=>'Text is required'],422);

$slug=(string)envv('CREATOR_AI_MODEL',envv('AI_DEFAULT_MODEL','aitunnel-auto'));
try{
    $model=ai_model_record($slug);
    $cost=max(0.0,(float)($model['credit_cost']??0));
    if($cost>0) wallet_spend((int)$u['id'],$cost,'Creator Tools: '.$action,'creator_tool',null);

    try{
        if($action==='moderate'){
            $system='You are a stream chat moderator. Return JSON only: {"action":"allow|warn|delete","reason":"short reason"}. Be conservative. Only delete clear spam, harassment, hate, threats, sexual content involving minors, or dangerous illegal instructions.';
        }elseif($action==='chatbot'){
            $system='You are Evruni, a concise friendly stream chat bot. Answer the viewer in Russian unless they clearly use another language. Maximum 2 short sentences. Do not pretend to control the stream.';
        }else{
            $system='You summarize a completed livestream. Return 4 short bullet points: main topics, notable moments, audience interaction, useful follow-up. Do not invent facts.';
        }
        $r=ai_chat([['role'=>'system','content'=>$system],['role'=>'user','content'=>$text]],$slug,.2,350);
        $out=(string)($r['choices'][0]['message']['content']??'');
        if($out==='')throw new RuntimeException('Empty AI response');
        json_out(['ok'=>true,'action'=>$action,'result'=>$out]);
    }catch(Throwable $e){
        if($cost>0) wallet_add((int)$u['id'],$cost,'USD','Creator Tools refund: '.$action,'creator_tool_refund',null);
        throw $e;
    }
}catch(Throwable $e){
    $status=str_contains(mb_strtolower($e->getMessage()),'недостаточно')?402:500;
    json_out(['error'=>$status===402?'Недостаточно средств.':'Creator tool temporarily unavailable.'],$status);
}
