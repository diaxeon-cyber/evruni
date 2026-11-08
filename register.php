<?php
require_once __DIR__.'/_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if(!verify_turnstile((string)($_POST['cf-turnstile-response']??''))){ $error='Подтвердите, что вы не бот'; }
  else {
  rate_limit('register',5,600);
  $email=filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL); $name=trim($_POST['name']??''); $pass=(string)($_POST['password']??'');
  try {
    if(!$email || strlen($pass)<10 || strlen($name)<2) throw new Exception('Проверьте данные');
    $s=$pdo->prepare("INSERT INTO users(email,password_hash,name,credits) VALUES(?,?,?,?)");
    $s->execute([$email,password_hash($pass,PASSWORD_DEFAULT),$name,0]);
    $newUid=(int)$pdo->lastInsertId();
    attach_referral_to_new_user($newUid);
    $_SESSION['uid']=(int)$pdo->lastInsertId(); csrf_token(); header('Location:/'); exit;
  } catch(Throwable $e){$error='Не удалось создать аккаунт';}
  }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><title>Регистрация — Evruni</title><link rel="stylesheet" href="/assets/app.css?v=303"><link rel="stylesheet" href="/assets/glass-refresh.css?v=2"><?php if(captcha_enabled() && (string)envv('TURNSTILE_SITE_KEY','')!==''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?></head>
<body class="auth"><form class="auth-card" method="post"><div class="logo">◈ Evruni</div><h1>Создать аккаунт</h1><?php if(!empty($error)):?><div class="error"><?=e($error)?></div><?php endif;?><input name="name" placeholder="Имя" required><input name="email" type="email" placeholder="Email" required><input name="password" type="password" placeholder="Пароль (10+ символов)" required><?php if(captcha_enabled() && (string)envv('TURNSTILE_SITE_KEY','')!==''): ?>
<div class="evruni-turnstile"><div class="cf-turnstile" data-sitekey="<?=e((string)envv('TURNSTILE_SITE_KEY',''))?>" data-theme="dark" data-size="flexible"></div></div>
<?php endif; ?><?php $googleOAuth=oauth_provider_config('google'); $githubOAuth=oauth_provider_config('github'); ?>
<?php if($googleOAuth || $githubOAuth): ?>
<div class="evruni-auth-divider"><span>или продолжить</span></div>
<div class="evruni-socials">
  <?php if($googleOAuth): ?><a class="evruni-social evruni-google" href="/api/auth.php?action=oauth_start&provider=google"><span>G</span><span>Google</span></a><?php endif; ?>
  <?php if($githubOAuth): ?><a class="evruni-social evruni-github" href="/api/auth.php?action=oauth_start&provider=github"><span>⌘</span><span>GitHub</span></a><?php endif; ?>
</div>
<?php endif; ?><button class="primary">Зарегистрироваться</button><a href="/login.php">У меня уже есть аккаунт</a></form></body></html>
