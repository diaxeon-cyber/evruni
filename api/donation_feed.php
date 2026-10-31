<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/donations.php';
if($_SERVER['REQUEST_METHOD']!=='GET') json_out(['error'=>'GET required'],405);
$slug=trim((string)($_GET['s']??''));
$after=max(0,(int)($_GET['after']??0));
if($slug===''||!preg_match('/^[a-z0-9]{6,40}$/',$slug)) json_out(['error'=>'Invalid link'],422);
$p=donation_profile_by_slug($slug);
if(!$p) json_out(['error'=>'Not found'],404);
$s=$pdo->prepare('SELECT id,public_id,donor_name,message,amount,currency,created_at,paid_at FROM creator_donation_intents WHERE profile_id=? AND status="paid" AND id>? ORDER BY id ASC LIMIT 10');
$s->execute([(int)$p['id'],$after]);
json_out(['ok'=>true,'items'=>$s->fetchAll(),'last_id'=>$after]);
