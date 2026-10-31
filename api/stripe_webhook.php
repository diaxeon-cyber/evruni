<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/credits.php'; require_once ROOT_PATH.'/_private/lib/fx.php'; require_once ROOT_PATH.'/_private/lib/security.php';
$payload=file_get_contents('php://input'); $sig=$_SERVER['HTTP_STRIPE_SIGNATURE']??''; $secret=(string)envv('STRIPE_WEBHOOK_SECRET','');
if(!$secret || !$sig) { http_response_code(400); exit; }
function stripe_verify(string $payload,string $sig,string $secret): bool {
  $parts=[]; foreach(explode(',',$sig) as $p){[$k,$v]=array_pad(explode('=',$p,2),2,'');$parts[$k][]=$v;}
  $t=(int)($parts['t'][0]??0); $v1=$parts['v1']??[];
  if(abs(time()-$t)>300) return false;
  $expected=hash_hmac('sha256',$t.'.'.$payload,$secret);
  foreach($v1 as $candidate) if(hash_equals($expected,$candidate)) return true;
  return false;
}
if(!stripe_verify($payload,$sig,$secret)){http_response_code(400);exit;}
$event=json_decode($payload,true); $obj=$event['data']['object']??[];
if(($event['type']??'')==='checkout.session.completed'){
  $sid=$obj['id']??''; $s=$pdo->prepare("SELECT * FROM payments WHERE external_id=? FOR UPDATE"); $pdo->beginTransaction(); $s->execute([$sid]); $p=$s->fetch();
  if($p && $p['status']!=='paid'){ $pdo->prepare("UPDATE payments SET status='paid',paid_at=NOW() WHERE id=?")->execute([$p['id']]); $pdo->commit(); $paidAmount=round(((float)$p['amount_cents'])/100,2); $paidCurrency=strtoupper((string)$p['currency']); wallet_add((int)$p['user_id'],$paidAmount,$paidCurrency,'Stripe payment','payment',(int)$p['id']); try{ referral_commission_for_payment((int)$p['id'],(int)$p['user_id'],fx_convert($paidAmount,$paidCurrency,'USD')); }catch(Throwable $e){} }
  elseif($pdo->inTransaction()) $pdo->commit();
}
http_response_code(200); echo 'ok';
