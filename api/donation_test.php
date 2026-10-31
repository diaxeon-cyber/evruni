<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/donations.php';
$u=require_auth(); require_csrf();
if($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);
$in=json_decode((string)file_get_contents('php://input'),true); if(!is_array($in)) json_out(['error'=>'Invalid JSON'],400);
$p=donation_profile_for_user((int)$u['id']);
$amount=max((float)$p['min_amount'],(float)($in['amount']??500));
$name=mb_substr(trim((string)($in['name']??'Тестовый зритель')),0,80);
$msg=mb_substr(trim((string)($in['message']??'Тестовое сообщение')),0,500);
$id=bin2hex(random_bytes(16));
$s=$pdo->prepare('INSERT INTO creator_donation_intents(profile_id,public_id,donor_name,message,amount,currency,status,provider_ref,paid_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
$s->execute([(int)$p['id'],$id,$name,$msg,$amount,$p['currency'],'paid','test']);
json_out(['ok'=>true,'id'=>$id,'amount'=>$amount,'currency'=>$p['currency']]);
