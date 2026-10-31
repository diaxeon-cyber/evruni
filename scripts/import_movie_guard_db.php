<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
global $pdo;
if (!$pdo) throw new RuntimeException('Database unavailable');

$pdo->exec("CREATE TABLE IF NOT EXISTS creator_film_filters (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 imdb_id VARCHAR(24) NULL,
 title VARCHAR(255) NOT NULL,
 title_key VARCHAR(255) NOT NULL,
 duration INT UNSIGNED NULL,
 version VARCHAR(64) NULL,
 source VARCHAR(120) NOT NULL DEFAULT 'local',
 license VARCHAR(120) NULL,
 scenes_json LONGTEXT NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_creator_film_title_version(title_key,version), KEY idx_creator_film_title(title_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function keyf(string $s):string{$s=mb_strtolower(trim($s),'UTF-8');$s=preg_replace('/[^\p{L}\p{N}]+/u',' ',$s)??$s;return trim(preg_replace('/\s+/u',' ',$s)??$s);}
function get_json(string $url):array{
 $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Accept: application/vnd.github+json','User-Agent: Evruni-Film-Guard/1.0']]);
 $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 if($raw===false||$code>=400)throw new RuntimeException('GitHub HTTP '.$code.' for '.$url);
 $j=json_decode($raw,true);if(!is_array($j))throw new RuntimeException('Invalid JSON from '.$url);return $j;
}

$tree=get_json('https://api.github.com/repos/arman-kh/MovieHavenDB/git/trees/master?recursive=1');
$items=$tree['tree']??[];$count=0;
$up=$pdo->prepare('INSERT INTO creator_film_filters(imdb_id,title,title_key,duration,version,source,license,scenes_json) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE imdb_id=VALUES(imdb_id),duration=VALUES(duration),source=VALUES(source),license=VALUES(license),scenes_json=VALUES(scenes_json),updated_at=CURRENT_TIMESTAMP');
foreach($items as $item){
 $path=(string)($item['path']??'');
 if(!preg_match('~^movies/(tt[0-9]+)\.json$~',$path,$m))continue;
 $data=get_json('https://raw.githubusercontent.com/arman-kh/MovieHavenDB/master/'.$path);
 $movie=$data[$m[1]]??null;if(!is_array($movie))continue;
 $title=trim((string)($movie['title']??''));if($title==='')continue;
 $scenes=is_array($movie['scenes']??null)?$movie['scenes']:[];
 $up->execute([$m[1],$title,keyf($title),isset($movie['duration'])?(int)$movie['duration']:null,(string)($movie['version']??'theatrical'),'MovieHavenDB','CC BY 4.0',json_encode($scenes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
 $count++;
}
echo "Imported $count film records\n";
