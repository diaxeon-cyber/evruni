<?php
declare(strict_types=1);
require_once ROOT_PATH . '/_private/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('evruni_session');
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    ini_set('session.cookie_httponly','1');
    ini_set('session.cookie_secure',(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')?'1':'0');
    ini_set('session.cookie_samesite','Lax');
    $sessionDomain = '';
    $sessionHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $sessionHost = preg_replace('/:\d+$/','',$sessionHost);
    if ($sessionHost === 'evruni.com' || str_ends_with($sessionHost, '.evruni.com')) {
        $sessionDomain = '.evruni.com';
    }
    session_set_cookie_params([
      'httponly'=>true,'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
      'samesite'=>'Lax','path'=>'/','domain'=>$sessionDomain
    ]);
    session_start();
}

function json_out(array $data, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function require_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''),(string)$token)) json_out(['error'=>'CSRF validation failed'],419);
}
function current_user(): ?array {
    global $pdo;
    if (!$pdo || empty($_SESSION['uid'])) return null;
    $s=$pdo->prepare("SELECT * FROM users WHERE id=? AND is_active=1");
    $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function require_auth(): array {
    $u=current_user();
    if (!$u) json_out(['error'=>'Authentication required'],401);
    return $u;
}
function admin_security_check(): void {
    // Optional IP allowlist. Leave empty to allow any IP (useful with dynamic IPs).
    $allow=trim((string)envv('ADMIN_IP_ALLOWLIST',''));
    if($allow!==''){
        $allowed=array_values(array_filter(array_map('trim',preg_split('/[,\\s]+/',$allow))));
        $ip=(string)($_SERVER['REMOTE_ADDR']??'');
        $ok=false;
        foreach($allowed as $rule){
            if($rule===$ip){$ok=true;break;}
            if(str_contains($rule,'/')){
                [$net,$bits]=array_pad(explode('/',$rule,2),2,null);
                if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) && filter_var($net,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
                    $bits=(int)$bits;
                    if($bits>=0 && $bits<=32){
                        $ipn=ip2long($ip); $netn=ip2long($net);
                        $mask=$bits===0?0:(-1 << (32-$bits));
                        if(($ipn & $mask)===($netn & $mask)){$ok=true;break;}
                    }
                }
            }
        }
        if(!$ok) json_out(['error'=>'Admin access restricted'],403);
    }
}
function require_admin(): array {
    $u=require_auth();
    if ($u['role'] !== 'admin') json_out(['error'=>'Admin access required'],403);
    admin_security_check();
    $now=time();
    $last=(int)($_SESSION['admin_last_seen']??0);
    if($last>0 && ($now-$last)>1800){
        $_SESSION=[];
        session_regenerate_id(true);
        json_out(['error'=>'Admin session expired'],401);
    }
    if($last===0) $_SESSION['admin_started']=$now;
    if(!empty($_SESSION['admin_started']) && ($now-(int)$_SESSION['admin_started'])>43200){
        $_SESSION=[];
        session_regenerate_id(true);
        json_out(['error'=>'Admin session expired'],401);
    }
    $_SESSION['admin_last_seen']=$now;
    return $u;
}
function audit(string $action, ?int $uid=null, array $meta=[]): void {
    global $pdo;
    if (!$pdo) return;
    $s=$pdo->prepare("INSERT INTO audit_log(user_id,action,ip,user_agent,meta) VALUES(?,?,?,?,?)");
    $s->execute([$uid,$action,$_SERVER['REMOTE_ADDR']??null,substr($_SERVER['HTTP_USER_AGENT']??'',0,255),json_encode($meta,JSON_UNESCAPED_UNICODE)]);
}
function rate_limit(string $key,int $max=30,int $window=60): void {
    global $pdo;
    if (!$pdo) return;
    $bucket=hash_hmac('sha256',$key . '|' . ($_SERVER['REMOTE_ADDR']??''),$GLOBALS['config']['app_key']);
    $now=time();
    $s=$pdo->prepare("SELECT * FROM rate_limits WHERE bucket=? FOR UPDATE");
    $pdo->beginTransaction();
    $s->execute([$bucket]); $r=$s->fetch();
    if (!$r || (int)$r['reset_at'] <= $now) {
        $pdo->prepare("REPLACE INTO rate_limits(bucket,hits,reset_at) VALUES(?,?,?)")->execute([$bucket,1,$now+$window]);
        $pdo->commit(); return;
    }
    if ((int)$r['hits'] >= $max) { $pdo->commit(); json_out(['error'=>'Too many requests'],429); }
    $pdo->prepare("UPDATE rate_limits SET hits=hits+1 WHERE bucket=?")->execute([$bucket]);
    $pdo->commit();
}

function captcha_enabled(): bool {
    return filter_var(envv('TURNSTILE_ENABLED','false'), FILTER_VALIDATE_BOOLEAN)
        && (string)envv('TURNSTILE_SITE_KEY','') !== ''
        && (string)envv('TURNSTILE_SECRET_KEY','') !== '';
}
function verify_turnstile(string $token): bool {
    if (!captcha_enabled()) return true;
    if ($token==='') return false;
    $secret=(string)envv('TURNSTILE_SECRET_KEY','');
    $payload=http_build_query([
        'secret'=>$secret,
        'response'=>$token,
        'remoteip'=>$_SERVER['REMOTE_ADDR']??''
    ]);
    $ctx=stream_context_create(['http'=>[
        'method'=>'POST',
        'header'=>"Content-Type: application/x-www-form-urlencoded\r\n",
        'content'=>$payload,
        'timeout'=>5
    ]]);
    $raw=@file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify',false,$ctx);
    if($raw===false)return false;
    $data=json_decode($raw,true);
    return is_array($data) && !empty($data['success']);
}
function oauth_provider_config(string $provider): ?array {
    $map=[
        'google'=>[
            'client_id'=>(string)envv('GOOGLE_OAUTH_CLIENT_ID',''),
            'client_secret'=>(string)envv('GOOGLE_OAUTH_CLIENT_SECRET',''),
            'authorize'=>'https://accounts.google.com/o/oauth2/v2/auth',
            'token'=>'https://oauth2.googleapis.com/token',
            'scope'=>'openid email profile'
        ],
        'github'=>[
            'client_id'=>(string)envv('GITHUB_OAUTH_CLIENT_ID',''),
            'client_secret'=>(string)envv('GITHUB_OAUTH_CLIENT_SECRET',''),
            'authorize'=>'https://github.com/login/oauth/authorize',
            'token'=>'https://github.com/login/oauth/access_token',
            'scope'=>'read:user user:email'
        ],
    ];
    if(!isset($map[$provider]) || $map[$provider]['client_id']==='' || $map[$provider]['client_secret']==='') return null;
    return $map[$provider];
}
function oauth_callback_url(string $provider): string {
    return rtrim((string)envv('APP_URL','https://evruni.com'),'/').'/api/auth.php?action=oauth_callback&provider='.rawurlencode($provider);
}
function oauth_http_post(string $url,array $data,array $headers=[]): ?array {
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query($data),
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_TIMEOUT=>10,
        CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json'],$headers),
    ]);
    $raw=curl_exec($ch);
    curl_close($ch);
    if(!is_string($raw)||$raw==='')return null;
    $d=json_decode($raw,true);
    return is_array($d)?$d:null;
}
function oauth_http_get(string $url,array $headers=[]): ?array {
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_TIMEOUT=>10,
        CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json'],$headers),
    ]);
    $raw=curl_exec($ch);
    curl_close($ch);
    if(!is_string($raw)||$raw==='')return null;
    $d=json_decode($raw,true);
    return is_array($d)?$d:null;
}
function oauth_find_or_create_user(string $provider,string $providerId,string $email,string $name=''): ?int {
    global $pdo;
    $email=strtolower(trim($email));
    if($providerId==='')return null;
    $pdo->beginTransaction();
    try{
        $st=$pdo->prepare("SELECT user_id FROM oauth_accounts WHERE provider=? AND provider_user_id=? LIMIT 1");
        $st->execute([$provider,$providerId]);
        $uid=(int)($st->fetchColumn()?:0);
        if($uid<1 && $email!==''){
            $st=$pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $st->execute([$email]);
            $uid=(int)($st->fetchColumn()?:0);
        }
        $created=false;
        if($uid<1){
            if($email==='') throw new RuntimeException('Provider did not return an email');
            $safeName=trim($name);
            if($safeName==='')$safeName=ucfirst(strtok($email,'@'));
            $st=$pdo->prepare("INSERT INTO users(email,password_hash,name,credits) VALUES(?,?,?,?)");
            $st->execute([$email,password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),$safeName,0]);
            $uid=(int)$pdo->lastInsertId();
            $created=true;
        }
        $st=$pdo->prepare("INSERT INTO oauth_accounts(user_id,provider,provider_user_id,email,display_name) VALUES(?,?,?,?,?)
          ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),email=VALUES(email),display_name=VALUES(display_name)");
        $st->execute([$uid,$provider,$providerId,$email,$name]);
        $pdo->commit();
        if($created) attach_referral_to_new_user($uid);
        return $uid;
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        return null;
    }
}


function referral_settings(): array {
    global $pdo;
    try {
        $r=$pdo->query("SELECT * FROM referral_settings WHERE id=1 LIMIT 1")->fetch();
        return $r ?: ['enabled'=>1,'commission_percent'=>10,'cookie_days'=>30];
    } catch(Throwable $e) { return ['enabled'=>0,'commission_percent'=>0,'cookie_days'=>30]; }
}
function referral_cookie_payload(): array {
    $raw=(string)($_COOKIE['evruni_referral']??'');
    if($raw==='')return ['code'=>'','click_id'=>0];
    $parts=explode('|',$raw,2);
    return ['code'=>preg_replace('/[^A-Za-z0-9_-]/','',(string)($parts[0]??'')),'click_id'=>(int)($parts[1]??0)];
}
function ensure_referral_code(int $uid): string {
    global $pdo;
    try {
        $st=$pdo->prepare("SELECT code FROM referral_codes WHERE user_id=? LIMIT 1");$st->execute([$uid]);$existing=(string)($st->fetchColumn()?:'');
        if($existing!=='')return $existing;
        do {
          $code=strtoupper(substr(base_convert((string)$uid,10,36),0,8).strtoupper(bin2hex(random_bytes(3))));$code=substr($code,0,16);
          $q=$pdo->prepare("SELECT 1 FROM referral_codes WHERE code=? LIMIT 1");$q->execute([$code]);
        } while($q->fetchColumn());
        $pdo->prepare("INSERT INTO referral_codes(user_id,code) VALUES(?,?)")->execute([$uid,$code]);
        return $code;
    } catch(Throwable $e) { return ''; }
}
function capture_referral_visit(): void {
    global $pdo;
    $code=trim((string)($_GET['ref']??''));
    if($code==='' || !preg_match('/^[A-Za-z0-9_-]{4,32}$/',$code))return;
    try{
        $st=$pdo->prepare("SELECT user_id FROM referral_codes WHERE code=? LIMIT 1");$st->execute([$code]);$referrer=(int)($st->fetchColumn()?:0);
        if($referrer<1)return;
        if(!empty($_SESSION['uid']) && (int)$_SESSION['uid']===$referrer)return;
        $settings=referral_settings();
        $days=max(1,min(90,(int)($settings['cookie_days']??30)));
        $ip=(string)($_SERVER['REMOTE_ADDR']??'');
        $visitor=hash_hmac('sha256',$ip.'|'.substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255),$GLOBALS['config']['app_key']);
        $ipHash=$ip!==''?hash_hmac('sha256',$ip,$GLOBALS['config']['app_key']):null;
        $ins=$pdo->prepare("INSERT INTO referral_clicks(code,referrer_user_id,visitor_hash,ip_hash,user_agent,landing_path) VALUES(?,?,?,?,?,?)");
        $ins->execute([$code,$referrer,$visitor,$ipHash,substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255),substr((string)($_SERVER['REQUEST_URI']??'/'),0,255)]);
        $clickId=(int)$pdo->lastInsertId();
        setcookie('evruni_referral',$code.'|'.$clickId,['expires'=>time()+86400*$days,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    }catch(Throwable $e){}
}
function attach_referral_to_new_user(int $uid): void {
    global $pdo;
    if($uid<1)return;
    $payload=referral_cookie_payload();$code=$payload['code'];$clickId=(int)$payload['click_id'];
    if($code==='')return;
    try{
        $st=$pdo->prepare("SELECT user_id FROM referral_codes WHERE code=? LIMIT 1");$st->execute([$code]);$referrer=(int)($st->fetchColumn()?:0);
        if($referrer<1 || $referrer===$uid)return;
        $exists=$pdo->prepare("SELECT id FROM referrals WHERE referred_user_id=? LIMIT 1");$exists->execute([$uid]);if($exists->fetchColumn())return;
        $pdo->prepare("INSERT INTO referrals(referrer_user_id,referred_user_id,code,click_id,status) VALUES(?,?,?,?, 'active')")->execute([$referrer,$uid,$code,$clickId?:null]);
        if($clickId>0){$pdo->prepare("UPDATE referral_clicks SET referrer_user_id=? WHERE id=?")->execute([$referrer,$clickId]);}
    }catch(Throwable $e){}
}
function referral_commission_for_payment(int $paymentId,int $referredUserId,float $paymentUsd): void {
    global $pdo;
    if($paymentId<1 || $referredUserId<1 || $paymentUsd<=0)return;
    try{
        $cfg=referral_settings();if(empty($cfg['enabled']))return;
        $pct=max(0,min(100,(float)($cfg['commission_percent']??0)));if($pct<=0)return;
        $st=$pdo->prepare("SELECT * FROM referrals WHERE referred_user_id=? AND status='active' LIMIT 1");$st->execute([$referredUserId]);$ref=$st->fetch();if(!$ref)return;
        $commission=round($paymentUsd*$pct/100,6);if($commission<=0)return;
        $q=$pdo->prepare("INSERT INTO referral_commissions(referral_id,payment_id,referrer_user_id,referred_user_id,percent,payment_usd,commission_usd) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=id");
        $q->execute([(int)$ref['id'],$paymentId,(int)$ref['referrer_user_id'],$referredUserId,$pct,$paymentUsd,$commission]);
        if($q->rowCount()>0){
            require_once ROOT_PATH.'/_private/lib/credits.php';
            wallet_add((int)$ref['referrer_user_id'],$commission,'USD','Реферальное начисление','referral_commission',$paymentId);
            $pdo->prepare("UPDATE referrals SET converted_at=COALESCE(converted_at,NOW()) WHERE id=?")->execute([(int)$ref['id']]);
        }
    }catch(Throwable $e){}
}

function e(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
