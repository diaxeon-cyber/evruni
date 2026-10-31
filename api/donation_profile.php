<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/donations.php';

$u=require_auth();
donation_tables();
if($_SERVER['REQUEST_METHOD']==='GET'){
    $p=donation_profile_for_user((int)$u['id']);
    json_out(['ok'=>true,'profile'=>$p,'public_url'=>donation_public_url((string)$p['slug'])]);
}
require_csrf();
if($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);
rate_limit('donation_profile',20,60);
$in=json_decode((string)file_get_contents('php://input'),true);
if(!is_array($in)) json_out(['error'=>'Invalid JSON'],400);
$p=donation_profile_for_user((int)$u['id']);
$title=trim((string)($in['title']??'Поддержать стримера'));
$description=trim((string)($in['description']??'Спасибо за поддержку!'));
$currency=strtoupper(preg_replace('/[^A-Z]/','',(string)($in['currency']??'RUB')));
$min=(float)($in['min_amount']??100);
$max=(float)($in['max_amount']??0);
$method=(string)($in['payment_method']??'yookassa');
$url=trim((string)($in['payment_url']??''));
$duration=max(3,min(30,(int)($in['overlay_duration']??8)));
$overlayTitle=trim((string)($in['overlay_title']??'Новый донат'));
$template=trim((string)($in['overlay_template']??'💜 {name} поддержал на {amount} {currency}'));
$allowed=['yookassa','robokassa','cloudpayments','paypal','bank','payment_link','other'];
if(!in_array($method,$allowed,true)) json_out(['error'=>'Неизвестный платёжный агрегатор'],422);
if($title===''||mb_strlen($title)>120||$description===''||mb_strlen($description)>500) json_out(['error'=>'Некорректный текст'],422);
if(strlen($currency)<3||strlen($currency)>6) json_out(['error'=>'Некорректная валюта'],422);
if($min<=0||$min>100000000) json_out(['error'=>'Некорректная минимальная сумма'],422);
if($max<0||($max>0&&$max<$min)) json_out(['error'=>'Некорректный максимум'],422);
if($url!=='' && !filter_var($url,FILTER_VALIDATE_URL)) json_out(['error'=>'Ссылка оплаты должна быть HTTPS/URL'],422);
if($url!=='' && !str_starts_with(strtolower($url),'https://')) json_out(['error'=>'Ссылка оплаты должна использовать HTTPS'],422);
if($template===''||mb_strlen($template)>500) json_out(['error'=>'Некорректный шаблон уведомления'],422);
$s=$pdo->prepare('UPDATE creator_donation_profiles SET title=?,description=?,currency=?,min_amount=?,max_amount=?,payment_method=?,payment_url=?,overlay_duration=?,overlay_title=?,overlay_template=?,updated_at=NOW() WHERE user_id=?');
$s->execute([$title,$description,$currency,$min,$max>0?$max:null,$method,$url,$duration,$overlayTitle,$template,(int)$u['id']]);
$p=donation_profile_for_user((int)$u['id']);
json_out(['ok'=>true,'profile'=>$p,'public_url'=>donation_public_url((string)$p['slug'])]);
