<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

function envv_local(string $key, mixed $default=null): mixed {
    $v=getenv($key);
    return ($v===false || $v==='') ? $default : $v;
}

function http_get_diag(string $url): array {
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_CONNECTTIMEOUT=>10,
        CURLOPT_TIMEOUT=>20,
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_USERAGENT=>'Evruni-AI-Diagnostic/1.0',
    ]);
    $body=curl_exec($ch);
    $error=curl_error($ch);
    $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $ip=(string)curl_getinfo($ch,CURLINFO_PRIMARY_IP);
    curl_close($ch);
    return ['http_code'=>$code,'primary_ip'=>$ip,'curl_error'=>$error?:null,'body_preview'=>mb_substr((string)$body,0,500)];
}

$openai=http_get_diag('https://api.openai.com/v1/models');

$result=[
    'ok'=>true,
    'php_version'=>PHP_VERSION,
    'curl_available'=>function_exists('curl_init'),
    'server_hostname'=>gethostname(),
    'openai_connection'=>$openai,
    'api_key_present'=>trim((string)envv_local('OPENAI_API_KEY',''))!=='',
    'api_key_length'=>strlen((string)envv_local('OPENAI_API_KEY','')),
    'note'=>'This diagnostic never returns the API key itself.',
];

echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
