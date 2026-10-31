<?php
require_once __DIR__.'/../config/config.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
while(true){
  if(!$pdo){sleep(5);continue;}
  $s=$pdo->query("SELECT * FROM generations WHERE status='queued' ORDER BY id LIMIT 1")->fetch();
  if(!$s){sleep(2);continue;}
  $pdo->prepare("UPDATE generations SET status='processing' WHERE id=? AND status='queued'")->execute([$s['id']]);
  try {
    $result=($s['type']==='text'||$s['type']==='research'||$s['type']==='document')?generate_text($s['prompt']):'Worker/provider integration required for '.$s['type'];
    $pdo->prepare("UPDATE generations SET status='completed',result_text=? WHERE id=?")->execute([$result,$s['id']]);
  } catch(Throwable $e){
    $pdo->prepare("UPDATE generations SET status='failed',error_message=? WHERE id=?")->execute([$e->getMessage(),$s['id']]);
  }
}
