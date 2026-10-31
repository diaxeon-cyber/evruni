<?php
declare(strict_types=1);
require_once ROOT_PATH . '/_private/config/config.php';

function donation_tables(): void {
    global $pdo;
    if (!$pdo) throw new RuntimeException('Database unavailable');
    $pdo->exec("CREATE TABLE IF NOT EXISTS creator_donation_profiles (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        slug VARCHAR(40) NOT NULL,
        title VARCHAR(120) NOT NULL DEFAULT 'Поддержать стримера',
        description VARCHAR(500) NOT NULL DEFAULT 'Спасибо за поддержку!',
        currency VARCHAR(8) NOT NULL DEFAULT 'USD',
        min_amount DECIMAL(12,2) NOT NULL DEFAULT 1.00,
        max_amount DECIMAL(12,2) NULL,
        payment_method VARCHAR(32) NOT NULL DEFAULT 'payment_link',
        payment_url TEXT NULL,
        payment_method_ru VARCHAR(32) NOT NULL DEFAULT 'yookassa',
        payment_url_ru TEXT NULL,
        payment_method_intl VARCHAR(32) NOT NULL DEFAULT 'paypal',
        payment_url_intl TEXT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        overlay_duration INT UNSIGNED NOT NULL DEFAULT 8,
        overlay_title VARCHAR(120) NOT NULL DEFAULT 'Новый донат',
        overlay_template VARCHAR(500) NOT NULL DEFAULT '💜 {name} поддержал на {amount} {currency}',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uq_donation_user (user_id), UNIQUE KEY uq_donation_slug (slug),
        KEY idx_donation_active_slug (slug, active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $columns = $pdo->query("SHOW COLUMNS FROM creator_donation_profiles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('payment_method_ru', $columns, true)) $pdo->exec("ALTER TABLE creator_donation_profiles ADD payment_method_ru VARCHAR(32) NOT NULL DEFAULT 'yookassa'");
    if (!in_array('payment_url_ru', $columns, true)) $pdo->exec("ALTER TABLE creator_donation_profiles ADD payment_url_ru TEXT NULL");
    if (!in_array('payment_method_intl', $columns, true)) $pdo->exec("ALTER TABLE creator_donation_profiles ADD payment_method_intl VARCHAR(32) NOT NULL DEFAULT 'paypal'");
    if (!in_array('payment_url_intl', $columns, true)) $pdo->exec("ALTER TABLE creator_donation_profiles ADD payment_url_intl TEXT NULL");
    $pdo->exec("CREATE TABLE IF NOT EXISTS creator_donation_intents (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        profile_id BIGINT UNSIGNED NOT NULL,
        public_id CHAR(32) NOT NULL,
        donor_name VARCHAR(80) NOT NULL DEFAULT 'Аноним',
        message VARCHAR(500) NOT NULL DEFAULT '',
        amount DECIMAL(12,2) NOT NULL,
        currency VARCHAR(8) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        provider_ref VARCHAR(160) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        paid_at DATETIME NULL,
        PRIMARY KEY (id), UNIQUE KEY uq_donation_public_id (public_id), KEY idx_donation_profile_status (profile_id,status,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function donation_slug(): string {
    return strtolower(substr(bin2hex(random_bytes(8)),0,12));
}

function donation_profile_language(?array $user=null): string {
    $user = $user ?: (function_exists('current_user') ? current_user() : null);
    $raw = '';
    if (is_array($user)) {
        foreach (['language','lang','locale','preferred_language'] as $key) {
            if (!empty($user[$key])) { $raw=(string)$user[$key]; break; }
        }
    }
    if ($raw==='' && isset($_SESSION['language'])) $raw=(string)$_SESSION['language'];
    if ($raw==='' && isset($_SESSION['lang'])) $raw=(string)$_SESSION['lang'];
    $raw=strtolower(str_replace('_','-',trim($raw)));
    return $raw!=='' ? $raw : 'ru';
}

function donation_provider_region(string $language): string {
    return str_starts_with(strtolower($language), 'ru') ? 'ru' : 'intl';
}

function donation_provider_options(string $region): array {
    if ($region==='ru') return [
        'yookassa'=>'ЮKassa',
        'robokassa'=>'Robokassa',
        'cloudpayments'=>'CloudPayments',
        'bank'=>'Банк / СБП',
        'payment_link'=>'Другая платёжная ссылка'
    ];
    return ['paypal'=>'PayPal'];
}

function donation_profile_for_user(int $uid): array {
    global $pdo;
    donation_tables();
    $s=$pdo->prepare('SELECT * FROM creator_donation_profiles WHERE user_id=? LIMIT 1');
    $s->execute([$uid]);
    $p=$s->fetch();
    if ($p) return $p;
    $slug=donation_slug();
    $s=$pdo->prepare('INSERT INTO creator_donation_profiles(user_id,slug,payment_method_ru,payment_method_intl) VALUES(?,?,?,?)');
    $s->execute([$uid,$slug,'yookassa','paypal']);
    $s=$pdo->prepare('SELECT * FROM creator_donation_profiles WHERE user_id=? LIMIT 1');
    $s->execute([$uid]);
    return $s->fetch() ?: [];
}

function donation_profile_by_slug(string $slug): ?array {
    global $pdo;
    donation_tables();
    $s=$pdo->prepare('SELECT * FROM creator_donation_profiles WHERE slug=? AND active=1 LIMIT 1');
    $s->execute([$slug]);
    $p=$s->fetch();
    return $p ?: null;
}

function donation_append_reference(string $url,string $ref): string {
    if ($url==='') return '';
    $sep=str_contains($url,'?')?'&':'?';
    return $url.$sep.'evruni_ref='.rawurlencode($ref);
}

function donation_public_url(string $slug): string {
    return rtrim((string)envv('APP_URL','https://evruni.com'),'/').'/donate.php?s='.rawurlencode($slug);
}
