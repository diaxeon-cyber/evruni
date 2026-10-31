<?php
require_once __DIR__.'/_private/config/config.php';
require_once ROOT_PATH.'/_private/lib/security.php';
rate_limit('admin_page',30,300);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');
$u=require_admin();
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#070a12">
<title>Evruni — Admin</title>
<link rel="stylesheet" href="/assets/app.css?v=282">
<style>
.admin-wrap{min-height:100vh;padding:28px 34px 55px;max-width:1500px;margin:auto}
.admin-head{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:25px}
.admin-brand{display:flex;align-items:center;gap:12px}.admin-brand .orb{width:40px;height:40px;border-radius:13px;display:grid;place-items:center;background:radial-gradient(circle at 35% 25%,#ddd5ff,#8466ff 45%,#342078);box-shadow:0 0 35px rgba(126,91,255,.3);animation:orbFloat 4.8s ease-in-out infinite}.admin-brand h1{margin:0;font-size:25px;letter-spacing:-.04em}.admin-brand p{margin:4px 0 0;color:#6f7a91;font-size:11px}
.admin-nav{display:flex;gap:6px;overflow-x:auto;overflow-y:auto;max-height:68vh;align-content:flex-start;padding:6px;margin-bottom:18px;border:1px solid var(--line);border-radius:16px;background:rgba(9,13,24,.68);backdrop-filter:blur(18px)}.admin-nav button{white-space:nowrap;border:1px solid transparent;background:transparent;color:#7f8aa2;padding:10px 13px;border-radius:11px;cursor:pointer;transition:.2s}.admin-nav button:hover{color:#fff;background:rgba(255,255,255,.04)}.admin-nav button.active{color:#fff;background:rgba(141,112,255,.14);border-color:rgba(160,141,255,.18)}
.admin-content{animation:pageIn .4s cubic-bezier(.16,1,.3,1)}
.admin-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:14px}.admin-stat{padding:18px;border:1px solid var(--line);border-radius:16px;background:rgba(11,16,29,.74);box-shadow:0 12px 35px rgba(0,0,0,.12);transition:.22s}.admin-stat:hover{transform:translateY(-3px);border-color:rgba(160,141,255,.24)}.admin-stat b{display:block;font-size:24px;color:#ab98ff}.admin-stat span{font-size:10px;color:#69758c}
.admin-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:14px}.admin-panel{border:1px solid var(--line);border-radius:18px;background:rgba(10,15,28,.72);overflow:hidden}.admin-panel h3{margin:0;padding:16px 17px;border-bottom:1px solid var(--line);font-size:14px}.admin-panel .body{padding:14px}.admin-table{width:100%;border-collapse:collapse;font-size:11px}.admin-table th{text-align:left;color:#68748c;font-weight:700;padding:9px;border-bottom:1px solid var(--line)}.admin-table td{padding:10px 9px;border-bottom:1px solid rgba(255,255,255,.04);color:#c9cfdd}.admin-table tr:last-child td{border-bottom:0}.admin-table tr:hover td{background:rgba(255,255,255,.018)}.badge{display:inline-flex;padding:4px 7px;border-radius:7px;background:rgba(141,112,255,.1);color:#aa99ee;font-size:9px}.badge.good{color:#70e5b7;background:rgba(87,226,173,.07)}.badge.bad{color:#ff9fb4;background:rgba(255,111,145,.07)}
.admin-tools{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}.admin-tools input,.admin-tools select{min-width:160px;background:rgba(7,11,22,.78);border:1px solid var(--line);color:#e9ecf7;border-radius:11px;padding:9px 11px;outline:0}.admin-tools input:focus,.admin-tools select:focus{border-color:rgba(158,137,255,.55);box-shadow:0 0 0 4px rgba(141,112,255,.08)}.tiny{font-size:9px;color:#68748c}.actions{display:flex;gap:5px;flex-wrap:wrap}.actions button{padding:6px 8px;border-radius:8px;border:1px solid var(--line);background:rgba(255,255,255,.035);color:#aeb7c9;cursor:pointer}.actions button:hover{color:#fff;border-color:rgba(160,141,255,.3)}
.system-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.analytics-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.chart-panel{border:1px solid var(--line);border-radius:18px;background:rgba(10,15,28,.72);overflow:hidden}
.chart-panel h3{margin:0;padding:16px 17px 4px;font-size:14px}
.chart-panel .chart-meta{padding:0 17px 10px;color:#68748c;font-size:10px}
.chart-wrap{padding:8px 14px 14px;min-height:250px}
.admin-chart{width:100%;height:230px;display:block}
.chart-grid-line{stroke:rgba(255,255,255,.06);stroke-width:1}
.chart-axis{fill:#68748c;font-size:9px}
.chart-line{fill:none;stroke:#9d88ff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
.chart-area{fill:rgba(141,112,255,.08)}
.chart-bar{fill:#8f78f2}
.notice-editor{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.notice-editor label{display:block;color:#7f8aa2;font-size:10px;margin-bottom:6px}
.notice-editor input,.notice-editor textarea{width:100%;box-sizing:border-box;background:rgba(7,11,22,.78);border:1px solid var(--line);color:#e9ecf7;border-radius:11px;padding:10px 11px;outline:0;font:inherit}
.notice-editor textarea{min-height:100px;resize:vertical;line-height:1.45}.notice-editor label{display:block;color:#7f8aa2;font-size:10px;margin-bottom:6px}.notice-editor input,.notice-editor select,.notice-editor textarea{width:100%;box-sizing:border-box;background:rgba(7,11,22,.78);border:1px solid var(--line);color:#e9ecf7;border-radius:11px;padding:10px 11px;outline:0;font:inherit}
.notice-editor input:focus,.notice-editor textarea:focus{border-color:rgba(158,137,255,.55);box-shadow:0 0 0 4px rgba(141,112,255,.08)}
.notice-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px}
.notice-toggle{display:flex;align-items:center;gap:8px;color:#aeb7c9;font-size:11px}
.notice-toggle input{accent-color:#8f78f2}
.notice-help{font-size:10px;color:#68748c;margin-top:8px}
@media(max-width:900px){.analytics-grid,.notice-editor{grid-template-columns:1fr}}.system-item{padding:15px;border:1px solid var(--line);border-radius:13px;background:rgba(8,12,23,.68)}.system-item b{display:block;font-size:15px;margin-bottom:4px}.system-item span{color:#727e95;font-size:10px}
@media(max-width:1050px){.admin-stats{grid-template-columns:repeat(3,1fr)}.admin-grid{grid-template-columns:1fr}.system-grid{grid-template-columns:1fr 1fr}}
@media(max-width:650px){.admin-wrap{padding:18px 12px 40px}.admin-head{align-items:flex-start}.admin-stats{grid-template-columns:1fr 1fr}.system-grid{grid-template-columns:1fr}.admin-table{min-width:650px}.admin-panel .body{overflow:auto}.admin-nav{margin-bottom:13px}}
</style>
<meta name="evruni-admin-lang-fix" content="96">
<style>
.payment-settings-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}.payment-settings-grid select,.payment-fields input{width:100%;box-sizing:border-box;margin-top:8px;background:rgba(7,11,22,.78);border:1px solid var(--line);color:#e9ecf7;border-radius:11px;padding:10px 11px;outline:0}.payment-toggle{display:flex;align-items:center;gap:8px;margin-top:10px;color:#aeb7c9;font-size:11px}.payment-toggle input{accent-color:#8f78f2}.payment-fields{display:grid;grid-template-columns:1fr;gap:10px;margin-top:8px}.payment-fields label{display:block;color:#7f8aa2;font-size:10px}.payment-help{margin-top:10px;color:#68748c;font-size:10px;line-height:1.45}@media(max-width:900px){.payment-settings-grid{grid-template-columns:1fr}}
</style>

<style id="admin-user-role-v2">
.user-role-select{min-width:165px;background:rgba(7,11,22,.88);border:1px solid var(--line);color:#dfe4f1;border-radius:8px;padding:6px 8px;outline:0}
.user-role-select:focus{border-color:rgba(158,137,255,.55);box-shadow:0 0 0 3px rgba(141,112,255,.08)}
</style>
<style id="admin-nav-dropdown-v2">
.admin-nav{display:flex;gap:8px;align-items:flex-start;position:relative;z-index:10000;overflow:visible;max-height:none;padding:8px;margin-bottom:18px}
.admin-nav-group{position:relative;z-index:10001;flex:0 0 auto;padding:0;border:0}
.admin-nav-toggle{display:flex;align-items:center;gap:8px;white-space:nowrap;border:1px solid var(--line);background:rgba(15,20,34,.82);color:#cbd2e2;padding:10px 13px;border-radius:11px;cursor:pointer;font-size:12px;font-weight:700}
.admin-nav-toggle span{font-size:13px;color:#7f8aa2;transition:.18s}
.admin-nav-group.open .admin-nav-toggle,.admin-nav-toggle:hover{color:#fff;background:rgba(141,112,255,.12);border-color:rgba(160,141,255,.22)}
.admin-nav-group.open .admin-nav-toggle span{transform:rotate(180deg)}
.admin-nav-sub{display:none;position:absolute;top:calc(100% + 7px);left:0;min-width:190px;padding:6px;z-index:100000;border:1px solid var(--line);border-radius:13px;background:rgba(8,12,22,.97);box-shadow:0 18px 45px rgba(0,0,0,.38);backdrop-filter:blur(16px)}
.admin-nav-group.open .admin-nav-sub{display:grid;gap:3px}
.admin-nav-sub button{width:100%;text-align:left;white-space:nowrap;border:1px solid transparent;background:transparent;color:#8d98af;padding:9px 10px;border-radius:9px;cursor:pointer;font-size:12px}
.admin-nav-sub button:hover{color:#fff;background:rgba(255,255,255,.045)}
.admin-nav-sub button.active{color:#fff;background:rgba(141,112,255,.16);border-color:rgba(160,141,255,.22)}
/* Safe hover bridge: gives the cursor time to move from the toggle into the submenu. */
.admin-nav-group::after{content:"";position:absolute;left:0;right:0;top:100%;height:28px;display:block}
.admin-nav-sub{pointer-events:auto}
@media(max-width:650px){.admin-nav{overflow-x:auto}.admin-nav-sub{position:fixed;left:auto;min-width:190px}.admin-nav-toggle{padding:9px 11px;font-size:11px}}
</style>
</head>
<body>
<div class="dashboard-ambient ambient-a"></div><div class="dashboard-ambient ambient-b"></div>
<main class="admin-wrap">
<header class="admin-head">
  <div class="admin-brand"><div class="orb">✦</div><div><h1>Evruni Admin</h1><p>Control center · <?=htmlspecialchars((string)$u['email'],ENT_QUOTES,'UTF-8')?></p></div></div>
  <div class="actions"><a class="ghost" href="/" style="text-decoration:none">← Evruni</a><a class="ghost" href="/login.php" style="text-decoration:none">Logout</a></div>

<div class="admin-language-switch" aria-label="Language">
  <button type="button" data-admin-lang="ru">RU</button>
  <button type="button" data-admin-lang="en">EN</button>
</div>
</header>
<nav class="admin-nav" id="adminNav" aria-label="Evruni Admin navigation">
  <div class="admin-nav-group"><button class="admin-nav-toggle" type="button">Обзор <span>⌄</span></button><div class="admin-nav-sub"><button class="active" data-tab="dashboard" data-admin-i18n="admin_overview">Обзор</button><button data-tab="users" data-admin-i18n="admin_users">Пользователи</button></div></div>
  <div class="admin-nav-group"><button class="admin-nav-toggle" type="button">ИИ <span>⌄</span></button><div class="admin-nav-sub"><button data-tab="models" data-admin-i18n="admin_models">Модели</button><button data-tab="ai_settings">Настройка ИИ</button></div></div>
  <div class="admin-nav-group"><button class="admin-nav-toggle" type="button">Финансы <span>⌄</span></button><div class="admin-nav-sub"><button data-tab="payments" data-admin-i18n="admin_payment_settings">Платежи</button><button data-tab="generations" data-admin-i18n="admin_generations">Генерации</button><button data-tab="usage" data-admin-i18n="admin_usage">Использование</button></div></div>
  <div class="admin-nav-group"><button class="admin-nav-toggle" type="button">Контроль <span>⌄</span></button><div class="admin-nav-sub"><button data-tab="analytics" data-admin-i18n="admin_analytics">Аналитика</button><button data-tab="audit">Журнал</button><button data-tab="system" data-admin-i18n="admin_system">Система</button></div></div>
  <div class="admin-nav-group"><button class="admin-nav-toggle" type="button">Сообщество <span>⌄</span></button><div class="admin-nav-sub"><button data-tab="referrals">Бонусы и рефералы</button><button data-tab="support" data-admin-i18n="admin_support">Поддержка</button><button data-tab="notice" data-admin-i18n="admin_notice">Уведомления</button></div></div>
</nav>
<section id="adminContent" class="admin-content"></section>
</main>
<div id="adminToast" class="toast-stack"></div>
<script>window.EVRUNI={csrf:<?=json_encode(csrf_token(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>}</script>
<script src="/assets/admin.js?v=113"></script>

<style>
.admin-language-switch{
  position:fixed;top:18px;right:22px;z-index:99999;
  display:flex;gap:4px;padding:4px;border:1px solid rgba(255,255,255,.12);
  border-radius:10px;background:rgba(12,12,20,.82);backdrop-filter:blur(12px)
}
.admin-language-switch button{
  border:0;background:transparent;color:rgba(255,255,255,.65);
  padding:5px 9px;border-radius:7px;cursor:pointer;font:600 12px/1 system-ui
}
.admin-language-switch button.active{background:rgba(126,91,255,.32);color:#fff}
</style>


<style>
.admin-language-switch{
  position:fixed;top:18px;right:22px;z-index:99999;display:flex;gap:4px;
  padding:4px;border:1px solid rgba(255,255,255,.12);border-radius:10px;
  background:rgba(12,12,20,.82);backdrop-filter:blur(12px)
}
.admin-language-switch button{
  border:0;background:transparent;color:rgba(255,255,255,.65);
  padding:5px 9px;border-radius:7px;cursor:pointer;font:600 12px/1 system-ui
}
.admin-language-switch button.active{background:rgba(126,91,255,.32);color:#fff}
</style>
<script id="admin-language-script">
(function(){
  var D={
    ru:{
      Overview:"Обзор",Users:"Пользователи",Models:"Модели",Payments:"Платежи",
      Generations:"Генерации",Usage:"Использование",Audit:"Аудит",System:"Система",
      Analytics:"Аналитика",Notice:"Уведомление",Save:"Сохранить",Cancel:"Отмена",
      Close:"Закрыть",Search:"Поиск",Refresh:"Обновить",Enabled:"Включено",
      Disabled:"Отключено",Actions:"Действия",Status:"Статус",Role:"Роль",
      Balance:"Баланс",Date:"Дата",Amount:"Сумма",Model:"Модель",User:"Пользователь",
      Type:"Тип",Created:"Создано",Provider:"Провайдер",Cost:"Стоимость",
      Currency:"Валюта",Email:"Email",ID:"ID",Logout:"Выйти"
    },
    en:{
      "Обзор":"Overview","Пользователи":"Users","Модели":"Models","Платежи":"Payments",
      "Генерации":"Generations","Использование":"Usage","Аудит":"Audit","Система":"System",
      "Аналитика":"Analytics","Уведомление":"Notice","Сохранить":"Save","Отмена":"Cancel",
      "Закрыть":"Close","Поиск":"Search","Обновить":"Refresh","Включено":"Enabled",
      "Отключено":"Disabled","Действия":"Actions","Статус":"Status","Роль":"Role",
      "Баланс":"Balance","Дата":"Date","Сумма":"Amount","Модель":"Model",
      "Пользователь":"User","Тип":"Type","Создано":"Created","Провайдер":"Provider",
      "Стоимость":"Cost","Валюта":"Currency","Email":"Email","ID":"ID","Выйти":"Logout"
    }
  };

  function getLang(){
    return (localStorage.getItem('evruni_language')||localStorage.getItem('language')||'ru')==='en'?'en':'ru';
  }

  function translateControl(el, lang){
    /* Never alter value/data attributes: only the visible label. */
    var label=el.querySelector('[data-admin-label]');
    if(label){
      var source=label.getAttribute('data-admin-label');
      var val=(D[lang]&&D[lang][source]) || source;
      label.textContent=val;
      return;
    }
    var txt=(el.textContent||'').trim();
    if(!txt || txt.length>80) return;
    var val=(D[lang]&&D[lang][txt]) || txt;
    if(val!==txt){
      for(var i=0;i<el.childNodes.length;i++){
        var n=el.childNodes[i];
        if(n.nodeType===3 && n.nodeValue.trim()){
          n.nodeValue=n.nodeValue.replace(n.nodeValue.trim(),val);
          break;
        }
      }
    }
  }

  function applyAdminLanguage(){
    var lang=getLang();
    document.documentElement.lang=lang;

    document.querySelectorAll('button,a,[role="button"],input[type="button"],input[type="submit"]').forEach(function(el){
      if(el.matches('[data-admin-lang]')) return;
      translateControl(el,lang);
    });

    document.querySelectorAll('[data-admin-label]').forEach(function(el){
      var source=el.getAttribute('data-admin-label');
      if(source && D[lang] && D[lang][source]) el.textContent=D[lang][source];
    });

    document.querySelectorAll('[data-admin-lang]').forEach(function(b){
      b.classList.toggle('active',b.getAttribute('data-admin-lang')===lang);
    });
  }

  window.evruniAdminApplyLanguage=applyAdminLanguage;

  document.addEventListener('click',function(e){
    var b=e.target.closest('[data-admin-lang]');
    if(!b) return;
    e.preventDefault();
    e.stopPropagation();
    var lang=b.getAttribute('data-admin-lang')==='en'?'en':'ru';
    localStorage.setItem('evruni_language',lang);
    localStorage.setItem('language',lang);
    applyAdminLanguage();
  },true);

  document.addEventListener('DOMContentLoaded',function(){
    applyAdminLanguage();
    var root=document.getElementById('adminContent')||document.body;
    new MutationObserver(function(){ applyAdminLanguage(); }).observe(root,{childList:true,subtree:true});
  });
})();
</script>
<script id="admin-language-script">
(function(){
  function setLang(lang){
    lang=lang==='en'?'en':'ru';
    localStorage.setItem('evruni_language',lang);
    localStorage.setItem('language',lang);
    document.documentElement.lang=lang;
    if(window.evruniSetAdminLanguage) window.evruniSetAdminLanguage(lang);
    else if(window.evruniAdminApplyLanguage) window.evruniAdminApplyLanguage(document.body);
    document.querySelectorAll('[data-admin-lang]').forEach(function(b){
      b.classList.toggle('active',b.getAttribute('data-admin-lang')===lang);
    });
  }
  document.addEventListener('click',function(e){
    var b=e.target.closest('[data-admin-lang]');
    if(!b)return;
    e.preventDefault();
    e.stopImmediatePropagation();
    setLang(b.getAttribute('data-admin-lang'));
  },true);
  document.addEventListener('DOMContentLoaded',function(){
    setTimeout(function(){
      setLang((localStorage.getItem('evruni_language')||localStorage.getItem('language')||'ru'));
    },50);
  });
})();
</script>


<style id="evruni-admin-clean-ui">
/* Evruni Admin: original horizontal menu, tightened to fit all sections */
.admin-nav{
  display:flex !important;
  flex-wrap:nowrap !important;
  align-items:stretch !important;
  gap:5px !important;
  width:100%;
  white-space:nowrap !important;
  overflow:visible !important;
}
.admin-nav button{
  flex:1 1 0;
  min-width:0;
  padding:9px 7px !important;
  font-size:12px !important;
  line-height:1.15 !important;
  border-radius:9px !important;
  overflow:hidden;
  text-overflow:ellipsis;
}
@media(max-width:1100px){
  .admin-nav{gap:3px !important}
  .admin-nav button{font-size:11px !important;padding:8px 4px !important}
}
@media(max-width:760px){
  .admin-nav{
    overflow-x:auto !important;
    justify-content:flex-start !important;
    padding-bottom:4px;
    scrollbar-width:thin;
  }
  .admin-nav button{
    flex:0 0 auto;
    min-width:max-content;
    font-size:12px !important;
    padding:9px 11px !important;
  }
}

/* AI settings: dark Evruni inputs */
.admin-panel input.ai-set-key,
.admin-panel select.ai-set-status{
  width:100% !important;
  box-sizing:border-box !important;
  height:40px !important;
  padding:0 12px !important;
  border:1px solid rgba(139,130,255,.22) !important;
  border-radius:10px !important;
  background:#171827 !important;
  color:#eef0f8 !important;
  outline:none !important;
}
.admin-panel input.ai-set-key::placeholder{
  color:rgba(218,221,235,.38) !important;
}
.admin-panel input.ai-set-key:focus,
.admin-panel select.ai-set-status:focus{
  border-color:rgba(99,102,241,.72) !important;
  box-shadow:0 0 0 3px rgba(99,102,241,.12) !important;
}

/* Evruni Admin: grouped navigation */
.admin-nav{display:flex;gap:10px;align-items:stretch;overflow-x:auto;overflow-y:hidden;max-height:none;padding:8px;margin-bottom:18px;border:1px solid var(--line);border-radius:16px;background:rgba(9,13,24,.72);backdrop-filter:blur(18px);scrollbar-width:thin}
.admin-nav-group{display:flex;align-items:center;gap:4px;padding:4px 6px;border-right:1px solid rgba(255,255,255,.07);flex:0 0 auto}
.admin-nav-group:last-child{border-right:0}
.admin-nav-label{font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:#66718a;padding:0 7px;white-space:nowrap}
.admin-nav button{white-space:nowrap;border:1px solid transparent;background:transparent;color:#8d98af;padding:9px 11px;border-radius:10px;cursor:pointer;transition:.2s;font-size:13px}
.admin-nav button:hover{color:#fff;background:rgba(255,255,255,.045)}
.admin-nav button.active{color:#fff;background:rgba(141,112,255,.16);border-color:rgba(160,141,255,.22);box-shadow:0 0 18px rgba(126,91,255,.08)}
@media(max-width:900px){.admin-nav{gap:5px}.admin-nav-label{display:none}.admin-nav-group{padding:2px 3px}}
@media(max-width:650px){.admin-nav{margin-bottom:13px;padding:6px}.admin-nav-group{gap:2px}.admin-nav button{font-size:11px;padding:8px 9px}}
</style>
</body></html>
