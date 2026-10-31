(function(){
  'use strict';
  function renderCreatorTools(){
    var view=document.getElementById('view');
    var title=document.getElementById('viewTitle');
    if(!view)return;
    if(title)title.textContent='Creator Tools';
    view.innerHTML=`<section class="view-card creator-tools-view">
      <div class="view-top"><div><div class="eyebrow">EVRUNI CREATOR TOOLS</div><h2>Инструменты для стрима</h2><p class="view-sub">AI-инструменты, которые делают стрим проще, интерактивнее и профессиональнее.</p></div></div>
      <div class="creator-tools-lightweight"><span>⚡</span><div><b>Lightweight mode</b><small>AI запускается только по событию — без постоянного анализа стрима, видео или чата.</small></div></div>
      <div class="creator-tools-grid">${[['🎙️','Voice Cleaner','Локальное улучшение голоса.','voice_cleaner'],['💬','AI Chat Bot','Ответы зрителям по событию.','chatbot'],['🛡️','AI Moderator','Модерация новых сообщений.','moderator'],['🎨','Chat Overlay','Чат поверх сцены.','overlay'],['🔔','Smart Alerts','Донаты, подписки и raid.','alerts'],['🌍','Live Translator','Перевод выбранной реплики.','translator'],['🔞','Content Blur','Локальный blur/цензура.','blur'],['✂️','Auto Clips','Отметка яркого момента.','clips'],['🧠','Stream Copilot','Подсказка по событию.','copilot'],['📊','Stream Analytics','Ключевые показатели сессии.','analytics'],['📝','Stream Summary','Итог после стрима.','summary'],['📱','Shorts Maker','Пакет для Shorts/Reels/TikTok.','shorts']].map(function(t){return `<article class="creator-tool-card" data-tool="${t[3]}"><div class="creator-tool-icon">${t[0]}</div><div class="creator-tool-copy"><h3>${t[1]}</h3><p>${t[2]}</p></div><label class="creator-tool-switch"><input type="checkbox"><span></span></label></article>`}).join('')}</div>
      <div class="creator-tools-event-note"><b>Lightweight:</b> сервер получает только маленькие AI/event-запросы. Видео, аудио и OBS WebSocket остаются на устройстве пользователя.</div>
      <div class="creator-tools-event-note"><a href="/creator-tools.php" style="color:inherit">Открыть полный Creator Workspace →</a></div>
    </section>`;
    view.querySelectorAll('.creator-tool-card').forEach(function(card){var key='evruni_creator_tool_'+card.dataset.tool,input=card.querySelector('input');try{input.checked=localStorage.getItem(key)==='1'}catch(_){ }input.addEventListener('change',function(){try{localStorage.setItem(key,input.checked?'1':'0')}catch(_){ }});});
  }
  function bind(button){
    if(!button||button.__evruniCreatorBound)return;
    button.__evruniCreatorBound=true;
    button.onclick=function(ev){ev.preventDefault();ev.stopImmediatePropagation();document.querySelectorAll('.nav').forEach(function(x){x.classList.remove('active')});button.classList.add('active');renderCreatorTools();return false;};
  }
  function install(){
    var nav=document.querySelector('.sidebar nav'); if(!nav)return;
    var button=nav.querySelector('[data-view="creator-tools"]');
    if(!button){var sandbox=nav.querySelector('[data-view="sandbox"]');button=document.createElement('button');button.type='button';button.className='nav creator-tools-nav';button.dataset.view='creator-tools';button.innerHTML='<span>🎥</span> <span data-i18n="nav_creator_tools">Creator Tools</span>';if(sandbox&&sandbox.nextSibling)nav.insertBefore(button,sandbox.nextSibling);else nav.appendChild(button);}
    bind(button);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',install);else install();
  window.EvruniCreatorTools={render:renderCreatorTools,install:install};
})();
