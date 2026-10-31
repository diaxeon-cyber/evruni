(()=>{
'use strict';
if(location.pathname!=='/creator-tools.php')return;
function init(){
 if(document.getElementById('evruni-settings-groups'))return;
 const tools=document.querySelector('.tools');
 if(!tools)return false;
 const cards=[...tools.querySelectorAll('.card')];
 if(!cards.length)return false;
 const groups=[
  {id:'live',icon:'🎬',title:'Эфир и OBS',sub:'Запуск, запись, звук и управление сценами',keys:['Voice Cleaner','Stream Analytics','Auto Clips']},
  {id:'chat',icon:'💬',title:'Чат и модерация',sub:'Общение со зрителями и защита чата',keys:['AI Chat Bot','AI Moderator','Chat Overlay','Live Translator']},
  {id:'alerts',icon:'🔔',title:'Донаты и алерты',sub:'Уведомления, события и оформление',keys:['Smart Alerts']},
  {id:'ai',icon:'🧠',title:'AI для стрима',sub:'Помощь во время эфира и после него',keys:['Stream Copilot','Stream Summary','Shorts Maker']},
  {id:'visual',icon:'🎨',title:'Видео и оформление',sub:'Экран, цензура и визуальные эффекты',keys:['Content Censor']}
 ];
 const text=el=>(el.querySelector('h4')?.textContent||'').trim();
 const wrap=document.createElement('div');wrap.id='evruni-settings-groups';wrap.className='settings-groups';
 const style=document.createElement('style');style.id='evruni-settings-groups-css';style.textContent=`
.settings-groups{display:flex;flex-direction:column;gap:16px}.settings-group{padding:14px;border:1px solid rgba(255,255,255,.07);border-radius:18px;background:rgba(255,255,255,.018)}.settings-group-head{display:flex;align-items:center;gap:10px;margin:0 0 10px}.settings-group-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:11px;background:rgba(124,92,255,.11);font-size:17px}.settings-group-title{font-size:14px;font-weight:700}.settings-group-sub{font-size:9px;color:var(--muted);margin-top:2px}.settings-group-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.settings-group-grid .card{min-width:0}.settings-group-toggle{margin-left:auto;border:1px solid var(--line);border-radius:9px;background:rgba(255,255,255,.04);color:#aeb9cc;padding:6px 9px;font-size:10px;cursor:pointer}.settings-group.collapsed .settings-group-grid{display:none}@media(max-width:1050px){.settings-group-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.settings-group{padding:10px}.settings-group-grid{grid-template-columns:1fr}}
`;
 document.head.appendChild(style);
 groups.forEach(g=>{
  const matched=cards.filter(c=>g.keys.some(k=>text(c).toLowerCase().includes(k.toLowerCase())));
  if(!matched.length)return;
  const section=document.createElement('section');section.className='settings-group';section.dataset.group=g.id;
  section.innerHTML=`<div class="settings-group-head"><div class="settings-group-icon">${g.icon}</div><div><div class="settings-group-title">${g.title}</div><div class="settings-group-sub">${g.sub}</div></div><button class="settings-group-toggle" type="button">Свернуть</button></div><div class="settings-group-grid"></div>`;
  const grid=section.querySelector('.settings-group-grid');matched.forEach(c=>grid.appendChild(c));
  section.querySelector('.settings-group-toggle').onclick=()=>{section.classList.toggle('collapsed');section.querySelector('.settings-group-toggle').textContent=section.classList.contains('collapsed')?'Развернуть':'Свернуть'};
  wrap.appendChild(section);
 });
 const remaining=cards.filter(c=>!wrap.querySelector('.card')||!wrap.querySelector('.card').contains(c));
 if(remaining.length){const section=document.createElement('section');section.className='settings-group';section.innerHTML='<div class="settings-group-head"><div class="settings-group-icon">⚙️</div><div><div class="settings-group-title">Другие инструменты</div><div class="settings-group-sub">Дополнительные функции Creator Tools</div></div></div><div class="settings-group-grid"></div>';remaining.forEach(c=>section.querySelector('.settings-group-grid').appendChild(c));wrap.appendChild(section)}
 tools.replaceWith(wrap);return true;
}
function start(){let n=0;const tick=()=>{if(init())return;if(++n<30)setTimeout(tick,250)};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',tick,{once:true});else tick()}
start();
})();