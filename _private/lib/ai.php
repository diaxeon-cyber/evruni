<?php
declare(strict_types=1);
require_once ROOT_PATH.'/_private/lib/security.php';

/**
 * Evruni independent provider layer.
 * No aggregator URLs are hard-coded. Each model resolves to a provider + provider_model.
 */
function ai_provider_config(string $provider): array {
    $p=strtolower(trim($provider));
    return match($p) {
        'openai' => ['base'=>rtrim((string)envv('OPENAI_BASE_URL','https://api.openai.com/v1'),'/'),'key'=>(string)envv('OPENAI_API_KEY', envv('AI_API_KEY','')),'kind'=>'openai'],
        'deepseek' => ['base'=>rtrim((string)envv('DEEPSEEK_BASE_URL','https://api.deepseek.com/v1'),'/'),'key'=>(string)envv('DEEPSEEK_API_KEY',''),'kind'=>'openai'],
        'qwen' => ['base'=>rtrim((string)envv('QWEN_BASE_URL','https://dashscope.aliyuncs.com/compatible-mode/v1'),'/'),'key'=>(string)envv('QWEN_API_KEY',''),'kind'=>'openai'],
        'mistral' => ['base'=>rtrim((string)envv('MISTRAL_BASE_URL','https://api.mistral.ai/v1'),'/'),'key'=>(string)envv('MISTRAL_API_KEY',''),'kind'=>'openai'],
        'aitunnel' => ['base'=>rtrim((string)envv('AITUNNEL_BASE_URL','https://api.aitunnel.ru/v1'),'/'),'key'=>(string)envv('AITUNNEL_API_KEY',''),'kind'=>'openai'],
        'anthropic' => ['base'=>'https://api.anthropic.com','key'=>(string)envv('ANTHROPIC_API_KEY',''),'kind'=>'anthropic'],
        'google' => ['base'=>'https://generativelanguage.googleapis.com','key'=>(string)envv('GOOGLE_API_KEY',''),'kind'=>'google'],
        default => throw new RuntimeException('Unknown AI provider: '.$provider),
    };
}

function ai_http(string $url,string $method='GET',array $headers=[],?string $body=null,int $timeout=180): array {
    $ch=curl_init($url);
    $opts=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>20,CURLOPT_HTTPHEADER=>$headers];
    if($body!==null){$opts[CURLOPT_POSTFIELDS]=$body;}
    curl_setopt_array($ch,$opts);
    $raw=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if($raw===false) throw new RuntimeException($err?:'AI request failed');
    $data=json_decode($raw,true);
    if($code>=400){$msg=is_array($data)?($data['error']['message']??$data['message']??('Provider HTTP '.$code)):('Provider HTTP '.$code); throw new RuntimeException((string)$msg);}
    return ['status'=>$code,'raw'=>$raw,'data'=>is_array($data)?$data:[]];
}

function ai_openai_chat(array $cfg,array $payload): array {
    if($cfg['key']==='') throw new RuntimeException('OpenAI-compatible provider key is not configured.');
    $payload['model']=(string)$payload['model'];
    $r=ai_http($cfg['base'].'/chat/completions','POST',['Content-Type: application/json','Authorization: Bearer '.$cfg['key']],json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),180);
    return $r['data'];
}

function ai_anthropic_chat(array $cfg,array $payload): array {
    if($cfg['key']==='') throw new RuntimeException('Anthropic API key is not configured.');
    $system=''; $messages=[];
    foreach(($payload['messages']??[]) as $m){
        $role=(string)($m['role']??'user'); $content=(string)($m['content']??'');
        if($role==='system'){$system.=$content."\n";continue;}
        $messages[]=['role'=>$role==='assistant'?'assistant':'user','content'=>$content];
    }
    $body=['model'=>(string)$payload['model'],'max_tokens'=>(int)($payload['max_tokens']??2048),'messages'=>$messages];
    if($system!=='')$body['system']=trim($system);
    if(isset($payload['temperature']))$body['temperature']=(float)$payload['temperature'];
    $r=ai_http('https://api.anthropic.com/v1/messages','POST',['Content-Type: application/json','x-api-key: '.$cfg['key'],'anthropic-version'=>'2023-06-01'],json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),180);
    $d=$r['data']; $text=''; foreach(($d['content']??[]) as $part){if(($part['type']??'')==='text')$text.=(string)($part['text']??'');}
    return ['id'=>$d['id']??null,'object'=>'chat.completion','model'=>$payload['model'],'choices'=>[['index'=>0,'message'=>['role'=>'assistant','content'=>$text],'finish_reason'=>'stop']],'usage'=>['prompt_tokens'=>(int)($d['usage']['input_tokens']??0),'completion_tokens'=>(int)($d['usage']['output_tokens']??0)]];
}

function ai_google_chat(array $cfg,array $payload): array {
    if($cfg['key']==='') throw new RuntimeException('Google API key is not configured.');
    $contents=[]; $system='';
    foreach(($payload['messages']??[]) as $m){
        $role=(string)($m['role']??'user'); $text=(string)($m['content']??'');
        if($role==='system'){$system.=$text."\n";continue;}
        $contents[]=['role'=>$role==='assistant'?'model':'user','parts'=>[['text'=>$text]]];
    }
    $body=['contents'=>$contents];
    if($system!=='')$body['systemInstruction']=['parts'=>[['text'=>trim($system)]]];
    if(isset($payload['temperature'])||isset($payload['max_tokens'])){
        $body['generationConfig']=[];
        if(isset($payload['temperature']))$body['generationConfig']['temperature']=(float)$payload['temperature'];
        if(isset($payload['max_tokens']))$body['generationConfig']['maxOutputTokens']=(int)$payload['max_tokens'];
    }
    $url=$cfg['base'].'/v1beta/models/'.rawurlencode((string)$payload['model']).':generateContent?key='.rawurlencode($cfg['key']);
    $r=ai_http($url,'POST',['Content-Type: application/json'],json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),180); $d=$r['data'];
    $text=(string)($d['candidates'][0]['content']['parts'][0]['text']??'');
    return ['id'=>null,'object'=>'chat.completion','model'=>$payload['model'],'choices'=>[['index'=>0,'message'=>['role'=>'assistant','content'=>$text],'finish_reason'=>'stop']],'usage'=>['prompt_tokens'=>(int)($d['usageMetadata']['promptTokenCount']??0),'completion_tokens'=>(int)($d['usageMetadata']['candidatesTokenCount']??0)]];
}


function generate_text(string $prompt): string {
    $model = (string)envv('AI_DEFAULT_MODEL','aitunnel-auto');
    $r = ai_chat([['role'=>'user','content'=>$prompt]], $model, .7, 2048);
    return (string)($r['choices'][0]['message']['content'] ?? '');
}

function generate_image(string $prompt,string $slug='image-default',array $options=[]): array {
    $m = ai_model_record($slug);
    $cfg = ai_provider_config((string)$m['provider']);
    if ($cfg['kind'] !== 'openai') throw new RuntimeException('Image adapter currently requires an OpenAI-compatible provider.');
    if ($cfg['key']==='') throw new RuntimeException('OpenAI API key is not configured.');
    $body = ['model'=>(string)$m['provider_model'], 'prompt'=>$prompt];
    $size=trim((string)($options['size']??'1024x1024'));
    $quality=trim((string)($options['quality']??'auto'));
    $n=max(1,min(10,(int)($options['quantity']??1)));
    if($size!=='')$body['size']=$size;
    if($quality!=='')$body['quality']=$quality;
    $body['n']=$n;
    if(!empty($options['transparent']))$body['background']='transparent';
    $r = ai_http($cfg['base'].'/images/generations','POST',['Content-Type: application/json','Authorization: Bearer '.$cfg['key']],json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),180);
    $items=[]; foreach(($r['data']??[]) as $item){$items[]=(string)($item['b64_json']??$item['url']??'');}
    return $items;
}

function generate_speech(string $text): string {
    $m = ai_model_record('speech-default'); $cfg=ai_provider_config((string)$m['provider']);
    if ($cfg['key']==='') throw new RuntimeException('OpenAI API key is not configured.');
    $body=['model'=>(string)$m['provider_model'],'input'=>$text,'voice'=>'alloy','response_format'=>'mp3'];
    $r=ai_http($cfg['base'].'/audio/speech','POST',['Content-Type: application/json','Authorization: Bearer '.$cfg['key']],json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),180);
    return (string)$r['raw'];
}

function transcribe_audio(string $tmpPath,string $filename): string {
    $m=ai_model_record('transcribe-default');$cfg=ai_provider_config((string)$m['provider']);
    if($cfg['key']==='') throw new RuntimeException('OpenAI API key is not configured.');
    $ch=curl_init($cfg['base'].'/audio/transcriptions');
    $post=['model'=>(string)$m['provider_model'],'file'=>new CURLFile($tmpPath,mime_content_type($tmpPath)?:'application/octet-stream',$filename)];
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_TIMEOUT=>180,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$cfg['key']]]);
    $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
    if($raw===false)throw new RuntimeException($err?:'Transcription request failed');
    $d=json_decode($raw,true);if($code>=400)throw new RuntimeException((string)($d['error']['message']??'Transcription provider error'));
    return (string)($d['text']??'');
}

function ai_model_record(string $slug): array {
    global $pdo;
    $s=$pdo->prepare('SELECT * FROM ai_models WHERE enabled=1 AND (slug=? OR provider_model=?) LIMIT 1'); $s->execute([$slug,$slug]); $m=$s->fetch();
    if(!$m) throw new RuntimeException('Model not found or disabled: '.$slug);
    return $m;
}
function ai_provider_configured(string $provider): bool {
    try { $c=ai_provider_config($provider); return trim((string)$c['key'])!==''; } catch(Throwable $e) { return false; }
}
function ai_chat(array $messages,string $slug,float $temperature=.7,?int $max=null): array {
    $m=ai_model_record($slug); $cfg=ai_provider_config((string)$m['provider']);
    $payload=['model'=>(string)($m['provider_model']??$slug),'messages'=>$messages,'temperature'=>$temperature]; if($max)$payload['max_tokens']=$max;
    return match($cfg['kind']) {'openai'=>ai_openai_chat($cfg,$payload),'anthropic'=>ai_anthropic_chat($cfg,$payload),'google'=>ai_google_chat($cfg,$payload),default=>throw new RuntimeException('Unsupported provider adapter')};
}
function ai_call_model(array $payload,string $slug): array {
    $m=ai_model_record($slug); $cfg=ai_provider_config((string)$m['provider']); $kind=$cfg['kind'];
    if($kind!=='openai') throw new RuntimeException('This endpoint is currently available for OpenAI-compatible providers only.');
    if($cfg['key']==='') throw new RuntimeException('Provider API key is not configured.');
    $payload['model']=(string)($m['provider_model']??$slug);
    $endpoint=(string)($payload['_endpoint']??'chat/completions'); unset($payload['_endpoint']);
    return ai_openai_chat($cfg,['model'=>$payload['model'],'messages'=>$payload['messages']??[],'temperature'=>$payload['temperature']??.7]);
}
function ai_models_local(): array { global $pdo; $rows=$pdo->query("SELECT slug,name,type,provider,provider_model,credit_cost,enabled,description FROM ai_models WHERE enabled=1 ORDER BY type,name")->fetchAll(); foreach($rows as &$r){$r['configured']=ai_provider_configured((string)$r['provider']);} unset($r); return $rows; }
