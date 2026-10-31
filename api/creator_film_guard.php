<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';

$u=require_auth();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

global $pdo;
if (!$pdo) { http_response_code(503); echo json_encode(['error'=>'Database unavailable'],JSON_UNESCAPED_UNICODE); exit; }

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

function norm_film(string $s): string {
 $s=trim(mb_strtolower($s,'UTF-8'));
 $s=preg_replace('/[^\p{L}\p{N}]+/u',' ', $s) ?? $s;
 return trim(preg_replace('/\s+/u',' ',$s) ?? $s);
}
function j(array $x,int $status=200): never {
 http_response_code($status); echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

$title=trim((string)($_GET['title'] ?? ''));
if ($title==='') j(['error'=>'title required'],422);
$key=norm_film($title);

$st=$pdo->prepare('SELECT id,imdb_id,title,duration,version,source,license,scenes_json FROM creator_film_filters WHERE title_key LIKE ? ORDER BY CHAR_LENGTH(title_key) ASC LIMIT 5');
$st->execute(['%'.$key.'%']);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
 $tokens=array_values(array_filter(explode(' ',$key),fn($x)=>mb_strlen($x,'UTF-8')>=3));
 if ($tokens) {
   $conds=[];$args=[];
   foreach($tokens as $t){$conds[]='title_key LIKE ?';$args[]='%'.$t.'%';}
   $st=$pdo->prepare('SELECT id,imdb_id,title,duration,version,source,license,scenes_json FROM creator_film_filters WHERE '.implode(' AND ',$conds).' ORDER BY CHAR_LENGTH(title_key) ASC LIMIT 5');
   $st->execute($args);$rows=$st->fetchAll(PDO::FETCH_ASSOC);
 }
}

$films=[];
foreach($rows as $r){
 $sc=json_decode((string)$r['scenes_json'],true);
 if(!is_array($sc))$sc=[];
 usort($sc,fn($a,$b)=>(float)($a['start']??0)<=>(float)($b['start']??0));
 $films[]=[
  'id'=>(int)$r['id'],'imdb_id'=>$r['imdb_id'],'title'=>$r['title'],'duration'=>$r['duration']!==null?(int)$r['duration']:null,
  'version'=>$r['version'],'source'=>$r['source'],'license'=>$r['license'],'scenes'=>$sc
 ];
}

j(['ok'=>true,'query'=>$title,'count'=>count($films),'films'=>$films,'mode'=>'database_first']);
