<?php
require_once __DIR__.'/../_private/config/config.php';require_once ROOT_PATH.'/_private/lib/security.php';require_once ROOT_PATH.'/_private/lib/fx.php';require_once ROOT_PATH.'/_private/lib/payment_settings.php';
$u=require_auth();$cur=strtoupper((string)($u['currency']??'EUR'));if(!in_array($cur,['EUR','USD','GBP','RUB'],true))$cur='EUR';
$base=[
 ['slug'=>'starter','name'=>'Starter','credits'=>10,'eur'=>10,'discount'=>0,'featured'=>false],
 ['slug'=>'plus','name'=>'Plus','credits'=>50,'eur'=>45,'discount'=>10,'featured'=>false],
 ['slug'=>'pro','name'=>'Pro','credits'=>100,'eur'=>85,'discount'=>15,'featured'=>true],
 ['slug'=>'business','name'=>'Business','credits'=>250,'eur'=>200,'discount'=>20,'featured'=>false],
 ['slug'=>'scale','name'=>'Scale','credits'=>500,'eur'=>375,'discount'=>25,'featured'=>false]
];
$out=[];
foreach($base as $p){
  $amount=round(fx_convert((float)$p['eur'],'EUR',$cur),2);
  $out[]=['slug'=>$p['slug'],'name'=>$p['name'],'amount'=>$amount,'currency'=>$cur,'base_eur'=>$p['eur'],'credits'=>$p['credits'],'discount'=>$p['discount'],'featured'=>$p['featured'],'price_per_credit'=>number_format($amount/$p['credits'],2,'.','')];
}

$ps=payment_settings();
json_out(['currency'=>$cur,'symbol'=>fx_symbol($cur),'eur_per_credit'=>round(fx_convert(1,'EUR',$cur),6),'packages'=>[],'method_configs'=>$ps['method_configs']??[]]);
