(function(){
  'use strict';

  function escapeHtml(value){
    return String(value ?? '').replace(/[&<>"']/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];
    });
  }

  function renderCreatorTools(){
    var view=document.getElementById('view');
    var title=document.getElementById('viewTitle');
    if(!view)return;
    if(title)title.textContent='Creator Tools';
    view.innerHTML=`<section class="view-card creator-tools-view">
      <div class="view-top"><div>
        <div class="eyebrow">EVRUNI CREATOR TOOLS</div>
        <h2>Инструменты для стрима</h2>
        <p class="view-sub">AI-инструменты, которые делают стрим проще, интерактивнее и профессиональнее.</p>
      </div></div>
      <div class="creator-tools-lightweight">
        <span>⚡</span><div><b>Lightweight mode</b><small>AI запускается только по событию — без постоянного анализа стрима, видео или чата.</small></div>
      </div>
      <div class="creator-tools-grid">
        ${[
          ['🎙️','Voice Cleaner','Улучшение голоса и шумоподавление.','voice_cleaner'],
          ['💬','AI Chat Bot','Короткие ответы зрителям и команды чата.','chatbot'],
          ['🛡️','AI Moderator','Модерация новых сообщений по событию.','moderator'],
          ['🎨','Chat Overlay','Готовый чат-оверлей для трансляции.','overlay'],
          ['🔔','Smart Alerts','Уведомления о подписках, донатах и рейдах.','alerts'],
          ['🌍','Live Translator','Перевод выбранных сообщений или реплик.','translator'],
          ['🔞','Content Blur','Размытие чувствительного контента.','blur'],
          ['✂️','Auto Clips','Отметка ярких моментов для последующей нарезки.','clips'],
          ['🧠','Stream Copilot','Помощь по событиям без непрерывного анализа.','copilot'],
          ['📊','Stream Analytics','Ключевые показатели трансляции.','analytics'],
          ['📝','Stream Summary','Краткий итог после завершения стрима.','summary'],
          ['📱','Shorts Maker','Подготовка моментов под Shorts/Reels/TikTok.','shorts']
        ].map(function(t){return `<article class="creator-tool-card" data-tool="${t[3]}">
          <div class="creator-tool-icon">${t[0]}</div><div class="creator-tool-copy"><h3>${t[1]}</h3><p>${t[2]}</p></div>
          <label class="creator-tool-switch"><input type="checkbox"><span></span></label>
        </article>`}).join('')}
      </div>
      <div class="creator-tools-event-note"><b>Система не работает постоянно.</b> Модерация запускается на новом сообщении, клип — по отметке момента, а сводка — только после стрима или по запросу.</div>
    </section>`;

    view.querySelectorAll('.creator-tool-card').forEach(function(card){
      var key='evruni_creator_tool_'+card.dataset.tool;
      var input=card.querySelector('input');
      try{input.checked=localStorage.getItem(key)==='1'}catch(_){ }
      input.addEventListener('change',function(){try{localStorage.setItem(key,input.checked?'1':'0')}catch(_){ }});
    });
  }

  function install(){
    var nav=document.querySelector('.sidebar nav');
    if(!nav || nav.querySelector('[data-view="creator-tools"]'))return;
    var sandbox=nav.querySelector('[data-view="sandbox"]');
    var button=document.createElement('button');
    button.type='button';
    button.className='nav creator-tools-nav';
    button.dataset.view='creator-tools';
    button.innerHTML='<span>🎥</span> <span data-i18n="nav_creator_tools">Creator Tools</span>';
    if(sandbox&&sandbox.nextSibling)nav.insertBefore(button,sandbox.nextSibling);else nav.appendChild(button);
    button.addEventListener('click',function(){
      nav.querySelectorAll('.nav').forEach(function(x){x.classList.remove('active')});
      button.classList.add('active');
      renderCreatorTools();
    });
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',install);else install();
  window.EvruniCreatorTools={render:renderCreatorTools,install:install};
})();
