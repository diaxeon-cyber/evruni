<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php';

if (($_GET['action']??'')==='oauth_start') {
    $provider=trim((string)($_GET['provider']??''));
    if(!in_array($provider,['google','github'],true)) http_response_code(404) && exit;
    rate_limit('oauth_start_'.$provider,10,300);
    $cfg=oauth_provider_config($provider);
    if(!$cfg){ http_response_code(503); echo 'OAuth provider is not configured'; exit; }
    $state=bin2hex(random_bytes(32));
    // Keep a small rolling set so opening OAuth in another tab does not invalidate
    // an already-started login flow. Every state is still random and single-use.
    $stateKey='oauth_states_'.$provider;
    $states=is_array($_SESSION[$stateKey]??null)?$_SESSION[$stateKey]:[];
    $now=time();
    $states=array_values(array_filter($states, static fn($row)=>is_array($row) && (int)($row['created']??0) >= ($now-600)));
    $states[]=['value'=>$state,'created'=>$now];
    if(count($states)>5) $states=array_slice($states,-5);
    $_SESSION[$stateKey]=$states;
    $_SESSION['oauth_state_'.$provider]=$state;
    $_SESSION['oauth_started_'.$provider]=$now;
    // Keep a short-lived OAuth state cookie as a fallback when the callback
    // returns on another Evruni hostname (e.g. www -> apex).
    $cookieDomain = '';
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/','',$host);
    if ($host === 'evruni.com' || str_ends_with($host, '.evruni.com')) $cookieDomain = '.evruni.com';
    setcookie('evruni_oauth_state_'.$provider, $state, [
        'expires'=>time()+600,'path'=>'/','domain'=>$cookieDomain,'secure'=>true,'httponly'=>true,'samesite'=>'Lax'
    ]);
    session_write_close();
    $params=[
        'client_id'=>$cfg['client_id'],
        'redirect_uri'=>oauth_callback_url($provider),
        'response_type'=>'code',
        'scope'=>$cfg['scope'],
        'state'=>$state
    ];
    if($provider==='google'){$params['access_type']='online';$params['prompt']='select_account';}
    header('Location: '.$cfg['authorize'].'?'.http_build_query($params),true,302);
    exit;
}
if (($_GET['action']??'')==='oauth_callback') {
    $provider=trim((string)($_GET['provider']??''));
    if(!in_array($provider,['google','github'],true)) { http_response_code(404); exit; }
    rate_limit('oauth_callback_'.$provider,20,300);
    $cfg=oauth_provider_config($provider);
    $state=(string)($_GET['state']??'');
    $saved=(string)($_SESSION['oauth_state_'.$provider]??'');
    $savedCookie=(string)($_COOKIE['evruni_oauth_state_'.$provider]??'');
    $stateKey='oauth_states_'.$provider;
    $savedStates=is_array($_SESSION[$stateKey]??null)?$_SESSION[$stateKey]:[];
    $now=time();
    $savedStates=array_values(array_filter($savedStates, static fn($row)=>is_array($row) && (int)($row['created']??0) >= ($now-600)));
    $stateInSession=false;
    foreach($savedStates as $row){
        if(hash_equals((string)($row['value']??''),$state)){ $stateInSession=true; break; }
    }
    unset($_SESSION['oauth_state_'.$provider],$_SESSION['oauth_started_'.$provider]);
    $_SESSION[$stateKey]=$savedStates;
    $cookieDomain = '';
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/','',$host);
    if ($host === 'evruni.com' || str_ends_with($host, '.evruni.com')) $cookieDomain = '.evruni.com';
    setcookie('evruni_oauth_state_'.$provider, '', [
        'expires'=>time()-3600,'path'=>'/','domain'=>$cookieDomain,'secure'=>true,'httponly'=>true,'samesite'=>'Lax'
    ]);
    $stateValid = ($state !== '' && ($stateInSession || ($saved !== '' && hash_equals($saved,$state)) || ($savedCookie !== '' && hash_equals($savedCookie,$state))));
    if($stateValid){
        // Consume this state so it cannot be replayed.
        $_SESSION[$stateKey]=array_values(array_filter($_SESSION[$stateKey]??[], static fn($row)=>!is_array($row) || !hash_equals((string)($row['value']??''),$state)));
    }
    if(!$cfg || !$stateValid){
        http_response_code(400); echo 'Invalid OAuth state'; exit;
    }
    $code=(string)($_GET['code']??'');
    if($code===''){http_response_code(400);echo 'OAuth authorization failed';exit;}
    $token=oauth_http_post($cfg['token'],[
        'client_id'=>$cfg['client_id'],
        'client_secret'=>$cfg['client_secret'],
        'code'=>$code,
        'redirect_uri'=>oauth_callback_url($provider),
        'grant_type'=>'authorization_code'
    ],$provider==='github'?['Accept: application/json']:[]);
    $access=(string)($token['access_token']??'');
    if($access===''){http_response_code(502);echo 'OAuth token exchange failed';exit;}
    if($provider==='google'){
        $profile=oauth_http_get('https://openidconnect.googleapis.com/v1/userinfo',['Authorization: Bearer '.$access]);
        $pid=(string)($profile['sub']??'');
        $email=(string)($profile['email']??'');
        $name=(string)($profile['name']??'');
    }else{
        $profile=oauth_http_get('https://api.github.com/user',['Authorization: Bearer '.$access,'User-Agent: Evruni']);
        $pid=(string)($profile['id']??'');
        $email=(string)($profile['email']??'');
        if($email===''){
            $emails=oauth_http_get('https://api.github.com/user/emails',['Authorization: Bearer '.$access,'User-Agent: Evruni']);
            foreach(($emails?:[]) as $em){ if(!empty($em['primary']) && !empty($em['verified'])){$email=(string)$em['email'];break;} }
        }
        $name=(string)($profile['name']??$profile['login']??'');
    }
    if($pid==='' || $email===''){http_response_code(422);echo 'OAuth account did not provide a verified email';exit;}
    $uid=oauth_find_or_create_user($provider,$pid,$email,$name);
    if(!$uid){http_response_code(500);echo 'Could not create account';exit;}
    // Establish the authenticated session before redirecting.
    // Keep the same session semantics as the normal email login/registration
    // flow; this avoids hosts that lose the regenerated session during OAuth.
    $_SESSION['uid']=$uid;
    csrf_token();
    session_regenerate_id(false);
    audit('oauth_login',$uid,['provider'=>$provider]);
    session_write_close();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: /',true,303);
    exit;
}

if (($_GET['action']??'')==='logout') {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
    }
    session_destroy();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location:/');
    exit;
}
json_out(['ok'=>true,'user'=>current_user()]);
