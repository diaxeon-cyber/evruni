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
$text=function_exists('mb_substr')?mb_substr($text,0,12000):substr($text,0,12000);
$language=trim((string)($input['language']??'ru'));
$targetLanguage=trim((string)($input['target_language']??'English'));
$languageMap=['ru'=>'Russian','en'=>'English','de'=>'German','es'=>'Spanish'];
$language=$languageMap[$language]??'Russian';
$targetLanguage=preg_replace('/[^A-Za-z -]/','',$targetLanguage)?:'English';
$allowed=['moderate','chatbot','summary','translate','shorts','copilot','clip_note'];
if(!in_array($action,$allowed,true)) json_out(['error'=>'Unknown creator tool'],422);
if($action==='clip_note') json_out(['ok'=>true,'action'=>$action,'queued'=>true,'message'=>'Момент отмечен. Тяжёлая обработка запускается отдельно.']);
if($text==='') json_out(['error'=>'Text is required'],422);

$slug=(string)envv('CREATOR_AI_MODEL',envv('AI_DEFAULT_MODEL','aitunnel-auto'));
try{
    $model=ai_model_record($slug);
    $cost=max(0.0,(float)($model['credit_cost']??0));
    if($cost>0) wallet_spend((int)$u['id'],$cost,'Creator Tools: '.$action,'creator_tool',null);
    try{
        if($action==='moderate') {
            $system='You are an event-driven stream chat moderator. Return JSON only: {"action":"allow|warn|delete","reason":"short reason"}. Be conservative. Delete only clear spam, harassment, hate, threats, sexual content involving minors, or dangerous illegal instructions. Otherwise allow.';
        } elseif($action==='chatbot') {
            $system='You are Evruni, a concise friendly stream chat bot. Answer in '.$language.'. Maximum 2 short sentences. Do not claim to control the stream or invent facts.';
        } elseif($action==='translate') {
            $system='Translate the supplied livestream message into '.$targetLanguage.'. Return only the translation, no commentary.';
        } elseif($action==='shorts') {
            $system='Turn the supplied stream moment into a short-form video package. Return exactly: HOOK, TITLE, CAPTION, HASHTAGS. Keep each line concise. Do not invent facts not present in the input. Write the package in '.$language.'.';
        } elseif($action==='copilot') {
            $system='You are a lightweight livestream copilot. Based only on the supplied event, give one concise useful next action for the streamer. Maximum 2 sentences. Do not claim to continuously watch the stream. Answer in '.$language.'.';
        } else {
            $system='You summarize a completed livestream. Return 4 short bullet points: main topics, notable moments, audience interaction, useful follow-up. Do not invent facts. Write in '.$language.'.';
        }
        $r=ai_chat([['role'=>'system','content'=>$system],['role'=>'user','content'=>$text]],$slug,.2,450);
        $out=(string)($r['choices'][0]['message']['content']??'');
        if($out==='') throw new RuntimeException('Empty AI response');
        json_out(['ok'=>true,'action'=>$action,'result'=>$out]);
    }catch(Throwable $e){
        if($cost>0) wallet_add((int)$u['id'],$cost,'USD','Creator Tools refund: '.$action,'creator_tool_refund',null);
        throw $e;
    }
}catch(Throwable $e){
    $msg=$e->getMessage();
    $low=function_exists('mb_strtolower')?mb_strtolower($msg):strtolower($msg);
    $status=str_contains($low,'недостаточно')?402:500;
    json_out(['error'=>$status===402?'Недостаточно средств.':'Creator tool temporarily unavailable.'],$status);
}
