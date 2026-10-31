<?php
declare(strict_types=1);
require_once ROOT_PATH.'/_private/lib/fx.php';

function ai_pricing_table(): void {
 global $pdo;
 $pdo->exec("CREATE TABLE IF NOT EXISTS ai_pricing (
 slug VARCHAR(80) NOT NULL PRIMARY KEY,
 input_usd DECIMAL(16,8) NULL,
 output_usd DECIMAL(16,8) NULL,
 request_usd DECIMAL(16,8) NULL,
 source_url TEXT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $pdo->exec("CREATE TABLE IF NOT EXISTS ai_pricing_settings (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 markup_percent DECIMAL(8,3) NOT NULL DEFAULT 20,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $pdo->exec("INSERT IGNORE INTO ai_pricing_settings(id,markup_percent) VALUES(1,20)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS ai_provider_settings (
   provider VARCHAR(80) NOT NULL PRIMARY KEY,
   api_url TEXT NULL,
   docs_url TEXT NULL,
   health_url TEXT NULL,
   load_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
   status VARCHAR(24) NOT NULL DEFAULT 'unknown',
   note VARCHAR(255) NULL,
   updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function ai_markup_percent(): float {
 global $pdo; ai_pricing_table();
 return max(0,min(500,(float)$pdo->query("SELECT markup_percent FROM ai_pricing_settings WHERE id=1")->fetchColumn()));
}
function ai_set_markup_percent(float $v): void {
 global $pdo; ai_pricing_table(); $v=max(0,min(500,$v));
 $s=$pdo->prepare("UPDATE ai_pricing_settings SET markup_percent=? WHERE id=1"); $s->execute([$v]);
}
function ai_pricing_seed(): array {
 return [
  'gpt-6-astra'=>[10.00,50.00,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-6.1-sol'=>[2.00,10.00,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-6-luna'=>[0.10,0.50,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-5.3-codex'=>[1.75,14.00,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-image-2.5-sunburst'=>[5.00,30.00,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-image-2.5-flare'=>[5.00,30.00,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-realtime-2.1'=>[4.00,24.00,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-realtime-2.1-mini'=>[0.60,2.40,null,'https://developers.openai.com/api/docs/pricing'],
  'gpt-transcribe'=>[null,null,0.0045,'https://developers.openai.com/api/docs/pricing'],
  'deepseek-flash'=>[0.30,1.20,null,'https://api-docs.deepseek.com/quick_start/pricing/'],
  'deepseek-v4-pro'=>[1.32,3.96,null,'https://api-docs.deepseek.com/quick_start/pricing/'],
  'qwen3.7-max'=>[1.65,4.951,null,'https://www.alibabacloud.com/help/en/model-studio/model-pricing'],
  'qwen3.7-plus'=>[0.40,1.60,null,'https://www.alibabacloud.com/help/en/model-studio/model-pricing'],
  'qwen3.8-flash'=>[0.113,0.382,null,'https://www.alibabacloud.com/help/en/model-studio/model-pricing'],
  'mistral-large-3'=>[0.50,1.50,null,'https://docs.mistral.ai/inference/pricing'],
  'mistral-medium-3.5'=>[1.50,7.50,null,'https://docs.mistral.ai/inference/pricing'],
  'mistral-small-4'=>[0.15,0.60,null,'https://docs.mistral.ai/inference/pricing'],
  'ministral-3-14b'=>[0.20,0.20,null,'https://docs.mistral.ai/inference/pricing'],
  'ministral-3-8b'=>[0.15,0.15,null,'https://docs.mistral.ai/inference/pricing'],
  'ministral-3-3b'=>[0.10,0.10,null,'https://docs.mistral.ai/inference/pricing'],
  'claude-opus-5-5'=>[4.00,20.00,null,'https://claude.com/pricing'],
  'claude-sonnet-5-5'=>[2.00,10.00,null,'https://claude.com/pricing'],
  'claude-haiku-5-5'=>[0.10,0.50,null,'https://claude.com/pricing'],
  'gemini-3.8-flash'=>[0.75,3.75,null,'https://ai.google.dev/gemini-api/docs/pricing'],
  'gemini-3.6-flash'=>[0.75,3.75,null,'https://ai.google.dev/gemini-api/docs/pricing'],
  'gemini-3.5-flash-lite'=>[0.30,2.50,null,'https://ai.google.dev/gemini-api/docs/pricing'],
  'gemini-3.1-flash-lite'=>[0.25,1.50,null,'https://ai.google.dev/gemini-api/docs/pricing'],
  'openai-gpt-4o-mini'=>[0.15,0.60,null,'https://developers.openai.com/api/docs/pricing'],
  'aitunnel-auto'=>[null,null,null,'https://Evruni/docs'],
  'image-default'=>[null,null,0.040,'https://developers.openai.com/api/docs/pricing'],
  'speech-default'=>[null,null,0.006,'https://developers.openai.com/api/docs/pricing'],
  'transcribe-default'=>[null,null,0.0045,'https://developers.openai.com/api/docs/pricing'],
 ];
}
function ai_catalog_seed(): void {
 global $pdo;
 // Seeding on every models request caused needless INSERT/UPDATE work.
 // Existing installations already have the catalog; seed only when it is empty.
 static $checked=false;
 if($checked) return;
 $checked=true;
 try{
   $count=(int)$pdo->query("SELECT COUNT(*) FROM ai_models")->fetchColumn();
   if($count>0) return;
 }catch(Throwable $e){ return; }
 $rows=[
  ['gpt-6-astra','GPT-6 Astra','text','openai','gpt-6-astra',1,'OpenAI flagship reasoning and coding'],
  ['gpt-6.1-sol','GPT-6.1 Sol','text','openai','gpt-6.1-sol',1,'OpenAI balanced flagship model'],
  ['gpt-6-luna','GPT-6 Luna','text','openai','gpt-6-luna',1,'OpenAI efficient high-volume model'],
  ['gpt-5.3-codex','GPT-5.3 Codex','text','openai','gpt-5.3-codex',1,'OpenAI coding model'],
  ['gpt-image-2.5-sunburst','GPT-Image-2.5 Sunburst','image','openai','gpt-image-2.5-sunburst',5,'OpenAI image generation and editing'],
  ['gpt-image-2.5-flare','GPT-Image-2.5 Flare','image','openai','gpt-image-2.5-flare',5,'OpenAI fast image generation'],
  ['gpt-realtime-2.1','GPT-Realtime-2.1','voice','openai','gpt-realtime-2.1',8,'OpenAI realtime audio'],
  ['gpt-realtime-2.1-mini','GPT-Realtime-2.1 Mini','voice','openai','gpt-realtime-2.1-mini',4,'OpenAI realtime audio mini'],
  ['gpt-transcribe','GPT-Transcribe','voice','openai','gpt-transcribe',1,'OpenAI speech transcription'],
  ['deepseek-flash','DeepSeek Flash','text','deepseek','deepseek-flash',1,'DeepSeek V4.1 Flash'],
  ['deepseek-v4-pro','DeepSeek V4 Pro','text','deepseek','deepseek-v4-pro',2,'DeepSeek V4 Pro'],
  ['qwen3.7-max','Qwen3.7 Max','text','qwen','qwen3.7-max',2,'Alibaba Qwen flagship'],
  ['qwen3.7-plus','Qwen3.7 Plus','text','qwen','qwen3.7-plus',1,'Alibaba Qwen balanced model'],
  ['qwen3.8-flash','Qwen3.8 Flash','text','qwen','qwen3.8-flash',1,'Alibaba Qwen fast model'],
  ['mistral-large-3','Mistral Large 3','text','mistral','mistral-large-3',1,'Mistral flagship model'],
  ['mistral-medium-3.5','Mistral Medium 3.5','text','mistral','mistral-medium-3.5',1,'Mistral medium model'],
  ['mistral-small-4','Mistral Small 4','text','mistral','mistral-small-4',1,'Mistral efficient model'],
  ['ministral-3-14b','Ministral 3 14B','text','mistral','ministral-3-14b',1,'Mistral compact model'],
  ['ministral-3-8b','Ministral 3 8B','text','mistral','ministral-3-8b',1,'Mistral compact model'],
  ['ministral-3-3b','Ministral 3 3B','text','mistral','ministral-3-3b',1,'Mistral compact model'],
  ['claude-opus-5-5','Claude Opus 5.5','text','anthropic','claude-opus-5-5',3,'Anthropic agentic coding and enterprise'],
  ['claude-sonnet-5-5','Claude Sonnet 5.5','text','anthropic','claude-sonnet-5-5',2,'Anthropic high-performance coding and agents'],
  ['claude-haiku-5-5','Claude Haiku 5.5','text','anthropic','claude-haiku-5-5',1,'Anthropic fast efficient model'],
  ['gemini-3.8-flash','Gemini 3.8 Flash','text','google','gemini-3.8-flash',1,'Google latest Flash model'],
  ['gemini-3.6-flash','Gemini 3.6 Flash','text','google','gemini-3.6-flash',1,'Google Flash model'],
  ['gemini-3.5-flash-lite','Gemini 3.5 Flash-Lite','text','google','gemini-3.5-flash-lite',1,'Google efficient high-throughput model'],
  ['gemini-3.1-flash-lite','Gemini 3.1 Flash-Lite','text','google','gemini-3.1-flash-lite',1,'Google cost-efficient model'],
  ['seedance-2-fast','Seedance 2.0 Fast','video','bytedance','seedance-2.0-fast',20,'ByteDance video generation'],
  ['kling-video-pro','Kling Video Pro','video','kling','kling-video-pro',20,'Kling video generation'],
  ['veo-video','Veo Video','video','google','veo-video',20,'Google video generation'],
  ['runway-video','Runway Video','video','runway','runway-video',20,'Runway video generation'],
  ['aitunnel-auto','Evruni Auto','text','aitunnel','auto',1,'Evruni automatic model routing'],
 ];
 $sql="INSERT IGNORE INTO ai_models(slug,name,type,provider,provider_model,credit_cost,enabled,description) VALUES(?,?,?,?,?,?,1,?)";
 $st=$pdo->prepare($sql);
 foreach($rows as $r)$st->execute($r);
 foreach(['deepseek-v4-flash','qwen-plus','mistral-large-4','gemini-3-8-flash'] as $legacy){$s=$pdo->prepare("UPDATE ai_models SET enabled=0 WHERE slug=?");$s->execute([$legacy]);}
}

function ai_pricing_refresh(bool $force=false): void {
 global $pdo; ai_pricing_table(); $seed=ai_pricing_seed();
 foreach($seed as $slug=>$r){
  $q=$pdo->prepare("SELECT updated_at FROM ai_pricing WHERE slug=?"); $q->execute([$slug]); $old=$q->fetchColumn();
  if(!$force && $old && (time()-strtotime((string)$old))<900) continue;
  $s=$pdo->prepare("INSERT INTO ai_pricing(slug,input_usd,output_usd,request_usd,source_url,updated_at) VALUES(?,?,?,?,?,CURRENT_TIMESTAMP)
  ON DUPLICATE KEY UPDATE input_usd=VALUES(input_usd),output_usd=VALUES(output_usd),request_usd=VALUES(request_usd),source_url=VALUES(source_url),updated_at=CURRENT_TIMESTAMP");
  $s->execute([$slug,$r[0],$r[1],$r[2],$r[3]]);
 }
}
function ai_pricing_auto_refresh(): void {
 try { ai_pricing_refresh(false); } catch(Throwable $e) {}
}
function ai_model_category(array $model): array {
 $type=strtolower(trim((string)($model['type']??'')));
 $slug=strtolower((string)($model['slug']??''));
 $name=strtolower((string)($model['name']??''));

 $map=[
  'text'=>['key'=>'text','label'=>'Текст и чат'],
  'image'=>['key'=>'image','label'=>'Изображения'],
  'video'=>['key'=>'video','label'=>'Видео'],
  'voice'=>['key'=>'voice','label'=>'Аудио и голос'],
  'research'=>['key'=>'research','label'=>'Исследования'],
  'document'=>['key'=>'document','label'=>'Документы'],
 ];
 if(isset($map[$type])) return $map[$type];

 if(str_contains($slug,'video') || str_contains($name,'video')) return ['key'=>'video','label'=>'Видео'];
 if(str_contains($slug,'image') || str_contains($name,'image')) return ['key'=>'image','label'=>'Изображения'];
 if(str_contains($slug,'audio') || str_contains($slug,'speech') || str_contains($slug,'realtime') || str_contains($slug,'transcrib') || str_contains($name,'audio') || str_contains($name,'voice')) return ['key'=>'voice','label'=>'Аудио и голос'];
 if(str_contains($slug,'research') || str_contains($name,'research')) return ['key'=>'research','label'=>'Исследования'];
 if(str_contains($slug,'document') || str_contains($name,'document')) return ['key'=>'document','label'=>'Документы'];

 return ['key'=>'text','label'=>'Текст и чат'];
}

function ai_pricing_for_models(array $models,string $currency='EUR'): array {
 global $pdo; ai_pricing_auto_refresh(); $markup=ai_markup_percent(); $currency=strtoupper($currency);
 $rows=$pdo->query("SELECT * FROM ai_pricing")->fetchAll(); $map=[];
 foreach($rows as $r) $map[$r['slug']]=$r;
 foreach($models as &$m){
  $cat=ai_model_category($m); $m['category_key']=$cat['key']; $m['category']=$cat['label'];
  $p=$map[$m['slug']]??null;
  if(!$p) continue;
  $m['price_input_usd']=$p['input_usd']!==null?(float)$p['input_usd']:null;
  $m['price_output_usd']=$p['output_usd']!==null?(float)$p['output_usd']:null;
  $m['price_request_usd']=$p['request_usd']!==null?(float)$p['request_usd']:null;
  $m['price_source']=$p['source_url']??null;
  $m['price_updated_at']=$p['updated_at']??null;
  $base=$m['price_request_usd']!==null?(float)$m['price_request_usd']:(((float)($m['price_input_usd']??0)+(float)($m['price_output_usd']??0))/2);
  $m['markup_percent']=$markup;
  $m['display_input_currency']=$m['price_input_usd']!==null?fx_convert((float)$m['price_input_usd']*(1+$markup/100),'USD',$currency):null;
  $m['display_output_currency']=$m['price_output_usd']!==null?fx_convert((float)$m['price_output_usd']*(1+$markup/100),'USD',$currency):null;
  $m['display_request_currency']=$m['price_request_usd']!==null?fx_convert((float)$m['price_request_usd']*(1+$markup/100),'USD',$currency):null;
  $m['display_unit_currency']=$base>0?fx_convert($base*(1+$markup/100),'USD',$currency):null;
  // Generation UI and the actual wallet charge share the model's credit_cost.
  // Token/request catalog prices are kept separate from generation prices.
  $type=strtolower((string)($m['type']??''));
  $generationBase=(in_array($type,['image','video'],true))
    ?ai_generation_quote_usd($m,['size'=>'1024x1024','quality'=>'auto','quantity'=>1]):0.0;
  $m['display_generation_currency']=($generationBase>0)
    ?fx_convert($generationBase,'USD',$currency):null;
  $m['generation_pricing']=[
    'unit'=>$type==='video'?'second':($type==='image'?'image':'request'),
    'base_quantity'=>$type==='video'?5:1,
    'base_cost_usd'=>$generationBase,
    'price_includes_markup'=>false,
  ];
  $m['display_currency']=$currency;
 }
 return $models;
}
function ai_generation_quote_usd(array $model,array $input=[]): float {
 $type=strtolower((string)($model['type']??''));
 $slug=strtolower((string)($model['slug']??$model['provider_model']??''));
 $base=max(0,(float)($model['credit_cost']??0));
 if($type==='image'){
  $size=strtolower((string)($input['size']??'auto'));
  $q=strtolower((string)($input['quality']??'auto'));
  $qty=max(1,min(10,(int)($input['quantity']??1)));

  /*
   * Image billing is not a flat token/request price.  For GPT Image 2.5,
   * output is billed by image tokens and changes with size/quality.  The
   * values below are an estimate for the UI/wallet quote; the provider's
   * published token rate is used rather than the old $5/M text-input rate.
   */
  if(strpos($slug,'gpt-image-2.5')!==false && (float)($model['price_output_usd']??0)>0){
   $tokenByQuality=[
    'low'=>196,
    'medium'=>1056,
    'high'=>4160,
    'xhigh'=>6240,
    'max'=>8320,
    'auto'=>1056,
   ];
   $tokens=(float)($tokenByQuality[$q]??1056);
   if(preg_match('/^\s*(\d+)x(\d+)\s*$/',$size,$mm)){
    $pixels=max(1,(int)$mm[1]*(int)$mm[2]);
    $tokens*=max(0.625,min(8.0,$pixels/(1024*1024)));
   }
   $outputCost=$tokens*((float)$model['price_output_usd'])/1000000.0;
   return round($outputCost*$qty,6);
  }

  $sm=1.0;
  if(preg_match('/^\s*(\d+)x(\d+)\s*$/',$size,$mm)){
   $pixels=max(1,(int)$mm[1]*(int)$mm[2]);
   $sm=max(0.75,min(4.0,$pixels/(1024*1024)));
  }
  $qm=['low'=>0.5,'medium'=>1.0,'high'=>2.0,'xhigh'=>3.0,'max'=>4.0,'standard'=>1.0,'auto'=>1.0][$q]??1.0;
  return round($base*$sm*$qm*$qty,6);
 }
 if($type==='video'){
  $r=strtolower((string)($input['resolution']??''));
  $d=(int)preg_replace('/[^0-9]/','',(string)($input['duration']??'5'));
  $fps=(int)preg_replace('/[^0-9]/','',(string)($input['fps']??'24'));
  $audio=!empty($input['audio']);
  $rm=preg_match('/1080|1920|1440|2k|4k/',$r)?1.5:1.0;
  $fm=$fps>=30?1.1:1.0;
  $am=$audio?1.1:1.0;
  $perSecond=$base/5.0;
  return round($perSecond*max(1,$d)*$rm*$fm*$am,6);
 }
 return $base;
}

function ai_actual_cost_usd(array $model,array $usage=[]): float {
 $in=(float)($model['price_input_usd']??0); $out=(float)($model['price_output_usd']??0); $req=(float)($model['price_request_usd']??0);
 $it=(int)($usage['prompt_tokens']??$usage['input_tokens']??0); $ot=(int)($usage['completion_tokens']??$usage['output_tokens']??0);
 if($in>0||$out>0) return ($it/1000000*$in)+($ot/1000000*$out);
 return $req;
}
function ai_customer_cost_usd(array $model,array $usage=[]): float {
 return ai_actual_cost_usd($model,$usage)*(1+ai_markup_percent()/100);
}
