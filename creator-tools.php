<?php
require_once __DIR__.'/_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
$u=require_auth();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#070a12">
<title>Evruni — Creator Tools</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/app.css?v=303">
<style>
.ct{min-height:100vh;padding:30px;max-width:1400px;margin:auto}.ct-head{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:28px}.ct-brand{display:flex;align-items:center;gap:13px}.ct-orb{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:radial-gradient(circle at 30% 25%,#ddd5ff,#7c5cff 45%,#26184f);box-shadow:0 0 40px rgba(126,91,255,.3)}.ct-head h1{margin:0;font-size:30px;letter-spacing:-.04em}.ct-head p{margin:5px 0 0;color:var(--muted)}.ct-back{color:var(--text);text-decoration:none;padding:10px 14px;border:1px solid var(--line);border-radius:12px;background:rgba(255,255,255,.03)}.ct-hero{padding:28px;border:1px solid var(--line);border-radius:24px;background:linear-gradient(135deg,rgba(23,25,49,.9),rgba(10,13,25,.86));box-shadow:var(--shadow);margin-bottom:22px}.ct-hero h2{font-size:25px;margin:0 0 8px}.ct-hero p{max-width:800px;color:var(--muted);line-height:1.6;margin:0}.ct-status{display:inline-flex;align-items:center;gap:8px;margin-top:18px;padding:7px 11px;border-radius:999px;background:rgba(87,226,173,.08);color:var(--success);font-size:12px}.ct-status i{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 12px currentColor}.ct-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.ct-card{border:1px solid var(--line);border-radius:20px;padding:20px;background:var(--panel);min-height:185px;display:flex;flex-direction:column;transition:.2s}.ct-card:hover{transform:translateY(-2px);border-color:rgba(141,112,255,.35)}.ct-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:rgba(141,112,255,.1);font-size:20px;margin-bottom:15px}.ct-card h3{margin:0 0 6px;font-size:16px}.ct-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.55;flex:1}.ct-toggle{margin-top:17px;display:flex;justify-content:space-between;align-items:center;padding-top:13px;border-top:1px solid var(--line);font-size:12px;color:var(--muted)}.ct-switch{position:relative;width:42px;height:23px}.ct-switch input{opacity:0;width:0;height:0}.ct-slider{position:absolute;inset:0;border-radius:30px;background:#252b3d;cursor:pointer;transition:.2s}.ct-slider:before{content:"";position:absolute;width:17px;height:17px;left:3px;top:3px;border-radius:50%;background:#9aa3b8;transition:.2s}.ct-switch input:checked+.ct-slider{background:#7359e8}.ct-switch input:checked+.ct-slider:before{transform:translateX(19px);background:white}.ct-light{color:#57e2ad}.ct-note{margin-top:20px;padding:15px 17px;border:1px dashed rgba(141,112,255,.28);border-radius:16px;color:var(--muted);font-size:12px;line-height:1.5}.ct-footer{margin-top:25px;color:var(--muted2);font-size:11px}@media(max-width:900px){.ct-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.ct{padding:18px}.ct-head{align-items:flex-start}.ct-head h1{font-size:24px}.ct-grid{grid-template-columns:1fr}.ct-hero{padding:21px}}
</style>
</head>
<body class="dashboard-page">
<div class="dashboard-ambient ambient-a"></div><div class="dashboard-ambient ambient-b"></div>
<main class="ct">
<header class="ct-head">
  <div class="ct-brand"><div class="ct-orb">✦</div><div><h1>Creator Tools</h1><p>Evruni AI tools for streamers</p></div></div>
  <a class="ct-back" href="/">← Evruni</a>
</header>
<section class="ct-hero">
  <div class="eyebrow">EVRUNI CREATOR</div>
  <h2>Инструменты для стрима без постоянной нагрузки</h2>
  <p>Здесь собраны функции, которые можно включать только тогда, когда они нужны. Никаких постоянных фоновых AI-процессов: интерфейс работает локально, а тяжёлые операции подключаются отдельно по событию.</p>
  <span class="ct-status"><i></i> Lightweight mode</span>
</section>
<section class="ct-grid">
<?php
$tools=[
 ['🎙️','Voice Cleaner','Подготовка голоса: шумоподавление и улучшение речи.','voice'],
 ['💬','AI Chat Bot','Ответы бота, команды, опросы и интерактив с чатом.','chat'],
 ['🛡️','AI Moderator','Фильтрация спама, токсичности и запрещённых сообщений.','moderation'],
 ['🎨','Chat Overlay','Вывод чата на стрим с готовым оформлением.','overlay'],
 ['🔔','Smart Alerts','Реакции на подписки, донаты и другие события.','alerts'],
 ['🌍','Live Translator','Перевод выбранных сообщений или речи по запросу.','translator'],
 ['🔞','Content Blur','Локальная настройка размытия чувствительных сцен.','blur'],
 ['✂️','Auto Clips','Пометка интересных моментов для последующей обработки.','clips'],
 ['🧠','Stream Copilot','Подсказки стримеру на основе событий, а не постоянного анализа.','copilot'],
 ['📊','Stream Analytics','Лёгкая статистика событий и активности без постоянного AI.','analytics'],
 ['📝','Stream Summary','Сводка после завершения стрима одним запуском.','summary'],
 ['📱','Shorts Maker','Подготовка выбранного клипа к вертикальному формату.','shorts']
];
foreach($tools as [$icon,$name,$desc,$key]):
?><article class="ct-card"><div class="ct-icon"><?=$icon?></div><h3><?=htmlspecialchars($name,ENT_QUOTES,'UTF-8')?></h3><p><?=htmlspecialchars($desc,ENT_QUOTES,'UTF-8')?></p><div class="ct-toggle"><span class="ct-state" data-state="<?=$key?>">Выключено</span><label class="ct-switch"><input type="checkbox" data-tool="<?=$key?>"><span class="ct-slider"></span></label></div></article><?php endforeach; ?>
</section>
<div class="ct-note"><strong>⚡ Почему это не нагружает сервер:</strong> переключатели сохраняются в браузере, а страница не запускает циклические запросы, WebSocket или постоянный анализ видео/чата. В дальнейшем каждый тяжёлый инструмент можно подключить отдельным событием и лимитом.</div>
<div class="ct-footer">Evruni Creator Tools · <?=htmlspecialchars((string)$u['name'],ENT_QUOTES,'UTF-8')?></div>
</main>
<script>
(()=>{const key='evruni_creator_tools_v1';let state={};try{state=JSON.parse(localStorage.getItem(key)||'{}')}catch(e){}document.querySelectorAll('[data-tool]').forEach(input=>{const name=input.dataset.tool;input.checked=state[name]===true;const label=input.closest('.ct-card').querySelector('.ct-state');const sync=()=>{label.textContent=input.checked?'Включено':'Выключено';label.classList.toggle('ct-light',input.checked);state[name]=input.checked;localStorage.setItem(key,JSON.stringify(state));};input.addEventListener('change',sync);sync();});})();
</script>
</body></html>
