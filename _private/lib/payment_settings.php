<?php
declare(strict_types=1);

function ensure_payment_settings_table(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_settings (
      id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
      provider VARCHAR(40) NOT NULL DEFAULT 'stripe',
      enabled TINYINT(1) NOT NULL DEFAULT 0,
      publishable_key TEXT NULL,
      secret_key TEXT NULL,
      webhook_secret TEXT NULL,
      default_currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
      method_configs LONGTEXT NULL,
      updated_by BIGINT UNSIGNED NULL,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try { $pdo->exec("ALTER TABLE payment_settings ADD COLUMN method_configs LONGTEXT NULL"); } catch (Throwable $e) {}
    $pdo->exec("INSERT IGNORE INTO payment_settings(id,provider,enabled,default_currency,method_configs) VALUES(1,'stripe',0,'EUR',NULL)");
}

function payment_crypto_key(): string {
    $key=(string)envv('APP_KEY','dev-only-change-me');
    return hash('sha256',$key,true);
}
function payment_encrypt(string $value): string {
    if($value==='') return '';
    $iv=random_bytes(12); $tag='';
    $cipher=openssl_encrypt($value,'aes-256-gcm',payment_crypto_key(),OPENSSL_RAW_DATA,$iv,$tag);
    return base64_encode($iv.$tag.$cipher);
}
function payment_decrypt(?string $value): string {
    if(!$value) return '';
    $raw=base64_decode($value,true);
    if($raw===false || strlen($raw)<28) return '';
    $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
    $plain=openssl_decrypt($cipher,'aes-256-gcm',payment_crypto_key(),OPENSSL_RAW_DATA,$iv,$tag);
    return $plain===false?'':$plain;
}
function payment_settings(): array {
    global $pdo;
    ensure_payment_settings_table();
    $row=$pdo->query("SELECT * FROM payment_settings WHERE id=1 LIMIT 1")->fetch();
    if(!$row) $row=['provider'=>'stripe','enabled'=>0,'publishable_key'=>'','secret_key'=>'','webhook_secret'=>'','default_currency'=>'EUR','method_configs'=>null];
    $defaults=[
      'NL'=>[['card','Карта','Card',true],['crypto','Крипта','Crypto',false]],
      'RU'=>[['sbp','СБП','SBP',true],['card','Карта','Card',true],['crypto','Крипта','Crypto',false]],
      'OTHER'=>[['card','Карта','Card',true],['crypto','Крипта','Crypto',false]]
    ];
    $cfg=json_decode((string)($row['method_configs']??''),true);
    if(!is_array($cfg)) $cfg=$defaults;
    $row['method_configs']=$cfg;
    $row['publishable_key']=payment_decrypt($row['publishable_key']??'');
    $row['secret_key']=payment_decrypt($row['secret_key']??'');
    $row['webhook_secret']=payment_decrypt($row['webhook_secret']??'');
    return $row;
}
