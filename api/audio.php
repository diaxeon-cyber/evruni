<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
require_once ROOT_PATH.'/_private/lib/ai.php';
$u=require_auth(); require_csrf();
$action=trim((string)($_POST['action']??''));
if($action==='tts'){
    $text=trim((string)($_POST['text']??'')); if($text===''||strlen($text)>20000) json_out(['error'=>'Invalid text'],422);
    $m=ai_model_record((string)($_POST['model']??'speech-default')); if(($m['type']??'')!=='voice' || (string)$m['slug']!=='speech-default') json_out(['error'=>'Invalid speech model'],422);$cost=(float)$m['credit_cost'];if((float)$u['credits']<$cost)json_out(['error'=>'Недостаточно средств'],402);
    try{
        $bin=generate_speech($text);$name='media/generated/tts_'.(int)$u['id'].'_'.bin2hex(random_bytes(6)).'.mp3';$full=ROOT_PATH.'/'.$name;
        if(file_put_contents($full,$bin)===false)throw new RuntimeException('Unable to save audio');
        spend_credits((int)$u['id'],$cost,'Text to speech','audio',(int)$u['id']);json_out(['ok'=>true,'audio_url'=>'/'.$name,'charged'=>$cost]);
    }catch(Throwable $e){json_out(['error'=>$e->getMessage()],500);}
}
if($action==='transcribe'){
    if(empty($_FILES['file']['tmp_name']))json_out(['error'=>'Audio file is required'],422);
    $m=ai_model_record((string)($_POST['model']??'transcribe-default')); if(($m['type']??'')!=='voice' || (string)$m['slug']!=='transcribe-default') json_out(['error'=>'Invalid transcription model'],422);$cost=(float)$m['credit_cost'];if((float)$u['credits']<$cost)json_out(['error'=>'Недостаточно средств'],402);
    try{$text=transcribe_audio($_FILES['file']['tmp_name'],(string)$_FILES['file']['name']);spend_credits((int)$u['id'],$cost,'Audio transcription','audio',(int)$u['id']);json_out(['ok'=>true,'text'=>$text,'charged'=>$cost]);}
    catch(Throwable $e){json_out(['error'=>$e->getMessage()],500);}
}
json_out(['error'=>'Unsupported audio action'],422);
