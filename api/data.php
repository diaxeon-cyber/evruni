<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
$u=require_auth();
$type=$_GET['type']??'dashboard';
if($type==='balance'){
 $cur=wallet_currency_for_user((int)$u['id']);
 $bal=wallet_balance_exact((int)$u['id'],$cur);
 json_out(['user'=>['credits'=>$bal,'wallet_balance'=>$bal,'currency'=>$cur,'balance_currency'=>$cur]]);
}
if($type==='dashboard'){
 $g=$pdo->prepare("SELECT id,type,prompt,status,result_text,result_path,credits_spent,created_at FROM generations WHERE user_id=? ORDER BY id DESC LIMIT 20"); $g->execute([$u['id']]);
 json_out(['user'=>['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'credits'=>wallet_balance_exact((int)$u['id'],wallet_currency_for_user((int)$u['id'])),'role'=>$u['role'],'language'=>$u['language'],'currency'=>$u['currency'],'wallet_balance'=>wallet_balance_exact((int)$u['id'],wallet_currency_for_user((int)$u['id'])),'balance_currency'=>wallet_currency_for_user((int)$u['id'])],'generations'=>$g->fetchAll(),'csrf'=>csrf_token()]);
}

if($type==='notice'){
 try{
  $st=$pdo->prepare("SELECT enabled,title_ru,text_ru,title_en,text_en,updated_at FROM site_notices WHERE notice_key='development' LIMIT 1");
  $st->execute();
  $n=$st->fetch();
  if(!$n){ json_out(['enabled'=>true,'title_ru'=>'Evruni находится в разработке','text_ru'=>'Некоторые функции временно недоступны. Мы работаем над их запуском.','title_en'=>'Evruni is under development','text_en'=>'Some features are temporarily unavailable. We are working on bringing them online.']); }
  json_out(['notice'=>[
   'enabled'=>(int)$n['enabled']===1,
   'title_ru'=>$n['title_ru'],'text_ru'=>$n['text_ru'],
   'title_en'=>$n['title_en'],'text_en'=>$n['text_en'],
   'updated_at'=>$n['updated_at']
  ]]);
 }catch(Throwable $e){
  json_out(['notice'=>[
   'enabled'=>true,
   'title_ru'=>'Evruni находится в разработке',
   'text_ru'=>'Некоторые функции временно недоступны. Мы работаем над их запуском.',
   'title_en'=>'Evruni is under development',
   'text_en'=>'Some features are temporarily unavailable. We are working on bringing them online.'
  ]]);
 }
}

if($type==='models'){ $m=$pdo->query("SELECT slug,name,type,provider,credit_cost FROM ai_models WHERE enabled=1 ORDER BY id")->fetchAll(); json_out(['models'=>$m]); }
json_out(['error'=>'Unknown type'],400);
