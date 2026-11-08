<?php
require_once __DIR__.'/_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if(!verify_turnstile((string)($_POST['cf-turnstile-response']??''))){ $error='Подтвердите, что вы не бот'; }
  else {
  rate_limit('login',8,600);
  $s=$pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
  $s->execute([trim($_POST['email']??'')]); $u=$s->fetch();
  if ($u && password_verify($_POST['password']??'',$u['password_hash'])) {
    session_regenerate_id(true); $_SESSION['uid']=(int)$u['id']; csrf_token(); audit('login',(int)$u['id']); header('Location:/'); exit;
  }
  $error='Неверный email или пароль';
  }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><title>Вход — Evruni</title><link rel="stylesheet" href="/assets/app.css?v=303"><link rel="stylesheet" href="/assets/glass-refresh.css?v=2"><?php if(captcha_enabled() && (string)envv('TURNSTILE_SITE_KEY','')!==''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?></head>
<body class="auth"><form class="auth-card" method="post"><div class="logo">◈ Evruni</div><h1>С возвращением</h1><?php if(!empty($error)):?><div class="error"><?=e($error)?></div><?php endif;?><input name="email" type="email" placeholder="Email" required><input name="password" type="password" placeholder="Пароль" required><?php if(captcha_enabled() && (string)envv('TURNSTILE_SITE_KEY','')!==''): ?>
<div class="evruni-turnstile"><div class="cf-turnstile" data-sitekey="<?=e((string)envv('TURNSTILE_SITE_KEY',''))?>" data-theme="dark" data-size="flexible"></div></div>
<?php endif; ?><?php $googleOAuth=oauth_provider_config('google'); $githubOAuth=oauth_provider_config('github'); ?>
<?php if($googleOAuth || $githubOAuth): ?>
<div class="evruni-auth-divider"><span>или продолжить</span></div>
<div class="evruni-socials">
  <?php if($googleOAuth): ?><a class="evruni-social evruni-google" href="/api/auth.php?action=oauth_start&provider=google"><span>G</span><span>Google</span></a><?php endif; ?>
  <?php if($githubOAuth): ?><a class="evruni-social evruni-github" href="/api/auth.php?action=oauth_start&provider=github"><span>⌘</span><span>GitHub</span></a><?php endif; ?>
</div>
<?php endif; ?><button class="primary">Войти</button><a href="/register.php">Создать аккаунт</a></form></body></html>
