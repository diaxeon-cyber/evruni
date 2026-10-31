
const A=s=>document.querySelector(s), AA=s=>[...document.querySelectorAll(s)];
const ae=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
async function adminApi(action,opt={}){
  const parts=String(action||'').split('&');
  const url='/api/admin.php?action='+encodeURIComponent(parts.shift())+(parts.length?'&'+parts.join('&'):'');
  const r=await fetch(url,opt);let d={};try{d=await r.json()}catch(_){}
  if(!r.ok)throw Error(d.error||'Request failed');return d
}
async function adminPost(action,data){
  return adminApi(action,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.EVRUNI.csrf},body:JSON.stringify(data)})
}
function adminToast(title,msg='',type='info'){
  const stack=A('#adminToast'),el=document.createElement('div');el.className=`toast ${type}`;
  el.innerHTML=`<div>${type==='success'?'✓':type==='error'?'!':'✦'}</div><div><b>${ae(title)}</b>${msg?`<p>${ae(msg)}</p>`:''}</div>`;stack.appendChild(el);
  setTimeout(()=>{el.classList.add('hide');setTimeout(()=>el.remove(),340)},3900)
}
function money(v){return Number(v||0).toLocaleString('ru-RU',{maximumFractionDigits:2})}
function stats(s){return `<div class="admin-stats" style="grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:10px;">
<div class="admin-stat"><b>${s.users}</b><span>Пользователи</span></div><div class="admin-stat"><b>${s.active_users}</b><span>Активные</span></div><div class="admin-stat"><b>${s.generations}</b><span>Генерации</span></div><div class="admin-stat"><b>${money(s.payments)} ₽</b><span>Оплачено</span></div><div class="admin-stat"><b>${ae(s.display_currency||'EUR')} ${Number(s.total_wallet_credits||0).toFixed(2)}</b><span>Баланс кошельков</span></div><div class="admin-stat"><b>${ae(s.display_currency||'EUR')} ${Number(s.free_cash||0).toFixed(2)} <small style="font-size:12px">(${Number(s.free_cash_percent||0).toFixed(2)}%)</small></b><span>Свободная касса</span></div></div>`}
async function dashboard(){
 const d=await adminApi('dashboard'),s=d.stats;
 A('#adminContent').innerHTML=stats(s)+`<div class="admin-grid">
 <div class="admin-panel"><h3>Последние пользователи</h3><div class="body"><table class="admin-table"><thead><tr><th>ID</th><th>Email</th><th>Роль</th><th>Баланс</th><th>Создан</th></tr></thead><tbody>${d.recent_users.map(x=>`<tr><td>${x.id}</td><td>${ae(x.email)}</td><td><span class="badge">${ae((window.__userRoleLabels||{})[x.user_category||'ai_user']||'Пользователь ИИ')}</span></td><td>${money(x.credits)}</td><td>${ae(x.created_at)}</td></tr>`).join('')}</tbody></table></div></div>
 <div class="admin-panel"><h3>Latest generations</h3><div class="body"><table class="admin-table"><thead><tr><th>ID</th><th>User</th><th>Type</th><th>Status</th></tr></thead><tbody>${d.recent_generations.map(x=>`<tr><td>${x.id}</td><td>${x.user_id}</td><td>${ae(x.type)}</td><td><span class="badge ${x.status==='done'?'good':''}">${ae(x.status)}</span></td></tr>`).join('')}</tbody></table></div></div></div>`
}
async function users(){
 const d=await adminApi('users'),rows=d.users||[];
 const cats=['ai_user','ai_creator','ai_developer','ai_researcher','ai_business','ai_partner','ai_moderator','ai_admin'];
 const catLabels={ai_user:'Пользователь ИИ',ai_creator:'Создатель ИИ',ai_developer:'Разработчик ИИ',ai_researcher:'Исследователь ИИ',ai_business:'Бизнес-пользователь',ai_partner:'Партнёр Evruni',ai_moderator:'Модератор ИИ',ai_admin:'Администратор Evruni'};
 const roleLabels={...catLabels};
 window.__userRoleLabels=roleLabels;
 window.__userCategories={cats,catLabels,roleLabels};
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>Пользователи Evruni</h3><div class="body"><div class="admin-tools"><input id="userSearch" placeholder="Поиск по email…"><select id="userCategoryFilter"><option value="">Все роли</option>${cats.map(c=>`<option value="${c}">${catLabels[c]}</option>`).join('')}</select><button class="primary" id="userSearchBtn">Поиск</button></div><div style="overflow:auto"><table class="admin-table"><thead><tr><th>ID</th><th>Email</th><th>Роль</th><th>Доступ</th><th>Баланс</th><th>Создан</th><th>Действия</th></tr></thead><tbody id="usersBody">${userRows(rows)}</tbody></table></div></div></div>`;
 A('#userSearchBtn').onclick=async()=>{try{const q=A('#userSearch').value;const cat=A('#userCategoryFilter').value;const x=await adminApi('users&q='+encodeURIComponent(q)+(cat?'&category='+encodeURIComponent(cat):''));A('#usersBody').innerHTML=userRows(x.users||[])}catch(e){adminToast('Ошибка поиска',e.message,'error')}};
 A('#userCategoryFilter').onchange=()=>A('#userSearchBtn').click();
}
function userRows(rows){const L=window.__userCategories?.catLabels||{};const R=window.__userCategories?.roleLabels||{};return rows.map(x=>{const cat=x.user_category||'ai_user';return `<tr><td>${x.id}</td><td>${ae(x.email)}</td><td><select class="user-role-select" onchange="changeUserRole(${x.id},this.value)">${Object.entries(R).map(([k,v])=>`<option value="${k}" ${cat===k?'selected':''}>${v}</option>`).join('')}</select></td><td><span class="badge">${x.role==='admin'?'Доступ администратора':'Обычный доступ'}</span></td><td>${Number(x.balance??x.credits??0).toFixed(2)} ${ae(x.balance_currency||x.currency||'EUR')}</td><td>${ae(x.created_at)}</td><td><div class="actions"><button onclick="changeBalance(${x.id},${Number(x.balance??x.credits??0)},'${ae(x.balance_currency||x.currency||'EUR')}')">Баланс</button><button onclick="blockUser(${x.id},${x.is_active})">${x.is_active?'Заблокировать':'Активировать'}</button></div></td></tr>`}).join('')||'<tr><td colspan="7">Пользователи не найдены</td></tr>'}
async function changeUserRole(id,profile){try{await adminPost('user_role_update',{id,profile});adminToast('Роль пользователя изменена','','success')}catch(e){adminToast('Не удалось изменить роль',e.message,'error');users()}}
async function changeBalance(id,current,currency){const v=prompt(`Изменение реального баланса. Например +100 или -50 (${currency}):`,'+100');if(v===null)return;const n=Number(v);if(!n)return adminToast('Некорректная сумма','Введите число','error');const reason=prompt('Причина:','Admin adjustment')||'Admin adjustment';try{const r=await adminPost('credits',{id,amount:n,reason});adminToast('Баланс изменён',`${n>0?'+':''}${n.toFixed(2)} ${r.currency||currency}`,'success');users()}catch(e){adminToast('Не удалось изменить баланс',e.message,'error')}}
async function toggleUser(id,role){try{await adminPost('user_update',{id,role:role==='admin'?'user':'admin',status:'active'});adminToast('Роль изменена','','success');users()}catch(e){adminToast('Не удалось изменить роль',e.message,'error')}}
async function blockUser(id,isActive){const status=isActive?'blocked':'active';try{await adminPost('user_update',{id,role:'user',status});adminToast('Статус изменён','','success');users()}catch(e){adminToast('Не удалось изменить статус',e.message,'error')}}
async function models(){
 const d=await adminApi('models');
 const markup=Number(d.markup_percent||0);
 const adminCurrency=d.currency||'EUR';
 const money=(v,c)=>{const sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[c]||c||'€';return sym+Number(v||0).toFixed(4)};
 const price=x=>{
   const c=x.display_currency||'EUR';
   if(['image','video'].includes(String(x.type||'').toLowerCase()) && x.display_generation_currency!=null) return money(x.display_generation_currency,c)+' / '+(x.type==='video'?'5 sec':'image');
   if(x.display_request_currency!=null) return money(x.display_request_currency,c)+' / request';
   if(x.display_input_currency!=null||x.display_output_currency!=null) return money(x.display_input_currency,c)+' in · '+money(x.display_output_currency,c)+' out / 1M';
   return '—';
 };
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>AI Models <span class="tiny">· ${ae(adminCurrency)}</span></h3><div class="body">
   <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px">
     <label style="display:flex;align-items:center;gap:8px"><b>Общая наценка</b>
       <input id="globalMarkup" type="number" min="0" max="500" step="0.1" value="${markup}" style="width:90px;background:#070b16;border:1px solid #242b3d;color:#fff;border-radius:8px;padding:7px">
       <span>%</span>
     </label>
     <button class="ghost" id="saveGlobalMarkup">Сохранить</button>
     <span class="tiny">Цена уже рассчитана с этой наценкой</span>
   </div>
   <div class="admin-stats" style="grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px;margin-bottom:16px">
     ${(d.category_counts||[]).map(c=>`<div class="admin-stat"><b>${Number(c.enabled||0)} / ${Number(c.total||0)}</b><span>${ae(c.label)}</span></div>`).join('')}
   </div>
   <div class="tiny" style="margin-bottom:10px">Формат: включено / всего. Категория определяется автоматически по типу AI.</div>
   <div style="overflow:auto"><table class="admin-table"><thead><tr><th>Модель</th><th>Категория</th><th>Провайдер</th><th>Тип</th><th>Цена с наценкой</th><th>Статус</th></tr></thead><tbody>
   ${d.models.map(x=>`<tr><td><b>${ae(x.name)}</b><div class="tiny">${ae(x.slug)} → ${ae(x.provider_model)}</div></td><td><span class="badge">${ae(x.category||'Текст и чат')}</span></td><td>${ae(x.provider)}</td><td>${ae(x.type)}</td><td>${price(x)}</td><td><span class="badge ${x.enabled?'good':'bad'}">${x.enabled?'Включена':'Отключена'}</span></td></tr>`).join('')}
   </tbody></table></div></div></div>`;
 A('#saveGlobalMarkup').onclick=async()=>{
   const value=A('#globalMarkup').value;
   try{
     await adminPost('pricing_markup_update',{markup_percent:value});
     adminToast('Наценка обновлена','','success');
     models();
   }catch(e){adminToast('Ошибка наценки',e.message,'error')}
 };
}

async function ai_settings(){
 let d;
 try{d=await adminApi('ai_settings')}catch(e){adminToast('Настройка ИИ недоступна',e.message,'error');return;}
 const statusLabel={online:'Онлайн',degraded:'Нагрузка',offline:'Офлайн',maintenance:'Техработы',unknown:'Не задано'};
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>Настройка ИИ</h3><div class="body">
 <div class="tiny ai-settings-help" style="margin-bottom:14px">
<b>API ключи ИИ:</b> для настройки достаточно ввести API ключ. API-ссылка определяется системой автоматически для каждого провайдера.<br>
Если ключ уже задан, поле будет пустым — это нормально: для замены просто введите новый ключ.<br>
Поддерживаются ключи: <code>OPENAI_API_KEY</code>, <code>AITUNNEL_API_KEY</code>, <code>DEEPSEEK_API_KEY</code>, <code>QWEN_API_KEY</code>, <code>MISTRAL_API_KEY</code>, <code>ANTHROPIC_API_KEY</code>, <code>GOOGLE_API_KEY</code>.
</div>
 <div class="admin-stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:16px">
 ${(d.providers||[]).map(x=>`<div class="admin-stat"><b>${Number(x.enabled_models||0)} / ${Number(x.total_models||0)}</b><span>${ae(x.provider)}</span></div>`).join('')}
 </div>
 <div style="overflow:auto"><table class="admin-table"><thead><tr><th>Провайдер</th><th>ИИ</th><th>API ключ</th><th>Статус</th><th>Действие</th></tr></thead><tbody>
 ${(d.providers||[]).map(x=>{const q=x.setting||{};return `<tr data-ai-provider="${ae(x.provider)}">
 <td><b>${ae(x.provider)}</b>${q.env_source?`<div class="tiny" style="margin-top:4px">.env${q.env_configured?' · ключ задан':' · ключ не задан'}</div>`:''}</td>
 <td>${Number(x.enabled_models||0)} / ${Number(x.total_models||0)}</td>
 <td><input class="ai-set-key" type="password" value="" placeholder="${q.api_key_configured?'Ключ задан — введите новый':'Введите API ключ'}" autocomplete="new-password"></td>
 <td style="min-width:150px"><select class="ai-set-status">
   ${Object.keys(statusLabel).map(k=>`<option value="${k}" ${q.status===k?'selected':''}>${statusLabel[k]}</option>`).join('')}
 </select></td>
 <td><button class="ghost ai-set-save">Сохранить</button></td>
 </tr>`}).join('')||'<tr><td colspan="6" class="muted">Провайдеров пока нет.</td></tr>'}
 </tbody></table></div>
 </div></div>`;
 AA('.ai-set-save').forEach(btn=>btn.onclick=async()=>{
   const tr=btn.closest('tr'),provider=tr?.dataset.aiProvider;if(!provider)return;
   try{
     await adminPost('ai_settings_save',{
       provider,
       api_key:tr.querySelector('.ai-set-key').value,
       status:tr.querySelector('.ai-set-status').value
     });
     adminToast('Настройки ИИ сохранены','','success');
     ai_settings();
   }catch(e){adminToast('Ошибка настроек ИИ',e.message,'error')}
 });
}

async function simpleTable(action,title,cols,render){
 const d=await adminApi(action),rows=d[action]||[];
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>${title}</h3><div class="body"><div style="overflow:auto"><table class="admin-table"><thead><tr>${cols.map(a=>`<th>${a}</th>`).join('')}</tr></thead><tbody>${rows.map(render).join('')||'<tr><td colspan="'+cols.length+'" class="muted">No data.</td></tr>'}</tbody></table></div></div></div>`
}

async function payment_settings(){
 const d=await adminApi('payment_settings'),s=d.settings||{};
 const cfg=s.method_configs||{};
 const row=(country,label)=>{const a=cfg[country]||[];const on=t=>a.some(x=>x.type===t&&x.enabled);return `<div class="payment-country-box"><b>${label}</b><label><input type="checkbox" data-pay-method="${country}:card" ${on('card')?'checked':''}> 💳 Карта</label><label><input type="checkbox" data-pay-method="${country}:sbp" ${on('sbp')?'checked':''}> ◈ СБП</label><label><input type="checkbox" data-pay-method="${country}:crypto" ${on('crypto')?'checked':''}> ₿ Крипта</label></div>`};
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>Настройка платежей</h3><div class="body">
  <div class="payment-settings-grid">
   <div class="system-item"><b>Провайдер</b><span>Основной платёжный шлюз</span><select id="payProvider"><option value="stripe" ${s.provider==='stripe'?'selected':''}>Stripe</option></select></div>
   <div class="system-item"><b>Статус платежей</b><span>Разрешить пользователям пополнять баланс</span><label class="payment-toggle"><input type="checkbox" id="payEnabled" ${Number(s.enabled)?'checked':''}> Включить оплату</label></div>
   <div class="system-item"><b>Валюта по умолчанию</b><span>Используется для новых платёжных сессий</span><select id="payCurrency"><option value="EUR" ${s.default_currency==='EUR'?'selected':''}>EUR</option><option value="USD" ${s.default_currency==='USD'?'selected':''}>USD</option><option value="GBP" ${s.default_currency==='GBP'?'selected':''}>GBP</option><option value="RUB" ${s.default_currency==='RUB'?'selected':''}>RUB — ₽</option></select></div>
  </div>
  <div class="payment-fields">
   <label>Stripe Publishable Key<input id="payPk" value="${escAttr(s.publishable_key||'')}" autocomplete="off"></label>
   <label>Stripe Secret Key<input id="paySk" type="password" placeholder="Оставьте пустым, чтобы сохранить текущий ключ" autocomplete="new-password"></label>
   <label>Stripe Webhook Secret<input id="payWh" type="password" placeholder="Оставьте пустым, чтобы сохранить текущий secret" autocomplete="new-password"></label>
  </div>
  <div class="payment-method-settings"><h4>Способы оплаты по странам</h4><p>Включите только те способы, которые реально подключены у вашего платёжного провайдера.</p><div class="payment-country-grid">${row('RU','Россия')}${row('NL','Нидерланды')}${row('OTHER','Остальные страны')}</div></div>
  <div class="payment-help">Сейчас автоматическая оплата через Stripe работает для «Карта». СБП и крипта отображаются как доступные методы только после включения в настройках, но для их реального проведения нужен отдельный платёжный шлюз.</div>
  <div class="notice-actions"><span class="tiny">Платежи · способы оплаты · валюта</span><button class="primary" id="savePaymentSettings">Сохранить настройки</button></div>
 </div></div>`;
 A('#savePaymentSettings').onclick=async()=>{
  try{
   const methods={RU:[],NL:[],OTHER:[]};
   AA('[data-pay-method]').forEach(x=>{if(x.checked){const [country,type]=x.dataset.payMethod.split(':');methods[country].push({type,name_ru:type==='sbp'?'СБП':type==='crypto'?'Крипта':'Карта',name_en:type==='sbp'?'SBP':type==='crypto'?'Crypto':'Card',enabled:true})}});
   await adminPost('payment_settings_update',{provider:A('#payProvider').value,enabled:A('#payEnabled').checked?1:0,default_currency:A('#payCurrency').value,publishable_key:A('#payPk').value.trim(),secret_key:A('#paySk').value.trim(),webhook_secret:A('#payWh').value.trim(),method_configs:methods});
   adminToast('Платежные настройки сохранены','Настройки способов оплаты обновлены.','success');
   payment_settings();
  }catch(e){adminToast('Не удалось сохранить',e.message,'error')}
 };
}

async function generations(){await simpleTable('generations','Generations',['ID','User','Type','Model','Cost','Status','Date'],x=>`<tr><td>${x.id}</td><td>${x.user_id}</td><td>${ae(x.type)}</td><td>${ae(x.model_slug)}</td><td>${money(x.credits_spent)}</td><td>${ae(x.status)}</td><td>${ae(x.created_at)}</td></tr>`)}
async function usage(){await simpleTable('usage','API Usage',['ID','User','Key','Model','In','Out','Cost','Date'],x=>`<tr><td>${x.id}</td><td>${x.user_id}</td><td>${x.api_key_id||'—'}</td><td>${ae(x.model_slug)}</td><td>${x.input_tokens||0}</td><td>${x.output_tokens||0}</td><td>${money(x.cost)}</td><td>${ae(x.created_at)}</td></tr>`)}
async function audit(){await simpleTable('audit','Audit Log',['ID','User','Action','IP','Date'],x=>`<tr><td>${x.id}</td><td>${x.user_id}</td><td>${ae(x.action)}</td><td>${ae(x.ip_address)}</td><td>${ae(x.created_at)}</td></tr>`)}
async function system(){
 const d=await adminApi('system'),s=d.system;
 const providers=[['OpenAI',s.openai_key_configured],['DeepSeek',s.deepseek_key_configured],['Qwen',s.qwen_key_configured],['Mistral',s.mistral_key_configured],['Evruni',s.aitunnel_key_configured],['Anthropic',s.anthropic_key_configured],['Google',s.google_key_configured],['Stripe',s.stripe_configured]];
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>System status</h3><div class="body"><div class="system-grid">${providers.map(x=>`<div class="system-item"><b>${x[0]}</b><span class="badge ${x[1]?'good':'bad'}">${x[1]?'Configured':'Not configured'}</span></div>`).join('')}<div class="system-item"><b>PHP ${ae(s.php)}</b><span>${ae(s.server)}</span></div><div class="system-item"><b>${s.models} models</b><span>${s.enabled_models} enabled</span></div><div class="system-item"><b>Database</b><span class="badge good">Connected</span></div></div></div></div>`
}

function escAttr(v){return ae(v).replace(/\n/g,'&#10;')}
function points(rows){
  const m={};(rows||[]).forEach(r=>m[String(r.day)]=Number(r.value||0));
  const out=[],d=new Date(); d.setHours(0,0,0,0);
  const n=Number(window.__analyticsDays||30);
  d.setDate(d.getDate()-(n-1));
  for(let i=0;i<n;i++){const k=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');out.push({day:k,value:m[k]||0});d.setDate(d.getDate()+1)}
  return out;
}
function svgLine(rows,label){
  const w=760,h=220,p=28, vals=rows.map(x=>Number(x.value)||0), max=Math.max(1,...vals);
  const step=rows.length>1?(w-p*2)/(rows.length-1):0;
  const pts=rows.map((x,i)=>`${p+i*step},${h-p-(Number(x.value)||0)/max*(h-p*2)}`).join(' ');
  const area=`${p},${h-p} ${pts} ${p+(rows.length-1)*step},${h-p}`;
  const lines=[0,.25,.5,.75,1].map(t=>{const y=h-p-t*(h-p*2);return `<line class="chart-grid-line" x1="${p}" y1="${y}" x2="${w-p}" y2="${y}"/><text class="chart-axis" x="2" y="${y+3}">${money(max*t)}</text>`}).join('');
  const idx=[0,Math.floor((rows.length-1)/2),rows.length-1].filter((v,i,a)=>a.indexOf(v)===i);
  const labels=idx.map(i=>`<text class="chart-axis" text-anchor="${i===0?'start':i===rows.length-1?'end':'middle'}" x="${p+i*step}" y="${h-5}">${rows[i].day.slice(5)}</text>`).join('');
  return `<svg class="admin-chart" viewBox="0 0 ${w} ${h}" role="img" aria-label="${ae(label)}"><polyline class="chart-area" points="${area}" fill="rgba(141,112,255,.08)" stroke="none"/><g>${lines}</g><polyline class="chart-line" points="${pts}"/>${labels}</svg>`;
}
function svgBars(rows,label){
  const w=760,h=220,p=28, vals=rows.map(x=>Number(x.value)||0), max=Math.max(1,...vals), bw=Math.max(2,(w-p*2)/Math.max(1,rows.length)-2);
  const bars=rows.map((x,i)=>{const v=Number(x.value)||0, bh=v/max*(h-p*2), x0=p+i*((w-p*2)/Math.max(1,rows.length))+1, y=h-p-bh; return `<rect class="chart-bar" x="${x0}" y="${y}" width="${bw}" height="${bh}" rx="2"/>`}).join('');
  const labels=[0,Math.floor((rows.length-1)/2),rows.length-1].filter((v,i,a)=>a.indexOf(v)===i).map(i=>`<text class="chart-axis" text-anchor="${i===0?'start':i===rows.length-1?'end':'middle'}" x="${p+i*((w-p*2)/Math.max(1,rows.length))+bw/2}" y="${h-5}">${rows[i].day.slice(5)}</text>`).join('');
  return `<svg class="admin-chart" viewBox="0 0 ${w} ${h}" role="img" aria-label="${ae(label)}">${bars}${labels}</svg>`;
}
async function analytics(){
 const d=await adminApi('analytics'),a=d.analytics||{};window.__analyticsDays=a.days||30;
 const users=points(a.users),gens=points(a.generations),api=points(a.api_requests),pay=points(a.payments);
 A('#adminContent').innerHTML=`<div class="admin-stats">
  <div class="admin-stat"><b>${users.reduce((n,x)=>n+x.value,0)}</b><span>New users · 30d</span></div>
  <div class="admin-stat"><b>${gens.reduce((n,x)=>n+x.value,0)}</b><span>Generations · 30d</span></div>
  <div class="admin-stat"><b>${api.reduce((n,x)=>n+x.value,0)}</b><span>API requests · 30d</span></div>
  <div class="admin-stat"><b>${money(pay.reduce((n,x)=>n+x.value,0))} ₽</b><span>Paid volume · 30d</span></div>
 </div>
 <div class="analytics-grid">
  <div class="chart-panel"><h3>New users</h3><div class="chart-meta">Daily registrations · last 30 days</div><div class="chart-wrap">${svgLine(users,'New users')}</div></div>
  <div class="chart-panel"><h3>Generations</h3><div class="chart-meta">Daily AI generations · last 30 days</div><div class="chart-wrap">${svgBars(gens,'Generations')}</div></div>
  <div class="chart-panel"><h3>API activity</h3><div class="chart-meta">Daily API requests · last 30 days</div><div class="chart-wrap">${svgLine(api,'API requests')}</div></div>
  <div class="chart-panel"><h3>Paid volume</h3><div class="chart-meta">Daily successful payments · last 30 days</div><div class="chart-wrap">${svgBars(pay,'Paid volume')}</div></div>
 </div>`;
}
async function notice(){
 const d=await adminApi('notice'),n=d.notice||{};
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3>Development notice</h3><div class="body">
  <div class="notice-help">Этот блок отображается над рабочей областью AI Chat. Изменения применяются без изменения кода сайта.</div>
  <div class="notice-editor" style="margin-top:14px">
   <div><label>Заголовок · RU</label><input id="noticeTitleRu" maxlength="255" value="${escAttr(n.title_ru||'')}"></div>
   <div><label>Title · EN</label><input id="noticeTitleEn" maxlength="255" value="${escAttr(n.title_en||'')}"></div>
   <div><label>Текст · RU</label><textarea id="noticeTextRu">${ae(n.text_ru||'')}</textarea></div>
   <div><label>Text · EN</label><textarea id="noticeTextEn">${ae(n.text_en||'')}</textarea></div>
  </div>
  <div class="notice-actions"><label class="notice-toggle"><input type="checkbox" id="noticeEnabled" ${Number(n.enabled)!==0?'checked':''}> Показывать уведомление</label><button class="primary" id="saveNotice">Сохранить</button></div>
 </div></div>`;
 A('#saveNotice').onclick=async()=>{
  try{
   await adminPost('notice_update',{
    enabled:A('#noticeEnabled').checked?1:0,
    title_ru:A('#noticeTitleRu').value.trim(),text_ru:A('#noticeTextRu').value.trim(),
    title_en:A('#noticeTitleEn').value.trim(),text_en:A('#noticeTextEn').value.trim()
   });
   adminToast('Уведомление сохранено','Изменения будут видны на AI Chat.','success');
  }catch(e){adminToast('Не удалось сохранить',e.message,'error')}
 };
}

async function support(){
 const d=await adminApi('support_threads');
 const rows=d.threads||[];
 A('#adminContent').innerHTML=`<div class="admin-panel"><h3 data-admin-i18n="admin_support">Support</h3><div class="body"><div class="admin-tools"><select id="supportStatusFilter"><option value="">All</option><option value="open">Open</option><option value="closed">Closed</option></select><button class="primary" id="supportRefresh">Refresh</button></div><div style="overflow:auto"><table class="admin-table"><thead><tr><th>ID</th><th>User</th><th>Subject</th><th>Status</th><th>Messages</th><th>Updated</th><th>Action</th></tr></thead><tbody id="supportAdminRows">${supportAdminRows(rows)}</tbody></table></div></div></div>`;
 A('#supportRefresh').onclick=()=>loadSupportAdminList();
 A('#supportStatusFilter').onchange=()=>loadSupportAdminList();
}
function supportAdminRows(rows){
 return rows.map(x=>`<tr><td>${x.id}</td><td>${ae(x.email)}<div class="tiny">${ae(x.name||'')}</div></td><td>${ae(x.subject)}</td><td><span class="badge ${x.status==='open'?'good':''}">${ae(x.status)}</span></td><td>${x.message_count||0}</td><td>${ae(x.updated_at||'')}</td><td><div class="actions"><button onclick="openSupportAdmin(${x.id})">Open</button></div></td></tr>`).join('')||'<tr><td colspan="7">No support requests.</td></tr>';
}
async function loadSupportAdminList(){
 try{
   const q=A('#supportStatusFilter')?.value||'';
   const d=await adminApi('support_threads'+(q?'&status='+encodeURIComponent(q):''));
   A('#supportAdminRows').innerHTML=supportAdminRows(d.threads||[]);
 }catch(e){adminToast('Support',e.message,'error')}
}
async function openSupportAdmin(id){
 try{
  const d=await adminApi('support_thread&id='+id),t=d.thread;
  A('#adminContent').innerHTML=`<div class="admin-panel"><h3>Support · ${ae(t.subject)}</h3><div class="body"><div class="support-admin-head"><div><b>${ae(t.email)}</b><span class="tiny">${ae(t.name||'')}</span></div><button class="ghost" id="supportAdminStatus">${t.status==='open'?'Close':'Reopen'}</button></div><div class="support-admin-messages" id="supportAdminMessages">${(d.messages||[]).map(m=>`<div class="support-admin-msg ${m.author_role}"><b>${m.author_role==='admin'?'Evruni Support':ae(t.name||t.email)}</b><p>${ae(m.content)}</p><small>${ae(m.created_at||'')}</small></div>`).join('')}</div><div class="support-admin-compose"><textarea id="supportAdminInput" placeholder="Reply to the user…"></textarea><button class="primary" id="supportAdminSend">Reply</button></div></div></div>`;
  const box=A('#supportAdminMessages');if(box)box.scrollTop=box.scrollHeight;
  A('#supportAdminSend').onclick=async()=>{
    const msg=A('#supportAdminInput').value.trim();if(!msg)return;
    try{await adminPost('support_reply',{thread_id:id,message:msg});await openSupportAdmin(id)}catch(e){adminToast('Support reply failed',e.message,'error')}
  };
  A('#supportAdminStatus').onclick=async()=>{
    const status=t.status==='open'?'closed':'open';
    try{await adminPost('support_status',{thread_id:id,status});await openSupportAdmin(id)}catch(e){adminToast('Support status failed',e.message,'error')}
  };
 }catch(e){adminToast('Support',e.message,'error')}
}

async function referrals(){
 const d=await adminApi('referrals'),s=d.stats||{},set=d.settings||{};
 const taskRows=(d.tasks||[]).map(t=>`<tr><td>${t.id}</td><td><b>${ae(t.title_ru)}</b><div class="tiny">${ae(t.title_en||'')}</div></td><td>${ae(t.task_type)}</td><td>${Number(t.reward_usd).toFixed(4)} USD</td><td><span class="badge ${t.enabled?'good':'bad'}">${t.enabled?'Включено':'Отключено'}</span></td><td><div class="actions"><button onclick='editTask(${JSON.stringify(t).replace(/'/g,"&#39;")})'>Изменить</button><button onclick="deleteTask(${t.id})">Удалить</button></div></td></tr>`).join('')||'<tr><td colspan="6">Нет заданий</td></tr>';
 const subRows=(d.submissions||[]).map(x=>`<tr><td>${x.id}</td><td>${ae(x.email)}<div class="tiny">${ae(x.name||'')}</div></td><td>${ae(x.title_ru)}</td><td><span class="badge ${x.status==='approved'?'good':x.status==='rejected'?'bad':''}">${ae(x.status)}</span></td><td>${ae(x.proof_text||'')}</td><td><div class="actions">${x.status==='pending'?`<button onclick="reviewTask(${x.id},'approved')">Одобрить</button><button onclick="reviewTask(${x.id},'rejected')">Отклонить</button>`:''}</div></td></tr>`).join('')||'<tr><td colspan="6">Нет отправок</td></tr>';
 const top=(d.top||[]).map(x=>`<tr><td>${x.referrer_user_id}</td><td>${ae(x.email)}<div class="tiny">${ae(x.name||'')}</div></td><td>${x.referrals}</td><td>${Number(x.commission_usd).toFixed(4)} USD</td></tr>`).join('')||'<tr><td colspan="4">Нет данных</td></tr>';
 A('#adminContent').innerHTML=`<div class="admin-stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px"><div class="admin-stat"><b>${s.clicks||0}</b><span>Переходы</span></div><div class="admin-stat"><b>${s.referrals||0}</b><span>Регистрации по ссылкам</span></div><div class="admin-stat"><b>${s.paid_referrals||0}</b><span>Оплачивающие рефералы</span></div><div class="admin-stat"><b>${Number(s.commission_usd||0).toFixed(4)} USD</b><span>Реферальные начисления</span></div><div class="admin-stat"><b>${s.task_submissions||0}</b><span>Задания на проверке</span></div></div>
 <div class="admin-grid"><div class="admin-panel"><h3>Реферальная система</h3><div class="body"><div class="admin-tools"><label>Комиссия %<input id="refPct" type="number" min="0" max="100" step="0.1" value="${Number(set.commission_percent||10)}"></label><label>Cookie, дней<input id="refDays" type="number" min="1" max="90" value="${Number(set.cookie_days||30)}"></label><label style="display:flex;align-items:center;gap:8px;padding-top:22px"><input id="refEnabled" type="checkbox" ${Number(set.enabled)!==0?'checked':''}> Включена</label><button class="primary" id="saveRef">Сохранить</button></div><p class="tiny">Каждое успешное пополнение приглашённого пользователя начисляет указанный процент рефереру. Всё хранится в USD и отображается в валюте пользователя.</p></div></div><div class="admin-panel"><h3>Топ рефереров</h3><div class="body"><table class="admin-table"><thead><tr><th>ID</th><th>Пользователь</th><th>Рефералы</th><th>Начислено</th></tr></thead><tbody>${top}</tbody></table></div></div></div>
 <div class="admin-panel" style="margin-top:14px"><h3>Зарегистрированные рефералы</h3><div class="body"><div style="overflow:auto"><table class="admin-table"><thead><tr><th>ID</th><th>Код</th><th>Кто пригласил</th><th>Кого</th><th>Начислено</th><th>Дата</th></tr></thead><tbody>${(d.referrals||[]).map(x=>`<tr><td>${x.id}</td><td><code>${ae(x.code)}</code></td><td>${ae(x.referrer_email||'')}<div class="tiny">${ae(x.referrer_name||'')}</div></td><td>${ae(x.referred_email||'')}<div class="tiny">${ae(x.referred_name||'')}</div></td><td>${Number(x.commission_usd||0).toFixed(4)} USD</td><td>${ae(x.created_at||'')}</td></tr>`).join('')||'<tr><td colspan="6">Нет зарегистрированных рефералов</td></tr>'}</tbody></table></div></div></div>
 <div class="admin-panel" style="margin-top:14px"><h3>Последние переходы по ссылкам</h3><div class="body"><div style="overflow:auto"><table class="admin-table"><thead><tr><th>ID</th><th>Код</th><th>Реферер</th><th>Дата</th></tr></thead><tbody>${(d.clicks||[]).map(x=>`<tr><td>${x.id}</td><td><code>${ae(x.code)}</code></td><td>${ae(x.email||'—')}<div class="tiny">${ae(x.name||'')}</div></td><td>${ae(x.created_at||'')}</td></tr>`).join('')||'<tr><td colspan="4">Нет переходов</td></tr>'}</tbody></table></div></div></div>
 <div class="admin-panel" style="margin-top:14px"><h3>Задания <button class="primary" id="newTask" style="float:right;padding:7px 10px">+ Добавить</button></h3><div class="body"><div id="taskEditor"></div><div style="overflow:auto"><table class="admin-table"><thead><tr><th>ID</th><th>Задание</th><th>Тип</th><th>Награда</th><th>Статус</th><th>Действия</th></tr></thead><tbody>${taskRows}</tbody></table></div></div></div>
 <div class="admin-panel" style="margin-top:14px"><h3>Проверка заданий</h3><div class="body"><div style="overflow:auto"><table class="admin-table"><thead><tr><th>ID</th><th>Пользователь</th><th>Задание</th><th>Статус</th><th>Подтверждение</th><th>Действия</th></tr></thead><tbody>${subRows}</tbody></table></div></div></div>`;
 A('#saveRef').onclick=async()=>{try{await adminPost('referral_settings_update',{enabled:A('#refEnabled').checked?1:0,commission_percent:A('#refPct').value,cookie_days:A('#refDays').value});adminToast('Реферальные настройки сохранены','','success');referrals()}catch(e){adminToast('Ошибка',e.message,'error')}};
 A('#newTask').onclick=()=>showTaskEditor();
}
function showTaskEditor(t={}){
 const box=A('#taskEditor');if(!box)return;
 box.innerHTML=`<div style="padding:14px;border:1px solid var(--line);border-radius:14px;background:rgba(8,12,23,.7);margin-bottom:14px"><div class="notice-editor"><div><label>Название RU</label><input id="teTitleRu" value="${ae(t.title_ru||'')}"></div><div><label>Название EN</label><input id="teTitleEn" value="${ae(t.title_en||'')}"></div><div><label>Описание RU</label><textarea id="teDescRu">${ae(t.description_ru||'')}</textarea></div><div><label>Описание EN</label><textarea id="teDescEn">${ae(t.description_en||'')}</textarea></div><div><label>Награда, USD</label><input id="teReward" type="number" min="0" step="0.01" value="${Number(t.reward_usd||0)}"></div><div><label>Тип</label><select id="teType"><option value="manual" ${t.task_type==='manual'?'selected':''}>Ручная проверка</option><option value="link" ${t.task_type==='link'?'selected':''}>Ссылка</option></select></div><div><label>Ссылка</label><input id="teUrl" value="${ae(t.target_url||'')}" placeholder="https://..."></div><div><label>Порядок</label><input id="teSort" type="number" value="${Number(t.sort_order||0)}"></div></div><div class="notice-actions"><label class="notice-toggle"><input id="teProof" type="checkbox" ${t.id ? (Number(t.proof_required)!==0?'checked':'') : 'checked'}> Требовать подтверждение</label><label class="notice-toggle"><input id="teEnabled" type="checkbox" ${t.id ? (Number(t.enabled)!==0?'checked':'') : 'checked'}> Включено</label><div class="actions"><button class="ghost" onclick="A('#taskEditor').innerHTML=''">Отмена</button><button class="primary" id="saveTask">Сохранить</button></div></div></div>`;
 A('#saveTask').onclick=async()=>{try{await adminPost('task_save',{id:t.id||0,title_ru:A('#teTitleRu').value,title_en:A('#teTitleEn').value,description_ru:A('#teDescRu').value,description_en:A('#teDescEn').value,reward_usd:A('#teReward').value,task_type:A('#teType').value,target_url:A('#teUrl').value,sort_order:A('#teSort').value,proof_required:A('#teProof').checked?1:0,enabled:A('#teEnabled').checked?1:0});adminToast('Задание сохранено','','success');referrals()}catch(e){adminToast('Ошибка задания',e.message,'error')}};
}
function editTask(t){showTaskEditor(t)}
async function deleteTask(id){if(!confirm('Удалить задание?'))return;try{await adminPost('task_delete',{id});adminToast('Задание удалено','','success');referrals()}catch(e){adminToast('Ошибка',e.message,'error')}}
async function reviewTask(id,status){const note=prompt(status==='approved'?'Комментарий к одобрению:':'Причина отказа:','')??'';try{await adminPost('task_review',{id,status,review_note:note});adminToast(status==='approved'?'Задание одобрено':'Задание отклонено','','success');referrals()}catch(e){adminToast('Ошибка проверки',e.message,'error')}}

const tabs={dashboard,users,models,ai_settings,payments:payment_settings,generations,usage,audit,analytics,referrals,notice,support,system};
function closeAdminMenus(){AA('#adminNav .admin-nav-group').forEach(x=>{x.classList.remove('open');if(x._menuCloseTimer){clearTimeout(x._menuCloseTimer);x._menuCloseTimer=null}})}
function scheduleAdminMenuClose(g){if(!g)return;if(g._menuCloseTimer)clearTimeout(g._menuCloseTimer);g._menuCloseTimer=setTimeout(()=>{g.classList.remove('open');g._menuCloseTimer=null},650)}
function openAdminMenu(g){if(!g)return;if(g._menuCloseTimer){clearTimeout(g._menuCloseTimer);g._menuCloseTimer=null}AA('#adminNav .admin-nav-group').forEach(x=>{if(x!==g){if(x._menuCloseTimer)clearTimeout(x._menuCloseTimer);x._menuCloseTimer=null;x.classList.remove('open')}});g.classList.add('open')}
AA('#adminNav .admin-nav-group').forEach(g=>{
  const b=g.querySelector('.admin-nav-toggle');
  b?.addEventListener('click',e=>{e.stopPropagation();if(g.classList.contains('open')){g.classList.remove('open')}else openAdminMenu(g)});
  g.addEventListener('mouseenter',()=>openAdminMenu(g));
  g.addEventListener('mouseleave',()=>scheduleAdminMenuClose(g));
  g.querySelector('.admin-nav-sub')?.addEventListener('mouseenter',()=>openAdminMenu(g));
  g.querySelector('.admin-nav-sub')?.addEventListener('mouseleave',()=>scheduleAdminMenuClose(g));
});
AA('#adminNav button[data-tab]').forEach(b=>b.onclick=()=>{AA('#adminNav button[data-tab]').forEach(x=>x.classList.remove('active'));b.classList.add('active');closeAdminMenus();tabs[b.dataset.tab]?.()});
document.addEventListener('click',e=>{if(!e.target.closest('#adminNav'))closeAdminMenus()});
closeAdminMenus();
const _adminOverviewTab=A('#adminNav button[data-tab="dashboard"]');
AA('#adminNav button[data-tab]').forEach(x=>x.classList.remove('active'));
_adminOverviewTab?.classList.add('active');
window.__userRoleLabels={ai_user:'Пользователь ИИ',ai_creator:'Создатель ИИ',ai_developer:'Разработчик ИИ',ai_researcher:'Исследователь ИИ',ai_business:'Бизнес-пользователь',ai_partner:'Партнёр Evruni',ai_moderator:'Модератор ИИ',ai_admin:'Администратор Evruni'};
dashboard();


/* V9.8 ADMIN LANGUAGE ONLY */
(function(){
  const RU_EN = {
    "Обзор":"Overview","Пользователи":"Users","Модели":"Models","Платежи":"Payment settings","Платежные настройки":"Payment settings","Провайдер":"Provider","Сейчас подключён Stripe":"Stripe is currently connected","Статус платежей":"Payment status","Разрешить пользователям пополнять баланс":"Allow users to top up balance","Включить оплату":"Enable payments","Валюта по умолчанию":"Default currency","Используется для новых платёжных сессий":"Used for new payment sessions","Stripe Publishable Key":"Stripe Publishable Key","Stripe Secret Key":"Stripe Secret Key","Stripe Webhook Secret":"Stripe Webhook Secret","Оставьте пустым, чтобы сохранить текущий ключ":"Leave empty to keep the current key","Секретные ключи хранятся в базе в зашифрованном виде. Пустое секретное поле не заменяет уже сохранённый ключ.":"Secret keys are stored encrypted in the database. An empty secret field keeps the current key.","Stripe Checkout · пополнение кредитов":"Stripe Checkout · credit top-ups","Сохранить настройки":"Save settings","Платежные настройки сохранены":"Payment settings saved","Stripe готов к пополнению кредитов.":"Stripe is ready for credit top-ups.","Не удалось сохранить":"Could not save",
    "Генерации":"Generations","Использование":"Usage","Аудит":"Audit","Audit log":"Audit log",
    "Аналитика":"Analytics","Уведомление":"Notice","Система":"System","Поддержка":"Support","Support":"Support","Evruni Admin":"Evruni Admin",
    "Центр управления":"Control center","Выйти":"Logout","Последние пользователи":"Recent users",
    "Последние генерации":"Latest generations","Всего пользователей":"Total users",
    "Активные":"Active","Кредиты в кошельках":"Баланс кошельков","Объём платежей":"Paid volume",
    "Новые пользователи":"New users","Статус":"Status","Действия":"Actions","Роль":"Role",
    "Баланс":"Balance","Дата":"Date","Сумма":"Amount","Пользователь":"User","Тип":"Type",
    "Создано":"Created","Провайдер":"Provider","Стоимость":"Cost","Валюта":"Currency",
    "Модель":"Model","API-запросы":"API requests","Вход":"In","Выход":"Out","Ключ":"Key",
    "Сохранить":"Save","Отмена":"Cancel","Закрыть":"Close","Поиск":"Search","Обновить":"Refresh",
    "Включено":"Enabled","Отключено":"Disabled","Настроено":"Configured","Не настроено":"Not configured",
    "Подключено":"Connected","Нет данных.":"No data.","Состояние системы":"System status",
    "AI-модели":"AI Models","Модель":"Model","Действие":"Action","Кредиты":"Стоимость",
    "Make user":"Make user","Make admin":"Make admin","Сделать пользователем":"Make user",
    "Сделать администратором":"Make admin","Block/activate":"Block/activate","Заблокировать/активировать":"Block/activate",
    "Search email…":"Search email…","Поиск email…":"Search email…","Credits":"Balance",
    "Paid volume · 30d":"Paid volume · 30d","Generations · 30d":"Generations · 30d",
    "API requests · 30d":"API requests · 30d","New users · 30d":"New users · 30d",
    "Daily registrations · last 30 days":"Daily registrations · last 30 days",
    "Daily AI generations · last 30 days":"Daily AI generations · last 30 days",
    "Daily API requests · last 30 days":"Daily API requests · last 30 days",
    "Daily successful payments · last 30 days":"Daily successful payments · last 30 days",
    "Development notice":"Development notice",
    "Этот блок отображается над рабочей областью AI Chat. Изменения применяются без изменения кода сайта.":"This block is shown above the AI Chat workspace. Changes apply without changing the site code.",
    "Заголовок · RU":"Title · RU","Title · EN":"Title · EN","Текст · RU":"Text · RU","Text · EN":"Text · EN",
    "Показывать уведомление":"Show notification","Изменения будут видны на AI Chat.":"Changes will be visible in AI Chat.",
    "Уведомление сохранено":"Notice saved","Не удалось сохранить":"Could not save",
    "Модель обновлена":"Model updated","Ошибка модели":"Model error",
    "Баланс изменён":"Balance updated","Не удалось изменить баланс":"Could not update balance",
    "Роль изменена":"Role updated","Не удалось изменить роль":"Could not update role",
    "Статус изменён":"Status updated","Не удалось изменить статус":"Could not update status",
    "Некорректная сумма":"Invalid amount","Введите число":"Enter a number",
    "Изменение баланса. Например +100 или -50:":"Balance adjustment. For example +100 or -50:",
    "Причина:":"Reason:","Admin adjustment":"Admin adjustment",
    "Ошибка поиска":"Search failed","Ошибка запроса":"Request failed",
    "Configured":"Configured","Not configured":"Not configured","Connected":"Connected","Open":"Open","Closed":"Closed","Reply":"Reply","Reopen":"Reopen","All":"All"
  };
  const EN_RU = {};
  Object.keys(RU_EN).forEach(k=>{ if(!(RU_EN[k] in EN_RU)) EN_RU[RU_EN[k]]=k; });

  function adminLang(){
    return (localStorage.getItem('evruni_language')||localStorage.getItem('language')||'ru')==='en'?'en':'ru';
  }
  function translateText(v, lang){
    if(!v) return v;
    return lang==='en' ? (RU_EN[v]||v) : (EN_RU[v]||v);
  }
  function translateAdmin(root=document.body){
    const lang=adminLang();
    document.documentElement.lang=lang;
    const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);
    const nodes=[];
    while(walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(n=>{
      if(n.parentElement && /^(SCRIPT|STYLE|NOSCRIPT)$/.test(n.parentElement.tagName)) return;
      const raw=n.nodeValue, key=raw.trim();
      if(!key || key.length>180) return;
      const val=translateText(key,lang);
      if(val!==key) n.nodeValue=raw.replace(key,val);
    });
    root.querySelectorAll('input,textarea,select,[placeholder],[title],[aria-label]').forEach(el=>{
      ['placeholder','title','aria-label'].forEach(attr=>{
        const v=el.getAttribute(attr), nv=translateText(v,lang);
        if(v && nv!==v) el.setAttribute(attr,nv);
      });
    });
    AA('[data-admin-lang]').forEach(b=>b.classList.toggle('active',b.dataset.adminLang===lang));
  }
  window.evruniAdminApplyLanguage=translateAdmin;
  window.evruniSetAdminLanguage=function(lang){
    lang=lang==='en'?'en':'ru';
    localStorage.setItem('evruni_language',lang);
    localStorage.setItem('language',lang);
    translateAdmin(document.body);
  };
  const root=A('#adminContent');
  if(root) new MutationObserver(()=>translateAdmin(root)).observe(root,{childList:true,subtree:true});
  document.addEventListener('DOMContentLoaded',()=>setTimeout(()=>translateAdmin(document.body),0));
})();
