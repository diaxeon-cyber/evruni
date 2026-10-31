<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
$u=require_auth();
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='GET'){
    $uid=(int)$u['id'];
    $code=ensure_referral_code($uid);
    $cfg=referral_settings();
    $st=$pdo->prepare("SELECT COUNT(*) FROM referral_clicks WHERE referrer_user_id=?");$st->execute([$uid]);$clicks=(int)$st->fetchColumn();
    $st=$pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_user_id=?");$st->execute([$uid]);$referrals=(int)$st->fetchColumn();
    $st=$pdo->prepare("SELECT COALESCE(SUM(commission_usd),0) FROM referral_commissions WHERE referrer_user_id=?");$st->execute([$uid]);$earnedUsd=(float)$st->fetchColumn();
    $currency=strtoupper((string)($u['currency']??'USD'));if(!in_array($currency,['EUR','USD','GBP','RUB'],true))$currency='USD';
    $earned=fx_convert($earnedUsd,'USD',$currency);
    $st=$pdo->prepare("SELECT r.id,r.referred_user_id,r.created_at,r.converted_at,u.name,u.email,COALESCE((SELECT SUM(rc.commission_usd) FROM referral_commissions rc WHERE rc.referral_id=r.id),0) earned_usd FROM referrals r JOIN users u ON u.id=r.referred_user_id WHERE r.referrer_user_id=? ORDER BY r.id DESC LIMIT 100");$st->execute([$uid]);$rows=$st->fetchAll();
    foreach($rows as &$row){$row['earned']=fx_convert((float)$row['earned_usd'],'USD',$currency);unset($row['earned_usd']);}
    $tasks=$pdo->query("SELECT * FROM bonus_tasks WHERE enabled=1 ORDER BY sort_order ASC,id DESC")->fetchAll();
    $sub=$pdo->prepare("SELECT * FROM task_submissions WHERE user_id=?");$sub->execute([$uid]);$subs=[];foreach($sub->fetchAll() as $r){$subs[(int)$r['task_id']]=$r;}
    foreach($tasks as &$task){$task['reward']=fx_convert((float)$task['reward_usd'],'USD',$currency);$task['submission']=$subs[(int)$task['id']]??null;}
    json_out(['ok'=>true,'settings'=>$cfg,'currency'=>$currency,'currency_symbol'=>['EUR'=>'€','USD'=>'$','GBP'=>'£','RUB'=>'₽'][$currency]??$currency,'code'=>$code,'link'=>rtrim($config['app_url'],'/').'/?ref='.rawurlencode($code),'clicks'=>$clicks,'referrals'=>$referrals,'earned'=>$earned,'tasks'=>$tasks,'commission_percent'=>(float)$cfg['commission_percent']]);
}
require_csrf();
$in=json_decode(file_get_contents('php://input'),true)?:$_POST;$action=trim((string)($in['action']??''));$uid=(int)$u['id'];
if($action==='task_click'){
    $taskId=(int)($in['task_id']??0);$st=$pdo->prepare("SELECT id,target_url,enabled FROM bonus_tasks WHERE id=? LIMIT 1");$st->execute([$taskId]);$task=$st->fetch();if(!$task||!(int)$task['enabled'])json_out(['error'=>'Задание недоступно'],404);
    $pdo->prepare("INSERT INTO task_clicks(task_id,user_id) VALUES(?,?)")->execute([$taskId,$uid]);json_out(['ok'=>true,'url'=>(string)($task['target_url']??'')]);
}
if($action==='submit_task'){
    $taskId=(int)($in['task_id']??0);$proof=trim((string)($in['proof_text']??''));
    $st=$pdo->prepare("SELECT * FROM bonus_tasks WHERE id=? AND enabled=1 LIMIT 1");$st->execute([$taskId]);$task=$st->fetch();if(!$task)json_out(['error'=>'Задание недоступно'],404);
    if((int)$task['proof_required'] && $proof==='')json_out(['error'=>'Добавьте подтверждение выполнения'],422);
    if(mb_strlen($proof)>3000)json_out(['error'=>'Подтверждение слишком длинное'],422);
    $st=$pdo->prepare("SELECT status FROM task_submissions WHERE task_id=? AND user_id=? LIMIT 1");$st->execute([$taskId,$uid]);$existing=$st->fetchColumn();
    if($existing==='approved')json_out(['error'=>'Задание уже выполнено'],409);
    if($existing==='pending')json_out(['error'=>'Задание уже отправлено на проверку'],409);
    $pdo->prepare("INSERT INTO task_submissions(task_id,user_id,proof_text,status,submitted_at,reviewed_at,reviewed_by) VALUES(?,?,?,'pending',NOW(),NULL,NULL) ON DUPLICATE KEY UPDATE proof_text=VALUES(proof_text),status='pending',submitted_at=NOW(),reviewed_at=NULL,reviewed_by=NULL")->execute([$taskId,$uid,$proof]);
    audit('task.submit',$uid,['task_id'=>$taskId]);json_out(['ok'=>true]);
}
json_out(['error'=>'Unknown action'],404);
