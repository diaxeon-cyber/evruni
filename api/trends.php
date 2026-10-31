<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth();

$cacheDir=ROOT_PATH.'/_private/cache';
$cacheFile=$cacheDir.'/trend_feed_v2.json';
$ttl=12*60*60;
$force=isset($_GET['refresh']) && $_GET['refresh']==='1';

$fallback=[
 ['id'=>'hit-road','title'=>'Hit the Road Dude','meta'=>'1 фото · короткий ролик','tag'=>'↗ Растёт','desc'=>'Автомобиль, дорожная сцена и реплика героя.','cls'=>'trend-bulin','source'=>'https://stepintoscene.com/trends','prompt'=>'Создай вертикальный короткий ролик в трендовом формате Hit the Road Dude: герой или питомец за рулём старого пикапа, естественная дорожная камера, живое движение и короткий комедийный финал. Используй загруженное фото.'],
 ['id'=>'bulin','videoId'=>'7691875916644994326','title'=>'Bulin 47','meta'=>'1 фото · 10 сек','tag'=>'↗ Растёт','desc'=>'Красный свет, толпа и уличный freestyle.','cls'=>'trend-bulin','source'=>'https://www.remakeyourmeme.com/templates/bulin-47-ai-trend/','prompt'=>'Создай вертикальный street freestyle ролик в стиле Bulin 47: красные огни, толпа, человек с телефоном и колонкой, герой в центре исполняет энергичный freestyle, динамичная камера и короткие монтажные склейки. Используй загруженное фото как героя.'],
 ['id'=>'hotel','title'=>'AI Hotel Lobby','meta'=>'1–2 фото · короткий ролик','tag'=>'🔥 Популярен','desc'=>'Два героя, микрофон и яркая студийная сцена.','cls'=>'trend-burning','source'=>'https://stepintoscene.com/trends','prompt'=>'Создай вертикальный AI-ролик в формате Hotel Lobby: два героя по очереди у одного микрофона, яркий оранжевый фон, естественные движения, смена крупных планов и короткий музыкальный финал. Используй загруженные фото.'],
 ['id'=>'ghost','title'=>'Ghost Filter Prank','meta'=>'1 фото · Halloween','tag'=>'🎃 Растёт','desc'=>'Призрак появляется за героем и создаёт резкий финал.','cls'=>'trend-halloween','source'=>'https://stepintoscene.com/trends','prompt'=>'Создай вертикальный Halloween ролик: герой стоит в обычной сцене, на заднем плане постепенно появляется длинноволосый призрак, камера медленно приближается, затем короткий эффектный scare-финал. Используй загруженное фото.'],
 ['id'=>'zombie','videoId'=>'7691835688706116878','title'=>'Zombie Love Story','meta'=>'2 фото · 10–15 сек','tag'=>'🔥 В тренде','desc'=>'Тёмная сцена → превращение → объятие → тёплый финал.','cls'=>'trend-zombie','source'=>'https://aizombiecouple.org/','prompt'=>'Создай вертикальный TikTok/Shorts ролик в стиле Zombie Love Story: кинематографичная сцена превращения, эмоциональная встреча, плавная камера, объятие и тёплый финал. Используй загруженные фото как героев.'],
 ['id'=>'burning','videoId'=>'7694447027471600909','title'=>'Burning Bridges','meta'=>'1 фото · короткий ролик','tag'=>'🔥 Популярен','desc'=>'Вечеринка, бокал, камера и уверенный жест.','cls'=>'trend-burning','source'=>'https://jumponthetrend.app/trends/burning-bridges-ai-trend/','prompt'=>'Создай вертикальный короткий ролик в стиле Burning Bridges: роскошная вечеринка под хрустальными люстрами, герой в центре с бокалом, уверенный взгляд, жест рукой и плавное приближение камеры. Используй загруженное фото как героя.'],
 ['id'=>'raindance','videoId'=>'7689332850398678304','title'=>'Raindance','meta'=>'1–2 фото · 10–15 сек','tag'=>'✨ Эстетика','desc'=>'Дождь, золотой час и мягкий cinematic переход.','cls'=>'trend-raindance','source'=>'https://www.remakeyourmeme.com/templates/raindance/','prompt'=>'Создай вертикальный cinematic ролик в стиле Raindance: герой под лёгким дождём, золотой закат, ветер в одежде, плавный dolly-in и атмосферный переход между планами. Используй загруженное фото как героя.']
 ,['id'=>'turn-lights-off','title'=>'Turn the Lights Off','meta'=>'1 фото · 10 сек','tag'=>'🔥 Hot','desc'=>'Танцевальная сцена с резким световым переходом.','cls'=>'trend-burning','source'=>'https://jumponthetrend.app/trends/youtube-shorts/','people'=>1,'labels'=>['Фото героя'],'type'=>'face','prompt'=>'Создай вертикальный танцевальный short 9:16: герой оказывается на танцполе, закрывает глаза, поднимает руки, свет резко меняется и камера делает плавный push-in.']
 ,['id'=>'clapping-cat','title'=>'Clapping Cat','meta'=>'1 питомец · 5 сек','tag'=>'🐾 Pet','desc'=>'Короткий мем с питомцем и бесшовным loop.','cls'=>'trend-halloween','source'=>'https://jumponthetrend.app/trends/youtube-shorts/','people'=>1,'labels'=>['Фото питомца'],'type'=>'pet','prompt'=>'Создай вертикальный мемный ролик 9:16 на 5 секунд: питомец смотрит в камеру, улыбается и хлопает лапами. Сделай бесшовный loop.']
 ,['id'=>'knight-cat','title'=>'Knight Cat','meta'=>'1 питомец · 10 сек','tag'=>'🐾 Pet','desc'=>'Питомец в эпическом фэнтези-образе.','cls'=>'trend-zombie','source'=>'https://jumponthetrend.app/trends/youtube-shorts/','people'=>1,'labels'=>['Фото питомца'],'type'=>'pet','prompt'=>'Создай вертикальный fantasy short 9:16: питомец превращается в маленького рыцаря, держит меч, ветер двигает шерсть и плащ. Сохрани внешность питомца.']
 ,['id'=>'ai-crying','title'=>'AI Crying','meta'=>'1 фото · 10 сек','tag'=>'😢 Reaction','desc'=>'Эмоциональная реакция крупным планом.','cls'=>'trend-raindance','source'=>'https://videosi.io/trending','people'=>1,'labels'=>['Фото лица'],'type'=>'face','prompt'=>'Создай вертикальный emotional short 9:16: крупный план лица, эмоция постепенно усиливается до слёз, реалистичная мимика. Сохрани идентичность человека.']
 ,['id'=>'toc-toc','title'=>'Toc Toc Dance','meta'=>'1–2 лица · 10 сек','tag'=>'💃 Dance','desc'=>'Короткая синхронная танцевальная связка.','cls'=>'trend-bulin','source'=>'https://jumponthetrend.app/trends/youtube-shorts/','people'=>2,'labels'=>['Лицо 1','Лицо 2'],'type'=>'faces','prompt'=>'Создай вертикальный dance short 9:16: один или два героя выполняют короткую синхронную связку, камера двигается в ритм, финал возвращается к исходной позе.']
 ,['id'=>'dancing-dog','title'=>'Dancing Dog AI','meta'=>'1 питомец · 10 сек','tag'=>'🐶 New','desc'=>'Питомец становится главным танцевальным героем.','cls'=>'trend-bulin','source'=>'https://videosi.io/trending','people'=>1,'labels'=>['Фото питомца'],'type'=>'pet','prompt'=>'Создай вертикальный dance short 9:16 с питомцем: он встаёт, делает забавную танцевальную связку, камера следует за движением, финал — короткая поза в камеру.']
 ,['id'=>'jean-phil','title'=>'Jean Phil AI','meta'=>'1 фото · 9 сек','tag'=>'🆕 New','desc'=>'Быстрый AI-персонажный формат.','cls'=>'trend-raindance','source'=>'https://videosi.io/trending','people'=>1,'labels'=>['Фото лица'],'type'=>'face','prompt'=>'Создай вертикальный AI character short 9:16: герой быстро меняет окружение и образ, сохраняя лицо, затем делает короткое уверенное движение в камеру.']
 ,['id'=>'on-radar','title'=>'On The Radar','meta'=>'1–2 лица · 14 сек','tag'=>'🎤 Music','desc'=>'Уличная микрофонная сцена freestyle.','cls'=>'trend-bulin','source'=>'https://jumponthetrend.app/trends/youtube-shorts/','people'=>2,'labels'=>['Лицо 1','Лицо 2'],'type'=>'faces','prompt'=>'Создай вертикальный freestyle short 9:16: один или два героя в компактной музыкальной будке, микрофон в центре, динамичная реакция камеры.']
 ,['id'=>'rumplestiltskin','title'=>'Rumpelstiltskin Dance','meta'=>'1–2 лица · 9 сек','tag'=>'↗ Rising','desc'=>'Странный сказочный танец.','cls'=>'trend-zombie','source'=>'https://jumponthetrend.app/trends/youtube-shorts/','people'=>2,'labels'=>['Лицо 1','Лицо 2'],'type'=>'faces','prompt'=>'Создай вертикальный fantasy short 9:16: сказочный персонаж выполняет странный короткий танец, камера чуть дрожит как старое кино, второй герой может появиться в финале.']

];

function save_cache(string $file,array $data): void { @mkdir(dirname($file),0700,true); @file_put_contents($file,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX); }
function read_cache(string $file): ?array { if(!is_file($file))return null; $raw=@file_get_contents($file); $d=json_decode((string)$raw,true); return is_array($d)?$d:null; }

$cached=read_cache($cacheFile);
if(!$force && $cached && isset($cached['saved_at']) && (time()-(int)$cached['saved_at']<$ttl) && !empty($cached['trends'])){
  json_out(['trends'=>$cached['trends'],'updated_at'=>$cached['updated_at']??date('Y-m-d H:i',(int)$cached['saved_at'])]);
  exit;
}

$trends=$fallback;
$updatedAt=date('Y-m-d H:i');
$url='https://stepintoscene.com/trends';
$body='';
if(function_exists('curl_init')){
  $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>5,CURLOPT_USERAGENT=>'Evruni Trend Bot/1.0']); $body=(string)curl_exec($ch); curl_close($ch);
}
if($body!==''){
  $text=html_entity_decode(strip_tags($body),ENT_QUOTES|ENT_HTML5,'UTF-8');
  $map=[
    'Hit the Road Dude'=>['id'=>'hit-road','meta'=>'1 фото · короткий ролик','tag'=>'↗ Растёт','desc'=>'Автомобиль, дорожная сцена и реплика героя.','cls'=>'trend-bulin','source'=>$url],
    'Bulin 47 freestyle'=>['id'=>'bulin','videoId'=>'7691875916644994326','meta'=>'1 фото · 10 сек','tag'=>'↗ Растёт','desc'=>'Красный свет, толпа и уличный freestyle.','cls'=>'trend-bulin','source'=>'https://www.remakeyourmeme.com/templates/bulin-47-ai-trend/'],
    'AI generated hotel lobby'=>['id'=>'hotel','meta'=>'1–2 фото · короткий ролик','tag'=>'🔥 Популярен','desc'=>'Два героя, микрофон и яркая студийная сцена.','cls'=>'trend-burning','source'=>$url],
    'Ghost filter prank'=>['id'=>'ghost','meta'=>'1 фото · Halloween','tag'=>'🎃 Растёт','desc'=>'Призрак появляется за героем и создаёт резкий финал.','cls'=>'trend-halloween','source'=>$url],
    'Day of the Dead pet video'=>['id'=>'day-dead','meta'=>'1 фото · сезонный','tag'=>'🌙 Сезонный','desc'=>'Питомец проходит по яркому мосту из цветов.','cls'=>'trend-raindance','source'=>$url],
    'AI zombie love story'=>['id'=>'zombie','videoId'=>'7691835688706116878','meta'=>'2 фото · 10–15 сек','tag'=>'🔥 В тренде','desc'=>'Тёмная сцена → превращение → объятие → тёплый финал.','cls'=>'trend-zombie','source'=>'https://aizombiecouple.org/'],
    'Burning Bridges AI'=>['id'=>'burning','videoId'=>'7694447027471600909','meta'=>'1 фото · короткий ролик','tag'=>'🔥 Популярен','desc'=>'Вечеринка, бокал, камера и уверенный жест.','cls'=>'trend-burning','source'=>'https://jumponthetrend.app/trends/burning-bridges-ai-trend/'],
    'Raindance'=>['id'=>'raindance','videoId'=>'7689332850398678304','meta'=>'1–2 фото · 10–15 сек','tag'=>'✨ Эстетика','desc'=>'Дождь, золотой час и мягкий cinematic переход.','cls'=>'trend-raindance','source'=>'https://www.remakeyourmeme.com/templates/raindance/']
  ];
  $found=[];
  foreach($map as $name=>$base){
    if(stripos($text,$name)!==false){
      $prompt='Создай вертикальный короткий AI-ролик в формате '.$name.'. Сохрани характерное движение, композицию, работу камеры и динамику тренда, но создай новую сцену на основе загруженного пользователем фото. Не копируй исходный ролик покадрово.';
      $base['title']=$name; $base['prompt']=$prompt; $found[]=$base;
    }
  }
  if(count($found)>=3){$trends=$found;$updatedAt=date('Y-m-d H:i');}
}
$data=['saved_at'=>time(),'updated_at'=>$updatedAt,'trends'=>$trends];
save_cache($cacheFile,$data);
json_out(['trends'=>$trends,'updated_at'=>$updatedAt]);
