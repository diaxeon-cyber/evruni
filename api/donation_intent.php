<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/donations.php';

if($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);
$in=json_decode((string)file_get_contents('php://input'),true);
if(!is_array($in)) json_out(['error'=>'Invalid JSON'],400);
$slug=trim((string)($in['slug']??''));
$name=trim((string)($in['name']??'Аноним'));
$message=trim((string)($in['message']??''));
$amount=(float)($in['amount']??0);
$turnstile=(string)($in['turnstile']??'');
if($slug===''||!preg_match('/^[a-z0-9]{6,40}$/',$slug)) json_out(['error'=>'Invalid donation link'],422);
if(!verify_turnstile($turnstile)) json_out(['error'=>'Проверка безопасности не пройдена'],403);
rate_limit('donation_intent_'.$slug,12,60);
$p=donation_profile_by_slug($slug);
if(!$p) json_out(['error'=>'Donation page not found'],404);

$owner=null;
if(!empty($p['user_id'])){
    $st=$pdo->prepare('SELECT * FROM users WHERE id=? AND is_active=1 LIMIT 1');
    $st->execute([(int)$p['user_id']]);
    $owner=$st->fetch() ?: null;
}
$ownerLanguage=donation_profile_language($owner);
$ownerRegion=donation_provider_region($ownerLanguage);
$provider=$ownerRegion==='ru'?(string)($p['payment_method_ru']??'yookassa'):(string)($p['payment_method_intl']??'paypal');
$basePaymentUrl=$ownerRegion==='ru'?(string)($p['payment_url_ru']??''):(string)($p['payment_url_intl']??'');
if($basePaymentUrl==='') $basePaymentUrl=(string)($p['payment_url']??'');
if($basePaymentUrl==='') json_out(['error'=>'Стример ещё не настроил платёжный агрегатор'],409);
if($ownerRegion!=='ru' && $provider!=='paypal') json_out(['error'=>'Для этого профиля доступна международная оплата через PayPal'],409);
if($name==='')$name='Аноним';
$name=mb_substr($name,0,80);
$message=mb_substr($message,0,500);
if($amount < (float)$p['min_amount']) json_out(['error'=>'Сумма ниже минимальной'],422);
if($p['max_amount']!==null && $amount > (float)$p['max_amount']) json_out(['error'=>'Сумма выше максимальной'],422);
$amount=round($amount,2);
$publicId=bin2hex(random_bytes(16));
$s=$pdo->prepare('INSERT INTO creator_donation_intents(profile_id,public_id,donor_name,message,amount,currency,status) VALUES(?,?,?,?,?,?,?)');
$s->execute([(int)$p['id'],$publicId,$name,$message,$amount,$p['currency'],'pending']);
$payUrl=donation_append_reference($basePaymentUrl,$publicId);
json_out(['ok'=>true,'id'=>$publicId,'status'=>'pending','amount'=>number_format($amount,2,'.',''),'currency'=>$p['currency'],'provider'=>$provider,'profile_language'=>$ownerLanguage,'payment_url'=>$payUrl,'message'=>'Заявка создана. Завершите оплату на странице выбранного платёжного сервиса.']);
