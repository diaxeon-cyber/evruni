<?php
declare(strict_types=1);

/*
 * Evruni Jino layout:
 *   public web root = /domains/evruni.com/
 *   private code   = /domains/evruni.com/_private/
 */
const ROOT_PATH = __DIR__ . '/../..';

function load_dotenv_file(string $file): void {
    if (!is_file($file) || !is_readable($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line=trim($line); if($line===''||str_starts_with($line,'#')||!str_contains($line,'='))continue;
        [$key,$value]=array_map('trim',explode('=',$line,2)); if($key==='')continue;
        if(strlen($value)>=2){$first=$value[0];$last=$value[strlen($value)-1];if(($first==='"'&&$last==='"')||($first==="'"&&$last==="'"))$value=substr($value,1,-1);}
        if(getenv($key)===false)putenv($key.'='.$value);
    }
}
load_dotenv_file(ROOT_PATH.'/.env');
function envv(string $key,mixed $default=null): mixed { $v=getenv($key);return ($v===false||$v==='')?$default:$v; }
date_default_timezone_set('UTC');
$appKey=trim((string)envv('APP_KEY',''));
if($appKey==='' || strlen($appKey)<32 || $appKey==='dev-only-change-me'){
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'Server configuration error: APP_KEY is missing or insecure.'],JSON_UNESCAPED_UNICODE);
    exit;
}
$config=[
  'app_url'=>rtrim((string)envv('APP_URL','https://evruni.com'),'/'),
  'app_key'=>$appKey,
  'demo_mode'=>filter_var(envv('DEMO_MODE','false'),FILTER_VALIDATE_BOOLEAN),
  'upload_max_mb'=>(int)envv('UPLOAD_MAX_MB',25),
];
$db=[
  'host'=>envv('DB_HOST','127.0.0.1'),
  'port'=>envv('DB_PORT','3306'),
  'name'=>envv('DB_NAME',envv('DB_DATABASE','evruni')),
  'user'=>envv('DB_USER',envv('DB_USERNAME','root')),
  'pass'=>envv('DB_PASS',envv('DB_PASSWORD','')),
];
try{
    $pdo=new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",$db['user'],$db['pass'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
}catch(Throwable $e){$pdo=null;}
