<?php
require_once __DIR__.'/../../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
// OpenAI-compatible model discovery for Evruni API keys.
$h=$_SERVER['HTTP_AUTHORIZATION']??''; if(!preg_match('/^Bearer\s+(.+)$/i',$h,$m)) json_out(['error'=>['message'=>'Missing API key','type'=>'authentication_error']],401);
$hash=hash('sha256',trim($m[1]));$s=$pdo->prepare('SELECT id,user_id FROM api_keys WHERE key_hash=? AND is_active=1 AND (expires_at IS NULL OR expires_at>NOW())');$s->execute([$hash]);if(!$s->fetch())json_out(['error'=>['message'=>'Invalid API key','type'=>'authentication_error']],401);
$out=[];foreach(ai_models_local() as $m){$out[]=['id'=>$m['slug'],'object'=>'model','created'=>0,'owned_by'=>'evruni','name'=>$m['name'],'type'=>$m['type'],'provider'=>$m['provider']];}json_out(['object'=>'list','data'=>$out]);
