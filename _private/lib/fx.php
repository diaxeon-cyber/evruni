<?php
declare(strict_types=1);
function fx_rates(): array {
 $file=ROOT_PATH.'/_private/storage/fx_rates.json';$ttl=3600;
 if(is_file($file)&&(time()-(int)filemtime($file))<$ttl){$c=json_decode((string)file_get_contents($file),true);if(is_array($c['rates']??null)&&count($c['rates'])>=4)return $c['rates'];}
 $ch=curl_init((string)envv('FX_API_URL','https://open.er-api.com/v6/latest/EUR'));curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_USERAGENT=>'Evruni/4.0']);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 $d=json_decode((string)$raw,true);if($code>=200&&$code<300&&is_array($d['rates']??null)){$r=['EUR'=>1.0];foreach(['USD','GBP','RUB'] as $c)if(isset($d['rates'][$c])&&(float)$d['rates'][$c]>0)$r[$c]=(float)$d['rates'][$c];if(count($r)===4){@file_put_contents($file,json_encode(['updated_at'=>time(),'rates'=>$r],JSON_UNESCAPED_SLASHES),LOCK_EX);return $r;}}
 if(is_file($file)){$c=json_decode((string)file_get_contents($file),true);if(is_array($c['rates']??null)&&count($c['rates'])>=4)return $c['rates'];}
 throw new RuntimeException('Курс валют временно недоступен');
}
function fx_convert(float $amount,string $from,string $to): float{$from=strtoupper($from);$to=strtoupper($to);if($from===$to)return $amount;$r=fx_rates();if(!isset($r[$from],$r[$to]))throw new RuntimeException('Неподдерживаемая валюта');return $amount/(float)$r[$from]*(float)$r[$to];}
function fx_symbol(string $c): string{return ['EUR'=>'€','USD'=>'$','GBP'=>'£','RUB'=>'₽'][strtoupper($c)]??strtoupper($c);}
