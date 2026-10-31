<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
require_once ROOT_PATH.'/_private/lib/ai_pricing.php';
$u=require_auth();
ai_catalog_seed();
$cur=wallet_currency_for_user((int)$u['id']);
$models=ai_pricing_for_models(ai_models_local(),$cur);
$categoryCounts=[];
foreach($models as $m){
 $k=(string)($m['category_key']??'text');
 if(!isset($categoryCounts[$k]))$categoryCounts[$k]=['key'=>$k,'label'=>(string)($m['category']??$k),'count'=>0];
 $categoryCounts[$k]['count']++;
}
json_out(['models'=>$models,'providers'=>['openai','anthropic','google','deepseek','qwen','mistral','aitunnel'],'currency'=>$cur,'markup_percent'=>ai_markup_percent(),'category_counts'=>array_values($categoryCounts)]);
