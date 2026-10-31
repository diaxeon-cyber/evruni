<?php
require_once __DIR__.'/_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
require_once ROOT_PATH.'/_private/lib/credits.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
capture_referral_visit();
$u=current_user();
if($u){ $initialWalletBalance=wallet_total_converted((int)$u['id'],strtoupper((string)$u['currency'])); }
if(!$u){
?><!doctype html>
<html lang="ru" id="publicHtml">
<head><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/assets/favicon.svg">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#070a12">
<meta name="description" id="publicDescription" content="Evruni — современное AI-пространство для текста, изображений, исследований и API.">
<title>Evruni — AI Workspace</title>
<link rel="stylesheet" href="/assets/app.css?v=302">
</head>
<body class="public-page">

<div class="dashboard-ambient ambient-a"></div>
<div class="dashboard-ambient ambient-b"></div>

<header class="public-nav">
  <a class="logo" href="/" aria-label="Evruni"><span class="logo-mark" aria-hidden="true"><i></i></span><span class="logo-copy"><span class="logo-word">Evruni</span><span class="logo-tag">AI</span><span class="logo-comet" aria-hidden="true"><i></i></span></span></a>
<div class="public-actions">
    <a class="ghost" href="/login.php" data-i18n="login">Войти</a>
    <a class="primary" href="/register.php" data-i18n="create_account">Создать аккаунт</a>
  </div>
</header>

<main class="public-main">
      <div class="evruni-ai-ambient" aria-hidden="true">
        <span>OpenAI</span><span>Anthropic</span><span>Google AI</span><span>Meta AI</span>
        <span>xAI</span><span>Mistral AI</span><span>Cohere</span><span>DeepSeek</span>
        <span>Microsoft AI</span><span>Amazon AI</span><span>Adobe Firefly</span><span>Perplexity</span>
        <span>Stability AI</span><span>Runway</span><span>Hugging Face</span><span>ElevenLabs</span>
        <span>Midjourney</span><span>Groq</span><span>Qwen</span><span>Gemini</span>
      </div>

  <section class="public-hero">
    <div class="hero-copy">
      <div class="eyebrow"><span class="pulse-dot"></span> EVRUNI AI WORKSPACE</div>
      <h1 data-i18n-html="hero_title">Один AI-workspace.<br><span>Много возможностей.</span></h1>
      <p data-i18n="hero_text">Общайтесь с AI, создавайте изображения, исследуйте темы, работайте с документами и используйте API — в одном современном интерфейсе.</p>
      <div class="hero-actions">
        <button class="primary hero-ai" type="button" data-i18n="try_ai">Попробовать AI ✦</button>
        <a class="ghost" href="#features" data-i18n="view_features">Посмотреть возможности</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-orb">
<div class="orbit-one" aria-hidden="true"></div><div class="orbit-two" aria-hidden="true"></div><span>✦</span></div>
      <div class="hero-card hero-card-a"><b>AI Chat</b><small data-i18n="hero_chat_sub">Мульти-модельный диалог</small></div>
      <div class="hero-card hero-card-b"><b>Image Studio</b><small data-i18n="hero_image_sub">Генерация изображений</small></div>
      <div class="hero-card hero-card-c"><b>API</b><small data-i18n="hero_api_sub">OpenAI-compatible gateway</small></div>
    </div>
  </section>

  <section class="public-ai-card" id="ai">
    <div class="public-ai-head">
      <div>
        <div class="eyebrow">TRY EVRUNI</div>
        <h2 data-i18n="try_title">Что хотите сделать?</h2>
      </div>
      <span class="public-status"><i></i> AI online</span>
    </div>
    <div class="public-composer">
      <textarea id="publicPrompt" data-i18n-placeholder="prompt_placeholder" placeholder="Напишите запрос — например: «Придумай название для моего стартапа»"></textarea>
      <div class="public-composer-bottom">
        <span data-i18n="preview_note">Бесплатный просмотр интерфейса · авторизация потребуется перед отправкой</span>
        <button class="primary" id="publicSend" data-i18n="send">Отправить ✦</button>
      </div>
    </div>
  </section>

  <section class="public-section" id="features">
    <div class="section-heading">
      <div class="eyebrow">ONE WORKSPACE</div>
      <h2 data-i18n="features_title">Всё нужное для работы с AI</h2>
    </div>
    <div class="feature-grid">
      <article><span>✦</span><b>AI Chat</b><p data-i18n="feature_chat">Диалоги с современными AI-моделями и история запросов.</p></article>
      <article><span>◉</span><b>Image Studio</b><p data-i18n="feature_image">Создавайте визуалы из текста в едином рабочем пространстве.</p></article>
      <article><span>⌕</span><b>Research</b><p data-i18n="feature_research">Исследуйте темы и собирайте результаты в удобном формате.</p></article>
      <article><span>▤</span><b>Documents</b><p data-i18n="feature_docs">Загружайте документы и готовьте их для AI-сценариев.</p></article>
      <article><span>⌘</span><b>API</b><p data-i18n="feature_api">Используйте OpenAI-compatible API для своих приложений.</p></article>
      <article><span>₽</span><b>Usage & Billing</b><p data-i18n="feature_billing">Контролируйте баланс, расходы и активность в одном месте.</p></article>
    </div>
  </section>


  <section class="landing-feature-section landing-projects-section" id="projects-files">
    <div class="landing-projects-panel">
      <div class="landing-projects-copy">
        <div class="eyebrow">PROJECTS &amp; FILES</div>
        <h2>Храните контекст проекта в одном месте</h2>
        <p>Связывайте чаты, файлы и задачи внутри проектов, чтобы AI работал с нужным контекстом тогда, когда он нужен.</p>
      </div>
      <div class="landing-project-flow" aria-hidden="true">
        <div class="landing-flow-node landing-flow-project"><span>PROJECT</span><b>Evruni</b></div>
        <div class="landing-flow-line"></div>
        <div class="landing-flow-column">
          <div class="landing-flow-node"><span>CHATS</span><b>AI conversations</b></div>
          <div class="landing-flow-node"><span>FILES</span><b>Documents &amp; data</b></div>
        </div>
      </div>
    </div>
  </section>

  <section class="public-section public-strip" id="models">
    <div><div class="eyebrow">MODEL READY</div><h2 data-i18n="models_title_public">Единый интерфейс для разных AI-моделей</h2></div>
    <p data-i18n="models_text_public">Выбирайте модель под задачу и меняйте её без перестройки рабочего процесса.</p>
  </section>

  <section class="public-section public-strip" id="api">
    <div><div class="eyebrow">DEVELOPER READY</div><h2 data-i18n="api_title_public">API для ваших приложений</h2></div>
    <p data-i18n="api_text_public">Создавайте API-ключи, задавайте бюджеты и подключайте Evruni к собственным продуктам.</p>
  </section>
<section class="landing-intro-panel" aria-label="Evruni">
  <div class="landing-intro-glow"></div>
  <div class="landing-intro-copy">
    <span class="landing-intro-kicker">EVRUNI AI</span>
    <h2 data-i18n="landing_title">Один AI-пространство для твоих задач</h2>
    <p data-i18n="landing_text">Чаты, изображения, документы, исследования и проекты — в одном месте.</p>
    <div class="landing-intro-pills">
      <span data-i18n="landing_pill_chat">AI Chat</span>
      <span data-i18n="landing_pill_files">Files & Projects</span>
      <span data-i18n="landing_pill_tools">AI Tools</span>
    </div>
    <div class="landing-credit-box">
      <div>
        <strong data-i18n="landing_credits_title">Баланс для AI</strong>
        <small data-i18n="landing_credits_text">Пополняй баланс и используй его для AI-функций.</small>
      </div>
      <button type="button" class="landing-pricing-btn" data-i18n="landing_credits_btn">Пополнить баланс</button>
    </div>
  </div>
  <div class="landing-intro-orbit" aria-hidden="true">
    <div class="landing-orbit-ring landing-ring-one"></div>
    <div class="landing-orbit-ring landing-ring-two"></div>
    <div class="landing-orbit-ring landing-ring-three"></div>
<div class="landing-orbit-pulse"></div>
    <i></i><i></i><i></i><i></i>
    <b>AI</b>
  </div>
</section>

</main>

<footer class="public-footer">
  <span>◇ Evruni</span>
  <span data-i18n="workspace_footer">AI Workspace</span>
  <div><a href="/login.php" data-i18n="login">Войти</a><a href="/register.php" data-i18n="register">Регистрация</a></div>
</footer>

<div class="auth-modal" id="authModal" aria-hidden="true">
  <div class="auth-backdrop" data-close-auth></div>
  <div class="auth-modal-card" role="dialog" aria-modal="true" aria-labelledby="authTitle">
    <button class="auth-close" data-close-auth aria-label="Закрыть">×</button>
    <div class="empty-orb auth-orb">✦</div>
    <div class="eyebrow">EVRUNI AI</div>
    <h2 id="authTitle" data-i18n="auth_title">Войдите, чтобы продолжить</h2>
    <p data-i18n="auth_text">Ваш запрос сохранится после входа. Создайте аккаунт или войдите в существующий.</p>
    <div class="auth-modal-actions">
      <a class="primary" href="/login.php" data-i18n="login">Войти</a>
      <a class="ghost" href="/register.php" data-i18n="create_account">Создать аккаунт</a>
    </div>
  </div>
</div>

<script>
window.EVRUNI_PUBLIC=true;
(function(){
  const modal=document.getElementById('authModal');
  const prompt=document.getElementById('publicPrompt');
  const dict={
    ru:{login:'Войти',create_account:'Создать аккаунт',register:'Регистрация',hero_title:'Один AI-workspace.<br><span>Много возможностей.</span>',hero_text:'Общайтесь с AI, создавайте изображения, исследуйте темы, работайте с документами и используйте API — в одном современном интерфейсе.',try_ai:'Попробовать AI ✦',view_features:'Посмотреть возможности',hero_chat_sub:'Мульти-модельный диалог',hero_image_sub:'Генерация изображений',hero_api_sub:'OpenAI-compatible gateway',try_title:'Что хотите сделать?',prompt_placeholder:'Напишите запрос — например: «Придумай название для моего стартапа»',preview_note:'Бесплатный просмотр интерфейса · авторизация потребуется перед отправкой',send:'Отправить ✦',features_title:'Всё нужное для работы с AI',feature_chat:'Диалоги с современными AI-моделями и история запросов.',feature_image:'Создавайте визуалы из текста в едином рабочем пространстве.',feature_research:'Исследуйте темы и собирайте результаты в удобном формате.',feature_docs:'Загружайте документы и готовьте их для AI-сценариев.',feature_api:'Используйте OpenAI-compatible API для своих приложений.',feature_billing:'Контролируйте баланс, расходы и активность в одном месте.',models_title_public:'Единый интерфейс для разных AI-моделей',models_text_public:'Выбирайте модель под задачу и меняйте её без перестройки рабочего процесса.',api_title_public:'API для ваших приложений',api_text_public:'Создавайте API-ключи, задавайте бюджеты и подключайте Evruni к собственным продуктам.',workspace_footer:'AI Workspace',auth_title:'Войдите, чтобы продолжить',auth_text:'Ваш запрос сохранится после входа. Создайте аккаунт или войдите в существующий.'},
    en:{login:'Log in',create_account:'Create account',register:'Register',hero_title:'One AI workspace.<br><span>More possibilities.</span>',hero_text:'Chat with AI, create images, research topics, work with documents, and use an API — all in one modern workspace.',try_ai:'Try AI ✦',view_features:'Explore features',hero_chat_sub:'Multi-model dialogue',hero_image_sub:'Image generation',hero_api_sub:'OpenAI-compatible gateway',try_title:'What do you want to create?',prompt_placeholder:'Write a request — for example: “Come up with a name for my startup”',preview_note:'Free interface preview · sign-in is required before sending',send:'Send ✦',features_title:'Everything you need to work with AI',feature_chat:'Conversations with modern AI models and request history.',feature_image:'Create visuals from text in one unified workspace.',feature_research:'Research topics and collect results in a convenient format.',feature_docs:'Upload documents and prepare them for AI workflows.',feature_api:'Use the OpenAI-compatible API for your applications.',feature_billing:'Control credits, spending, and activity in one place.',models_title_public:'One interface for different AI models',models_text_public:'Choose the right model for the task and switch without rebuilding your workflow.',api_title_public:'API for your applications',api_text_public:'Create API keys, set budgets, and connect Evruni to your own products.',workspace_footer:'AI Workspace',auth_title:'Sign in to continue',auth_text:'Your request will be saved after sign-in. Create an account or sign in to an existing one.'}
  };
  function detectLanguage(){
    const langs=(navigator.languages&&navigator.languages.length?navigator.languages:[navigator.language||'en']).map(x=>String(x).toLowerCase());
    return langs.some(x=>x.startsWith('ru')||x.startsWith('uk')||x.startsWith('be'))?'ru':'en';
  }
  const lang=detectLanguage();
  document.documentElement.lang=lang;
  document.title=lang==='ru'?'Evruni — AI Workspace':'Evruni — AI Workspace';
  document.querySelectorAll('[data-i18n]').forEach(el=>{const k=el.dataset.i18n;if(dict[lang][k])el.textContent=dict[lang][k];});
  document.querySelectorAll('[data-i18n-html]').forEach(el=>{const k=el.dataset.i18nHtml;if(dict[lang][k])el.innerHTML=dict[lang][k];});
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el=>{const k=el.dataset.i18nPlaceholder;if(dict[lang][k])el.placeholder=dict[lang][k];});
  const modalOpen=()=>{modal.classList.add('show');modal.setAttribute('aria-hidden','false');document.body.classList.add('modal-open');};
  const close=()=>{modal.classList.remove('show');modal.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open');};
  document.querySelectorAll('[data-close-auth]').forEach(x=>x.addEventListener('click',close));
  document.querySelector('.hero-ai')?.addEventListener('click',()=>{document.getElementById('ai')?.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(()=>prompt?.focus(),450);});
  document.getElementById('publicSend')?.addEventListener('click',modalOpen);
  prompt?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();modalOpen();}});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
})();
</script>
</body>
</html><?php
exit;
}
?>
<!doctype html><html lang="ru" id="publicHtml"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#070a12"><title>Evruni — AI Workspace</title><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="/assets/app.css?v=302"></head><body class="dashboard-page"><div class="dashboard-ambient ambient-a"></div><div class="dashboard-ambient ambient-b"></div>
<div class="app"><aside class="sidebar"><a class="logo" href="/" aria-label="Evruni"><span class="logo-mark" aria-hidden="true"><i></i></span><span class="logo-copy"><span class="logo-word">Evruni</span><span class="logo-tag">AI</span></span></a><nav>
<button class="nav active" data-view="chat"><span>✦</span> <span data-i18n="nav_chat">AI Chat</span></button><button class="nav" data-view="image"><span>✧</span> <span data-i18n="nav_image">Image</span></button><button class="nav" data-view="video"><span>▷</span> <span data-i18n="nav_video">Video</span></button><button class="nav trend-nav" data-view="trendlab"><span>✦</span> <span data-i18n="nav_trendlab">Trend Lab</span></button><button class="nav" data-view="audio"><span>◒</span> <span data-i18n="nav_audio">Audio</span></button><button class="nav" data-view="research"><span>⌕</span> <span data-i18n="nav_research">Research</span></button><button class="nav" data-view="documents"><span>▤</span> <span data-i18n="nav_documents">Documents</span></button><button class="nav" data-view="projects"><span>◈</span> <span data-i18n="nav_projects">Projects</span></button><button class="nav" data-view="models"><span>⬡</span> <span data-i18n="nav_models">Models</span></button><button class="nav" data-view="presets"><span>◈</span> <span data-i18n="nav_presets">Presets</span></button><button class="nav" data-view="api"><span>⌘</span> <span data-i18n="nav_api">API Keys</span></button><button class="nav" data-view="sandbox"><span>◌</span> <span data-i18n="nav_sandbox">Sandbox</span></button><button class="nav bonus-nav" data-view="bonuses"><span class="bonus-nav-icon" aria-hidden="true">✦</span> <span data-i18n="nav_bonuses">Бонусы</span></button></nav><div class="side-bottom"><button class="nav" data-view="support"><span>◎</span> <span data-i18n="nav_support">Поддержка</span></button><?php if(($u['role']??'user')==='admin'): ?><a class="nav admin-nav" href="/admin.php"><span>◆</span> <span data-i18n="nav_admin">Admin</span></a><?php endif; ?></div></aside>
<main class="main"><header><div><div class="eyebrow"><span class="pulse-dot"></span> EVRUNI AI WORKSPACE</div><h1 id="viewTitle">What will you create?</h1></div><div class="header-tools"><div class="global-search-wrap"><input id="globalSearchInput" class="global-search-input" placeholder="Поиск Evruni…"><div id="globalSearchResults" class="global-search-results"></div></div><div class="profile-menu-wrap dashboard-profile" id="profileMenuWrap">
<button class="profile-trigger" id="profileTrigger" type="button" aria-expanded="false">
<span class="profile-credits"><span id="credits"><?=e(number_format((float)$initialWalletBalance,2,'.',''))?></span><small id="balanceCurrencyTop"><?=e((string)($initialCurrency??'EUR'))?></small></span>
<span class="profile-avatar"><?=e(strtoupper(substr((string)($u['name'] ?? 'U'),0,1)))?></span>
<span class="profile-trigger-text"><b><?=e($u['name'])?></b><small><?=e($u['email'] ?? '')?></small></span>
<span class="profile-chevron">⌄</span>
</button>
<div class="profile-dropdown" id="profileDropdown" aria-hidden="true">
<div class="profile-dropdown-head"><span class="profile-avatar large"><?=e(strtoupper(substr((string)($u['name'] ?? 'U'),0,1)))?></span><div><b><?=e($u['name'])?></b><small><?=e($u['email'] ?? '')?></small></div></div>
<div class="profile-balance"><span data-i18n="profile_balance">Баланс</span><strong><span id="profileCredits"><?=e(number_format((float)$initialWalletBalance,2,'.',''))?></span> <span id="profileCurrencySymbol">€</span></strong></div>
<button type="button" class="profile-item" data-profile-action="profile">⚙️ <span data-i18n="profile_settings">Профиль и настройки</span></button>
<button type="button" class="profile-item" data-profile-action="billing">₽ <span data-i18n="profile_billing">Баланс и пополнение</span></button>
<button type="button" class="profile-item" data-profile-action="usage">◔ <span data-i18n="profile_usage">Использование</span></button>
<button type="button" class="profile-item danger" data-profile-action="logout">↪ <span data-i18n="profile_logout">Выйти</span></button>
<?php if(($u['role']??'user')==='admin'): ?><a class="profile-item" href="/admin.php">◆ <span data-i18n="profile_admin">Админ-панель</span></a><?php endif; ?>
<div class="profile-divider"></div>

</div></div></div></header>
<div class="dev-notice" id="devNotice" role="status" aria-live="polite">
  <span class="dev-notice-dot"></span>
  <div class="dev-notice-copy">
    <strong data-i18n="development_title">Evruni находится в разработке</strong>
    <span data-i18n="development_text">Некоторые функции временно недоступны. Мы работаем над их запуском.</span>
  </div>
</div>

<div id="view"></div></main></div><script>window.EVRUNI={csrf:"<?=e(csrf_token())?>",name:<?=json_encode($u['name'])?>};</script><script>
window.EVRUNI_LANG=<?=json_encode((string)($u['language']??'ru'))?>;
document.documentElement.lang=window.EVRUNI_LANG;
</script>
<script src="/assets/app.js?v=303"></script>
<!-- Evruni Center v15.82 -->
<div class="evruni-center" id="evruniCenter">
  <button class="evruni-center-btn" id="evruniCenterBtn" type="button" aria-label="Центр Evruni">
    <span class="evruni-center-icon">✦</span>
    <span class="evruni-center-badge" id="evruniCenterBadge">0</span>
  </button>
  <div class="evruni-center-panel" id="evruniCenterPanel" hidden>
    <div class="evruni-center-head">
      <div>
        <div class="evruni-center-kicker">EVRUNI</div>
        <div class="evruni-center-title">Центр</div>
      </div>
      <button class="evruni-center-clear" id="evruniCenterClear" type="button">Очистить</button>
    </div>
    <div class="evruni-center-list" id="evruniCenterList">
      <div class="evruni-center-empty">Здесь появятся важные события.</div>
    </div>
  </div>
</div>

</body></html>