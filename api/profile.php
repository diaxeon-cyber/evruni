<?php
declare(strict_types=1);
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';

$u=require_auth();

if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
    json_out([
        'user'=>[
            'id'=>(int)$u['id'],
            'name'=>(string)$u['name'],
            'email'=>(string)$u['email'],
            'role'=>(string)$u['role'],
            'user_category'=>(string)($u['user_category']??(($u['role']??'user')==='admin'?'ai_admin':'ai_user')),
            'language'=>(string)($u['language']??'ru'),
            'currency'=>wallet_currency_for_user((int)$u['id']),
            'balance'=>wallet_balance_exact((int)$u['id'],wallet_currency_for_user((int)$u['id']))
        ]
    ]);
}

require_csrf();
$name=trim((string)($_POST['name']??$u['name']));
$lang=(string)($_POST['language']??($u['language']??'ru'));
$currency=strtoupper((string)($_POST['currency']??($u['currency']??'EUR')));
if($name==='') $name=(string)$u['name'];
if(!in_array($lang,['ru','en'],true)) $lang='ru';
if(!in_array($currency,['EUR','USD','GBP','RUB'],true)) $currency='EUR';

$s=$pdo->prepare("UPDATE users SET name=?, language=?, currency=? WHERE id=?");
$s->execute([$name,$lang,$currency,$u['id']]);

json_out([
    'ok'=>true,
    'user'=>[
        'name'=>$name,'email'=>(string)$u['email'],
        'language'=>$lang,'currency'=>$currency,
        'balance'=>wallet_total_converted((int)$u['id'],$currency)
    ]
]);
