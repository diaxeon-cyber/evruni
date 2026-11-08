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
<meta name="description" id="publicDescription" content="Evruni — современное AI-пространство для текста, изображений, исследований, трендов и стримеров.">
<title>Evruni — AI Workspace</title>
<link rel="stylesheet" href="/assets/app.css?v=302">
<style>
.public-extra{width:min(1180px,calc(100% - 40px));margin:0 auto 90px;position:relative;z-index:2}
.public-section-title{font-size:clamp(28px,5vw,48px);line-height:1.05;margin:0 0 12px;color:#f5f7ff;letter-spacing:-.03em}
.public-section-sub{margin:0 0 28px;color:#8792aa;font-size:16px;line-height:1.65;max-width:720px}
.public-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
.public-card{position:relative;overflow:hidden;min-height:180px;padding:24px;border:1px solid rgba(150,160,210,.14);border-radius:24px;background:linear-gradient(145deg,rgba(22,27,50,.86),rgba(8,12,24,.94));box-shadow:0 18px 60px rgba(0,0,0,.2)}
.public-card:after{content:"";position:absolute;width:120px;height:120px;right:-50px;top:-50px;border-radius:50%;background:radial-gradient(circle,rgba(120,90,255,.25),transparent 70%);pointer-events:none}
.public-card .icon{font-size:28px;display:block;margin-bottom:18px}.public-card h3{margin:0 0 8px;color:#f5f7ff;font-size:19px}.public-card p{margin:0;color:#8d98af;line-height:1.55;font-size:14px}
.public-card a{color:inherit;text-decoration:none}.public-wide{grid-column:span 2}.public-highlight{background:linear-gradient(135deg,rgba(48,29,92,.9),rgba(11,25,53,.95));border-color:rgba(124,110,255,.28)}
.public-trends{margin-top:24px;padding:30px;border-radius:28px;border:1px solid rgba(78,196,255,.18);background:linear-gradient(135deg,rgba(8,30,48,.9),rgba(19,12,46,.92));display:grid;grid-template-columns:1.15fr .85fr;gap:24px;align-items:center}
.public-pills{display:flex;flex-wrap:wrap;gap:9px;margin-top:18px}.public-pill{padding:8px 12px;border-radius:999px;border:1px solid rgba(120,190,255,.2);background:rgba(255,255,255,.045);color:#b8d9ff;font-size:12px}
.public-cta{margin-top:24px;display:inline-flex;align-items:center;gap:8px;padding:12px 18px;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#4f8cff);color:#fff!important;font-weight:700;text-decoration:none}
.public-stream{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.public-stream .public-card{min-height:155px}
@media(max-width:900px){.public-grid,.public-stream{grid-template-columns:repeat(2,minmax(0,1fr))}.public-wide{grid-column:span 2}.public-trends{grid-template-columns:1fr}}
@media(max-width:560px){.public-extra{width:min(100% - 24px,1180px);margin-bottom:55px}.public-grid,.public-stream{grid-template-columns:1fr}.public-wide{grid-column:span 1}.public-card{min-height:150px;padding:20px}.public-trends{padding:22px}.public-section-title{font-size:30px}}

/* Public landing refresh */
.public-page{overflow-x:hidden;background:#070914}
.public-nav{width:min(1240px,calc(100% - 48px));margin:0 auto;padding:22px 0;position:relative;z-index:5}
.public-main{padding-top:30px}
.public-hero{width:min(1240px,calc(100% - 48px));min-height:470px;margin:0 auto 76px;padding:clamp(34px,7vw,82px);display:flex;align-items:center;position:relative;overflow:hidden;border:1px solid rgba(157,139,255,.22);border-radius:36px;background:radial-gradient(ellipse at 82% 20%,rgba(107,76,255,.30),transparent 36%),radial-gradient(ellipse at 12% 100%,rgba(40,128,255,.16),transparent 42%),linear-gradient(135deg,rgba(23,23,53,.96),rgba(8,12,27,.98));box-shadow:0 30px 100px rgba(0,0,0,.34),inset 0 1px rgba(255,255,255,.06)}
.public-hero:after{content:"";position:absolute;width:430px;height:430px;right:-100px;bottom:-220px;border:1px solid rgba(163,145,255,.18);border-radius:50%;box-shadow:0 0 0 34px rgba(145,120,255,.035),0 0 0 72px rgba(145,120,255,.025),0 0 100px rgba(122,88,255,.2);pointer-events:none}
.hero-copy{max-width:760px;position:relative;z-index:1}
.public-hero h1{font-size:clamp(42px,7vw,76px);line-height:.99;letter-spacing:-.055em;margin:22px 0;color:#f8f8ff}
.public-hero h1 span{background:linear-gradient(95deg,#c6a7ff 5%,#8b9cff 55%,#7de0ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.public-hero p{max-width:650px;font-size:clamp(16px,2vw,19px);line-height:1.7;color:#aab3ca}
.public-hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}
.public-hero-actions a{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:15px;text-decoration:none;font-weight:750}
.public-hero-actions .primary{background:linear-gradient(120deg,#9064ff,#547fff);color:#fff;box-shadow:0 12px 34px rgba(108,88,255,.28)}
.public-hero-actions .secondary{color:#e4e7ff;border:1px solid rgba(190,190,255,.2);background:rgba(255,255,255,.045)}
.public-extra{margin-top:0}
.public-section-title{letter-spacing:-.045em}
.public-card{transition:transform .22s ease,border-color .22s ease,background .22s ease;backdrop-filter:blur(16px)}
.public-card:hover{transform:translateY(-4px);border-color:rgba(157,139,255,.38);background:linear-gradient(145deg,rgba(31,34,67,.94),rgba(10,15,31,.98))}
.public-card .icon{width:48px;height:48px;display:grid;place-items:center;border-radius:15px;background:linear-gradient(145deg,rgba(139,92,246,.22),rgba(79,140,255,.12));border:1px solid rgba(165,150,255,.18)}
.public-cta{transition:transform .2s ease,filter .2s ease}.public-cta:hover{transform:translateY(-2px);filter:brightness(1.12)}
.public-trends{box-shadow:inset 0 1px rgba(255,255,255,.04),0 22px 70px rgba(0,0,0,.18)}
@media(max-width:700px){.public-nav{width:calc(100% - 28px);padding:16px 0}.public-main{padding-top:12px}.public-hero{width:calc(100% - 28px);min-height:420px;margin-bottom:54px;padding:28px 22px;border-radius:26px}.public-hero h1{font-size:clamp(39px,11vw,58px)}.public-extra{width:calc(100% - 28px)}.public-section-title{font-size:29px}}


.public-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:30px 0 72px}
.public-step{padding:23px;border-radius:22px;border:1px solid rgba(150,160,210,.14);background:rgba(15,20,39,.72)}
.public-step b{display:block;color:#c7b8ff;font-size:12px;letter-spacing:.12em;margin-bottom:14px}
.public-step h3{margin:0 0 8px;color:#f5f7ff;font-size:18px}
.public-step p{margin:0;color:#929db5;font-size:14px;line-height:1.6}
.public-bottom-cta{margin:68px 0 20px;padding:clamp(26px,5vw,48px);text-align:center;border:1px solid rgba(150,130,255,.2);border-radius:28px;background:radial-gradient(ellipse at 50% 0,rgba(123,85,255,.2),transparent 65%),rgba(15,18,38,.78)}
.public-bottom-cta h2{font-size:clamp(27px,4vw,40px);letter-spacing:-.04em;color:#f5f7ff;margin:0 0 10px}
.public-bottom-cta p{color:#929db5;line-height:1.6;margin:0 auto 20px;max-width:620px}
@media(max-width:700px){.public-steps{grid-template-columns:1fr;margin-bottom:52px}.public-step{padding:20px}.public-bottom-cta{margin-top:50px}}


.public-grid .public-card{grid-column:span 2;min-height:155px;padding:21px;border-radius:22px}
.public-grid .public-card:nth-child(5),.public-grid .public-card:nth-child(6){grid-column:span 3}
.public-grid .public-card .icon{width:48px;height:48px;display:grid;place-items:center;margin:0 0 14px;border-radius:15px;background:linear-gradient(145deg,rgba(169,94,255,.25),rgba(75,118,255,.13));border:1px solid rgba(187,151,255,.24);color:#d9b9ff}
.public-creator-panel{position:relative;display:grid;grid-template-columns:.95fr 1.05fr;gap:clamp(22px,4vw,52px);align-items:center;overflow:hidden;margin-top:72px;padding:clamp(22px,4vw,42px);border:1px solid rgba(193,112,255,.55);border-radius:30px;background:radial-gradient(ellipse at 15% 45%,rgba(130,46,255,.35),transparent 52%),linear-gradient(115deg,rgba(34,16,76,.96),rgba(12,13,36,.98));box-shadow:0 0 42px rgba(144,66,255,.18),inset 0 1px rgba(255,255,255,.09)}
.creator-art{min-height:280px;position:relative;display:flex;align-items:center;justify-content:center;isolation:isolate}
.creator-screen{width:76%;height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;border:1px solid rgba(210,174,255,.65);border-radius:22px;background:linear-gradient(145deg,rgba(117,54,205,.7),rgba(15,21,55,.96));box-shadow:0 0 38px rgba(161,76,255,.42);transform:rotate(-5deg);color:#fff}
.creator-screen span{font-size:20px;font-weight:900;letter-spacing:.14em}.creator-screen b{font-size:48px;text-shadow:0 0 22px #d86bff}.creator-screen i,.creator-phone i{font-size:10px;letter-spacing:.18em;color:#d8c5ff;font-style:normal}
.creator-phone{position:absolute;right:2%;bottom:0;width:104px;height:190px;border:2px solid #d6b5ff;border-radius:22px;background:linear-gradient(160deg,#4a1c87,#10162e);box-shadow:0 0 30px rgba(200,80,255,.5);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;transform:rotate(8deg);color:white}
.creator-phone span{font-size:35px}.creator-phone b{font-size:30px}.creator-orbit{position:absolute;z-index:-1;width:310px;height:90px;border:2px solid rgba(187,93,255,.5);border-radius:50%;transform:rotate(-18deg);box-shadow:0 0 25px rgba(169,65,255,.4)}
.creator-copy h2{font-size:clamp(26px,3vw,40px);line-height:1.08;letter-spacing:-.04em;color:#fff;margin:14px 0}
.creator-copy p{font-size:15px;line-height:1.65;color:#c0bdd7;max-width:570px}
.creator-copy ul{list-style:none;padding:0;margin:20px 0;display:grid;gap:11px;color:#e7e0ff}
.creator-copy li{display:flex;gap:10px;align-items:center}.creator-copy li span{display:grid;place-items:center;width:22px;height:22px;flex:0 0 22px;border-radius:7px;background:linear-gradient(135deg,#a94dff,#633bda);color:white;font-weight:900}
@media(max-width:900px){.public-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.public-grid .public-card,.public-grid .public-card:nth-child(5),.public-grid .public-card:nth-child(6){grid-column:span 1}.public-creator-panel{grid-template-columns:1fr}.creator-art{min-height:230px;order:0}.creator-copy{order:1}}
@media(max-width:560px){.public-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.public-grid .public-card,.public-grid .public-card:nth-child(5),.public-grid .public-card:nth-child(6){min-height:142px;padding:15px}.public-grid .public-card h3{font-size:15px}.public-grid .public-card p{font-size:12px}.public-grid .public-card .icon{width:40px;height:40px;font-size:22px;margin-bottom:10px}.public-creator-panel{margin-top:54px;padding:20px;border-radius:24px;gap:12px}.creator-art{min-height:190px}.creator-screen{height:160px}.creator-phone{height:150px;width:82px}.creator-copy h2{font-size:28px}.creator-copy li{font-size:13px}}


.public-nav{display:flex;align-items:center;justify-content:space-between;gap:24px;border-bottom:1px solid rgba(164,145,255,.12)}.public-links{display:flex;align-items:center;gap:clamp(16px,2.5vw,34px)}.public-links a{color:#c6c9dc;text-decoration:none;font-size:14px;font-weight:650;transition:color .2s ease}.public-links a:hover{color:#d7b5ff}.public-actions{display:flex;align-items:center;gap:10px}.public-actions .ghost,.public-actions .primary{display:inline-flex;align-items:center;justify-content:center;padding:11px 17px;border-radius:13px;text-decoration:none;font-weight:750}.public-actions .ghost{color:#e8e7f5;border:1px solid rgba(190,190,255,.2);background:rgba(255,255,255,.045)}.public-actions .primary{color:#fff;background:linear-gradient(120deg,#a34dff,#744cff);box-shadow:0 8px 28px rgba(139,75,255,.28)}
@media(max-width:760px){.public-nav{flex-wrap:wrap;gap:14px;padding:14px 0}.public-links{order:3;width:100%;justify-content:space-between;gap:10px;padding:8px 2px 2px}.public-links a{font-size:12px}.public-actions{margin-left:auto}.public-actions .ghost,.public-actions .primary{padding:9px 12px;font-size:12px}}
@media(max-width:390px){.public-actions .primary{font-size:11px;padding:9px}.public-links a{font-size:11px}}

</style>
</head>
<body class="public-page">
<div class="dashboard-ambient ambient-a"></div><div class="dashboard-ambient ambient-b"></div>
<header class="public-nav"><a class="logo" href="/" aria-label="Evruni"><span class="logo-mark" aria-hidden="true"><i></i></span><span class="logo-copy"><span class="logo-word">Evruni</span><span class="logo-tag">AI</span></span></a><nav class="public-links" aria-label="Главная навигация"><a href="#capabilities">Возможности</a><a href="#trend-lab">Trend Lab</a><a href="#creator-tools">Для стримеров</a></nav><div class="public-actions"><a class="ghost" href="/login.php">Войти</a><a class="primary" href="/register.php">Начать бесплатно</a></div></header>
<main class="public-main">
<section class="public-hero"><div class="hero-copy"><div class="eyebrow"><span class="pulse-dot"></span> EVRUNI · AI WORKSPACE</div><h1>Твоя работа с AI.<br><span>В одном пространстве.</span></h1><p>Создавай изображения и видео, общайся с AI, исследуй идеи и собирай контент. Все основные инструменты — в едином рабочем пространстве Evruni.</p><div class="public-hero-actions"><a class="primary" href="/register.php">Начать бесплатно <span aria-hidden="true">→</span></a><a class="secondary" href="#capabilities">Изучить возможности</a></div></div></section>

<section class="public-extra" id="capabilities" aria-label="Возможности Evruni">
<h2 class="public-section-title">Всё главное — в одном месте.</h2>
<p class="public-section-sub">Не просто чат с AI. Evruni объединяет инструменты для создания контента, работы, исследований, трендов и стриминга.</p>
<div class="public-grid">
<article class="public-card"><span class="icon">✦</span><h3>AI Chat</h3><p>Общение с AI для идей, задач, текстов, решений и повседневной работы.</p></article>
<article class="public-card"><span class="icon">✧</span><h3>Image & Video</h3><p>Создавай визуальный контент и превращай идеи в готовые материалы.</p></article>
<article class="public-card"><span class="icon">⌕</span><h3>Research</h3><p>Исследуй темы, собирай информацию и получай структурированные ответы.</p></article>
<article class="public-card"><span class="icon">▤</span><h3>Documents</h3><p>Работай с документами, текстами и большими материалами без лишних сервисов.</p></article>
<article class="public-card public-wide public-highlight"><span class="icon">↗</span><h3>Trend Lab</h3><p>Отслеживай направления, которые набирают внимание, находи интересные темы и превращай тренды в идеи для контента.</p><div class="public-pills"><span class="public-pill">Растущие темы</span><span class="public-pill">Идеи контента</span><span class="public-pill">Анализ тренда</span><span class="public-pill">Контент-поводы</span></div><a class="public-cta" href="/register.php">Открыть Trend Lab →</a></article>
<article class="public-card public-wide"><span class="icon">🎥</span><h3>Creator Tools</h3><p>AI-инструменты для стримеров, которые работают поверх Twitch, YouTube и других платформ: чат, модерация, alerts, клипы, аналитика и управление стримом.</p><a class="public-cta" href="/register.php">Инструменты для стримера →</a></article>
</div>

<h2 class="public-section-title" style="margin-top:64px">От идеи до результата — без лишних переключений.</h2>
<p class="public-section-sub">Простой рабочий процесс: выбери инструмент, опиши задачу и продолжай работу в одном пространстве.</p>
<div class="public-steps"><article class="public-step"><b>01 / ВЫБЕРИ</b><h3>Подходящий инструмент</h3><p>Начни с чата, визуального контента, исследования или другого раздела Evruni.</p></article><article class="public-step"><b>02 / СОЗДАЙ</b><h3>Сформулируй задачу</h3><p>Опиши результат, который хочешь получить, и используй доступные настройки инструмента.</p></article><article class="public-step"><b>03 / ПРОДОЛЖАЙ</b><h3>Собери свою работу</h3><p>Возвращайся к проектам и используй другие инструменты по мере необходимости.</p></article></div>

<section class="public-creator-panel" id="creator-tools" aria-label="Инструменты для стримеров и TikTok-креаторов">
<div class="creator-art" aria-hidden="true"><div class="creator-screen"><span>EVRUNI</span><b>▶</b><i>LIVE WORKSPACE</i></div><div class="creator-phone"><span>♪</span><b>▶</b><i>12.4K</i></div><div class="creator-orbit"></div></div>
<div class="creator-copy"><div class="eyebrow"><span class="pulse-dot"></span> CREATOR WORKSPACE</div><h2>Стримерам и TikTok-креаторам</h2><p>Evruni помогает готовить контент, находить идеи и работать с инструментами для эфира в одном пространстве.</p><ul><li><span>✓</span> Идеи и сценарии для роликов</li><li><span>✓</span> Тренды TikTok и примеры</li><li><span>✓</span> AI-инструменты для контента</li><li><span>✓</span> Инструменты для работы со стримом</li></ul><a class="public-cta" href="/register.php">Открыть инструменты →</a></div>
</section>

<div class="public-trends" id="trend-lab"><div><div class="eyebrow"><span class="pulse-dot"></span> EVRUNI TREND LAB</div><h2 class="public-section-title" style="margin-top:12px">Тренд → идея → контент.</h2><p class="public-section-sub" style="margin-bottom:0">Находи темы, которые сейчас растут, разбирай их с AI и сразу превращай в сценарии, посты, видео или идеи для стрима.</p></div><div class="public-card" style="min-height:0"><span class="icon">⚡</span><h3>Что можно сделать</h3><p>Выбрать тренд → понять, почему он растёт → получить несколько углов подачи → создать контент в Evruni.</p><a class="public-cta" href="/register.php">Попробовать →</a></div></div>
<div class="public-bottom-cta"><h2>Попробуй Evruni в деле</h2><p>Создай аккаунт и познакомься с рабочим пространством. Доступность отдельных AI-инструментов зависит от подключённых моделей и настроек.</p><a class="public-cta" href="/register.php">Создать аккаунт →</a></div>
</section>
</main>
</body></html>
<?php exit; }
?>
<!doctype html><html lang="ru" id="publicHtml"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#070a12"><title>Evruni — AI Workspace</title><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="/assets/app.css?v=302"></head><body class="dashboard-page"><div class="dashboard-ambient ambient-a"></div><div class="dashboard-ambient ambient-b"></div>
<div class="app"><aside class="sidebar"><a class="logo" href="/" aria-label="Evruni"><span class="logo-mark" aria-hidden="true"><i></i></span><span class="logo-copy"><span class="logo-word">Evruni</span><span class="logo-tag">AI</span></span></a><nav>
<button class="nav active" data-view="chat"><span>✦</span> <span data-i18n="nav_chat">AI Chat</span></button><button class="nav" data-view="image"><span>✧</span> <span data-i18n="nav_image">Image</span></button><button class="nav" data-view="video"><span>▷</span> <span data-i18n="nav_video">Video</span></button><button class="nav trend-nav" data-view="trendlab"><span>✦</span> <span data-i18n="nav_trendlab">Trend Lab</span></button><button class="nav" data-view="audio"><span>◒</span> <span data-i18n="nav_audio">Audio</span></button><button class="nav" data-view="research"><span>⌕</span> <span data-i18n="nav_research">Research</span></button><button class="nav" data-view="documents"><span>▤</span> <span data-i18n="nav_documents">Documents</span></button><button class="nav" data-view="projects"><span>◈</span> <span data-i18n="nav_projects">Projects</span></button><button class="nav" data-view="models"><span>⬡</span> <span data-i18n="nav_models">Models</span></button><button class="nav" data-view="presets"><span>◈</span> <span data-i18n="nav_presets">Presets</span></button><button class="nav" data-view="api"><span>⌘</span> <span data-i18n="nav_api">API Keys</span></button><button class="nav" data-view="sandbox"><span>◌</span> <span data-i18n="nav_sandbox">Sandbox</span></button><button class="nav creator-tools-nav" type="button" data-view="creator-tools"><span>🎥</span> <span data-i18n="nav_creator_tools">Creator Tools</span><b class="creator-beta-badge">BETA</b></button><button class="nav bonus-nav" data-view="bonuses"><span class="bonus-nav-icon" aria-hidden="true">✦</span> <span data-i18n="nav_bonuses">Бонусы</span></button></nav><div class="side-bottom"><button class="nav" data-view="support"><span>◎</span> <span data-i18n="nav_support">Поддержка</span></button><?php if(($u['role']??'user')==='admin'): ?><a class="nav admin-nav" href="/admin.php"><span>◆</span> <span data-i18n="nav_admin">Admin</span></a><?php endif; ?></div></aside>
<main class="main"><header><div><div class="eyebrow"><span class="pulse-dot"></span> EVRUNI AI WORKSPACE</div><h1 id="viewTitle">What will you create?</h1></div><div class="header-tools"><div class="global-search-wrap"><input id="globalSearchInput" class="global-search-input" placeholder="Поиск Evruni…"><div id="globalSearchResults" class="global-search-results"></div></div><div class="profile-menu-wrap dashboard-profile" id="profileMenuWrap"><button class="profile-trigger" id="profileTrigger" type="button" aria-expanded="false"><span class="profile-credits"><span id="credits"><?=e(number_format((float)$initialWalletBalance,2,'.',''))?></span><small id="balanceCurrencyTop"><?=e((string)($initialCurrency??'EUR'))?></small></span><span class="profile-avatar"><?=e(strtoupper(substr((string)($u['name'] ?? 'U'),0,1)))?></span><span class="profile-trigger-text"><b><?=e($u['name'])?></b><small><?=e($u['email'] ?? '')?></small></span><span class="profile-chevron">⌄</span></button><div class="profile-dropdown" id="profileDropdown" aria-hidden="true"><div class="profile-dropdown-head"><span class="profile-avatar large"><?=e(strtoupper(substr((string)($u['name'] ?? 'U'),0,1)))?></span><div><b><?=e($u['name'])?></b><small><?=e($u['email'] ?? '')?></small></div></div><div class="profile-balance"><span data-i18n="profile_balance">Баланс</span><strong><span id="profileCredits"><?=e(number_format((float)$initialWalletBalance,2,'.',''))?></span> <span id="profileCurrencySymbol">€</span></strong></div><button type="button" class="profile-item" data-profile-action="profile">⚙️ <span data-i18n="profile_settings">Профиль и настройки</span></button><button type="button" class="profile-item" data-profile-action="billing">₽ <span data-i18n="profile_billing">Баланс и пополнение</span></button><button type="button" class="profile-item" data-profile-action="usage">◔ <span data-i18n="profile_usage">Использование</span></button><button type="button" class="profile-item danger" data-profile-action="logout">↪ <span data-i18n="profile_logout">Выйти</span></button><?php if(($u['role']??'user')==='admin'): ?><a class="profile-item" href="/admin.php">◆ <span data-i18n="profile_admin">Админ-панель</span></a><?php endif; ?><div class="profile-divider"></div></div></div></div></header><div id="view"></div></main></div>
<style id="evruni-sidebar-fallback-css">.sidebar{transition:width .24s ease,flex-basis .24s ease,padding .24s ease}.sidebar.sidebar-collapsed{width:84px;flex-basis:84px;padding-left:10px;padding-right:10px}.sidebar.sidebar-collapsed .logo-copy{display:none}.sidebar.sidebar-collapsed .nav{justify-content:center;padding-left:8px;padding-right:8px;gap:0}.sidebar.sidebar-collapsed .nav>span:not(:first-child),.sidebar.sidebar-collapsed .creator-beta-badge{display:none}.sidebar-collapse-toggle{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;margin:0 0 10px;padding:8px 10px;border:1px solid rgba(157,170,204,.12);border-radius:12px;background:rgba(255,255,255,.025);color:#8f9ab2;cursor:pointer;font-size:12px;line-height:1.2}.sidebar-collapse-toggle .collapse-label{white-space:nowrap}.sidebar.sidebar-collapsed .sidebar-collapse-toggle{font-size:0;padding:8px 4px}.sidebar.sidebar-collapsed .sidebar-collapse-toggle .collapse-label{display:none}.sidebar.sidebar-collapsed .sidebar-collapse-toggle:after{content:"›";font-size:18px;line-height:1}.creator-beta-badge{margin-left:auto;padding:3px 6px;border-radius:999px;border:1px solid rgba(103,232,255,.22);background:linear-gradient(135deg,rgba(139,92,246,.18),rgba(56,189,248,.12));color:#91eaff;font-size:8px;font-weight:800;letter-spacing:.1em;line-height:1}</style>
<script id="evruni-sidebar-fallback">(function(){function init(){var sidebar=document.querySelector('.sidebar');if(!sidebar)return;var nav=document.querySelector('.creator-tools-nav');if(nav)nav.addEventListener('click',function(e){e.preventDefault();e.stopImmediatePropagation();window.location.href='/creator-tools.php'},true);var btn=sidebar.querySelector('.sidebar-collapse-toggle');if(!btn){btn=document.createElement('button');btn.type='button';btn.className='sidebar-collapse-toggle';btn.innerHTML='<span aria-hidden="true">‹</span><span class="collapse-label">Свернуть меню</span>';btn.title='Свернуть меню';btn.setAttribute('aria-label','Свернуть меню');var logo=sidebar.querySelector('.logo');if(logo)logo.insertAdjacentElement('afterend',btn);else sidebar.prepend(btn);btn.addEventListener('click',function(){var c=!sidebar.classList.contains('sidebar-collapsed');sidebar.classList.toggle('sidebar-collapsed',c);btn.innerHTML=c?'<span aria-hidden="true">›</span><span class="collapse-label">Развернуть меню</span>':'<span aria-hidden="true">‹</span><span class="collapse-label">Свернуть меню</span>';btn.title=c?'Развернуть меню':'Свернуть меню';btn.setAttribute('aria-label',btn.title);try{localStorage.setItem('evruni_sidebar_collapsed_v1',c?'1':'0')}catch(_){}},false)}var collapsed=false;try{var saved=localStorage.getItem('evruni_sidebar_collapsed_v1');if(saved!==null)collapsed=saved==='1';}catch(_){}sidebar.classList.toggle('sidebar-collapsed',collapsed);btn.innerHTML=collapsed?'<span aria-hidden="true">›</span><span class="collapse-label">Развернуть меню</span>':'<span aria-hidden="true">‹</span><span class="collapse-label">Свернуть меню</span>';btn.title=collapsed?'Развернуть меню':'Свернуть меню';btn.setAttribute('aria-label',btn.title)}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init()})();</script>
<script src="/assets/app.js?v=304"></script>
</body></html>