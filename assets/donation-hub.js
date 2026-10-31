(()=>{
'use strict';
if(!/\/creator-tools\.php(?:$|\?)/.test(location.pathname+location.search)) return;
const KEY='evruni_donation_hub_v1';
const esc=s=>String(s??'').replace(/[&<>\"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));
function load(){try{return JSON.parse(localStorage.getItem(KEY)||'{}')}catch{return {}}}
function save(x){localStorage.setItem(KEY,JSON.stringify(x));}
function mount(){
 if(document.getElementById('evruniDonationHub')) return true;
 const host=document.querySelector('.ct')||document.querySelector('main'); if(!host) return false;
 const s=document.createElement('section'); s.id='evruniDonationHub'; s.className='glass donation-hub';
 s.innerHTML=`<div class="dh-head"><div><div class="dh-kicker">CREATOR MONEY</div><h3>💰 Донаты и выплаты</h3><p>Настрой один раз, а дальше донаты идут напрямую в подключённый платёжный сервис. Evruni не хранит данные банковской карты.</p></div><span class="dh-state" id="dhState">Не настроено</span></div>
 <div class="dh-grid">
  <div class="dh-card"><div class="dh-title">1. Получение донатов</div><div class="dh-sub">Куда зритель отправляет деньги</div>
   <label>Платёжный сервис<select id="dhProvider"><option value="">Выберите сервис</option><option value="payment_link">Платёжная ссылка</option><option value="stripe">Stripe</option><option value="paypal">PayPal</option><option value="yoomoney">ЮMoney</option><option value="other">Другой сервис</option></select></label>
   <label>Ссылка для доната<input id="dhLink" type="url" placeholder="https://..."></label>
   <div class="dh-two"><label>Валюта<select id="dhCurrency"><option>RUB</option><option>USD</option><option>EUR</option><option>GBP</option></select></label><label>Минимум<input id="dhMin" type="number" min="0" step="1" placeholder="100"></label></div>
   <button class="dh-btn primary" id="dhSave">Сохранить настройки</button>
  </div>
  <div class="dh-card"><div class="dh-title">2. Выплаты</div><div class="dh-sub">Куда выводятся заработанные средства</div>
   <label>Счёт для выплат<input id="dhPayout" type="text" placeholder="Подключённый аккаунт / email / ID"></label>
   <div class="dh-two"><label>Режим<select id="dhMode"><option value="auto">Автовыплата</option><option value="manual">Ручная выплата</option></select></label><label>Порог выплаты<input id="dhThreshold" type="number" min="0" step="1" placeholder="1000"></label></div>
   <div class="dh-safe">🔐 Реквизиты карты и банковского счёта вводятся непосредственно в платёжном сервисе. В Evruni сохраняется только идентификатор подключения.</div>
   <button class="dh-btn" id="dhConnect">Подключить аккаунт</button>
  </div>
  <div class="dh-card"><div class="dh-title">3. Вид доната</div><div class="dh-sub">Что увидит зритель и стрим</div>
   <label>Заголовок<input id="dhTitle" placeholder="Спасибо за поддержку! ❤️"></label>
   <label>Текст уведомления<textarea id="dhText" rows="3" placeholder="{name} отправил {amount}"></textarea></label>
   <div class="dh-two"><label>Показ, сек.<input id="dhDuration" type="number" min="2" max="30" value="6"></label><label>GIF/WEBP<input id="dhGif" type="file" accept="image/gif,image/webp,image/png,image/jpeg"></label></div>
  </div>
  <div class="dh-card"><div class="dh-title">4. Проверка</div><div class="dh-sub">Убедимся, что всё работает</div><div class="dh-test" id="dhTestBox">Готово к тесту</div><div class="dh-two"><input id="dhTestAmount" type="number" min="1" value="500"><button class="dh-btn primary" id="dhTest">🔔 Тест доната</button></div></div>
 </div><div class="dh-note">Важно: настоящий прямой приём и вывод денег зависит от выбранного платёжного провайдера. Эта панель не подменяет его KYC/OAuth и не хранит платёжные реквизиты.</div>`;
 host.appendChild(s);
 const x=load(); ['Provider','Link','Currency','Min','Payout','Mode','Threshold','Title','Text','Duration'].forEach(k=>{const id='dh'+k,el=document.getElementById(id);if(el&&x[k.toLowerCase()!==undefined?String(k).toLowerCase():String(k).toLowerCase()]!==undefined)el.value=x[String(k).toLowerCase()];});
 const map={dhProvider:'provider',dhLink:'link',dhCurrency:'currency',dhMin:'min',dhPayout:'payout',dhMode:'mode',dhThreshold:'threshold',dhTitle:'title',dhText:'text',dhDuration:'duration'};
 Object.entries(map).forEach(([id,key])=>{const el=document.getElementById(id);if(el&&x[key]!=null)el.value=x[key];});
 const update=()=>{const y=load();document.getElementById('dhState').textContent=y.provider&&y.link?'✓ Приём настроен':'Не настроено';};
 document.getElementById('dhSave').onclick=()=>{const y=load();Object.entries(map).forEach(([id,key])=>y[key]=document.getElementById(id).value.trim());save(y);update();};
 document.getElementById('dhConnect').onclick=()=>{document.getElementById('dhTestBox').textContent='Подключение выполняется через платёжный сервис. Для реального OAuth нужен выбранный провайдер.';};
 document.getElementById('dhTest').onclick=()=>{const a=document.getElementById('dhTestAmount').value||'500';document.getElementById('dhTestBox').textContent='💰 Тестовый донат +'+a+' '+(document.getElementById('dhCurrency').value||'RUB')+' — уведомление отправлено в Creator Tools';window.dispatchEvent(new CustomEvent('evruni:donation',{detail:{amount:a,currency:document.getElementById('dhCurrency').value||'RUB',name:'Тестовый зритель'}}));};
 document.getElementById('dhGif').onchange=e=>{const f=e.target.files?.[0];if(!f||f.size>2097152)return;const r=new FileReader();r.onload=()=>{const y=load();y.gif=r.result;save(y);};r.readAsDataURL(f);}; update(); return true;
}
const css=`<style>.donation-hub{margin-top:14px;padding:16px}.dh-head{display:flex;justify-content:space-between;gap:15px;align-items:flex-start}.dh-kicker{font-size:9px;letter-spacing:.18em;color:#9c8fff}.dh-head h3{margin:4px 0;font-size:18px}.dh-head p,.dh-sub{color:var(--muted);font-size:10px;line-height:1.45}.dh-state{padding:7px 10px;border:1px solid var(--line);border-radius:99px;font-size:10px}.dh-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:12px}.dh-card{padding:12px;border:1px solid var(--line);border-radius:14px;background:rgba(0,0,0,.14)}.dh-title{font-size:12px;font-weight:700}.dh-card label{display:block;color:var(--muted);font-size:9px;margin-top:9px}.dh-card input,.dh-card select,.dh-card textarea{display:block;width:100%;margin-top:4px;padding:9px;border:1px solid var(--line);border-radius:9px;background:#090d17;color:#fff;font:inherit;font-size:11px}.dh-two{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px}.dh-btn{margin-top:10px;border:1px solid var(--line);border-radius:9px;background:rgba(255,255,255,.05);color:#fff;padding:9px 12px;cursor:pointer}.dh-btn.primary{background:linear-gradient(135deg,#7359e8,#398cff);border-color:transparent}.dh-safe,.dh-note{margin-top:10px;padding:9px;border:1px dashed rgba(124,92,255,.25);border-radius:9px;color:var(--muted);font-size:9px;line-height:1.45}.dh-test{padding:12px;border-radius:9px;background:rgba(83,224,167,.06);border:1px solid rgba(83,224,167,.16);font-size:10px}.dh-test+.dh-two{align-items:end}@media(max-width:700px){.dh-grid{grid-template-columns:1fr}.dh-head{display:block}.dh-state{display:inline-flex;margin-top:8px}}</style>`;
document.head.insertAdjacentHTML('beforeend',css);
let n=0;const wait=()=>{if(mount()||n++>40)return;setTimeout(wait,250)};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',wait);else wait();
})();