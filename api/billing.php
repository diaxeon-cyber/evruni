<?php
require_once __DIR__.'/../_private/config/config.php';require_once ROOT_PATH.'/_private/lib/security.php';require_once ROOT_PATH.'/_private/lib/fx.php';require_once ROOT_PATH.'/_private/lib/payment_settings.php';
$u=require_auth();require_csrf();if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')json_out(['error'=>'POST required'],405);
$amount=(float)str_replace(',','.',trim((string)($_POST['amount']??'0')));
if($amount<0.01 || $amount>1000000)json_out(['error'=>'Укажите сумму пополнения от 0.01 до 1000000'],422);
$country=strtoupper(trim((string)($_POST['country']??'OTHER')));
$allowedCountries=['NL','RU','DE','FR','GB','US','CA','AU','AT','BE','ES','IT','PL','CH','SE','NO','DK','FI','IE','PT','CZ','RO','EE','LT','LV','OTHER'];
if(!in_array($country,$allowedCountries,true))$country='OTHER';
$method=(string)($_POST['method']??'card');
$countryCurrencyMap=[
 'NL'=>'EUR','DE'=>'EUR','FR'=>'EUR','AT'=>'EUR','BE'=>'EUR','ES'=>'EUR','IT'=>'EUR','PT'=>'EUR','IE'=>'EUR','FI'=>'EUR',
 'PL'=>'EUR','CZ'=>'EUR','RO'=>'EUR','CH'=>'EUR','SE'=>'EUR','NO'=>'EUR','DK'=>'EUR','EE'=>'EUR','LT'=>'EUR','LV'=>'EUR',
 'RU'=>'RUB','GB'=>'GBP',
 'US'=>'USD','CA'=>'USD','AU'=>'USD','NZ'=>'USD','MX'=>'USD','BR'=>'USD','AR'=>'USD','CL'=>'USD','CO'=>'USD','PE'=>'USD',
 'JP'=>'USD','KR'=>'USD','CN'=>'USD','HK'=>'USD','SG'=>'USD','IN'=>'USD','AE'=>'USD','SA'=>'USD','IL'=>'USD','TR'=>'USD',
 'ZA'=>'USD','EG'=>'USD','NG'=>'USD','OTHER'=>'USD'
];
$requestedCurrency=strtoupper(trim((string)($_POST['currency']??'')));
$cur=$countryCurrencyMap[$country]??'EUR';
if($requestedCurrency!=='' && $requestedCurrency!==$cur)json_out(['error'=>'Валюта не соответствует выбранной стране'],422);
$ps=payment_settings();
if(($ps['provider']??'stripe')!=='stripe' || empty($ps['enabled']))json_out(['error'=>'Платежи временно недоступны'],503);
$methodCfg=$ps['method_configs'][$country]??$ps['method_configs']['OTHER']??[];
$enabledMethods=[];
foreach($methodCfg as $m){if(!empty($m['enabled']))$enabledMethods[]=(string)($m['type']??'');}
if(!$enabledMethods) $enabledMethods=['card'];
if(!in_array($method,$enabledMethods,true))json_out(['error'=>'Выбранный способ оплаты недоступен для этой страны'],422);
if($method!=='card')json_out(['error'=>'Этот способ оплаты пока не подключён к платёжному шлюзу'],503);
$key=(string)($ps['secret_key']??'');if(!$key)json_out(['error'=>'Stripe is not configured'],503);
$credits=round(fx_convert($amount,$cur,'USD'),6);$minor=(int)round($amount*100);
$fields=http_build_query([
 'mode'=>'payment',
 'billing_address_collection'=>'required',
 'line_items[0][price_data][currency]'=>strtolower($cur),
 'line_items[0][price_data][unit_amount]'=>$minor,
 'line_items[0][price_data][product_data][name]'=>'Evruni — '.$amount.' '.$cur,
 'line_items[0][quantity]'=>1,
 'success_url'=>$config['app_url'].'/?payment=success',
 'cancel_url'=>$config['app_url'].'/?payment=cancel',
 'client_reference_id'=>(string)$u['id'],
 'metadata[user_id]'=>$u['id'],
 'metadata[credits]'=>$credits,
 'metadata[currency]'=>$cur,
 'metadata[amount]'=>$amount,
 'metadata[billing_country]'=>$country,
 'metadata[payment_method]'=>$method
]);
$ch=curl_init('https://api.stripe.com/v1/checkout/sessions');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$fields,CURLOPT_USERPWD=>$key.':',CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$r=json_decode($raw,true);if($code>=400)json_out(['error'=>$r['error']['message']??'Stripe error'],502);
$externalId=(string)($r['id']??'');if($externalId!==''){
  $q=$pdo->prepare("INSERT INTO payments(user_id,provider,external_id,package_slug,amount_cents,currency,credits,status,checkout_url) VALUES(?,?,?,?,?,?,?,?,?)");
  $q->execute([(int)$u['id'],'stripe',$externalId,'custom',$minor,$cur,$credits,'pending',(string)($r['url']??'')]);
}
json_out(['ok'=>true,'url'=>$r['url'],'amount'=>$amount,'currency'=>$cur,'balance_amount'=>$amount]);
