<?php
require_once __DIR__.'/../_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
require_once ROOT_PATH.'/_private/lib/payment_settings.php';
require_once ROOT_PATH.'/_private/lib/ai_pricing.php';

$u=require_admin();

function admin_json_input(): array {
    $raw=file_get_contents('php://input');
    if($raw===false || trim($raw)==='') return [];
    $d=json_decode($raw,true);
    return is_array($d)?$d:[];
}
function admin_int($v,int $fallback=0): int { return filter_var($v,FILTER_VALIDATE_INT)!==false?(int)$v:$fallback; }
function admin_float($v,float $fallback=0): float { return is_numeric($v)?(float)$v:$fallback; }

function ai_admin_provider_meta(): array {
    return [
        'openai'=>['env'=>'OPENAI_API_KEY','url'=>'https://api.openai.com/v1'],
        'aitunnel'=>['env'=>'AITUNNEL_API_KEY','url'=>'https://api.aitunnel.ru/v1'],
        'deepseek'=>['env'=>'DEEPSEEK_API_KEY','url'=>'https://api.deepseek.com/v1'],
        'qwen'=>['env'=>'QWEN_API_KEY','url'=>'https://dashscope.aliyuncs.com/compatible-mode/v1'],
        'mistral'=>['env'=>'MISTRAL_API_KEY','url'=>'https://api.mistral.ai/v1'],
        'anthropic'=>['env'=>'ANTHROPIC_API_KEY','url'=>'https://api.anthropic.com/v1'],
        'google'=>['env'=>'GOOGLE_API_KEY','url'=>'https://generativelanguage.googleapis.com'],
    ];
}
function ai_admin_env_path(): string { return ROOT_PATH.'/.env'; }
function ai_admin_write_env(string $key,string $value): void {
    $file=ai_admin_env_path();
    if(!is_file($file) || !is_readable($file) || !is_writable($file)) throw new RuntimeException('.env недоступен для записи. Проверь права файла.');
    $raw=file_get_contents($file); if($raw===false) throw new RuntimeException('Не удалось прочитать .env');
    $escaped=str_replace(["\\","\"","\r","\n"],["\\\\","\\\"","",""],$value);
    $line=$key.'="'.$escaped.'"';
    $pattern='/^'.preg_quote($key,'/').'\\s*=.*$/m';
    if(preg_match($pattern,$raw)) $raw=preg_replace($pattern,$line,$raw,1);
    else $raw=rtrim($raw)."\n".$line."\n";
    if(file_put_contents($file,$raw,LOCK_EX)===false) throw new RuntimeException('Не удалось сохранить .env');
    putenv($key.'='.$value);
}
function ai_admin_ensure_settings(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_provider_settings (
      provider VARCHAR(80) NOT NULL PRIMARY KEY,
      api_url TEXT NULL, docs_url TEXT NULL, health_url TEXT NULL,
      load_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
      status VARCHAR(24) NOT NULL DEFAULT 'unknown',
      note VARCHAR(255) NULL,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach(ai_admin_provider_meta() as $provider=>$meta){
        $st=$pdo->prepare("INSERT INTO ai_provider_settings(provider,api_url,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE api_url=VALUES(api_url)");
        $st->execute([$provider,$meta['url'],'unknown']);
    }
}


function ensure_api_usage_table_admin(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_usage (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT UNSIGNED NOT NULL,
      api_key_id BIGINT UNSIGNED NULL,
      model_slug VARCHAR(120) NOT NULL,
      endpoint VARCHAR(120) NOT NULL,
      input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
      output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
      cost DECIMAL(14,6) NOT NULL DEFAULT 0,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX(user_id,created_at), INDEX(model_slug,created_at)
    ) ENGINE=InnoDB");
}

function ensure_support_tables_admin(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_threads (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      subject VARCHAR(180) NOT NULL DEFAULT 'Support',
      status ENUM('open','closed') NOT NULL DEFAULT 'open',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      closed_at TIMESTAMP NULL,
      PRIMARY KEY(id),
      KEY idx_support_threads_user(user_id,updated_at),
      KEY idx_support_threads_status(status,updated_at),
      CONSTRAINT fk_support_threads_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      thread_id BIGINT UNSIGNED NOT NULL,
      user_id BIGINT UNSIGNED NULL,
      author_role ENUM('user','admin') NOT NULL,
      content TEXT NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY(id),
      KEY idx_support_messages_thread(thread_id,created_at),
      CONSTRAINT fk_support_messages_thread FOREIGN KEY(thread_id) REFERENCES support_threads(id) ON DELETE CASCADE,
      CONSTRAINT fk_support_messages_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensure_notice_table(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_notices (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      notice_key VARCHAR(64) NOT NULL,
      enabled TINYINT(1) NOT NULL DEFAULT 1,
      title_ru VARCHAR(255) NOT NULL,
      text_ru TEXT NOT NULL,
      title_en VARCHAR(255) NOT NULL,
      text_en TEXT NOT NULL,
      updated_by INT UNSIGNED NULL,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_site_notices_key (notice_key),
      KEY idx_site_notices_enabled (enabled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $st=$pdo->prepare("SELECT id FROM site_notices WHERE notice_key='development' LIMIT 1");
    $st->execute();
    if(!$st->fetchColumn()){
      $st=$pdo->prepare("INSERT INTO site_notices(notice_key,enabled,title_ru,text_ru,title_en,text_en,updated_by) VALUES('development',1,?,?,?,?,?,?)");
      $st->execute(['Evruni находится в разработке','Некоторые функции временно недоступны. Мы работаем над их запуском.','Evruni is under development','Some features are temporarily unavailable. We are working on bringing them online.',null]);
    }
}


// Self-heal the AI role profile column so an existing database never blanks the admin panel
// when the optional role migration has not been run yet.
try {
    $hasUserCategory = (bool)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='user_category'")->fetchColumn();
    if(!$hasUserCategory){
        $pdo->exec("ALTER TABLE users ADD COLUMN user_category VARCHAR(40) NOT NULL DEFAULT 'ai_user' AFTER role");
        $pdo->exec("UPDATE users SET user_category='ai_admin' WHERE role='admin'");
    }
} catch(Throwable $e) {
    // Keep the admin panel available even if the DB account cannot ALTER TABLE.
}

$method=$_SERVER['REQUEST_METHOD']??'GET';
$action=trim((string)($_GET['action']??$_POST['action']??''));
function ensure_referral_admin_defaults(): void {
    global $pdo;
    $pdo->exec("INSERT IGNORE INTO referral_settings(id,enabled,commission_percent,cookie_days) VALUES(1,1,10,30)");
}


if($method==='GET'){
    if($action==='dashboard' || $action===''){
        $stats=[
            'users'=>(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'admins'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn(),
            'active_users'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
            'credits'=>(float)$pdo->query("SELECT COALESCE(SUM(credits),0) FROM users")->fetchColumn(),
            'generations'=>(int)$pdo->query("SELECT COUNT(*) FROM generations")->fetchColumn(),
            'api_requests'=>(int)$pdo->query("SELECT COUNT(*) FROM api_usage")->fetchColumn(),
            'payments'=>(float)$pdo->query("SELECT COALESCE(SUM(amount_cents),0)/100 FROM payments WHERE status='paid'")->fetchColumn(),
        ];

        // Financial dashboard: paid cash minus outstanding customer wallet balances.
        // All amounts are normalized to USD using the existing FX service.
        $cashIn=0.0;
        foreach($pdo->query("SELECT currency,amount_cents FROM payments WHERE status='paid'") as $r){
            try{$cashIn+=fx_convert(((float)$r['amount_cents'])/100,(string)$r['currency'],'USD');}catch(Throwable $e){}
        }
        $walletLiability=0.0;
        foreach($pdo->query("SELECT currency,balance FROM wallet_balances WHERE balance<>0") as $r){
            try{$walletLiability+=fx_convert((float)$r['balance'],(string)$r['currency'],'USD');}catch(Throwable $e){}
        }
        $totalWalletCredits=$walletLiability;
        $freeCash=$cashIn-$walletLiability;
        $freeCashPercent=$cashIn>0?($freeCash/$cashIn*100):0.0;

        $adminCurrency=strtoupper((string)($u['currency']??'USD'));
        if(!in_array($adminCurrency,['EUR','USD','GBP','RUB'],true))$adminCurrency='USD';
        try{
            $cashInDisplay=fx_convert($cashIn,'USD',$adminCurrency);
            $walletDisplay=fx_convert($walletLiability,'USD',$adminCurrency);
            $freeCashDisplay=fx_convert($freeCash,'USD',$adminCurrency);
        }catch(Throwable $e){
            $cashInDisplay=$cashIn;
            $walletDisplay=$walletLiability;
            $freeCashDisplay=$freeCash;
        }
        $stats['cash_in']=round($cashInDisplay,2);
        $stats['wallet_liability']=round($walletDisplay,2);
        $stats['total_wallet_credits']=round($walletDisplay,2);
        $stats['free_cash']=round($freeCashDisplay,2);
        $stats['display_currency']=$adminCurrency;
        $stats['cash_in_eur']=round($cashIn,2);
        $stats['wallet_liability_eur']=round($walletLiability,2);
        $stats['total_wallet_credits_eur']=round($totalWalletCredits,2);
        $stats['free_cash_eur']=round($freeCash,2);
        $stats['free_cash_percent']=round($freeCashPercent,2);
        $recent=$pdo->query("SELECT id,email,role,COALESCE(user_category,'ai_user') AS user_category,currency,created_at FROM users ORDER BY id DESC LIMIT 8")->fetchAll();
        foreach($recent as &$rr){
            $rr['balance_currency']=wallet_currency_for_user((int)$rr['id']);
            $rr['balance']=wallet_balance_exact((int)$rr['id'],$rr['balance_currency']);
            $rr['credits']=$rr['balance'];
        }
        unset($rr);
        $gens=$pdo->query("SELECT g.id,g.user_id,g.type,g.status,g.credits_spent,g.created_at,COALESCE(m.slug,'') AS model_slug FROM generations g LEFT JOIN ai_models m ON m.id=g.model_id ORDER BY g.id DESC LIMIT 10")->fetchAll();
        json_out(['ok'=>true,'stats'=>$stats,'recent_users'=>$recent,'recent_generations'=>$gens]);
    }

    if($action==='users'){
        $q=trim((string)($_GET['q']??''));
        $category=trim((string)($_GET['category']??''));
        $allowed_categories=['ai_user','ai_creator','ai_developer','ai_researcher','ai_business','ai_partner','ai_moderator','ai_admin'];
        if(!in_array($category,$allowed_categories,true)) $category='';
        $limit=min(100,max(1,admin_int($_GET['limit']??50,50)));
        $where=[];$params=[];
        if($q!==''){ $where[]='email LIKE ?'; $params[]='%'.$q.'%'; }
        if($category!==''){ $where[]="COALESCE(user_category,'ai_user')=?"; $params[]=$category; }
        $sql="SELECT id,email,role,currency,is_active,created_at,COALESCE(user_category,'ai_user') AS user_category FROM users";
        if($where) $sql.=' WHERE '.implode(' AND ',$where);
        $sql.=" ORDER BY id DESC LIMIT $limit";
        $st=$pdo->prepare($sql); $st->execute($params);
        $users=$st->fetchAll();
        foreach($users as &$ur){
            $ur['balance_currency']=wallet_currency_for_user((int)$ur['id']);
            $ur['balance']=wallet_balance_exact((int)$ur['id'],$ur['balance_currency']);
            $ur['credits']=$ur['balance'];
        }
        unset($ur);
        json_out(['ok'=>true,'users'=>$users]);
    }

    if($action==='models'){
        $adminCurrency=strtoupper((string)($u['currency']??'USD'));
        if(!in_array($adminCurrency,['EUR','USD','GBP','RUB'],true))$adminCurrency='USD';
        $rows=ai_pricing_for_models($pdo->query("SELECT id,slug,name,provider,provider_model,type,credit_cost,enabled FROM ai_models ORDER BY id DESC")->fetchAll(), $adminCurrency);
        json_out(['ok'=>true,'models'=>$rows,'markup_percent'=>ai_markup_percent(),'currency'=>$adminCurrency]);
    }

    if($action==='payment_settings'){
        $s=payment_settings();
        json_out(['ok'=>true,'settings'=>[
            'provider'=>$s['provider'],
            'enabled'=>(int)$s['enabled'],
            'publishable_key'=>$s['publishable_key'],
            'secret_key'=>$s['secret_key'],
            'webhook_secret'=>$s['webhook_secret'],
            'default_currency'=>$s['default_currency'],
            'method_configs'=>$s['method_configs']
        ]]);
    }

    if($action==='payments'){
        $rows=$pdo->query("SELECT id,user_id,provider,external_id,amount_cents,currency,status,created_at FROM payments ORDER BY id DESC LIMIT 100")->fetchAll();
        json_out(['ok'=>true,'payments'=>$rows]);
    }

    if($action==='generations'){
        $rows=$pdo->query("SELECT g.id,g.user_id,g.type,g.status,g.credits_spent,COALESCE(m.slug,'') AS model_slug,g.created_at FROM generations g LEFT JOIN ai_models m ON m.id=g.model_id ORDER BY g.id DESC LIMIT 100")->fetchAll();
        json_out(['ok'=>true,'generations'=>$rows]);
    }

    if($action==='usage'){
        try{
            ensure_api_usage_table_admin();
            $rows=$pdo->query("SELECT id,user_id,api_key_id,model_slug,input_tokens,output_tokens,cost,created_at FROM api_usage ORDER BY id DESC LIMIT 100")->fetchAll();
        }catch(Throwable $e){
            $rows=[];
        }
        json_out(['ok'=>true,'usage'=>$rows]);
    }

    if($action==='audit'){
        $rows=$pdo->query("SELECT id,user_id,action,ip AS ip_address,created_at FROM audit_log ORDER BY id DESC LIMIT 150")->fetchAll();
        json_out(['ok'=>true,'audit'=>$rows]);
    }


    if($action==='referrals'){
        ensure_referral_admin_defaults();
        $settings=$pdo->query("SELECT * FROM referral_settings WHERE id=1 LIMIT 1")->fetch() ?: [];
        $stats=[
          'clicks'=>(int)$pdo->query("SELECT COUNT(*) FROM referral_clicks")->fetchColumn(),
          'referrals'=>(int)$pdo->query("SELECT COUNT(*) FROM referrals")->fetchColumn(),
          'paid_referrals'=>(int)$pdo->query("SELECT COUNT(DISTINCT r.id) FROM referrals r JOIN referral_commissions rc ON rc.referral_id=r.id")->fetchColumn(),
          'commission_usd'=>(float)$pdo->query("SELECT COALESCE(SUM(commission_usd),0) FROM referral_commissions")->fetchColumn(),
          'task_submissions'=>(int)$pdo->query("SELECT COUNT(*) FROM task_submissions WHERE status='pending'")->fetchColumn()
        ];
        $top=$pdo->query("SELECT r.referrer_user_id,u.email,u.name,COUNT(r.id) referrals,COALESCE(SUM(rc.commission_usd),0) commission_usd FROM referrals r JOIN users u ON u.id=r.referrer_user_id LEFT JOIN referral_commissions rc ON rc.referral_id=r.id GROUP BY r.referrer_user_id,u.email,u.name ORDER BY referrals DESC,commission_usd DESC LIMIT 50")->fetchAll();
        $referrals=$pdo->query("SELECT r.id,r.code,r.created_at,r.converted_at,ru.email referrer_email,ru.name referrer_name,uu.email referred_email,uu.name referred_name,COALESCE(SUM(rc.commission_usd),0) commission_usd FROM referrals r JOIN users ru ON ru.id=r.referrer_user_id JOIN users uu ON uu.id=r.referred_user_id LEFT JOIN referral_commissions rc ON rc.referral_id=r.id GROUP BY r.id,r.code,r.created_at,r.converted_at,ru.email,ru.name,uu.email,uu.name ORDER BY r.id DESC LIMIT 200")->fetchAll();
        $clicks=$pdo->query("SELECT rc.id,rc.code,rc.created_at,u.email,u.name FROM referral_clicks rc LEFT JOIN users u ON u.id=rc.referrer_user_id ORDER BY rc.id DESC LIMIT 150")->fetchAll();
        $tasks=$pdo->query("SELECT * FROM bonus_tasks ORDER BY sort_order ASC,id DESC")->fetchAll();
        $subs=$pdo->query("SELECT s.*,t.title_ru,u.email,u.name FROM task_submissions s JOIN bonus_tasks t ON t.id=s.task_id JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 200")->fetchAll();
        json_out(['settings'=>$settings,'stats'=>$stats,'top'=>$top,'clicks'=>$clicks,'referrals'=>$referrals,'tasks'=>$tasks,'submissions'=>$subs]);
    }
    if($action==='analytics'){
        $days=min(90,max(7,admin_int($_GET['days']??30,30)));
        $analytics=['days'=>$days,'users'=>[],'generations'=>[],'api_requests'=>[],'payments'=>[]];
        try{
            ensure_api_usage_table_admin();
            $start=(new DateTimeImmutable('today'))->modify('-'.($days-1).' days')->format('Y-m-d 00:00:00');
            $end=(new DateTimeImmutable('tomorrow'))->format('Y-m-d 00:00:00');
            $series=function(string $table,string $where='') use($pdo,$start,$end){
                $sql="SELECT DATE(created_at) AS day, COUNT(*) AS value FROM {$table} WHERE created_at>=? AND created_at<? {$where} GROUP BY DATE(created_at) ORDER BY day";
                $st=$pdo->prepare($sql);$st->execute([$start,$end]);return $st->fetchAll();
            };
            $payments=$pdo->prepare("SELECT DATE(created_at) AS day, COALESCE(SUM(amount_cents),0)/100 AS value FROM payments WHERE status='paid' AND created_at>=? AND created_at<? GROUP BY DATE(created_at) ORDER BY day");
            $payments->execute([$start,$end]);
            $analytics=[
              'days'=>$days,
              'users'=>$series('users'),
              'generations'=>$series('generations'),
              'api_requests'=>$series('api_usage'),
              'payments'=>$payments->fetchAll(),
            ];
        }catch(Throwable $e){
            // Keep the analytics screen available even if an optional analytics table is unavailable.
        }
        json_out(['ok'=>true,'analytics'=>$analytics]);
    }

    if($action==='notice'){
        ensure_notice_table();
        $st=$pdo->prepare("SELECT id,enabled,title_ru,text_ru,title_en,text_en,updated_at FROM site_notices WHERE notice_key='development' LIMIT 1");
        $st->execute();
        json_out(['ok'=>true,'notice'=>$st->fetch()]);
    }

    if($action==='support_threads'){
        ensure_support_tables_admin();
        $status=trim((string)($_GET['status']??''));
        $where=$status==='closed' ? "WHERE t.status='closed'" : ($status==='open' ? "WHERE t.status='open'" : "");
        $rows=$pdo->query("SELECT t.id,t.user_id,t.subject,t.status,t.created_at,t.updated_at,
          u.email,u.name,
          (SELECT m.content FROM support_messages m WHERE m.thread_id=t.id ORDER BY m.id DESC LIMIT 1) AS last_message,
          (SELECT COUNT(*) FROM support_messages m2 WHERE m2.thread_id=t.id) AS message_count
          FROM support_threads t JOIN users u ON u.id=t.user_id {$where} ORDER BY t.updated_at DESC LIMIT 200")->fetchAll();
        json_out(['ok'=>true,'threads'=>$rows]);
    }

    if($action==='support_thread'){
        ensure_support_tables_admin();
        $id=admin_int($_GET['id']??0);
        if($id<1) json_out(['error'=>'Invalid thread id'],422);
        $st=$pdo->prepare("SELECT t.id,t.user_id,t.subject,t.status,t.created_at,t.updated_at,t.closed_at,u.email,u.name
          FROM support_threads t JOIN users u ON u.id=t.user_id WHERE t.id=? LIMIT 1");
        $st->execute([$id]); $thread=$st->fetch();
        if(!$thread) json_out(['error'=>'Thread not found'],404);
        $st=$pdo->prepare("SELECT id,user_id,author_role,content,created_at FROM support_messages WHERE thread_id=? ORDER BY id ASC LIMIT 500");
        $st->execute([$id]);
        json_out(['ok'=>true,'thread'=>$thread,'messages'=>$st->fetchAll()]);
    }


    if($action==='ai_settings'){
        $meta=ai_admin_provider_meta();
        $settings=[]; $settingsError='';
        try{ ai_admin_ensure_settings(); $st=$pdo->query("SELECT provider,api_url,status,updated_at FROM ai_provider_settings"); foreach($st->fetchAll() as $r)$settings[strtolower((string)$r['provider'])]=$r; }
        catch(Throwable $e){ $settingsError=$e->getMessage(); }
        $counts=[];
        try{ $rows=$pdo->query("SELECT provider,COUNT(*) total_models,SUM(enabled=1) enabled_models FROM ai_models GROUP BY provider")->fetchAll(); foreach($rows as $r)$counts[strtolower((string)$r['provider'])]=['total'=>(int)$r['total_models'],'enabled'=>(int)$r['enabled_models']]; }catch(Throwable $e){}
        $providers=[];
        foreach($meta as $provider=>$m){
            $key=(string)envv($m['env'],''); $q=$settings[$provider]??[];
            $providers[]=[
                'provider'=>$provider,
                'total_models'=>$counts[$provider]['total']??0,
                'enabled_models'=>$counts[$provider]['enabled']??0,
                'setting'=>[
                    'api_key_configured'=>trim($key)!=='',
                    'env_configured'=>trim($key)!=='',
                    'env_source'=>$m['env'],
                    'status'=>(string)($q['status']??'unknown'),
                    'api_url'=>$m['url'],
                    'updated_at'=>$q['updated_at']??null,
                ],
            ];
        }
        json_out(['ok'=>true,'providers'=>$providers,'settings_error'=>$settingsError]);
    }

    if($action==='system'){
        $models=(int)$pdo->query("SELECT COUNT(*) FROM ai_models")->fetchColumn();
        $configured=(int)$pdo->query("SELECT COUNT(*) FROM ai_models WHERE enabled=1")->fetchColumn();
        json_out(['ok'=>true,'system'=>[
            'php'=>PHP_VERSION,
            'server'=>$_SERVER['SERVER_SOFTWARE']??'',
            'db'=>'connected',
            'models'=>$models,
            'enabled_models'=>$configured,
            'app_env'=>envv('APP_ENV','production'),
            'app_url'=>envv('APP_URL',''),
            'openai_key_configured'=>trim((string)envv('OPENAI_API_KEY',''))!=='',
            'deepseek_key_configured'=>trim((string)envv('DEEPSEEK_API_KEY',''))!=='',
            'qwen_key_configured'=>trim((string)envv('QWEN_API_KEY',''))!=='',
            'mistral_key_configured'=>trim((string)envv('MISTRAL_API_KEY',''))!=='',
            'aitunnel_key_configured'=>trim((string)envv('AITUNNEL_API_KEY',''))!=='',
            'anthropic_key_configured'=>trim((string)envv('ANTHROPIC_API_KEY',''))!=='',
            'google_key_configured'=>trim((string)envv('GOOGLE_API_KEY',''))!=='',
            'stripe_configured'=>trim((string)envv('STRIPE_SECRET_KEY',''))!=='' && trim((string)envv('STRIPE_SECRET_KEY',''))!=='CHANGE_ME',
        ]]);
    }

    json_out(['error'=>'Unknown admin action'],404);
}

if($method==='POST'){
    require_csrf();
    $in=admin_json_input();
    if(!$in && !empty($_POST)) $in=$_POST;

    if($action==='support_reply'){
        ensure_support_tables_admin();
        $threadId=admin_int($in['thread_id']??0);
        $message=trim((string)($in['message']??''));
        if($threadId<1 || $message==='') json_out(['error'=>'Invalid support reply'],422);
        $message=mb_substr($message,0,5000);
        $st=$pdo->prepare("SELECT id FROM support_threads WHERE id=? LIMIT 1");
        $st->execute([$threadId]);
        if(!$st->fetchColumn()) json_out(['error'=>'Thread not found'],404);
        $pdo->prepare("INSERT INTO support_messages(thread_id,user_id,author_role,content) VALUES(?,?, 'admin',?)")
          ->execute([$threadId,(int)$u['id'],$message]);
        $pdo->prepare("UPDATE support_threads SET status='open',closed_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?")
          ->execute([$threadId]);
        audit('admin.support_reply',(int)$u['id'],['thread_id'=>$threadId]);
        json_out(['ok'=>true]);
    }

    if($action==='support_status'){
        ensure_support_tables_admin();
        $threadId=admin_int($in['thread_id']??0);
        $status=in_array(($in['status']??''),['open','closed'],true)?$in['status']:'open';
        $st=$pdo->prepare("UPDATE support_threads SET status=?,closed_at=? WHERE id=?");
        $st->execute([$status,$status==='closed'?date('Y-m-d H:i:s'):null,$threadId]);
        if($st->rowCount()<1) json_out(['error'=>'Thread not found'],404);
        audit('admin.support_status',(int)$u['id'],['thread_id'=>$threadId,'status'=>$status]);
        json_out(['ok'=>true]);
    }

    if($action==='user_role_update'){
        $id=admin_int($in['id']??0);
        $profile=(string)($in['profile']??'ai_user');
        $allowed=['ai_user','ai_creator','ai_developer','ai_researcher','ai_business','ai_partner','ai_moderator','ai_admin'];
        if($id<1 || !in_array($profile,$allowed,true)) json_out(['error'=>'Некорректная роль'],422);
        $actualRole=$profile==='ai_admin'?'admin':'user';
        $st=$pdo->prepare('UPDATE users SET user_category=?, role=? WHERE id=?');
        $st->execute([$profile,$actualRole,$id]);
        audit('admin.user_role_update',(int)$u['id'],['target_user_id'=>$id,'profile'=>$profile,'role'=>$actualRole]);
        json_out(['ok'=>true,'profile'=>$profile,'role'=>$actualRole]);
    }

    if($action==='user_category_update'){
        $id=admin_int($in['id']??0);
        $category=(string)($in['category']??'ai_user');
        $allowed=['ai_user','ai_creator','ai_developer','ai_researcher','ai_business','ai_partner','ai_moderator','ai_admin'];
        if($id<1 || !in_array($category,$allowed,true)) json_out(['error'=>'Некорректная категория'],422);
        $st=$pdo->prepare('UPDATE users SET user_category=? WHERE id=?');
        $st->execute([$category,$id]);
        audit('admin.user_category_update',(int)$u['id'],['target_user_id'=>$id,'category'=>$category]);
        json_out(['ok'=>true,'category'=>$category]);
    }

    if($action==='user_update'){
        $id=admin_int($in['id']??0);
        if($id<1) json_out(['error'=>'Invalid user id'],422);
        $role=in_array(($in['role']??''),['user','admin'],true)?$in['role']:'user';
        $status=in_array(($in['status']??''),['active','blocked'],true)?$in['status']:'active';
        $active=$status==='active'?1:0;
        $st=$pdo->prepare("UPDATE users SET role=?, is_active=? WHERE id=?");
        $st->execute([$role,$active,$id]);
        audit('admin.user_update',(int)$u['id'],['target_user_id'=>$id,'role'=>$role,'status'=>$status]);
        json_out(['ok'=>true]);
    }

    if($action==='credits'){
        $id=admin_int($in['id']??0);
        $amount=admin_float($in['amount']??0);
        if($id<1 || $amount==0) json_out(['error'=>'Invalid user or amount'],422);
        $targetCurrency=wallet_currency_for_user($id);
        $reason=trim((string)($in['reason']??'Admin adjustment'));
        wallet_adjust_exact($id,$amount,$targetCurrency,$reason,'admin',0);
        $newBalance=wallet_balance_exact($id,$targetCurrency);
        audit('admin.credits_adjust',(int)$u['id'],['target_user_id'=>$id,'amount'=>$amount,'currency'=>$targetCurrency,'reason'=>$reason]);
        json_out(['ok'=>true,'amount'=>$amount,'currency'=>$targetCurrency,'balance'=>$newBalance]);
    }


    if($action==='referral_settings_update'){
        ensure_referral_admin_defaults();
        $enabled=!empty($in['enabled'])?1:0;$pct=admin_float($in['commission_percent']??10);$days=admin_int($in['cookie_days']??30);
        if($pct<0||$pct>100)json_out(['error'=>'Комиссия должна быть от 0 до 100%'],422);
        if($days<1||$days>90)json_out(['error'=>'Срок cookie должен быть от 1 до 90 дней'],422);
        $pdo->prepare("UPDATE referral_settings SET enabled=?,commission_percent=?,cookie_days=?,updated_by=? WHERE id=1")->execute([$enabled,$pct,$days,(int)$u['id']]);
        audit('admin.referral_settings_update',(int)$u['id'],['enabled'=>$enabled,'commission_percent'=>$pct,'cookie_days'=>$days]);json_out(['ok'=>true]);
    }
    if($action==='task_save'){
        $id=admin_int($in['id']??0);$titleRu=trim((string)($in['title_ru']??''));$titleEn=trim((string)($in['title_en']??''));$descRu=trim((string)($in['description_ru']??''));$descEn=trim((string)($in['description_en']??''));$reward=admin_float($in['reward_usd']??0);$type=in_array(($in['task_type']??'manual'),['link','manual'],true)?$in['task_type']:'manual';$url=trim((string)($in['target_url']??''));$proof=!empty($in['proof_required'])?1:0;$enabled=!empty($in['enabled'])?1:0;$sort=admin_int($in['sort_order']??0);
        if($titleRu===''||$descRu===''||$reward<0)json_out(['error'=>'Заполните название, описание и награду'],422);
        if($type==='link' && $url!=='' && !filter_var($url,FILTER_VALIDATE_URL))json_out(['error'=>'Некорректная ссылка задания'],422);
        if($id>0){$st=$pdo->prepare("UPDATE bonus_tasks SET title_ru=?,title_en=?,description_ru=?,description_en=?,reward_usd=?,task_type=?,target_url=?,proof_required=?,enabled=?,sort_order=? WHERE id=?");$st->execute([$titleRu,$titleEn,$descRu,$descEn,$reward,$type,$url?:null,$proof,$enabled,$sort,$id]);$event='admin.task_update';}
        else{$st=$pdo->prepare("INSERT INTO bonus_tasks(title_ru,title_en,description_ru,description_en,reward_usd,task_type,target_url,proof_required,enabled,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)");$st->execute([$titleRu,$titleEn,$descRu,$descEn,$reward,$type,$url?:null,$proof,$enabled,$sort]);$id=(int)$pdo->lastInsertId();$event='admin.task_create';}
        audit($event,(int)$u['id'],['task_id'=>$id]);json_out(['ok'=>true,'id'=>$id]);
    }
    if($action==='task_delete'){
        $id=admin_int($in['id']??0);if($id<1)json_out(['error'=>'Invalid task'],422);$pdo->prepare("DELETE FROM bonus_tasks WHERE id=?")->execute([$id]);audit('admin.task_delete',(int)$u['id'],['task_id'=>$id]);json_out(['ok'=>true]);
    }
    if($action==='task_review'){
        $id=admin_int($in['id']??0);$status=in_array(($in['status']??''),['approved','rejected'],true)?$in['status']:'';$note=trim((string)($in['review_note']??''));if($id<1||$status==='')json_out(['error'=>'Invalid review'],422);
        $st=$pdo->prepare("SELECT s.*,t.reward_usd,t.title_ru FROM task_submissions s JOIN bonus_tasks t ON t.id=s.task_id WHERE s.id=? LIMIT 1");$st->execute([$id]);$sub=$st->fetch();if(!$sub)json_out(['error'=>'Submission not found'],404);
        if($sub['status']==='approved')json_out(['error'=>'Задание уже одобрено'],409);
        if($status==='approved'){
            try{wallet_add((int)$sub['user_id'],(float)$sub['reward_usd'],'USD','Бонус за задание','task',$id);}catch(Throwable $e){json_out(['error'=>'Не удалось начислить награду'],500);}
        }
        $pdo->prepare("UPDATE task_submissions SET status=?,review_note=?,reviewed_at=NOW(),reviewed_by=? WHERE id=?")->execute([$status,$note,(int)$u['id'],$id]);
        audit('admin.task_review',(int)$u['id'],['submission_id'=>$id,'status'=>$status]);json_out(['ok'=>true]);
    }

    if($action==='ai_settings_save'){
        $provider=strtolower(trim((string)($in['provider']??''))); $meta=ai_admin_provider_meta();
        if(!isset($meta[$provider])) json_out(['error'=>'Неизвестный провайдер'],422);
        $status=(string)($in['status']??'unknown');
        if(!in_array($status,['online','degraded','offline','maintenance','unknown'],true)) $status='unknown';
        $key=trim((string)($in['api_key']??''));
        if($key!==''){
            if(strlen($key)>500) json_out(['error'=>'API ключ слишком длинный'],422);
            ai_admin_write_env($meta[$provider]['env'],$key);
        }
        ai_admin_ensure_settings();
        $pdo->prepare("UPDATE ai_provider_settings SET api_url=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE provider=?")->execute([$meta[$provider]['url'],$status,$provider]);
        audit('admin.ai_settings_save',(int)$u['id'],['provider'=>$provider,'key_changed'=>$key!=='','status'=>$status]);
        json_out(['ok'=>true,'provider'=>$provider,'key_changed'=>$key!=='']);
    }

    if($action==='payment_settings_update'){
        ensure_payment_settings_table();
        $provider=in_array(($in['provider']??''),['stripe'],true)?$in['provider']:'stripe';
        $enabled=!empty($in['enabled'])?1:0;
        $currency=strtoupper(trim((string)($in['default_currency']??'EUR')));
        if(!in_array($currency,['EUR','USD','GBP','RUB'],true)) $currency='EUR';
        $current=payment_settings();
        $pk=trim((string)($in['publishable_key']??''));
        $sk=trim((string)($in['secret_key']??''));
        $wh=trim((string)($in['webhook_secret']??''));
        if($pk==='') $pk=$current['publishable_key'];
        if($sk==='') $sk=$current['secret_key'];
        if($wh==='') $wh=$current['webhook_secret'];
        $methods=$in['method_configs']??[];
        if(!is_array($methods)) $methods=[];
        $allowed=['NL','RU','OTHER'];
        $clean=[];
        foreach($allowed as $country){
            $clean[$country]=[];
            foreach(($methods[$country]??[]) as $m){
                $type=(string)($m['type']??'');
                if(!in_array($type,['card','sbp','crypto'],true)) continue;
                $clean[$country]=array_merge($clean[$country],[[
                    'type'=>$type,
                    'name_ru'=>trim((string)($m['name_ru']??'')),
                    'name_en'=>trim((string)($m['name_en']??'')),
                    'enabled'=>!empty($m['enabled'])
                ]]);
            }
        }
        $methodJson=json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $st=$pdo->prepare("UPDATE payment_settings SET provider=?,enabled=?,publishable_key=?,secret_key=?,webhook_secret=?,default_currency=?,method_configs=?,updated_by=? WHERE id=1");
        $st->execute([$provider,$enabled,payment_encrypt($pk),payment_encrypt($sk),payment_encrypt($wh),$currency,$methodJson,(int)$u['id']]);
        audit('admin.payment_settings_update',(int)$u['id'],['provider'=>$provider,'enabled'=>$enabled,'currency'=>$currency]);
        json_out(['ok'=>true]);
    }

    if($action==='notice_update'){
        ensure_notice_table();
        $enabled=!empty($in['enabled'])?1:0;
        $titleRu=trim((string)($in['title_ru']??''));
        $textRu=trim((string)($in['text_ru']??''));
        $titleEn=trim((string)($in['title_en']??''));
        $textEn=trim((string)($in['text_en']??''));
        if($titleRu==='' || $textRu==='' || $titleEn==='' || $textEn==='') json_out(['error'=>'All notice fields are required'],422);
        if(mb_strlen($titleRu)>255 || mb_strlen($titleEn)>255) json_out(['error'=>'Notice title is too long'],422);
        $st=$pdo->prepare("UPDATE site_notices SET enabled=?,title_ru=?,text_ru=?,title_en=?,text_en=?,updated_by=? WHERE notice_key='development'");
        $st->execute([$enabled,$titleRu,$textRu,$titleEn,$textEn,(int)$u['id']]);
        audit('admin.notice_update',(int)$u['id'],['enabled'=>$enabled]);
        json_out(['ok'=>true]);
    }

    if($action==='pricing_markup_update'){
        $markup=admin_float($in['markup_percent']??20);
        if($markup<0 || $markup>500) json_out(['error'=>'Наценка должна быть от 0 до 500%'],422);
        ai_set_markup_percent($markup);
        audit('admin.pricing_markup_update',(int)$u['id'],['markup_percent'=>$markup]);
        json_out(['ok'=>true,'markup_percent'=>ai_markup_percent()]);
    }

    if($action==='model_update'){
        $id=admin_int($in['id']??0);
        $cost=admin_float($in['credit_cost']??0);
        $enabled=!empty($in['enabled'])?1:0;
        if($id<1 || $cost<0) json_out(['error'=>'Invalid model data'],422);
        $st=$pdo->prepare("UPDATE ai_models SET credit_cost=?, enabled=? WHERE id=?");
        $st->execute([$cost,$enabled,$id]);
        audit('admin.model_update',(int)$u['id'],['model_id'=>$id,'credit_cost'=>$cost,'enabled'=>$enabled]);
        json_out(['ok'=>true]);
    }

    json_out(['error'=>'Unknown admin action'],404);
}

json_out(['error'=>'Method not allowed'],405);
