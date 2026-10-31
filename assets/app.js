
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
let models=[],threads=[],currentThread=0,currentModel='',busy=false;
function initGlobalSearch(){
  const input=$('#globalSearchInput'), box=$('#globalSearchResults');
  if(!input||!box)return;
  let timer=0,searchController=null;
  input.addEventListener('input',()=>{
    clearTimeout(timer);
    if(searchController)searchController.abort();
    const q=input.value.trim();
    if(q.length<2){box.innerHTML='';box.classList.remove('show');return}
    timer=setTimeout(async()=>{
      try{
        const d=await api('/api/search.php?q='+encodeURIComponent(q),{signal:(searchController=new AbortController()).signal});
        const icons={project:'◈',chat:'✦',file:'▤',message:'⌁'};
        box.innerHTML=(d.results||[]).map(r=>`<button class="global-search-result" data-type="${esc(r.type)}" data-id="${Number(r.id)}"><span>${icons[r.type]||'•'}</span><b>${esc(r.title)}</b><small>${esc(r.meta||'')}</small></button>`).join('')||`<div class="muted">Ничего не найдено</div>`;
        box.classList.add('show');
        $$('.global-search-result').forEach(b=>b.onclick=()=>{
          const type=b.dataset.type,id=Number(b.dataset.id);box.classList.remove('show');input.value='';
          if(type==='project'){window.__evruniProjectId=id;document.querySelector('.nav[data-view="projects"]')?.click()}
          else {document.querySelector('.nav[data-view="chat"]')?.click();setTimeout(()=>loadThread(id),120)}
        });
      }catch(e){if(e?.name!=='AbortError')box.classList.remove('show')}
    },300);
  });
  document.addEventListener('click',e=>{if(!e.target.closest('.global-search-wrap'))box.classList.remove('show')});
}


function toast(title,message='',type='info'){
  let stack=$('.toast-stack');
  if(!stack){stack=document.createElement('div');stack.className='toast-stack';document.body.appendChild(stack)}
  const el=document.createElement('div');el.className=`toast ${type}`;
  el.innerHTML=`<div>${type==='success'?'✓':type==='error'?'!':'✦'}</div><div><b>${esc(title)}</b>${message?`<p>${esc(message)}</p>`:''}</div>`;
  stack.appendChild(el);setTimeout(()=>{el.classList.add('hide');setTimeout(()=>el.remove(),340)},3900);
}
function apiErrorMessage(error,status,url){
  const raw=typeof error==='string' ? error : (error?.message||'');
  const msg=String(raw||'').trim();
  const low=msg.toLowerCase();
  if(status===402 || /insufficient|недостаточно|insufficient_quota/.test(low)) return 'Недостаточно средств.';
  if(status===401 || /invalid api key|missing api key|unauthorized|не авториз/.test(low)) return 'Требуется авторизация.';
  if(status===403 || /forbidden|request not allowed|доступ запрещ/.test(low)) return 'Доступ запрещён.';
  if(status===404 || /not found|не найдено/.test(low)) return 'Запрошенный ресурс не найден или больше недоступен.';
  if(status===409 || /already|уже существует|закрыт/.test(low)) return msg||'Действие нельзя выполнить в текущем состоянии.';
  if(status===413 || /too large|слишком большой|file too large/.test(low)) return 'Файл слишком большой.';
  if(status===415 || /file type not allowed|тип файла/.test(low)) return 'Тип файла не поддерживается.';
  if(status===422){
    if(/invalid prompt|invalid message/.test(low)) return 'Проверьте заполнение полей.';
    if(/model unavailable|invalid .*model/.test(low)) return 'Модель недоступна.';
    if(/required|обязател/.test(low)) return msg||'Заполните обязательные поля.';
    return msg||'Проверьте введённые данные и попробуйте снова.';
  }
  if(status===429 || /rate limit|too many requests|слишком много запросов/.test(low)) return 'Слишком много запросов.';
  if(status>=500 || /server error|internal server|service unavailable/.test(low)) return 'Сервис временно недоступен.';
  if(!msg || msg==='Request failed') return 'Не удалось выполнить запрос. Попробуйте ещё раз.';
  return msg;
}
async function api(url,opt={}){
  const r=await fetch(url,opt);let d={};try{d=await r.json()}catch(_){}
  if(!r.ok){
    const err=(d&&typeof d.error==='object')?(d.error.message||d.error.code||''):d.error;
    throw Error(apiErrorMessage(err,r.status,url));
  }
  return d
}
const post=(url,data)=>api(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.EVRUNI.csrf},body:JSON.stringify(data)});
function shell(title,sub,body){return `<section class="view-card"><div class="view-top"><div><div class="eyebrow">${esc(sub)}</div><h2>${esc(title)}</h2></div></div>${body}</section>`}
function money(v){return Number(v||0).toFixed(2).replace('.',',')}
function modelPrice(m){
  const c=m.display_currency||'EUR',sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[c]||c;
  if(['image','video'].includes(String(m.type||'').toLowerCase()) && m.display_generation_currency!=null){
    const unit=m.type==='video'?'5 сек':'изображение';
    return `<span class="price-part"><b>${unit}</b><strong>${sym}${money(m.display_generation_currency)}</strong></span>`;
  }
  if(m.display_request_currency!=null)return `<span class="price-part"><b>За запрос</b><strong>${sym}${money(m.display_request_currency)}</strong></span>`;
  if(m.display_input_currency!=null||m.display_output_currency!=null)return `<span class="price-part"><b>${modelPickerIsEnglish() ? "Input" : "Ввод"}</b><strong>${sym}${money(m.display_input_currency)}</strong><em>/ ${modelPickerIsEnglish() ? "1M" : "1 млн"}</em></span><span class="price-sep"></span><span class="price-part"><b>${modelPickerIsEnglish() ? "Output" : "Вывод"}</b><strong>${sym}${money(m.display_output_currency)}</strong><em>/ ${modelPickerIsEnglish() ? "1M" : "1 млн"}</em></span>`;
  return `<span class="price-part"><b>${modelPickerIsEnglish() ? 'Price' : 'Цена'}</b><strong>${modelPickerIsEnglish() ? 'Price unavailable' : 'Стоимость не указана'}</strong></span>`;
}
function modelPickerIsEnglish(){
  const lang =
    window.EVRUNI_LANG ||
    window.currentLanguage ||
    window.language ||
    document.documentElement.lang ||
    'ru';
  return String(lang).toLowerCase().startsWith('en');
}

function selectedModelCostRange(m){
  if(!m)return {lo:null,hi:null,sym:''};
  const c=m.display_currency||'EUR',sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[c]||c;
  const req=m.display_request_currency;
  if(req!==null && req!==undefined && Number.isFinite(Number(req))) {
    const v=Number(req); return {lo:v,hi:v,sym};
  }
  const input=m.display_input_currency,output=m.display_output_currency;
  const hasInput=input!==null && input!==undefined && Number.isFinite(Number(input));
  const hasOutput=output!==null && output!==undefined && Number.isFinite(Number(output));
  if(hasInput&&hasOutput)return {lo:Math.min(Number(input),Number(output)),hi:Math.max(Number(input),Number(output)),sym};
  if(hasInput)return {lo:Number(input),hi:Number(input),sym};
  if(hasOutput)return {lo:Number(output),hi:Number(output),sym};
  const generation=m.display_generation_currency;
  if(generation!==null && generation!==undefined && Number.isFinite(Number(generation))) {
    const v=Number(generation); return {lo:v,hi:v,sym};
  }
  return {lo:null,hi:null,sym};
}
function imageCostMultiplier(size,quality,model){
  const s=String(size||'auto').toLowerCase(),q=String(quality||'auto').toLowerCase();
  let sm=1;
  const m=s.match(/^(\d+)x(\d+)$/); if(m) sm=Math.max(.625,Math.min(8,(Number(m[1])*Number(m[2]))/(1024*1024)));
  const key=(String(model?.slug||'')+' '+String(model?.name||'')).toLowerCase();
  if(/gpt.?image.?2\.5/.test(key)){
    const tokens={low:196,medium:1056,high:4160,xhigh:6240,max:8320,auto:1056};
    return sm*(Number(tokens[q]||1056)/1056);
  }
  const qm={low:.5,medium:1,high:2,xhigh:3,max:4,standard:1,auto:1}[q]??1;
  return sm*qm;
}
function videoCostMultiplier(resolution,duration,fps,quality,audio){
  const r=String(resolution||'').toLowerCase(),sec=Math.max(1,parseInt(duration,10)||5),f=parseInt(fps,10)||24;
  const rm=/1080|1920|1440|2k|4k/.test(r)?1.5:1;
  const fm=f>=30?1.1:1;
  const am=audio?1.1:1;
  return (sec/5)*rm*fm*am;
}
function dynamicModelCost(m,mult=1){
  if(!m)return '—';
  const isGeneration=['image','video'].includes(String(m.type||'').toLowerCase());
  if(isGeneration && m.display_generation_currency!==null && m.display_generation_currency!==undefined && Number.isFinite(Number(m.display_generation_currency))){
    const c=m.display_currency||'EUR',sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[c]||c;
    return `${money(Number(m.display_generation_currency)*mult)} ${sym}`;
  }
  const r=selectedModelCostRange(m); if(r.lo===null)return '—';
  const lo=r.lo*mult,hi=r.hi*mult;
  return lo===hi?`${money(lo)} ${r.sym}`:`${money(lo)} ${r.sym} — ${money(hi)} ${r.sym}`;
}
function selectedModelCost(m){
  if(!m)return '—';
  const c=m.display_currency||'EUR',sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[c]||c;
  const req=Number(m.display_request_currency);
  if(Number.isFinite(req))return `${money(req)} ${sym}`;
  const input=Number(m.display_input_currency),output=Number(m.display_output_currency);
  if(Number.isFinite(input)&&Number.isFinite(output)){
    const lo=Math.min(input,output),hi=Math.max(input,output);
    return `${money(lo)} ${sym} — ${money(hi)} ${sym}`;
  }
  if(Number.isFinite(input))return `${money(input)} ${sym}`;
  if(Number.isFinite(output))return `${money(output)} ${sym}`;
  return '—';
}
function modelPriceText(m){
  const c=m.display_currency||'EUR',sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[c]||c;
  if(['image','video'].includes(String(m.type||'').toLowerCase()) && m.display_generation_currency!=null){
    return `${sym}${money(m.display_generation_currency)} / ${m.type==='video'?(modelPickerIsEnglish()?'5 sec':'5 сек'):(modelPickerIsEnglish()?'image':'изобр.')}`;
  }
  if(m.display_request_currency!=null)return `${sym}${money(m.display_request_currency)} / ${modelPickerIsEnglish() ? 'request' : 'запрос'}`;
  if(m.display_input_currency!=null||m.display_output_currency!=null)return `${sym}${money(m.display_input_currency)} / ${sym}${money(m.display_output_currency)} · ${modelPickerIsEnglish() ? '1M' : '1 млн'}`;
  return modelPickerIsEnglish() ? 'Price unavailable' : 'Стоимость не указана';
}
function modelSortValue(m){
  if(m.display_request_currency!=null)return Number(m.display_request_currency)||0;
  if(m.display_input_currency!=null||m.display_output_currency!=null)
    return ((Number(m.display_input_currency)||0)+(Number(m.display_output_currency)||0))/2;
  return Number(m.credit_cost)||0;
}
function modelPickerData(id,mode='default'){
  const wrap=document.querySelector(`[data-picker-id="${id}"]`),sel=$('#'+id);
  if(!wrap||!sel)return [];
  const type=sel.dataset.modelType||'text';
  let list=models.filter(m=>m.enabled!==0&&(type==='all'||m.type===type||type==='text'&&m.type==='text'));
  if(wrap.dataset.pickerType==='slugs'){
    try{const slugs=JSON.parse(wrap.dataset.pickerSlugs||'[]');list=list.filter(m=>slugs.includes(m.slug));}catch(e){}
  }
  if(mode==='cheap')list.sort((a,b)=>modelSortValue(a)-modelSortValue(b));
  if(mode==='expensive')list.sort((a,b)=>modelSortValue(b)-modelSortValue(a));
  return list;
}
function renderModelPicker(id,mode='default'){
  const wrap=document.querySelector(`[data-picker-id="${id}"]`),listEl=$('#'+id+'List'),sel=$('#'+id);
  if(!wrap||!listEl||!sel)return;
  const list=modelPickerData(id,mode);
  listEl.innerHTML=list.map(m=>`<button type="button" class="model-picker-option" data-model-value="${esc(m.slug)}" ${m.configured===false?'disabled':''}><span class="model-picker-option-name">${esc(String(m.slug||'').toLowerCase()==='aitunnel-auto'?'Evruni Auto':m.name)}</span><span class="model-picker-option-price">${modelPriceText(m)}</span></button>`).join('');
  listEl.querySelectorAll('[data-model-value]').forEach(btn=>btn.addEventListener('click',()=>{
    sel.value=btn.dataset.modelValue; sel.dataset.currentModel=sel.value;
        saveSelectedModel(id,sel.value);
const trigger=$('#'+id+'Trigger'); if(trigger)trigger.querySelector('.model-picker-name').textContent=btn.querySelector('.model-picker-option-name').textContent;
    updateModelPrice(id); wrap.classList.remove('open');
    sel.dispatchEvent(new Event('change',{bubbles:true}));
  }));
}
function bindModelFilter(id){
  const wrap=document.querySelector(`[data-picker-id="${id}"]`),trigger=$('#'+id+'Trigger');
  if(!wrap||!trigger)return;
  trigger.addEventListener('click',e=>{
    e.stopPropagation();
    document.querySelectorAll('.model-picker.open').forEach(x=>{if(x!==wrap)x.classList.remove('open')});
    wrap.classList.toggle('open');
    if(wrap.classList.contains('open'))renderModelPicker(id,'default');
  });
  wrap.querySelectorAll('[data-model-filter]').forEach(btn=>btn.addEventListener('click',e=>{
    e.stopPropagation();
    wrap.querySelectorAll('[data-model-filter]').forEach(x=>x.classList.remove('active'));
    btn.classList.add('active'); renderModelPicker(id,btn.dataset.modelFilter);
  }));
  wrap.querySelector('[data-model-filter="default"]')?.classList.add('active');
  renderModelPicker(id,'default');
}
document.addEventListener('click',e=>{
  if(!e.target.closest('.model-picker'))document.querySelectorAll('.model-picker.open').forEach(x=>x.classList.remove('open'));
});
document.addEventListener('wheel',e=>{
  const list=e.target.closest('.model-picker-list');
  if(list) e.stopPropagation();
},{passive:true});

function modelPicker(id,type='text',extra=''){
  return `<div class="model-picker" data-picker-id="${id}" data-picker-type="${type}">
    <select id="${id}" data-model-type="${type}" ${extra} hidden>${modelOptions(type)}</select>
    <button type="button" class="model-picker-trigger" id="${id}Trigger"><span class="model-picker-name">${modelPickerIsEnglish() ? "Select model" : "Выберите модель"}</span><span class="model-picker-chevron">⌄</span></button>
    <div class="model-picker-menu" id="${id}Menu">
      <div class="model-picker-filter"><button type="button" data-model-filter="cheap">${modelPickerIsEnglish() ? "Cheapest" : "Дешёвые"}</button><button type="button" data-model-filter="expensive">${modelPickerIsEnglish() ? "Most expensive" : "Дорогие"}</button></div>
      <div class="model-picker-list" id="${id}List"></div>
    </div>
  </div>`;
}
function modelPickerBySlugs(id,slugs,extra=''){
  return `<div class="model-picker" data-picker-id="${id}" data-picker-type="slugs" data-picker-slugs='${JSON.stringify(slugs)}'>
    <select id="${id}" data-model-type="all" ${extra} hidden>${modelOptionsBySlugs(slugs)}</select>
    <button type="button" class="model-picker-trigger" id="${id}Trigger"><span class="model-picker-name">${modelPickerIsEnglish() ? "Select model" : "Выберите модель"}</span><span class="model-picker-chevron">⌄</span></button>
    <div class="model-picker-menu" id="${id}Menu">
      <div class="model-picker-filter"><button type="button" data-model-filter="cheap">${modelPickerIsEnglish() ? "Cheapest" : "Дешёвые"}</button><button type="button" data-model-filter="expensive">${modelPickerIsEnglish() ? "Most expensive" : "Дорогие"}</button></div>
      <div class="model-picker-list" id="${id}List"></div>
    </div>
  </div>`;
}
function refreshModelPickersLanguage(){
  document.querySelectorAll('.model-picker').forEach(wrap=>{
    const id=wrap.dataset.pickerId;
    const trigger=wrap.querySelector('.model-picker-trigger .model-picker-name');
    const sel=id?document.getElementById(id):null;
    if(trigger && (!sel || !sel.value)){
      trigger.textContent=modelPickerIsEnglish()?'Select model':'Выберите модель';
    }
    const list=wrap.querySelector('.model-picker-list');
    if(list && sel){
      const mode=wrap.dataset.modelFilterMode||'default';
      renderModelPicker(id,mode);
      if(sel.value){
        const chosen=models.find(m=>m.slug===sel.value);
        if(chosen && trigger) trigger.textContent=String(chosen.slug||'').toLowerCase()==='aitunnel-auto'?'Evruni Auto':chosen.name;
      }
    }
  });
}
function updateModelPrice(id){
  const el=$('#'+id),badge=$('#'+id+'Price');if(!el||!badge)return;
  const m=models.find(x=>x.slug===el.value);badge.innerHTML=m?modelPrice(m):'';
}
function modelSelectionKey(id){return 'evruni_selected_model_'+String(id||'model')}
function readSavedModel(id){try{return localStorage.getItem(modelSelectionKey(id))||''}catch(_){return ''}}
function saveSelectedModel(id,slug){try{if(slug)localStorage.setItem(modelSelectionKey(id),slug)}catch(_){}}
function activateModelPicker(id){
  const list=modelPickerData(id,'default');
  if(!list.length)return null;
  const saved=readSavedModel(id);
  const chosen=list.find(m=>m.slug===saved&&m.configured!==false)||list.find(m=>m.configured!==false)||list[0];
  if(chosen){
    setModelPickerSelection(id,chosen.slug);
    saveSelectedModel(id,chosen.slug);
    updateModelPrice(id);
  }
  return chosen;
}
function activateAllModelPickers(){
  document.querySelectorAll('.model-picker').forEach(wrap=>{
    const id=wrap.dataset.pickerId;
    if(!id)return;
    const chosen=activateModelPicker(id);
    if(chosen)renderModelPicker(id,'default');
  });
}
function setModelPickerSelection(id,slug){
  const el=$('#'+id),wrap=document.querySelector(`[data-picker-id="${id}"]`);
  if(!el||!slug)return;
  const m=models.find(x=>x.slug===slug&&x.enabled!==0);
  if(!m)return;
  el.value=slug;el.dataset.currentModel=slug;
  const trigger=wrap?.querySelector('.model-picker-trigger .model-picker-name');
  if(trigger)trigger.textContent=String(m.slug||'').toLowerCase()==='aitunnel-auto'?'Evruni Auto':m.name;
}
function bindModelPrice(id){
  const el=$('#'+id);if(!el)return;
  const list=modelPickerData(id,'default');
  const saved=readSavedModel(id);
  const chosen=list.find(m=>m.slug===saved&&m.configured!==false)||list.find(m=>m.configured!==false)||list[0];
  if(chosen)setModelPickerSelection(id,chosen.slug);
  el.addEventListener('change',()=>{saveSelectedModel(id,el.value);updateModelPrice(id)});
  bindModelFilter(id);
  if(chosen)setModelPickerSelection(id,chosen.slug);
  updateModelPrice(id);
  if(chosen)saveSelectedModel(id,chosen.slug);
}
function modelOptions(type='text'){return models.filter(m=>m.enabled!==0&&(type==='all'||m.type===type||type==='text'&&m.type==='text')).map(m=>`<option value="${esc(m.slug)}" ${m.configured===false?'disabled':''}>${esc(String(m.slug||'').toLowerCase()==='aitunnel-auto'?'Evruni Auto':m.name)}${m.configured===false?' · не настроено':''}</option>`).join('')}
function modelOptionsBySlugs(slugs){return models.filter(m=>m.enabled!==0&&slugs.includes(m.slug)).map(m=>`<option value="${esc(m.slug)}" ${m.configured===false?'disabled':''}>${esc(String(m.slug||'').toLowerCase()==='aitunnel-auto'?'Evruni Auto':m.name)}${m.configured===false?' · не настроено':''}</option>`).join('')}
function setBusy(btn,state,label='Processing…'){if(!btn)return;if(state){btn.dataset.original=btn.innerHTML;btn.disabled=true;btn.innerHTML=`<span class="spinner"></span>${esc(label)}`}else{btn.disabled=false;btn.innerHTML=btn.dataset.original||'Done'}}
async function loadModels(){
  try{
    const key='evruni_models_cache_v2',now=Date.now(),cached=sessionStorage.getItem(key);
    if(cached){const c=JSON.parse(cached);if(c.t&&now-c.t<60000&&Array.isArray(c.models))models=c.models;}
    if(!models.length){
      const d=await api('/api/models.php');models=(d.models||[]).map(m=>{
      if(String(m.slug||'').toLowerCase()==='aitunnel-auto') return Object.assign({},m,{name:'Evruni Auto'});
      return m;
    });
      try{sessionStorage.setItem(key,JSON.stringify({t:now,models}));}catch(_){}
    }
    const savedChat=readSavedModel('chatModel');const savedChatModel=models.find(x=>x.slug===savedChat&&x.enabled!==0&&x.configured!==false);currentModel=(savedChatModel?.slug)||currentModel||((models.find(x=>x.slug==='aitunnel-auto'&&x.configured!==false)||models.find(x=>x.type==='text'&&x.configured!==false)||models[0]||{}).slug||'');
    activateAllModelPickers();
    if($('#videoModel')) renderVideoControls();
    if($('#imgModel')) renderImageControls();
  }catch(e){models=[];toast('Models unavailable',e.message,'error')}
}
async function refreshModelsForCurrency(){
  const key='evruni_models_cache_v2';
  try{sessionStorage.removeItem(key)}catch(_){}
  models=[];
  await loadModels();
  document.querySelectorAll('.model-picker').forEach(wrap=>{
    const id=wrap.dataset.pickerId;
    const sel=id?document.getElementById(id):null;
    if(!sel)return;
    const mode=wrap.dataset.modelFilterMode||'default';
    renderModelPicker(id,mode);
    if(sel.value) updateModelPrice(id);
  });
  await loadBalance();
}
async function loadBalance(){try{const d=await api('/api/data.php?type=balance');if($('#credits')){const cur=d.user.currency||'EUR';const sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[cur]||cur;$('#credits').textContent=Number(d.user.wallet_balance??d.user.credits).toFixed(2);const s=$('#credits')?.nextElementSibling;if(s)s.textContent=cur;const p=$('#profileCredits');if(p)p.textContent=Number(d.user.wallet_balance??d.user.credits).toFixed(2);const ps=$('#profileCurrencySymbol');if(ps)ps.textContent=sym}}catch(_){}}
function emptyChat(){return `<div class="empty-chat"><div class="evruni-ai-sphere-wrap" aria-hidden="true"><div class="evruni-ai-sphere"></div></div><h3>${tr('empty_chat')}</h3><p>${tr('empty_chat_hint')}</p></div>`}
function renderAttachedFiles(){
  const wrap=document.getElementById('chatAttachedFiles');
  if(!wrap)return;
  const names=Array.isArray(window.__evruniAttachedDocNames)?window.__evruniAttachedDocNames:[];
  const escFn=typeof esc==='function'?esc:(v)=>String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  wrap.innerHTML=names.map((name,i)=>`<div class="attached-file" data-index="${i}"><span class="attached-file-name">${escFn(name)}</span></div>`).join('');
}

function renderChat(){
  $('#viewTitle').textContent=tr('nav_chat');
  $('#view').innerHTML=shell(tr('chat_title'),tr('chat_sub'),`<div class="toolbar chat-toolbar" style="padding:0 26px">${modelPicker('chatModel','text',`class="chat-toolbar-btn" aria-label="${tr('choose_ai')}"`)}<button class="ghost chat-toolbar-btn" id="newChat">${tr('new_chat')}</button></div><div class="chat-layout"><aside class="thread-list" id="threadList"></aside><div class="chat-panel"><div id="messages" class="messages">${emptyChat()}</div><div class="chat-composer"><div id="chatAttachedFiles" class="chat-attached-files"></div><textarea id="chatInput" placeholder="${tr('chat_placeholder')}"></textarea><div><button class="ghost" id="attach">${tr('attach')}</button><button class="primary" id="chatSend">${tr('send')}</button></div></div></div></div>`);bindModelPrice('chatModel');currentModel=$('#chatModel').value||currentModel;saveSelectedModel('chatModel',currentModel);$('#chatSend').onclick=sendChat;$('#newChat').onclick=()=>{currentThread=0;loadThread(0);toast(tr('new_chat'),'Диалог готов','success')};
  $('#chatInput').addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendChat()}});
  $('#attach').onclick=attachChatFile;loadThreads();
}
async function loadThreads(){try{const pid=Number(window.__evruniProjectId||0);const d=await api('/api/threads.php'+(pid?'?project='+pid:''));threads=d.threads||[];const projectLabel=window.EVRUNI_LANG==='en'?'Project':'Проект';$('#threadList').innerHTML=threads.map(t=>`<button class="thread ${t.id==currentThread?'active':''}" data-id="${t.id}"><span class="thread-title">${esc(t.title)}</span><small>${esc(t.model_slug||'')}</small>${t.project_name?`<small class="thread-project">${projectLabel}: ${esc(t.project_name)}</small>`:''}</button>`).join('')||'<div class="muted">Нет чатов</div>';$$('.thread').forEach(x=>x.onclick=()=>loadThread(+x.dataset.id))}catch(e){toast('Не удалось загрузить чаты',e.message,'error')}}
async function loadThread(id){currentThread=id;await loadThreads();if(!id){$('#messages').innerHTML=emptyChat();return}try{const d=await api('/api/threads.php?id='+id);$('#messages').innerHTML=(d.messages||[]).map(m=>`<div class="msg ${m.role}"><span>${m.role==='user'?'You':'Evruni'}</span><p>${esc(m.content)}</p></div>`).join('')||emptyChat();$('#messages').scrollTop=999999}catch(e){toast('Чат не загрузился',e.message,'error')}}
async function sendChat(){
  if(busy)return;
  const input=$('#chatInput');
  const typedMessage=input?.value.trim()||'';
  const attachedDocs=Array.isArray(window.__evruniAttachedDocs)?window.__evruniAttachedDocs:[];
  // Allow sending attached files without requiring extra text.
  // The backend needs a non-empty message, so use a clear document-only instruction.
  const message=typedMessage || (attachedDocs.length ? 'Разбери прикреплённый файл и объясни, что в нём важно, что от меня требуется и что делать дальше.' : '');
  if(!message)return;
  const btn=$('#chatSend'),model=$('#chatModel').value||currentModel,box=$('#messages');
  busy=true;setBusy(btn,true,'Генерирует…');
  input.value='';

  // Put the user's message into the chat immediately. Do not reload the whole thread.
  if(box){
    const empty=box.querySelector('.empty-chat');
    if(empty) empty.remove();
    const userEl=document.createElement('div');
    userEl.className='msg user msg-local';
    userEl.innerHTML=`<span>You</span><p>${esc(message)}</p>`;
    box.appendChild(userEl);
    const generating=document.createElement('div');
    generating.className='msg assistant msg-generating';
    generating.innerHTML=`<span>Evruni</span><p><span class="generating-label">Генерирует</span><span class="generating-dots"><i></i><i></i><i></i></span></p>`;
    box.appendChild(generating);
    requestAnimationFrame(()=>{box.scrollTo({top:box.scrollHeight,behavior:'smooth'})});
  }

  try{
    const d=await post('/api/chat.php',{thread_id:currentThread,project_id:Number(window.__evruniProjectId||0),model,message,document_ids:attachedDocs});
    currentThread=d.thread_id;
    const generating=box?.querySelector('.msg-generating');
    if(generating){
      generating.classList.remove('msg-generating');
      generating.classList.add('msg-ready');
      generating.innerHTML=`<span>Evruni</span><p>${esc(d.message||'')}</p>`;
    }else if(box){
      const answer=document.createElement('div');
      answer.className='msg assistant msg-ready';
      answer.innerHTML=`<span>Evruni</span><p>${esc(d.message||'')}</p>`;
      box.appendChild(answer);
    }
    requestAnimationFrame(()=>{if(box)box.scrollTo({top:box.scrollHeight,behavior:'smooth'})});
    await loadThreads();
    await loadBalance();
    window.__evruniAttachedDocs=[];
    window.__evruniAttachedDocNames=[];
    renderAttachedFiles();
  }catch(e){
    const generating=box?.querySelector('.msg-generating');
    const errorText=e?.message||'Не удалось получить ответ от ИИ.';
    if(generating) generating.remove();
    input.value=message;
    window.__evruniAttachedDocs=[];window.__evruniAttachedDocNames=[];renderAttachedFiles();
    toast('Не удалось отправить',errorText,'error');
  }finally{
    busy=false;setBusy(btn,false);
  }
}
function attachChatFile(){
  const input=document.createElement('input');input.type='file';input.accept='.txt,.csv,.json,.pdf,.docx';input.multiple=true;input.hidden=true;
  input.onchange=async()=>{
    const files=[...input.files||[]];if(!files.length)return;
    const pid=Number($('#chatProject')?.value||window.__evruniProjectId||0);
    window.__evruniAttachedDocs=window.__evruniAttachedDocs||[];
    window.__evruniAttachedDocNames=window.__evruniAttachedDocNames||[];
    for(const f of files){
      const fd=new FormData();fd.append('file',f);if(pid)fd.append('project_id',pid);fd.append('csrf',window.EVRUNI.csrf);
      try{const d=await api('/api/documents.php',{method:'POST',body:fd});window.__evruniAttachedDocs.push(Number(d.id));window.__evruniAttachedDocNames.push(f.name);renderAttachedFiles();toast(tr('file_added'),f.name,'success')}
      catch(e){toast(tr('file_error'),e.message,'error')}
    }
  };
  document.body.appendChild(input);input.click();setTimeout(()=>input.remove(),1000);
}

async function runGeneration(type,button,prompt,extra={}){
  if(!prompt.trim()){toast('Нужен текст','Введите описание или запрос','error');return null}
  setBusy(button,true,'Generating…');try{const d=await post('/api/generate.php',Object.assign({type,prompt:prompt.trim()},extra));await loadBalance();return d}catch(e){toast('Генерация не выполнена',e.message,'error');return null}finally{setBusy(button,false)}
}
function imageModelProfile(model){
  const key=(String(model?.slug||'')+' '+String(model?.name||'')).toLowerCase();
  const p={
    size:['auto','1024x1024','1536x1024','1024x1536'],
    quality:['auto','low','medium','high'],
    quantity:['1','2','3','4'],
    transparent:true,
    references:16
  };
  if(/gpt.?image|image.?2\.5|dall.?e|openai/.test(key)){
    p.size=['auto','1024x1024','1536x1024','1024x1536'];
    p.quality=/image.?2\.5/.test(key)?['auto','low','medium','high','xhigh','max']:['auto','low','medium','high'];
    p.quantity=['1','2','3','4'];
    p.transparent=true; p.references=16;
  }else if(/flux|recraft|ideogram|imagen|stable.?diffusion|sdxl/.test(key)){
    p.size=['1024x1024','1536x1024','1024x1536'];
    p.quality=['standard','high'];
    p.quantity=['1','2','3','4'];
    p.transparent=/recraft|flux/.test(key); p.references=/recraft|ideogram|flux/.test(key)?16:4;
  }
  return p;
}

function imageModelName(){
  const m=models.find(x=>x.slug===$('#imgModel')?.value);
  return m?.name||'Изображение AI';
}

function imageReferenceCard(){
  return `<div class="video-media-card image-reference-card">
    <div class="video-media-head"><b>Референсы</b><span>необязательно, до 16</span></div>
    <label class="video-drop" for="imgReferencesFile">
      <span class="video-drop-icon">▧</span>
      <span class="video-drop-text">Выбрать изображения</span>
      <small>PNG, JPG, WEBP · можно вставить Ctrl+V</small>
    </label>
    <input id="imgReferencesFile" class="video-media-input" type="file" accept="image/*" multiple hidden>
    <div id="imgReferencesPreview" class="video-media-preview"></div>
  </div>`;
}

function bindImageReferences(){
  const input=$('#imgReferencesFile'),preview=$('#imgReferencesPreview');
  if(!input||!preview)return;
  input.addEventListener('change',()=>{
    preview.innerHTML='';
    [...(input.files||[])].slice(0,16).forEach(file=>{
      const url=URL.createObjectURL(file);
      const item=document.createElement('div'); item.className='video-media-thumb';
      item.innerHTML=`<img src="${url}" alt=""><button type="button" title="Удалить">×</button>`;
      item.querySelector('button').onclick=()=>{try{URL.revokeObjectURL(url)}catch(_){} item.remove()};
      preview.appendChild(item);
    });
  });
}

function renderImageControls(){
  const sel=$('#imgModel'); if(!sel)return;
  const model=models.find(m=>m.slug===sel.value)||models.find(m=>m.type==='image'&&m.configured!==false)||models.find(m=>m.type==='image');
  if(model && !sel.value)sel.value=model.slug;
  const p=imageModelProfile(model||{});
  const size=$('#imgSize'),quality=$('#imgQuality'),qty=$('#imgQuantity');
  const keepSelectValue=(el,values)=>{
    if(!el)return;
    const next=values.map(String);
    const current=[...el.options].map(o=>o.value);
    const prev=el.value;
    const same=current.length===next.length && current.every((v,i)=>v===next[i]);
    if(!same){
      el.innerHTML=next.map(x=>`<option value="${esc(x)}">${esc(x)}</option>`).join('');
      el.value=next.includes(prev)?prev:(next[0]||'');
    }else if(!el.value && next.length){
      el.value=next[0];
    }
  };
  keepSelectValue(size,p.size);
  keepSelectValue(quality,p.quality);
  keepSelectValue(qty,p.quantity);
  const transparent=$('#imgTransparentRow'); if(transparent)transparent.hidden=!p.transparent;
  const refs=$('#imgReferencesSection'); if(refs)refs.hidden=p.references<1;
  const chips=$('#imageCapabilityChips');
  if(chips){
    const a=[`Референсы ×${p.references}`];
    if(p.transparent)a.push('Прозрачный фон');
    a.push(`До ${p.quantity[p.quantity.length-1]} шт.`);
    chips.innerHTML=a.map(x=>`<span>${esc(x)}</span>`).join('');
  }
  const name=$('#imgSelectedModelName'); if(name)name.textContent=model?.name||'Изображение AI';
  const provider=$('#imgSelectedProvider'); if(provider)provider.textContent=model?.provider||'provider';
  const price=$('#imgCostValue'); if(price){const mult=imageCostMultiplier(size?.value,quality?.value,model); const total=mult*Number(qty?.value||1); price.textContent=dynamicModelCost(model,total); price.classList.add('category-cost-value'); const hint=$('#imgCostHint'); if(hint)hint.textContent=Number(qty?.value||1)>1?`за ${qty.value} изображения`:'за изображение';}
}

function renderImage(){
  $('#viewTitle').textContent=tr('nav_image');
  $('#view').innerHTML=shell(tr('image_title'),tr('image_sub'),`
    <div class="video-studio image-studio">
      <section class="video-section video-prompt-section">
        <div class="video-section-head"><div><h3>Промпт</h3><small id="imagePromptCount">0 симв.</small></div></div>
        <textarea id="imgPrompt" class="video-prompt-input" maxlength="4000" placeholder="${tr('image_placeholder')}"></textarea>
        <div id="imgReferencesSection" class="image-reference-wrap">${imageReferenceCard()}</div>
      </section>

      <section class="video-section">
        <div class="video-section-head">
          <div><h3>Параметры модели</h3><small>Настройки меняются автоматически под выбранный ИИ.</small></div>
          <div id="imageCapabilityChips" class="video-capability-chips"></div>
        </div>
        <div class="video-param-grid">
          <div class="video-param model-param"><label>Модель</label>${modelPicker('imgModel','image')}</div>
          <div class="video-param"><label>Размер</label><select id="imgSize"></select></div>
          <div class="video-param"><label>Качество</label><select id="imgQuality"></select></div>
          <div class="video-param"><label>Количество</label><select id="imgQuantity"></select></div>
        </div>
        <label class="video-check-row" id="imgTransparentRow"><input id="imgTransparent" type="checkbox"><span>Прозрачный фон</span></label>
      </section>

      <section class="video-section image-cost-section">
        <div class="image-cost-copy"><small>СТОИМОСТЬ</small><strong id="imgCostValue">—</strong><span id="imgCostHint">за изображение</span></div>
        <button class="primary video-generate-button" id="imgGo">◉&nbsp;&nbsp;${tr('generate')}</button>
      </section>
      <div id="imgStatus" class="info-box"></div>
      <div class="preview" id="imgPreview"><div class="preview-orb">◉</div><span>Предпросмотр</span></div>
    </div>`);

  bindModelPrice('imgModel');
  bindImageReferences();

  const prompt=$('#imgPrompt');
  if(prompt)prompt.addEventListener('input',()=>{$('#imagePromptCount').textContent=`${prompt.value.length} симв.`});
  $('#imgModel').addEventListener('change',renderImageControls);
  const updateImageCost=()=>{
    const model=models.find(m=>m.slug===$('#imgModel')?.value)||null;
    const size=$('#imgSize'),quality=$('#imgQuality'),qty=$('#imgQuantity');
    const mult=imageCostMultiplier(size?.value,quality?.value,model);
    const count=Math.max(1,Number(qty?.value||1));
    const price=$('#imgCostValue');
    if(price){
      price.textContent=dynamicModelCost(model,mult*count);
      price.classList.add('category-cost-value');
      const hint=$('#imgCostHint');
      if(hint)hint.textContent=count>1?`за ${count} изображения`:'за изображение';
    }
  };
  ['imgSize','imgQuality','imgQuantity'].forEach(id=>$('#'+id)?.addEventListener('change',updateImageCost));
  renderImageControls();

  $('#imgGo').onclick=async()=>{
    const d=await runGeneration('image',$('#imgGo'),prompt.value,{
      model:$('#imgModel').value,
      size:$('#imgSize').value,
      quality:$('#imgQuality').value,
      quantity:Number($('#imgQuantity').value||1),
      transparent:$('#imgTransparent')?.checked?1:0
    });
    if(d){
      const paths=Array.isArray(d.result_paths)&&d.result_paths.length?d.result_paths:(d.result_path?[d.result_path]:[]);
      if(paths.length)$('#imgPreview').innerHTML=paths.map(p=>`<img src="${esc(p)}" alt="Generated image" style="max-width:48%;max-height:48%;object-fit:contain;border-radius:16px;margin:4px">`).join('');
      else $('#imgStatus').textContent=d.result_text||'Готово';
      toast('Изображение готово',paths.length>1?`Сгенерировано: ${paths.length}`:'Результат сохранён','success');
    }
  };
}
function videoModelProfile(model){
  const slug=String(model?.slug||'').toLowerCase();
  const name=String(model?.name||'').toLowerCase();
  const key=slug+' '+name;
  const profile={
    resolution:['480x480','720x1280','1280x720','1920x1080'],
    duration:['4 сек','5 сек','8 сек','10 сек'],
    fps:['24 fps','30 fps'],
    ratio:['16:9','9:16','1:1'],
    audio:true,
    first:true,last:true,reference:true,video:false
  };
  if(/seedance|seed dream|bytedance/.test(key)){
    profile.resolution=['480x480','720x1280','1280x720','1920x1080'];
    profile.duration=['4 сек','5 сек','8 сек','10 сек'];
    profile.fps=['24 fps','30 fps'];
    profile.audio=true; profile.first=true; profile.last=true; profile.reference=true; profile.video=false;
  }else if(/kling/.test(key)){
    profile.resolution=['720p','1080p'];
    profile.duration=['5 сек','10 сек'];
    profile.fps=['24 fps','30 fps'];
    profile.audio=false; profile.first=true; profile.last=true; profile.reference=true; profile.video=true;
  }else if(/runway/.test(key)){
    profile.resolution=['720p','1080p'];
    profile.duration=['5 сек','10 сек'];
    profile.fps=['24 fps'];
    profile.audio=false; profile.first=true; profile.last=false; profile.reference=true; profile.video=true;
  }else if(/sora/.test(key)){
    profile.resolution=['720p','1080p'];
    profile.duration=['5 сек','10 сек','15 сек'];
    profile.fps=['24 fps'];
    profile.audio=true; profile.first=true; profile.last=false; profile.reference=true; profile.video=false;
  }else if(/veo/.test(key)){
    profile.resolution=['720p','1080p'];
    profile.duration=['5 сек','8 сек'];
    profile.fps=['24 fps','30 fps'];
    profile.audio=true; profile.first=true; profile.last=false; profile.reference=true; profile.video=false;
  }else if(/wan|hailuo|minimax/.test(key)){
    profile.resolution=['720p','1080p'];
    profile.duration=['5 сек','10 сек'];
    profile.fps=['24 fps'];
    profile.audio=false; profile.first=true; profile.last=true; profile.reference=true; profile.video=true;
  }
  return profile;
}

function videoModelName(){
  const m=models.find(x=>x.slug===$('#videoModel')?.value);
  return m?.name||'Видео AI';
}

function videoInputCard(id,title,hint,accept,multiple=false){
  return `<div class="video-media-card" data-video-card="${id}">
    <div class="video-media-head"><b>${esc(title)}</b><span>${esc(hint)}</span></div>
    <label class="video-drop" for="${id}File">
      <span class="video-drop-icon">▧</span>
      <span class="video-drop-text">Выбрать файл</span>
      <small>PNG, JPG, WEBP${accept.includes('video')?', MP4, WEBM':''}</small>
    </label>
    <input id="${id}File" class="video-media-input" type="file" accept="${accept}" ${multiple?'multiple':''} hidden>
    <div id="${id}Preview" class="video-media-preview"></div>
  </div>`;
}

function bindVideoMedia(id,multiple=false){
  const input=$('#'+id+'File'), preview=$('#'+id+'Preview');
  if(!input||!preview)return;
  input.addEventListener('change',()=>{
    preview.innerHTML='';
    const files=[...(input.files||[])];
    files.forEach((file,index)=>{
      const url=URL.createObjectURL(file);
      const item=document.createElement('div'); item.className='video-media-thumb';
      if(file.type.startsWith('video/')){
        item.innerHTML=`<video src="${url}" muted playsinline></video><button type="button" title="Удалить">×</button>`;
      }else{
        item.innerHTML=`<img src="${url}" alt=""><button type="button" title="Удалить">×</button>`;
      }
      item.querySelector('button').onclick=()=>{
        try{URL.revokeObjectURL(url)}catch(_){}
        files.splice(index,1);
        const dt=new DataTransfer(); files.forEach(f=>dt.items.add(f)); input.files=dt.files;
        item.remove();
      };
      preview.appendChild(item);
    });
  });
}

function renderVideoControls(){
  const sel=$('#videoModel'); if(!sel)return;
  const model=models.find(m=>m.slug===sel.value)||models.find(m=>m.type==='video'&&m.configured!==false)||models.find(m=>m.type==='video');
  if(model && !sel.value) sel.value=model.slug;
  const profile=videoModelProfile(model||{});
  const resolution=$('#videoResolution'),duration=$('#videoDuration'),fps=$('#videoFps'),ratio=$('#videoRatio');
  if(resolution)resolution.innerHTML=profile.resolution.map((x,i)=>`<option ${i===0?'selected':''}>${x}</option>`).join('');
  if(duration)duration.innerHTML=profile.duration.map((x,i)=>`<option ${i===0?'selected':''}>${x}</option>`).join('');
  if(fps)fps.innerHTML=profile.fps.map((x,i)=>`<option ${i===0?'selected':''}>${x}</option>`).join('');
  if(ratio)ratio.innerHTML=profile.ratio.map((x,i)=>`<option ${i===0?'selected':''}>${x}</option>`).join('');
  const audio=$('#videoAudioRow'); if(audio)audio.hidden=!profile.audio;
  const first=$('[data-video-card="firstFrame"]'),last=$('[data-video-card="lastFrame"]'),ref=$('[data-video-card="reference"]'),vin=$('[data-video-card="videoInput"]');
  if(first)first.hidden=!profile.first;
  if(last)last.hidden=!profile.last;
  if(ref)ref.hidden=!profile.reference;
  if(vin)vin.hidden=!profile.video;
  const caps=$('#videoCapabilityChips');
  if(caps){
    const chips=[];
    if(profile.audio)chips.push('Аудио');
    if(profile.first)chips.push('Первый кадр');
    if(profile.last)chips.push('Последний кадр');
    if(profile.reference)chips.push('Референс');
    if(profile.video)chips.push('Входное видео');
    caps.innerHTML=chips.map(x=>`<span>${esc(x)}</span>`).join('');
  }
  const name=$('#videoSelectedModelName'); if(name)name.textContent=model?.name||'Видео AI';
  const provider=$('#videoSelectedProvider'); if(provider)provider.textContent=model?.provider||'provider';
  const price=$('#videoCostValue'); if(price){const mult=videoCostMultiplier(resolution?.value,duration?.value,fps?.value,'', $('#videoAudio')?.checked); price.textContent=dynamicModelCost(model,mult); price.classList.add('category-cost-value');}
  const status=$('#videoModelHint'); if(status)status.textContent=model?`Настройки для ${model.name}`:'Выберите модель';
}

async function loadRecentVideoGenerations(){
  const box=$('#videoRecentList'); if(!box)return;
  try{
    const d=await api('/api/data.php?type=dashboard');
    const rows=(d.generations||[]).filter(x=>x.type==='video').slice(0,8);
    box.innerHTML=rows.map(g=>{
      const status=String(g.status||'').toLowerCase();
      const icon=status==='completed'?'▶':status==='failed'?'!':'◌';
      const label=status==='completed'?'Готово':status==='failed'?'Ошибка':'В очереди';
      return `<article class="video-recent-row">
        <div class="video-recent-icon">${icon}</div>
        <div class="video-recent-copy"><b>${esc(g.prompt||'Без описания')}</b><small>${esc(videoModelName())} · ${esc(g.created_at||'')} · ${label}</small></div>
        <strong>${esc(String(g.credits_spent??''))}</strong>
      </article>`;
    }).join('') || `<div class="muted">Видео ещё не создавались.</div>`;
  }catch(_){
    box.innerHTML=`<div class="muted">История видео пока недоступна.</div>`;
  }
}

function trendFallbackData(){
  return [
    {id:'hotel-lobby',title:'Hotel Lobby',platform:'TikTok · Shorts · Reels',meta:'2 лица · 15 сек',tag:'🔥 Hot',desc:'Два героя, один микрофон и узнаваемая студийная сцена.',cls:'trend-hotel',source:'https://jumponthetrend.app/trends/youtube-shorts/',videoId:'',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Покажи двух героев сразу',prompt:'Вертикальный short 9:16 в формате Hotel Lobby: два героя по очереди у одного микрофона, яркий студийный фон, естественная мимика, смена крупных планов и короткий музыкальный финал. Создай новую сцену на основе загруженных лиц.'},
    {id:'turn-lights-off',title:'Turn the Lights Off',platform:'Shorts · TikTok · Reels',meta:'1 фото · 10 сек',tag:'🔥 Hot',desc:'Танцевальная сцена с резким световым переходом.',cls:'trend-burning',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:1,labels:['Фото героя'],type:'face',hook:'Сразу показать героя в танце',prompt:'Создай вертикальный 9:16 short: герой оказывается на танцполе, закрывает глаза, поднимает руки, свет резко меняется и камера делает плавный push-in. Сохрани лицо и естественные движения.'},
    {id:'clapping-cat',title:'Clapping Cat',platform:'TikTok · Shorts · Reels',meta:'1 питомец · 5 сек',tag:'🐾 Pet',desc:'Короткий мем с питомцем и идеально зацикленным финалом.',cls:'trend-halloween',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:1,labels:['Фото питомца'],type:'pet',hook:'Крупный план питомца',prompt:'Создай вертикальный мемный ролик 9:16 на 5 секунд: питомец смотрит в камеру, улыбается и медленно хлопает лапами/лапами с человеческим жестом. Сделай бесшовный loop.'},
    {id:'knight-cat',title:'Knight Cat',platform:'TikTok · Shorts · Reels',meta:'1 питомец · 10 сек',tag:'🐾 Pet',desc:'Питомец в эпическом фэнтези-образе.',cls:'trend-zombie',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:1,labels:['Фото питомца'],type:'pet',hook:'Герой-питомец в первом кадре',prompt:'Создай вертикальный fantasy short 9:16: питомец превращается в маленького рыцаря, держит большой меч, ветер двигает шерсть и плащ, камера делает эпический подъём. Сохрани внешность питомца.'},
    {id:'ai-crying',title:'AI Crying',platform:'TikTok · Reels · Shorts',meta:'1 фото · 10 сек',tag:'😢 Reaction',desc:'Эмоциональная реакция с постепенным усилением мимики.',cls:'trend-raindance',source:'https://videosi.io/trending',people:1,labels:['Фото лица'],type:'face',hook:'Сильная эмоция крупным планом',prompt:'Создай вертикальный emotional short 9:16: крупный план лица, эмоция постепенно усиливается до слёз, лёгкое дрожание камеры и реалистичная мимика. Сохрани идентичность человека.'},
    {id:'toc-toc',title:'Toc Toc Dance',platform:'TikTok · Shorts · Reels',meta:'1–2 лица · 10 сек',tag:'💃 Dance',desc:'Короткая синхронная танцевальная связка.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Оба героя появляются в первом кадре',prompt:'Создай вертикальный dance short 9:16: один или два героя выполняют короткую синхронную связку, камера слегка двигается в ритм, финал возвращается к исходной позе.'},
    {id:'on-radar',title:'On The Radar',platform:'TikTok · Shorts · Reels',meta:'1–2 лица · 14 сек',tag:'🎤 Music',desc:'Уличная микрофонная сцена в формате freestyle.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Микрофон и лицо в первые секунды',prompt:'Создай вертикальный freestyle short 9:16: один или два героя в компактной музыкальной будке, микрофон в центре, динамичная реакция камеры и энергичный перформанс.'},
    {id:'alors-danse',title:'Alors on Danse',platform:'Shorts · TikTok · Reels',meta:'2 лица · 18 сек',tag:'🎭 Duo',desc:'Комедийный дуэт с камерой почти вплотную к героям.',cls:'trend-hotel',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Дуэт сразу у камеры',prompt:'Создай вертикальный комедийный duo short 9:16: два героя рядом с камерой, один задаёт ритм, второй неожиданно реагирует, затем оба синхронно выполняют финальное движение.'},
    {id:'doorbell-tornado',title:'Doorbell Cam Tornado',platform:'TikTok · Shorts',meta:'1 фото · 10 сек',tag:'🌪️ AI Scene',desc:'Псевдокамера домофона и неожиданный погодный хаос.',cls:'trend-halloween',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:1,labels:['Фото героя'],type:'face',hook:'Сразу показать камеру наблюдения',prompt:'Создай вертикальный found-footage short 9:16 в стиле камеры домофона: герой выходит к двери, начинается сильный ветер, листья и предметы летят вокруг, герой пытается удержаться и финал резко обрывается.'},
    {id:'thriller-pet',title:'Thriller Pet',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 10 сек',tag:'🎃 Seasonal',desc:'Питомец в хоррор-танце с Halloween атмосферой.',cls:'trend-halloween',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:1,labels:['Фото питомца'],type:'pet',hook:'Питомец в красном образе',prompt:'Создай вертикальный Halloween short 9:16: питомец в сценическом образе выполняет короткое зомби-танцевальное движение, ветер, туман и красный свет, без копирования конкретного кадра.'},
    {id:'rumplestiltskin',videoUrl:'https://jumponthetrend.app/trends/rumpelstiltskin-ai-trend/',title:'Rumpelstiltskin Dance',platform:'TikTok · Shorts · Reels',meta:'1–2 лица · 9 сек',tag:'↗ Rising',desc:'Странный сказочный танец с сильным визуальным образом.',cls:'trend-zombie',source:'https://jumponthetrend.app/trends/youtube-shorts/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Сказочный персонаж сразу в кадре',prompt:'Создай вертикальный fantasy short 9:16: сказочный персонаж выполняет странный короткий танец, камера чуть дрожит как старое кино, второй герой может появиться в финале.'},
    {id:'dancing-dog',videoUrl:'https://jumponthetrend.app/trends/ai-pet-dance/',title:'Dancing Dog AI',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 10 сек',tag:'🐶 New',desc:'Питомец превращается в главного танцевального героя.',cls:'trend-bulin',source:'https://videosi.io/trending',people:1,labels:['Фото питомца'],type:'pet',hook:'Питомец крупным планом',prompt:'Создай вертикальный dance short 9:16 с питомцем: он встаёт, делает забавную танцевальную связку, камера следует за движением, финал — короткая поза в камеру.'},
    {id:'jean-phil',title:'Jean Phil AI',platform:'TikTok · Shorts · Reels',meta:'1 фото · 9 сек',tag:'🆕 New',desc:'Быстрый AI-персонажный формат с коротким payoff.',cls:'trend-raindance',source:'https://videosi.io/trending',people:1,labels:['Фото лица'],type:'face',hook:'Сильный образ в первом кадре',prompt:'Создай вертикальный AI character short 9:16: герой быстро меняет окружение и образ, сохраняя лицо, затем делает короткое уверенное движение в камеру.'},
    {id:'jawn-dance',videoUrl:'https://jumponthetrend.app/trends/jawn/',title:'Jawn Dance',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 10 сек',tag:'💃 New',desc:'Быстрая танцевальная петля для одного или двух героев.',cls:'trend-bulin',source:'https://videosi.io/trending',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Движение начинается мгновенно',prompt:'Создай вертикальный dance loop 9:16: один или два героя начинают движение сразу, камера слегка приближает, затем финальная поза совпадает с первым кадром.'},
    {id:'zombie',videoUrl:'https://jumponthetrend.app/trends/zombie-love-story-ai-trend/',title:'Zombie Love Story',platform:'TikTok · Shorts · Reels',meta:'2 лица · 10–15 сек',tag:'🔥 Trend',desc:'Превращение → встреча → эмоциональный финал.',cls:'trend-zombie',source:'https://aizombiecouple.org/',videoId:'7691835688706116878',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Покажи двух героев сразу',prompt:'Создай вертикальный Zombie Love Story short 9:16: кинематографичное превращение, эмоциональная встреча двух героев, объятие и тёплый финал. Сохрани лица.'},
    {id:'burning',videoUrl:'https://jumponthetrend.app/trends/burning-bridges-ai-trend/',title:'Burning Bridges',platform:'TikTok · Shorts · Reels',meta:'1–2 лица · 20 сек',tag:'🔥 Hot',desc:'Вечеринка, уверенный герой и cinematic камера.',cls:'trend-burning',source:'https://jumponthetrend.app/trends/burning-bridges-ai-trend/',videoId:'7694447027471600909',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Начни с героя под люстрой',prompt:'Создай вертикальный cinematic party short 9:16: роскошная вечеринка, герой в центре, второй герой может появиться в середине, плавное приближение камеры и уверенный финальный жест.'},
    {id:'bulin',videoUrl:'https://jumponthetrend.app/trends/bulin-47-ai-trend/',title:'Bulin 47',platform:'TikTok · Shorts · Reels',meta:'1 лицо · 15 сек',tag:'🔥 Hot',desc:'Красный свет, толпа и энергичный freestyle.',cls:'trend-bulin',source:'https://www.remakeyourmeme.com/templates/bulin-47-ai-trend/',videoId:'7691875916644994326',people:1,labels:['Фото лица'],type:'face',hook:'Герой в центре толпы',prompt:'Создай вертикальный street freestyle short 9:16: красные огни, толпа, герой в центре, энергичная камера и короткие монтажные склейки. Сохрани лицо героя.'},
    {id:'raindance',videoUrl:'https://jumponthetrend.app/trends/raindance-ai-trend/',title:'Raindance',platform:'TikTok · Shorts · Reels',meta:'1–2 лица · 15 сек',tag:'✨ Cinematic',desc:'Дождь, золотой час и мягкое движение камеры.',cls:'trend-raindance',source:'https://jumponthetrend.app/trends/youtube-shorts/',videoId:'7689332850398678304',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Первый кадр — золотой час',prompt:'Создай вертикальный cinematic short 9:16: золотой час, лёгкий дождь, ветер в одежде, плавный dolly-in, два героя и эмоциональный финал.'},
    {id:'photo-motion',videoUrl:'https://www.youtube.com/results?search_query=AI+photo+to+video+trend+2026',title:'Photo → Motion',platform:'TikTok · Shorts',meta:'1 фото · 8–10 сек',tag:'✨ AI',desc:'Самый простой вариант: одно фото превращается в short.',cls:'trend-raindance',source:'https://blog.youtube/news-and-events/new-shorts-creation-tools-2025/',people:1,labels:['Фото героя'],type:'face',hook:'Начни с исходного фото',prompt:'Преврати загруженное фото в вертикальный cinematic short 9:16. Сохрани идентичность, добавь естественное движение лица, одежды и камеры, один выразительный переход и loop-friendly финал.'},
    {id:'product-ugc',videoUrl:'https://ads.tiktok.com/creative/creativeCenter/trends/video',title:'Product UGC Hook',platform:'TikTok · Reels · Shorts',meta:'1 лицо + продукт · 12–15 сек',tag:'💼 UGC',desc:'Лицо + продукт + hook + демонстрация + CTA.',cls:'trend-bulin',source:'https://ads.tiktok.com/creative/creativeCenter/trends/video',people:1,labels:['Лицо','Фото продукта'],type:'face-product',hook:'Начни с проблемы, а не с логотипа',prompt:'Создай вертикальный UGC short 9:16: герой начинает с проблемы, показывает продукт, демонстрирует одно преимущество и заканчивает естественным CTA. Сохрани внешность героя и продукт.'},
    {id:'cinematic-loop',videoUrl:'https://www.youtube.com/results?search_query=cinematic+loop+tiktok+trend+2026',title:'Cinematic Loop',platform:'TikTok · Shorts · Reels',meta:'1 лицо · 8–10 сек',tag:'↻ Loop',desc:'Последний кадр незаметно возвращает к первому.',cls:'trend-raindance',source:'https://support.google.com/youtube/answer/16738000',people:1,labels:['Главное лицо'],type:'face',hook:'Первый и последний кадр совпадают',prompt:'Создай вертикальный cinematic loop 9:16 на 8–10 секунд. Первый и последний кадр визуально совпадают. Один герой выполняет простое действие, камера плавная, свет стабильный.'}

    ,{id:'single-ladies-dog',title:'Single Ladies Dog',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 15 сек',tag:'↗ Rising',desc:'Питомец занимает центральное место в танце.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/single-ladies-dog-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/single-ladies-dog-ai-trend/',people:1,labels:['Фото питомца'],type:'pet',hook:'Питомец в центре танца',prompt:'Создай вертикальный dance short 9:16: питомец занимает центральное место в динамичной танцевальной сцене, повторяет движения и сохраняет внешность.'}
    ,{id:'i-wanna-love-you',title:'I Wanna Love You',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 10 сек',tag:'↗ Rising',desc:'Питомец в костюме поёт в ночном селфи-кадре.',cls:'trend-raindance',source:'https://jumponthetrend.app/trends/i-wanna-love-you-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/i-wanna-love-you-ai-trend/',people:1,labels:['Фото питомца'],type:'pet',hook:'Ночной selfie-клип',prompt:'Создай вертикальный AI pet short 9:16: питомец в ярком костюме снимает ночное селфи у подсвеченной площадки, поёт и делает жест в камеру.'}
    ,{id:'smalltown-boy-cats',title:'Smalltown Boy Cats',platform:'TikTok · Reels · Shorts',meta:'2 питомца · 21 сек',tag:'🔥 Hot',desc:'Два питомца синхронно двигаются у красной ограды.',cls:'trend-zombie',source:'https://jumponthetrend.app/trends/smalltown-boy-cats-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/smalltown-boy-cats-ai-trend/',people:2,labels:['Питомец 1','Питомец 2'],type:'pet',hook:'Два питомца в одном кадре',prompt:'Создай вертикальный AI pet short 9:16: два питомца синхронно двигаются у красной ограды, выразительная мимика, лёгкая камера и музыкальный финал.'}
    ,{id:'gang-gang-dance',title:'Gang Gang Dance',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 15 сек',tag:'🔥 Hot',desc:'Freestyle battle с энергичной камерой.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/gang-gang-dance-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/gang-gang-dance-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Battle floor',prompt:'Создай вертикальный freestyle battle short 9:16: герой один на танцполе, резкие knee pops и движения руками, камера снимает как phone video, энергичный финал.'}
    ,{id:'toc-toc-dance',title:'Toc Toc Dance',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 10 сек',tag:'🔥 Hot',desc:'Хоп, стук и финальный удар в ритм.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/toc-toc-dance-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/toc-toc-dance-ai-trend/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Движение начинается сразу',prompt:'Создай вертикальный dance short 9:16: один или два героя выполняют короткую связку с прыжком, стуком и финальным kick, камера динамичная.'}
    ,{id:'ramalama-costume',title:'Ramalama Costume Walk',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 10 сек',tag:'🎃 Halloween',desc:'Костюмированный проход в стиле Halloween.',cls:'trend-zombie',source:'https://jumponthetrend.app/trends/ramalama-costume-walk-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/ramalama-costume-walk-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Костюм появляется в первом кадре',prompt:'Создай вертикальный Halloween fashion walk 9:16: герой идёт в ярком костюме, камера следует за ним, свет и фон кинематографичные, уверенный финал.'}
    ,{id:'clapping-cat-loop',title:'Clapping Cat Loop',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 5 сек',tag:'🐾 Viral',desc:'Питомец медленно хлопает лапами прямо в камеру.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/clapping-cat-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/clapping-cat-ai-trend/',people:1,labels:['Фото питомца'],type:'pet',hook:'Крупный план мордочки',prompt:'Создай короткий вертикальный AI pet video 9:16: питомец крупным планом улыбается и медленно хлопает лапами в камеру, комедийная реакция.'}
    ,{id:'money-flex',title:'Money Flex',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 10 сек',tag:'💸 Viral',desc:'Комедийный money-flex формат с быстрым payoff.',cls:'trend-raindance',source:'https://jumponthetrend.app/trends/money-flex-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/money-flex-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Деньги появляются сразу',prompt:'Создай вертикальный comedy short 9:16: герой демонстрирует пачку денег, бросает их в камеру и делает уверенный финальный жест, динамичный монтаж.'}

    ,{id:'perfect-partner',title:'Perfect Partner',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 10 сек',tag:'✨ New',desc:'Синхронный дуэт с эффектным появлением второго героя.',cls:'trend-hotel',source:'https://jumponthetrend.app/trends/perfect-partner-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/perfect-partner-ai-trend/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Второй герой появляется в ритм',prompt:'Создай вертикальный duo short 9:16: первый герой начинает движение, второй появляется в идеальный музыкальный момент, затем оба синхронно завершают связку.'}
    ,{id:'lion-friend',title:'Lion Friend',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 10 сек',tag:'🦁 AI',desc:'Герой неожиданно встречает льва в кинематографичной сцене.',cls:'trend-raindance',source:'https://jumponthetrend.app/trends/lion-friend-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/lion-friend-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Животное появляется в первом переходе',prompt:'Создай вертикальный cinematic AI short 9:16: герой идёт по природной локации, рядом появляется лев, они спокойно смотрят друг на друга, камера делает плавный круг.'}
    ,{id:'flying-roadster',title:'Flying Roadster',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 12 сек',tag:'🚗 AI',desc:'Автомобильный переход с эффектом полёта.',cls:'trend-burning',source:'https://jumponthetrend.app/trends/flying-roadster-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/flying-roadster-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Авто появляется через переход',prompt:'Создай вертикальный cinematic car short 9:16: герой стоит рядом с автомобилем, машина эффектно взлетает над дорогой, камера делает быстрый orbit и возвращается к герою.'}
    ,{id:'crazy-frog',title:'Crazy Frog',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 8 сек',tag:'⚡ Meme',desc:'Быстрый абсурдный meme-format с резким финалом.',cls:'trend-halloween',source:'https://jumponthetrend.app/trends/crazy-frog-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/crazy-frog-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Абсурд начинается сразу',prompt:'Создай вертикальный meme short 9:16: герой начинает обычное движение, внезапно появляется абсурдный персонаж, камера резко приближает и финал возвращается к первому кадру.'}
    ,{id:'ozil-goal',title:'Ozil Goal',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 10 сек',tag:'⚽ Sport',desc:'Футбольный момент с эффектным голом и реакцией.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/ozil-goal-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/ozil-goal-ai-trend/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Удар начинается в первом кадре',prompt:'Создай вертикальный football short 9:16: герой получает мяч, делает техничный удар, второй герой реагирует, камера следует за мячом и возвращается на празднование.'}
    ,{id:'can-i-ride',title:'Can I Ride',platform:'TikTok · Reels · Shorts',meta:'1 лицо · 10 сек',tag:'😂 Comedy',desc:'Комедийная сцена с неожиданной поездкой.',cls:'trend-hotel',source:'https://jumponthetrend.app/trends/can-i-ride-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/can-i-ride-ai-trend/',people:1,labels:['Фото лица'],type:'face',hook:'Вопрос и реакция в первом кадре',prompt:'Создай вертикальный comedy short 9:16: герой подходит к необычному транспорту, задаёт вопрос взглядом в камеру, садится и начинается неожиданно быстрая поездка.'}
    ,{id:'closed-sunday',title:'Closed on Sunday',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 12 сек',tag:'🎵 Music',desc:'Музыкальный lip-sync с быстрыми сменами планов.',cls:'trend-burning',source:'https://jumponthetrend.app/trends/closed-on-sunday-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/closed-on-sunday-ai-trend/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Lip-sync начинается сразу',prompt:'Создай вертикальный music short 9:16: один или два героя делают lip-sync, камера переключается между крупным и общим планом, финал — синхронный жест.'}
    ,{id:'la-calabaza-cat',title:'La Calabaza Cat',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 8 сек',tag:'🐱 Pet',desc:'Питомец в смешном тыквенном образе.',cls:'trend-halloween',source:'https://jumponthetrend.app/trends/la-calabaza-cat-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/la-calabaza-cat-ai-trend/',people:1,labels:['Фото питомца'],type:'pet',hook:'Тыквенный образ в первом кадре',prompt:'Создай вертикальный pet short 9:16: питомец появляется в забавном тыквенном образе, делает короткое движение под музыку и смотрит прямо в камеру.'}
    ,{id:'zombie-walk',title:'Zombie Walk',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 12 сек',tag:'🧟 Trend',desc:'Кинематографичная прогулка с эффектом превращения.',cls:'trend-zombie',source:'https://jumponthetrend.app/trends/zombie-walk-ai-trend/',videoUrl:'https://jumponthetrend.app/trends/zombie-walk-ai-trend/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Превращение начинается на ходу',prompt:'Создай вертикальный cinematic zombie short 9:16: два героя идут навстречу, один постепенно превращается, камера плавно приближается и заканчивает сцену на реакции второго.'}
    ,{id:'ai-selfie-transition',title:'AI Selfie Transition',platform:'TikTok · Reels · Shorts',meta:'1 фото · 8 сек',tag:'✨ AI',desc:'Обычное селфи превращается в новую сцену.',cls:'trend-raindance',source:'https://jumponthetrend.app/trends/ai-selfie-transition/',videoUrl:'https://jumponthetrend.app/trends/ai-selfie-transition/',people:1,labels:['Фото лица'],type:'face',hook:'Начни с обычного селфи',prompt:'Создай вертикальный AI transition 9:16: обычное селфи плавно превращается в кинематографичную сцену, сохрани лицо, волосы и ключевые черты.'}
    ,{id:'pet-fashion-walk',title:'Pet Fashion Walk',platform:'TikTok · Reels · Shorts',meta:'1 питомец · 10 сек',tag:'🐾 Fashion',desc:'Питомец выходит как модель на мини-подиум.',cls:'trend-bulin',source:'https://jumponthetrend.app/trends/pet-fashion-walk/',videoUrl:'https://jumponthetrend.app/trends/pet-fashion-walk/',people:1,labels:['Фото питомца'],type:'pet',hook:'Питомец выходит прямо к камере',prompt:'Создай вертикальный pet fashion short 9:16: питомец идёт к камере как модель, одежда и фон стильные, камера мягко отъезжает, финал — поза.'}
    ,{id:'street-reaction',title:'Street Reaction',platform:'TikTok · Reels · Shorts',meta:'1–2 лица · 10 сек',tag:'🎤 UGC',desc:'Уличная реакция с быстрым вопросом и ответом.',cls:'trend-hotel',source:'https://jumponthetrend.app/trends/street-reaction/',videoUrl:'https://jumponthetrend.app/trends/street-reaction/',people:2,labels:['Лицо 1','Лицо 2'],type:'faces',hook:'Вопрос задаётся в первом кадре',prompt:'Создай вертикальный street reaction short 9:16: один герой задаёт короткий вопрос, второй неожиданно отвечает, камера держит handheld-динамику и заканчивает на реакции.'}

  ];
}

const TREND_VIDEO_IDS={bulin:'7691875916644994326',zombie:'7691835688706116878',burning:'7694447027471600909',raindance:'7689332850398678304'};
function trendVideoId(t){return t.videoId||TREND_VIDEO_IDS[t.id]||'';}
function trendVideoEmbed(t){const id=trendVideoId(t);
 if(id)return `<div class="trend-v2-real-video"><iframe class="trend-tiktok-frame" loading="lazy" src="https://www.tiktok.com/player/v1/${encodeURIComponent(id)}?autoplay=1&loop=1&controls=1&music_info=0&description=0" title="${esc(t.title)} — пример тренда" allow="autoplay; encrypted-media; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" frameborder="0"></iframe><div class="trend-v2-video-label">TikTok · пример тренда</div></div>`;
 return `<div class="trend-v2-scene"><div class="trend-v2-orb"></div><div class="trend-v2-person"></div><div class="trend-v2-person second"></div><strong>${esc(t.title)}</strong><small>${esc(t.hook||'AI short')}</small></div>`;
}
function trendVideoToolsMarkup(t){const id=trendVideoId(t), url=t.videoUrl||t.source||''; if(id)return `<div class="trend-video-tools"><button class="trend-sound-toggle" type="button" data-muted="1" aria-label="Включить звук" title="Включить звук"><svg class="trend-sound-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4Zm12.2 1.3a4 4 0 0 1 0 4.4M18.7 7.8a8 8 0 0 1 0 8.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button><a class="trend-watch-btn" href="${esc(url)}" target="_blank" rel="noopener" aria-label="Открыть пример видео">↗</a></div>`; return `<div class="trend-video-tools"><button class="trend-sound-toggle trend-sound-external" type="button" data-url="${esc(url)}" aria-label="Смотреть видео со звуком" title="Смотреть видео со звуком"><svg class="trend-sound-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4Zm12.2 1.3a4 4 0 0 1 0 4.4M18.7 7.8a8 8 0 0 1 0 8.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button><a class="trend-watch-btn" href="${esc(url)}" target="_blank" rel="noopener" aria-label="Открыть пример видео">↗</a></div>`;}
function trendCardMarkup(t){return `<article class="trend-card-v2" data-trend-id="${esc(t.id)}" data-trend-prompt="${esc(t.prompt||'')}" data-trend-source="${esc(t.source||'')}" data-trend-video-url="${esc(t.videoUrl||t.source||'')}" data-trend-people="${esc(String(t.people||1))}" data-trend-type="${esc(t.type||'face')}" data-trend-labels="${esc(JSON.stringify(t.labels||['Ваше фото']))}">
  <div class="trend-v2-preview ${esc(t.cls||'trend-burning')}"><div class="trend-v2-phone"><div class="trend-v2-top"><span>●</span><b>${esc(t.platform?.split('·')[0]?.trim()||'SHORT')}</b><span>⋯</span></div>${trendVideoEmbed(t)}<div class="trend-v2-bottom"><span>♡ 12.4K</span><span>↗</span></div></div><span class="trend-v2-format">9:16</span></div>
  <div class="trend-v2-body"><div class="trend-v2-title"><h3>${esc(t.title)}</h3><span>${esc(t.tag||'В тренде')}</span></div><div class="trend-v2-platform">${esc(t.platform||'TikTok · Shorts · Reels')}</div><p>${esc(t.desc||'Готовый формат короткого видео.')}</p><div class="trend-v2-meta"><span>◉ ${esc(t.meta||'1 фото')}</span><span>⚡ ${esc(t.type==='pet'?'Питомец':t.type==='face-product'?'Лицо + продукт':t.people>1?'2 лица':'1 лицо')}</span></div><div class="trend-v2-card-tools">${trendVideoToolsMarkup(t)}</div><div class="trend-v2-actions"><button class="primary trend-create-v2" type="button">Создать</button><button class="ghost trend-save-v2" type="button" aria-label="Сохранить">♡</button></div></div>
</article>`}
function renderTrendLabContent(list){return `<div class="trend-lab-v2"><div class="trend-v2-hero"><div><div class="eyebrow">EVRUNI TREND LAB</div><h1>Идея → фото → готовый short</h1><p>Не нужно разбираться в промптах. Выбери формат, загрузи нужные фото — Evruni сама подготовит сценарий, кадры и параметры.</p></div><div class="trend-v2-status"><i></i><b>Живые тренды</b><small>обновление по кэшу</small></div></div><div class="trend-v2-toolbar"><div class="trend-v2-search"><input id="trendSearch" placeholder="Найти тренд…"></div><div id="trendFilters" class="trend-filters"><button class="active" data-filter="all">Все</button><button data-filter="1">1 фото</button><button data-filter="2">2 лица</button><button data-filter="pet">Питомцы</button><button data-filter="ugc">UGC</button></div><button id="trendRefresh" class="ghost">↻ Обновить</button></div><div id="trendGrid" class="trend-v2-grid">${list.map(trendCardMarkup).join('')}</div><div id="trendPagination" class="trend-pagination"></div><div class="trend-v2-footnote">У каждого тренда есть пример для просмотра. Для внешних примеров откроется страница с оригинальным видео и звуком.</div></div>`}
function bindTrendVideoSound(root=document){root.querySelectorAll('.trend-sound-toggle').forEach(btn=>{if(btn.dataset.bound)return;btn.dataset.bound='1';btn.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();if(btn.classList.contains('trend-sound-external')){window.open(btn.dataset.url||'#','_blank','noopener');return;}const frame=btn.closest('.trend-v2-preview')?.querySelector('.trend-tiktok-frame');if(!frame?.contentWindow)return;const muted=btn.dataset.muted!=='0';frame.contentWindow.postMessage({type:muted?'unMute':'mute','x-tiktok-player':true},'*');btn.dataset.muted=muted?'0':'1';btn.classList.toggle('is-on',muted);btn.setAttribute('aria-label',muted?'Выключить звук':'Включить звук');btn.title=muted?'Выключить звук':'Включить звук';});});}
function bindTrendLab(list){const grid=$('#trendGrid');if(!grid)return;const saved=JSON.parse(localStorage.getItem('evruni_trend_saved_v1')||'[]');let page=Number(localStorage.getItem('evruni_trend_page_v1')||'1');const perPage=9;const total=Math.max(1,Math.ceil(list.length/perPage));page=Math.min(Math.max(page,1),total);function paint(){const q=($('#trendSearch')?.value||'').toLowerCase();const filter=$('#trendFilters .active')?.dataset.filter||'all';const filtered=list.filter(t=>{const text=(t.title+' '+(t.desc||'')+' '+(t.tag||'')).toLowerCase();let ok=!q||text.includes(q);if(filter==='1')ok=ok&&(t.people||1)===1;if(filter==='2')ok=ok&&(t.people||1)===2;if(filter==='pet')ok=ok&&t.type==='pet';if(filter==='ugc')ok=ok&&t.type==='face-product';return ok});const pages=Math.max(1,Math.ceil(filtered.length/perPage));page=Math.min(page,pages);grid.innerHTML=filtered.slice((page-1)*perPage,page*perPage).map(trendCardMarkup).join('');grid.querySelectorAll('.trend-create-v2').forEach(b=>b.onclick=e=>{e.stopPropagation();openTrendCreateModal(b.closest('.trend-card-v2'))});grid.querySelectorAll('.trend-save-v2').forEach(b=>b.onclick=e=>{e.stopPropagation();const c=b.closest('.trend-card-v2'),id=c.dataset.trendId,idx=saved.indexOf(id);if(idx>=0){saved.splice(idx,1);c.classList.remove('saved');b.textContent='♡'}else{saved.push(id);c.classList.add('saved');b.textContent='♥'}localStorage.setItem('evruni_trend_saved_v1',JSON.stringify(saved));});saved.forEach(id=>grid.querySelector(`[data-trend-id="${CSS.escape(id)}"]`)?.classList.add('saved'));bindTrendVideoSound(grid);const pag=$('#trendPagination');pag.innerHTML='';for(let i=1;i<=pages;i++){const b=document.createElement('button');b.type='button';b.textContent=String(i);if(i===page)b.className='active';b.onclick=()=>{page=i;localStorage.setItem('evruni_trend_page_v1',String(page));paint();window.scrollTo({top:document.querySelector('.trend-lab-v2')?.offsetTop||0,behavior:'smooth'});};pag.appendChild(b);}const more=document.createElement('span');more.textContent=` ${filtered.length} трендов · 9 на странице`;pag.appendChild(more);}
$('#trendSearch')?.addEventListener('input',()=>{page=1;paint()});$('#trendFilters')?.querySelectorAll('button').forEach(b=>b.onclick=()=>{$('#trendFilters .active')?.classList.remove('active');b.classList.add('active');page=1;paint()});paint();}
async function renderTrendLab(){ $('#viewTitle').textContent='Trend Lab';const feed=await loadTrendFeed(false);$('#view').innerHTML=shell('Trend Lab','TikTok · Shorts · Reels — готовые форматы для создания своих роликов',renderTrendLabContent(feed.trends));const meta=$('#trendUpdateMeta');if(meta)meta.textContent=feed.updated_at?`Обновлено ${feed.updated_at} · автообновление не чаще 12 часов`:'Автообновление включено';bindTrendLab(feed.trends);$('#trendRefresh')?.addEventListener('click',async()=>{const b=$('#trendRefresh');if(b){b.disabled=true;b.textContent='…'}const fresh=await loadTrendFeed(true);$('#view').innerHTML=shell('Trend Lab','TikTok · Shorts · Reels — готовые форматы для создания своих роликов',renderTrendLabContent(fresh.trends));bindTrendLab(fresh.trends);});}

async function loadTrendFeed(force=false){
  const KEY='evruni_trend_feed_v2'; const TTL=12*60*60*1000;
  try{
    const cached=JSON.parse(localStorage.getItem(KEY)||'null');
    if(!force&&cached&&Array.isArray(cached.trends)&&cached.trends.length&&(Date.now()-(cached.ts||0)<TTL)) return cached;
  }catch(_){ }
  try{
    const r=await fetch('/api/trends.php'+(force?'?refresh=1':''),{credentials:'same-origin',cache:'no-store'});
    if(!r.ok)throw new Error('trend feed');
    const data=await r.json();
    if(Array.isArray(data.trends)&&data.trends.length){
      const out={trends:data.trends,ts:Date.now(),updated_at:data.updated_at||''};
      try{localStorage.setItem(KEY,JSON.stringify(out));}catch(_){ }
      return out;
    }
  }catch(_){ }
  return {trends:trendFallbackData(),ts:Date.now(),updated_at:''};
}

async function renderTrendLab(){
  $('#viewTitle').textContent='Trend Lab';
  const feed=await loadTrendFeed(false);
  $('#view').innerHTML=shell('Trend Lab','Популярные форматы для TikTok, Reels и Shorts',renderTrendLabContent(feed.trends));
  const meta=$('#trendUpdateMeta');
  if(meta){meta.textContent=feed.updated_at?`Обновлено: ${feed.updated_at} · следующий авто-проверочный запрос не раньше чем через 12 часов`:'Автообновление включено · используется кэшированный список';}
  bindTrendLab(feed.trends);
  $('#trendRefresh')?.addEventListener('click',async()=>{
    const b=$('#trendRefresh'); if(b){b.disabled=true;b.textContent='↻ Обновление…';}
    const fresh=await loadTrendFeed(true);
    $('#view').innerHTML=shell('Trend Lab','Популярные форматы для TikTok, Reels и Shorts',renderTrendLabContent(fresh.trends));
    const m=$('#trendUpdateMeta'); if(m)m.textContent=fresh.updated_at?`Обновлено: ${fresh.updated_at}`:'Список обновлён';
    bindTrendLab(fresh.trends);
  });
}

function renderVideo(){
  $('#viewTitle').textContent=tr('nav_video');
  $('#view').innerHTML=shell(tr('video_title'),tr('video_sub'),`
    <div class="video-studio">
      <section class="video-section video-prompt-section">
        <div class="video-section-head"><div><h3>Промпт</h3><small id="videoPromptCount">0 / 2000</small></div></div>
        <textarea id="videoPrompt" class="video-prompt-input" maxlength="2000" placeholder="${tr('video_placeholder')}"></textarea>
      </section>

      <section class="video-section">
        <div class="video-section-head">
          <div><h3>Параметры модели</h3><small id="videoModelHint">Выберите модель</small></div>
          <div id="videoCapabilityChips" class="video-capability-chips"></div>
        </div>
        <div class="video-param-grid">
          <div class="video-param model-param"><label>Модель</label>${modelPicker('videoModel','video')}</div>
          <div class="video-param"><label>Разрешение</label><select id="videoResolution"></select></div>
          <div class="video-param"><label>Длительность</label><select id="videoDuration"></select></div>
          <div class="video-param"><label>FPS</label><select id="videoFps"></select></div>
          <div class="video-param"><label>Соотношение</label><select id="videoRatio"></select></div>
        </div>
        <label class="video-check-row" id="videoAudioRow"><input id="videoAudio" type="checkbox" checked><span>Сгенерировать аудиодорожку</span></label>
      </section>

      <section class="video-section">
        <div class="video-section-head">
          <div><h3>Изображения для модели</h3><small>Добавьте первый/последний кадр, референсы или исходное видео — набор меняется в зависимости от выбранного ИИ.</small></div>
        </div>
        <div class="video-media-grid">
          ${videoInputCard('firstFrame','Первый кадр','image-to-video','image/*')}
          ${videoInputCard('lastFrame','Последний кадр','image-to-video','image/*')}
          ${videoInputCard('reference','Референс','style reference','image/*',true)}
          ${videoInputCard('videoInput','Исходное видео','video-to-video','video/*')}
        </div>
      </section>

      <section class="video-cost-bar">
        <div><small>СТОИМОСТЬ</small><b id="videoCostValue">—</b><span id="videoSelectedProvider"></span></div>
        <button class="primary video-generate-btn" id="videoGo"><span>▷</span>${tr('create_video')}</button>
      </section>

      <section class="video-recent">
        <div class="video-recent-head"><h3>Последние генерации</h3><button class="ghost" id="videoRefreshRecent">Обновить</button></div>
        <div id="videoRecentList" class="video-recent-list"><div class="muted">Загрузка…</div></div>
      </section>
      <div id="videoStatus" class="info-box video-status">${tr('video_info')}</div>
    </div>
  `);

  bindModelPrice('videoModel');
  // Trend Lab lives in its own tab.
  renderVideoControls();
  $('#videoModel').addEventListener('change',renderVideoControls);
  ['videoResolution','videoDuration','videoFps','videoRatio','videoAudio'].forEach(id=>$('#'+id)?.addEventListener('change',renderVideoControls));
  $('#videoPrompt').addEventListener('input',()=>{
    const n=$('#videoPrompt').value.length; $('#videoPromptCount').textContent=`${n} / 2000`;
  });
  ['firstFrame','lastFrame','reference','videoInput'].forEach(id=>bindVideoMedia(id,id==='reference'));
  $('#videoRefreshRecent').onclick=loadRecentVideoGenerations;
  $('#videoGo').onclick=async()=>{
    const model=$('#videoModel').value;
    const payload={
      model,
      resolution:$('#videoResolution').value,
      duration:$('#videoDuration').value,
      fps:$('#videoFps').value,
      aspect_ratio:$('#videoRatio').value,
      audio:$('#videoAudio')?.checked?1:0,
      first_frame:$('#firstFrameFile')?.files?.[0]?.name||'',
      last_frame:$('#lastFrameFile')?.files?.[0]?.name||'',
      references:[...($('#referenceFile')?.files||[])].map(f=>f.name),
      source_video:$('#videoInputFile')?.files?.[0]?.name||''
    };
    const d=await runGeneration('video',$('#videoGo'),$('#videoPrompt').value,payload);
    if(d){
      $('#videoStatus').textContent=`Задача #${d.id} создана. ${d.result_text||'Ожидает provider.'}`;
      toast('Видео поставлено в очередь',`Задача #${d.id}`,'success');
      loadRecentVideoGenerations();
    }
  };
  loadRecentVideoGenerations();
}

function renderAudio(){
  $('#viewTitle').textContent=tr('nav_audio');
  $('#view').innerHTML=shell(tr('audio_title'),tr('audio_sub'),`<div class="two-col"><div class="mini-card"><h3>${tr('tts_title')}</h3>${modelPickerBySlugs('ttsModel',['speech-default'],`class="chat-toolbar-btn"`)}<textarea id="ttsText" class="big-input" placeholder="${tr('tts_placeholder')}"></textarea><button class="primary" id="ttsGo">${tr('generate_voice')}</button><div id="ttsResult" class="info-box">${tr('audio_ready')}</div></div><div class="mini-card"><h3>${tr('stt_title')}</h3>${modelPickerBySlugs('sttModel',['transcribe-default'],`class="chat-toolbar-btn"`)}<div class="evruni-file-picker evruni-audio-picker"><input id="audioFile" class="audio-file-input-native" type="file" accept="audio/*"><label class="evruni-file-button" for="audioFile">${tr('choose_audio_file')||'Выбрать аудиофайл'}</label></div><button class="primary" id="transcribeGo">${tr('transcribe')}</button><div id="transcribeResult" class="result-box">${tr('transcript_ready')}</div></div></div>`);bindModelPrice('ttsModel');bindModelPrice('sttModel');
  $('#ttsGo').onclick=async()=>{const b=$('#ttsGo');if(!$('#ttsText').value.trim()){toast('Нужен текст','Введите текст для озвучки','error');return}setBusy(b,true,'Generating…');try{const d=await post('/api/audio.php',{action:'tts',model:$('#ttsModel').value,text:$('#ttsText').value});$('#ttsResult').innerHTML=`<audio controls style="width:100%" src="${esc(d.audio_url)}"></audio>`;await loadBalance();toast('Озвучка готова','Аудиофайл создан','success')}catch(e){toast('TTS не выполнен',e.message,'error')}finally{setBusy(b,false)}};
  $('#audioFile').onchange=()=>{};$('#transcribeGo').onclick=async()=>{const f=$('#audioFile').files[0];if(!f){toast('Выберите аудиофайл','Нужен файл для транскрибации','error');return}const b=$('#transcribeGo');setBusy(b,true,'Transcribing…');const fd=new FormData();fd.append('action','transcribe');fd.append('model',$('#sttModel').value);fd.append('file',f);fd.append('csrf',window.EVRUNI.csrf);try{const d=await api('/api/audio.php',{method:'POST',body:fd});$('#transcribeResult').textContent=d.text;await loadBalance();toast('Расшифровка готова','Текст получен','success')}catch(e){toast('Транскрибация не выполнена',e.message,'error')}finally{setBusy(b,false)}}
}
async function renderSupport(){
  $('#viewTitle').textContent='Поддержка';
  $('#view').innerHTML=`<section class="view-card"><div class="view-top"><div><div class="eyebrow">ПОДДЕРЖКА</div><h2>Поддержка</h2><p class="view-sub">Задайте вопрос или сообщите о проблеме</p></div><button class="ghost support-new-top" id="supportNew">＋ Новое обращение</button></div>
    <div class="support-layout">
      <aside class="support-list-panel">
        <div class="support-list-head"><b>Обращения</b></div>
        <div id="supportThreads" class="support-threads"><div class="muted">У вас пока нет обращений</div></div>
      </aside>
      <section class="support-chat-panel" id="supportPanel">
        <div class="support-placeholder"><div class="empty-orb support-orb">✦</div><h3>Выберите обращение</h3><p>Выберите обращение слева или создайте новое</p></div>
      </section>
    </div></section>`;
  let current=0, timer=0;
  const loadList=async()=>{
    try{
      const d=await api('/api/support.php?action=threads');
      $('#supportThreads').innerHTML=(d.threads||[]).map(t=>{const unread=!!(JSON.parse(localStorage.getItem('evruni_support_unread_v2')||'{}')[String(t.id)]);return `<button class="support-thread ${t.id==current?'active':''} ${unread?'unread':''}" data-id="${t.id}"><span><b>${esc(t.subject)}${unread?'<em class="support-unread-dot" aria-label="непрочитано"></em>':''}</b><small>${t.status==='open'?'Открыто':'Закрыто'} · ${t.message_count||0}</small></span><i>${esc(String(t.updated_at||'').slice(0,16))}</i></button>`}).join('')||`<div class="muted">У вас пока нет обращений</div>`;
      $$('.support-thread').forEach(b=>b.onclick=()=>loadThread(+b.dataset.id));
    }catch(e){toast('Поддержка',e.message,'error')}
  };
  const loadThread=async(id)=>{
    current=id;
    try{
      const d=await api('/api/support.php?action=thread&id='+id),t=d.thread;
      $('#supportPanel').innerHTML=`<div class="support-chat-head"><div><b>${esc(t.subject)}</b><small>${t.status==='open'?'Открыто':'Закрыто'}</small></div><button class="ghost" id="supportStatus">${t.status==='open'?'Закрыть обращение':'Открыть обращение'}</button></div><div class="support-messages" id="supportMessages">${(d.messages||[]).map(m=>`<div class="support-msg ${m.author_role}"><span>${m.author_role==='admin'?'Служба поддержки Evruni':'Вы'}</span><p>${esc(m.content)}</p><small>${esc(m.created_at||'')}</small></div>`).join('')}</div><div class="support-compose">${t.status==='open'?`<textarea id="supportInput" placeholder="Опишите вашу проблему или вопрос..."></textarea><button class="primary" id="supportSend">Отправить</button>`:`<div class="support-closed-note">Обращение закрыто</div>`}</div>`;
      const box=$('#supportMessages');if(box)box.scrollTop=box.scrollHeight;
      $('#supportStatus').onclick=async()=>{
        try{await post('/api/support.php?action='+(t.status==='open'?'close':'reopen'),{thread_id:id});await loadThread(id);await loadList()}
        catch(e){toast('Поддержка',e.message,'error')}
      };
      if(t.status==='open'){
        $('#supportSend').onclick=async()=>{
          const msg=$('#supportInput').value.trim();if(!msg)return;
          setBusy($('#supportSend'),true,'Отправка…');
          try{await post('/api/support.php?action=message',{thread_id:id,message:msg});$('#supportInput').value='';await loadThread(id);await loadList()}
          catch(e){toast('Поддержка',e.message,'error')}
          finally{setBusy($('#supportSend'),false)}
        };
      }
    }catch(e){toast('Поддержка',e.message,'error')}
  };
  $('#supportNew').onclick=()=>{
    $('#supportPanel').innerHTML=`<div class="support-chat-head"><div><b>Новое обращение</b><small>Создайте обращение в поддержку</small></div></div><div class="support-compose support-new-compose"><input id="supportSubject" placeholder="Тема обращения"><textarea id="supportInput" placeholder="Опишите вашу проблему или вопрос..."></textarea><button class="primary" id="supportSend">Отправить</button></div>`;
    $('#supportSend').onclick=async()=>{
      const subject=$('#supportSubject').value.trim(),msg=$('#supportInput').value.trim();
      if(!subject||!msg){toast('Поддержка','Заполните тему и сообщение','error');return}
      setBusy($('#supportSend'),true,'Отправка…');
      try{
        const d=await post('/api/support.php?action=create',{subject,message:msg});
        await loadList();
        if(d.thread_id)await loadThread(d.thread_id);
        toast('Поддержка','Обращение создано','success');
      }catch(e){toast('Поддержка',e.message,'error')}
      finally{setBusy($('#supportSend'),false)}
    };
  };
  await loadList();
  if(timer)clearInterval(timer);
  timer=setInterval(loadList,30000);
}
function renderResearch(){
  $('#viewTitle').textContent=tr('nav_research');$('#view').innerHTML=shell(tr('research_title'),tr('research_sub'),`<div class="studio-single"><textarea id="researchPrompt" class="big-input" placeholder="${tr('research_placeholder')}"></textarea><div class="toolbar">${modelPicker('researchModel','research')}<button class="primary" id="researchGo">${tr('research_button')}</button></div><div id="researchResult" class="result-box">${tr('research_ready')}</div></div>`);bindModelPrice('researchModel');
  $('#researchGo').onclick=async()=>{const d=await runGeneration('research',$('#researchGo'),$('#researchPrompt').value,{model:$('#researchModel').value});if(d)$('#researchResult').textContent=d.result_text||d.message||'Готово'}
}
async function renderDocuments(){
  $('#viewTitle').textContent=tr('nav_documents');$('#view').innerHTML=shell(tr('docs_title'),tr('docs_sub'),`<div class="upload-zone" id="uploadZone"><div class="evruni-file-picker"><input id="docFile" type="file" accept=".txt,.md,.csv,.json,.pdf,.docx"><label class="evruni-file-button" for="docFile">${tr('choose_file')||'Выбрать файл'}</label><span class="evruni-file-name" id="docFileName">${tr('file_not_selected')||'Файл не выбран'}</span></div><h3>${tr('upload_document')}</h3><p>${tr('docs_types')}</p><button class="primary" id="docUpload">${tr('upload')}</button></div><div id="docList" class="list"></div><div id="docAnalysis" class="doc-analysis" hidden></div>`);
  const upload=async()=>{const f=$('#docFile').files[0];if(!f){toast(tr('choose_file'),tr('doc_needed'),'error');return}const b=$('#docUpload');setBusy(b,true,'Uploading…');const fd=new FormData();fd.append('file',f);fd.append('csrf',window.EVRUNI.csrf);try{await api('/api/documents.php',{method:'POST',body:fd});toast(tr('document_uploaded'),f.name,'success');renderDocuments()}catch(e){toast(tr('upload_failed'),e.message,'error')}finally{setBusy(b,false)}};
  const analyze=async(id,name,button)=>{const box=$('#docAnalysis');if(!box)return;box.hidden=false;box.innerHTML=`<div class="doc-analysis-head"><div><span class="eyebrow">EVRUNI</span><h3>${tr('understand_this')}</h3><p>${esc(name)}</p></div></div><div class="doc-analysis-loading">${tr('understanding_document')}</div>`;setBusy(button,true,tr('analyzing'));try{const model=currentModel||models.find(m=>m.type==='text'&&m.configured!==false)?.slug||'aitunnel-auto';const prompt=`Разберись с этим документом для пользователя. Ответь строго по содержимому документа, без выдуманных фактов. Структура:
1. Что это
2. Кратко о чём
3. Что от пользователя требуется
4. Важные даты и сроки
5. Деньги и суммы
6. Риски или пункты, которые стоит проверить
7. Что делать дальше
Если какого-то пункта в документе нет, так и напиши. Пиши понятно и кратко на русском языке.`;const d=await post('/api/chat.php',{thread_id:0,project_id:0,model,message:prompt,document_ids:[Number(id)]});box.innerHTML=`<div class="doc-analysis-head"><div><span class="eyebrow">EVRUNI · ${tr('understand_this')}</span><h3>${tr('analysis_ready')}</h3><p>${esc(name)}</p></div><button class="ghost" id="docAnalysisChat">${tr('open_in_chat')}</button></div><div class="doc-analysis-result">${esc(d.message||'').replace(/\n/g,'<br>')}</div>`;$('#docAnalysisChat').onclick=()=>{document.querySelector('.nav[data-view="chat"]')?.click();setTimeout(()=>loadThread(Number(d.thread_id||0)),120)}}catch(e){box.innerHTML=`<div class="doc-analysis-head"><div><span class="eyebrow">EVRUNI</span><h3>${tr('analysis_failed')}</h3></div></div><div class="doc-analysis-result">${esc(e.message)}</div>`}finally{setBusy(button,false)}};
  $('#docUpload').onclick=upload;$('#docFile').onchange=()=>{$('#docFileName').textContent=$('#docFile').files[0]?.name||tr('file_not_selected')||'Файл не выбран'};const zone=$('#uploadZone');zone.ondragover=e=>{e.preventDefault();zone.classList.add('dragover')};zone.ondragleave=()=>zone.classList.remove('dragover');zone.ondrop=e=>{e.preventDefault();zone.classList.remove('dragover');if(e.dataTransfer.files[0]){$('#docFile').files=e.dataTransfer.files;upload()}};
  try{const d=await api('/api/documents.php');$('#docList').innerHTML=(d.documents||[]).map(x=>`<div class="list-row doc-list-row"><span>▤ ${esc(x.name)}</span><div class="doc-row-actions"><small>${Math.round(x.size_bytes/1024)} KB</small><button class="ghost doc-understand" data-id="${Number(x.id)}" data-name="${esc(x.name)}">${tr('understand_this')}</button></div></div>`).join('')||'<div class="muted">'+tr('no_documents')+'</div>';document.querySelectorAll('.doc-understand').forEach(b=>b.onclick=()=>analyze(b.dataset.id,b.dataset.name,b))}catch(e){toast(tr('documents_load_failed'),e.message,'error')}
}
async function renderModels(){
  $('#viewTitle').textContent=tr('nav_models');$('#view').innerHTML=shell(tr('models_title'),tr('models_sub'),`<div class="model-filters"><input id="modelSearch" placeholder="${tr('search_models')}"><select id="modelCategory"><option value="">${tr('all_types')}</option></select></div><div id="modelGrid" class="model-grid"></div>`);
  const cats=[...new Map(models.map(m=>[m.category_key||m.type,{key:m.category_key||m.type,label:m.category||m.type}])).values()];
  $('#modelCategory').innerHTML=`<option value="">${tr('all_types')}</option>`+cats.map(c=>`<option value="${esc(c.key)}">${esc(c.label)}</option>`).join('');
  const draw=()=>{const q=$('#modelSearch').value.toLowerCase(),t=$('#modelCategory').value;$('#modelGrid').innerHTML=models.filter(m=>(!q||m.name.toLowerCase().includes(q)||m.slug.toLowerCase().includes(q))&&(!t||(m.category_key||m.type)===t)).map(m=>`<article class="model-card"><div><b>${esc(String(m.slug||'').toLowerCase()==='aitunnel-auto'?'Evruni Auto':m.name)}</b><small>${esc(m.category||m.type)} · ${esc(m.provider)} · ${m.configured===false?tr('not_configured'):tr('ready')}</small></div><strong>${modelPrice(m)}</strong></article>`).join('')};$('#modelSearch').oninput=draw;$('#modelCategory').onchange=draw;draw()
}
async function renderPresets(){
  $('#viewTitle').textContent=tr('nav_presets');$('#view').innerHTML=shell(tr('presets_title'),tr('presets_sub'),`<div class="preset-form"><input id="presetName" placeholder="${tr('preset_name')}">${modelPicker('presetModel','all')}<textarea id="presetSystem" class="big-input" placeholder="${tr('system_prompt')}"></textarea><button class="primary" id="presetSave">${tr('save_preset')}</button></div><div id="presetList" class="list"></div>`);bindModelPrice('presetModel');
  $('#presetSave').onclick=async()=>{const b=$('#presetSave');setBusy(b,true,'Saving…');try{await post('/api/presets.php',{name:$('#presetName').value,model_slug:$('#presetModel').value,system_prompt:$('#presetSystem').value});toast('Preset сохранён','','success');renderPresets()}catch(e){toast('Не удалось сохранить',e.message,'error')}finally{setBusy(b,false)}};try{const d=await api('/api/presets.php');$('#presetList').innerHTML=(d.presets||[]).map(x=>`<div class="list-row"><span><b>${esc(x.name)}</b><small>${esc(x.model_slug)}</small></span><button class="danger" onclick="deletePreset(${x.id})">Delete</button></div>`).join('')||'<div class="muted">'+tr('no_presets')+'</div>'}catch(e){toast('Presets unavailable',e.message,'error')}
}
async function deletePreset(id){try{await api('/api/presets.php?id='+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':window.EVRUNI.csrf}});toast('Preset удалён','','success');renderPresets()}catch(e){toast('Удаление не удалось',e.message,'error')}}
async function renderSandbox(){
  $('#viewTitle').textContent='API Sandbox';$('#view').innerHTML=shell(tr('sandbox_title'),tr('sandbox_sub'),`<div class="sandbox"><div class="toolbar">${modelPicker('sbModel','text')}<select id="sbEndpoint"><option>chat/completions</option><option>images/generations</option></select></div><textarea id="sbPrompt" class="big-input" placeholder="${tr('write_prompt')}"></textarea><div class="toolbar"><button class="primary" id="sbRun">${tr('run_request')}</button><button class="ghost" id="sbCopy">${tr('copy_curl')}</button></div><pre id="sbResult" class="codebox">${tr('response_here')}</pre></div>`);
  $('#sbRun').onclick=async()=>{const b=$('#sbRun');setBusy(b,true,'Running…');try{const d=await post('/api/chat.php',{model:$('#sbModel').value,message:$('#sbPrompt').value});$('#sbResult').textContent=JSON.stringify(d,null,2);toast('Request completed','','success')}catch(e){$('#sbResult').textContent=e.message;toast('Request failed',e.message,'error')}finally{setBusy(b,false)}};
  $('#sbCopy').onclick=async()=>{const curl=`curl https://evruni.com/api/v1/chat/completions -H "Authorization: Bearer YOUR_KEY" -H "Content-Type: application/json" -d '${JSON.stringify({model:$('#sbModel').value,messages:[{role:'user',content:$('#sbPrompt').value||'Hello'}]}).replace(/'/g,"'\\''")}'`;try{await navigator.clipboard.writeText(curl);toast('cURL скопирован','','success')}catch(_){toast('Не удалось скопировать','Скопируйте запрос вручную','error')}}
}
async function renderApi(){
  $('#viewTitle').textContent=tr('nav_api');$('#view').innerHTML=shell(tr('api_title'),tr('api_sub'),`<div class="key-create"><input id="keyName" placeholder="${tr('key_name')}"><input id="keyBudget" type="number" placeholder="${tr('budget')}"><button class="primary" id="keyCreate">${tr('create_key')}</button></div><div class="warning">${tr('api_warning')}</div><div id="keyList" class="list"></div>`);bindModelPrice('sbModel');
  $('#keyCreate').onclick=async()=>{const b=$('#keyCreate');setBusy(b,true,'Creating…');try{const d=await post('/api/keys.php',{name:$('#keyName').value,budget:$('#keyBudget').value});if(d.key){try{await navigator.clipboard.writeText(d.key)}catch(_){}toast('API key создан','Ключ скопирован в буфер, если браузер разрешил','success')}renderApi()}catch(e){toast('Не удалось создать ключ',e.message,'error')}finally{setBusy(b,false)}};try{const d=await api('/api/keys.php');$('#keyList').innerHTML=(d.keys||[]).map(k=>`<div class="list-row"><span><b>${esc(k.name)}</b><small>${esc(k.key_prefix)}… · spent ${esc(k.spent)}</small></span><button class="danger" onclick="revokeKey(${k.id})">Revoke</button></div>`).join('')||'<div class="muted">'+tr('no_keys')+'</div>'}catch(e){toast('API keys unavailable',e.message,'error')}
}
async function revokeKey(id){try{await api('/api/keys.php?id='+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':window.EVRUNI.csrf}});toast('API key отозван','','success');renderApi()}catch(e){toast('Не удалось отозвать',e.message,'error')}}
async function renderUsage(){const usageCurrency=({EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[(window.__evruniUsageCurrency||'EUR')])||window.__evruniUsageCurrency||'EUR';
  $('#viewTitle').textContent=tr('nav_usage');
  const days=window.__evruniUsageDays||30;
  try{
    const d=await api('/api/usage.php?days='+days);window.__evruniUsageCurrency=d.currency||'EUR';
    const maxDaily=Math.max(1,...(d.rows||[]).map(x=>Number(x.cost||0)));
    const maxModel=Math.max(1,...(d.models||[]).map(x=>Number(x.cost??x.credits??0)));
    const maxProject=Math.max(1,...(d.projects||[]).map(x=>Number(x.cost??x.credits??0)));
    $('#view').innerHTML=shell(tr('usage_title'),tr('usage_sub'),`
      <div class="usage-period">
        <button class="ghost usage-period-btn ${days===7?'active':''}" data-days="7">7 ${tr('days_short')}</button>
        <button class="ghost usage-period-btn ${days===30?'active':''}" data-days="30">30 ${tr('days_short')}</button>
        <button class="ghost usage-period-btn ${days===90?'active':''}" data-days="90">90 ${tr('days_short')}</button>
      </div>
      <div class="stats">
        <div><b>${Number(d.generation_totals?.cost||0).toFixed(2)} ${({EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[d.currency]||d.currency||'EUR')}</b><span>${tr('credits_spent')}</span></div>
        <div><b>${Number(d.generation_totals?.operations||0)}</b><span>${tr('ai_operations')}</span></div>
        <div><b>${Number(d.totals?.requests||0)}</b><span>${tr('api_requests')}</span></div>
        <div><b>${Number(d.totals?.cost||0).toFixed(4)}</b><span>${tr('provider_cost')}</span></div>
      </div>
      <div class="usage-grid">
        <section class="usage-card"><h3>${tr('usage_daily')}</h3>
          <div class="usage-bars">${(d.rows||[]).slice(-14).map(x=>`<div class="usage-bar-row"><span>${esc(x.day)}</span><div><i style="width:${Math.max(3,Math.round(Number(x.cost||0)/maxDaily*100))}%"></i></div><b>${Number(x.cost||0).toFixed(3)}</b></div>`).join('')||'<div class="muted">'+tr('no_usage')+'</div>'}</div>
        </section>
        <section class="usage-card"><h3>${tr('usage_models')}</h3>
          <div class="usage-list">${(d.models||[]).map(x=>`<div class="usage-list-row"><div><b>${esc(x.model)}</b><small>${x.operations} ${tr('operations')}</small></div><strong>${Number(x.cost??x.credits??0).toFixed(2)} ${({EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[d.currency]||d.currency||'EUR')}</strong><span><i style="width:${Math.max(3,Math.round(Number(x.cost??x.credits??0)/maxModel*100))}%"></i></span></div>`).join('')||'<div class="muted">'+tr('no_usage')+'</div>'}</div>
        </section>
        <section class="usage-card"><h3>${tr('usage_projects')}</h3>
          <div class="usage-list">${(d.projects||[]).map(x=>`<div class="usage-list-row"><div><b>${esc(x.project)}</b><small>${x.operations} ${tr('operations')}</small></div><strong>${Number(x.cost??x.credits??0).toFixed(2)} ${({EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[d.currency]||d.currency||'EUR')}</strong><span><i style="width:${Math.max(3,Math.round(Number(x.cost??x.credits??0)/maxProject*100))}%"></i></span></div>`).join('')||'<div class="muted">'+tr('no_usage')+'</div>'}</div>
        </section>
        <section class="usage-card"><h3>${tr('usage_recent')}</h3>
          <div class="usage-list">${(d.recent||[]).map(x=>`<div class="usage-list-row recent"><div><b>${esc(x.model||x.type)}</b><small>${esc(x.project||tr('no_project'))} · ${esc(x.status)}</small></div><strong>${Number(x.cost??x.credits_spent??0).toFixed(2)} ${({EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[d.currency]||d.currency||'EUR')}</strong><time>${esc(String(x.created_at||'').slice(0,16))}</time></div>`).join('')||'<div class="muted">'+tr('no_usage')+'</div>'}</div>
        </section>
      </div>`);
    $$('.usage-period-btn').forEach(btn=>btn.onclick=()=>{window.__evruniUsageDays=Number(btn.dataset.days);renderUsage()});
  }catch(e){$('#view').innerHTML=shell(tr('usage_title'),tr('usage_sub'),`<div class="info-box">${esc(e.message||'Usage unavailable')}</div>`)}
}
async function renderBilling(){
  $('#viewTitle').textContent=tr('nav_billing');
  try{
    const d=await api('/api/packages.php');const sym=d.symbol||'€';
    const countries=[
      ['NL','Нидерланды','Netherlands','EUR','€'],['RU','Россия','Russia','RUB','₽'],['DE','Германия','Germany','EUR','€'],['FR','Франция','France','EUR','€'],['GB','Великобритания','United Kingdom','GBP','£'],
      ['US','США','United States','USD','$'],['CA','Канада','Canada','USD','$'],['AU','Австралия','Australia','USD','$'],['AT','Австрия','Austria','EUR','€'],['BE','Бельгия','Belgium','EUR','€'],['ES','Испания','Spain','EUR','€'],['IT','Италия','Italy','EUR','€'],['PL','Польша','Poland','EUR','€'],
      ['CH','Швейцария','Switzerland','EUR','€'],['SE','Швеция','Sweden','EUR','€'],['NO','Норвегия','Norway','EUR','€'],['DK','Дания','Denmark','EUR','€'],['FI','Финляндия','Finland','EUR','€'],['IE','Ирландия','Ireland','EUR','€'],['PT','Португалия','Portugal','EUR','€'],['CZ','Чехия','Czechia','EUR','€'],
      ['RO','Румыния','Romania','EUR','€'],['EE','Эстония','Estonia','EUR','€'],['LT','Литва','Lithuania','EUR','€'],['LV','Латвия','Latvia','EUR','€'],['OTHER','Другая страна','Other country','EUR','€']
    ];
    const countryCurrency=Object.fromEntries(countries.map(c=>[c[0],{code:c[3],symbol:c[4]}]));
    // Evruni supports EUR/USD/GBP/RUB for payments. For countries without
    // a directly supported local currency, use the more natural regional
    // settlement currency: EUR in Europe, USD outside Europe.
    const smartCurrencyByCountry={
      RU:['RUB','₽'],GB:['GBP','£'],
      NL:['EUR','€'],DE:['EUR','€'],FR:['EUR','€'],AT:['EUR','€'],BE:['EUR','€'],ES:['EUR','€'],IT:['EUR','€'],PT:['EUR','€'],IE:['EUR','€'],FI:['EUR','€'],
      PL:['EUR','€'],CZ:['EUR','€'],RO:['EUR','€'],SE:['EUR','€'],NO:['EUR','€'],DK:['EUR','€'],CH:['EUR','€'],EE:['EUR','€'],LT:['EUR','€'],LV:['EUR','€'],
      US:['USD','$'],CA:['USD','$'],AU:['USD','$'],NZ:['USD','$'],MX:['USD','$'],BR:['USD','$'],AR:['USD','$'],CL:['USD','$'],CO:['USD','$'],PE:['USD','$'],
      JP:['USD','$'],KR:['USD','$'],CN:['USD','$'],HK:['USD','$'],SG:['USD','$'],IN:['USD','$'],AE:['USD','$'],SA:['USD','$'],IL:['USD','$'],
      TR:['USD','$'],ZA:['USD','$'],EG:['USD','$'],NG:['USD','$']
    };
    const getTopupCurrency=(code)=>{
      const c=smartCurrencyByCountry[code]||countryCurrency[code]||['EUR','€'];
      return {code:c[0],symbol:c[1]};
    };
    const countryOptions=countries.map(c=>`<option value="${c[0]}">${EVRUNI_LANG==='ru'?c[1]:c[2]} — ${c[3]} ${c[4]}</option>`).join('');
    const configured=d.method_configs||{};
    const renderMethods=()=>{
      const raw=configured[$('#billingCountry')?.value]||configured.OTHER||[];
      const list=raw.filter(m=>m&&m.enabled).map(m=>[m.type,m.type==='sbp'?'◈':m.type==='crypto'?'₿':'💳',m.name_ru||m.type,m.name_en||m.type]);
      const finalList=list.length?list:[['card','💳','Карта','Card']];
      $('#billingMethods').innerHTML=finalList.map((m,i)=>`<button type="button" class="payment-method-btn ${i===0?'active':''}" data-method="${m[0]}"><span>${m[1]}</span><b>${EVRUNI_LANG==='ru'?m[2]:m[3]}</b></button>`).join('');
      $$('.payment-method-btn').forEach(b=>b.onclick=()=>{$$('.payment-method-btn').forEach(x=>x.classList.remove('active'));b.classList.add('active')});
    };
    const selectedTopupCurrency=()=>getTopupCurrency($('#billingCountry')?.value||'OTHER');
    const syncTopupCurrency=()=>{
      const c=selectedTopupCurrency();
      const amountLabel=$('#billingAmountCurrency');
      const preview=$('#billingAmountPreview');
      if(amountLabel) amountLabel.textContent=`${c.code} ${c.symbol}`;
      if(preview){
        const amount=Math.max(0,Number($('#billingAmount')?.value||0));
        preview.textContent=`${amount.toFixed(2)} ${c.code}`;
      }
      const hint=$('#billingCurrencyHint');
      if(hint) hint.textContent=EVRUNI_LANG==='ru'?`Валюта оплаты: ${c.code} ${c.symbol}`:`Payment currency: ${c.code} ${c.symbol}`;
    };
    $('#view').innerHTML=shell(tr('billing_title'),tr('billing_sub'),`
      <div class="billing-country">
        <div><b>${tr('billing_country_title')}</b><small>${tr('billing_country_sub')}</small></div>
        <select id="billingCountry" class="billing-country-select">${countryOptions}</select>
      </div>
      <section class="billing-topup-card">
        <div><b>${tr('billing_amount_title')}</b><small id="billingCurrencyHint">${tr('billing_amount_sub')}</small></div>
        <div class="billing-amount-row"><input id="billingAmount" type="number" min="1" max="1000000" step="0.01" value="100" inputmode="decimal"><span id="billingAmountCurrency">EUR €</span></div>
        <div class="billing-price-preview"><span>${tr('billing_total')}</span><strong id="billingAmountPreview">—</strong></div>
      </section>
      <section class="billing-method-card">
        <div><b>${tr('billing_method_title')}</b><small>${tr('billing_method_sub')}</small></div>
        <div id="billingMethods" class="billing-methods"></div>
      </section>
      <div class="billing-action"><button class="primary" id="topupBalanceBtn">${tr('billing_topup')}</button></div>
      <div class="info-box" style="margin:0 26px 26px">${tr('billing_info')}</div>`);
    const preview=()=>{
      const c=selectedTopupCurrency();
      const amount=Math.max(0.01,Number($('#billingAmount')?.value||0));
      $('#billingAmountPreview').textContent=`${amount.toFixed(2)} ${c.code}`;
    };
    $('#billingCountry').onchange=()=>{renderMethods();syncTopupCurrency();};
    $('#billingAmount').oninput=preview;
    renderMethods();syncTopupCurrency();preview();
    $('#topupBalanceBtn').onclick=async()=>{
      const amount=Number($('#billingAmount')?.value||0);
      if(!amount||amount<0.01){toast(tr('billing_amount_invalid'),tr('billing_amount_sub'),'error');return}
      const country=$('#billingCountry')?.value||'OTHER';
      const currency=selectedTopupCurrency().code;
      const method=$('.payment-method-btn.active')?.dataset.method||'card';
      const btn=$('#topupBalanceBtn');setBusy(btn,true,'Opening…');
      try{
        const fd=new FormData();fd.append('amount',String(amount));fd.append('country',country);fd.append('currency',currency);fd.append('method',method);fd.append('csrf',window.EVRUNI.csrf);
        const r=await api('/api/billing.php',{method:'POST',body:fd});if(r.url)location.href=r.url;
      }catch(e){toast(tr('payment_not_ready'),e.message,'error')}finally{setBusy(btn,false)}
    };
  }catch(e){$('#view').innerHTML=shell(tr('billing_title'),tr('billing_sub'),`<div class="info-box" style="margin:0 26px">${tr('billing_load_error')}: ${esc(e.message)}</div>`)}
}
/* Evruni i18n */
const EVRUNI_I18N={
  ru:{
    profile:'Профиль',account:'АККАУНТ',personal:'ЛИЧНЫЕ ДАННЫЕ',preferences:'НАСТРОЙКИ',
    details:'Данные профиля',workspace:'Настройки рабочей области',name:'Имя',email:'Email',role:'Роль',
    language:'Язык',currency:'Валюта баланса',save:'Сохранить изменения',billing:'Баланс и пополнение',
    logout:'Выйти',balance:'Баланс',converted:'Баланс отображается в выбранной валюте',saved:'Сохранено',
    updated:'Настройки профиля обновлены',saveError:'Не удалось сохранить',profileError:'Не удалось загрузить профиль',
    nav_chat:'AI Chat',nav_image:'Изображение',days_short:'дн.',credits_spent:'Потрачено',ai_operations:'AI-операции',api_requests:'API-запросы',usage_daily:'Расход по дням',usage_models:'Расход по моделям',usage_projects:'Расход по проектам',usage_recent:'Последние операции',operations:'операций',no_project:'Без проекта',nav_video:'Видео',nav_trendlab:'Trend Lab',nav_audio:'Аудио',nav_research:'Исследование',
    nav_documents:'Документы',nav_models:'Модели',nav_presets:'Пресеты',nav_api:'API-ключи',nav_bonuses:'Бонусы',
    nav_sandbox:'Песочница',nav_projects:'Проекты',nav_usage:'Использование',nav_support:'Поддержка',nav_billing:'Баланс',nav_admin:'Админ',nav_logout:'Выйти',profile_menu:'Профиль',profile_balance:'Баланс',profile_settings:'Настройки',profile_billing:'Баланс и пополнение',profile_admin:'Админ-панель',profile_logout:'Выйти',profile_credits:'',bonuses_title:'Бонусы',bonuses_sub:'Приглашайте пользователей, получайте процент с их пополнений и выполняйте задания.',loading:'Загрузка…',no_referrals:'Пока никого. Поделитесь ссылкой, чтобы приглашённые появились здесь.',no_tasks:'Пока нет доступных заданий.',earn_referrals:'Зарабатывайте на рефералах',share_text:'Отправьте свою ссылку друзьям. Вы получаете процент с каждого их пополнения.',copy:'Копировать',copied:'Ссылка скопирована',copy_manual:'Скопируйте ссылку вручную',invited:'Приглашено',clicks:'Переходы',earned:'Заработано',commission:'Процент',step1_title:'Поделитесь ссылкой',step1_text:'Отправьте её коллегам и друзьям',step2_title:'Они регистрируются',step2_text:'Аккаунт создаётся по вашей ссылке',step3_title:'Вы получаете процент',step3_text:'С каждого их пополнения',invited_title:'Приглашённые',tasks_title:'Задания за награду',task_link:'Перейти',task_manual:'Задание',open_task:'Открыть',submit_task:'Отправить на проверку',completed:'Выполнено',pending_review:'На проверке',proof_prompt:'Добавьте подтверждение выполнения:',submitted:'Отправлено на проверку',task_error:'Не удалось выполнить действие',
    chat_title:'Чат с ИИ',chat_sub:'MULTI-MODEL CHAT',choose_ai:'Выбор ИИ',new_chat:'＋ Новый чат',chat_placeholder:'Напишите сообщение… Enter — отправить, Shift+Enter — новая строка',chat_search:'Поиск чатов…',all_projects:'Все проекты',rename_chat_prompt:'Новое название чата',projects_title:'Проекты',projects_sub:'AI PROJECTS',project_search:'Поиск проектов…',project_new:'Новый проект',project_name_prompt:'Название проекта',project_created:'Проект создан',project_empty:'Проектов пока нет',project_no_description:'Без описания',project_chats:'чатов',open:'Открыть',add_file:'Добавить файл',no_files:'Файлов пока нет',file_added:'Файл добавлен',file_error:'Ошибка файла',delete_file_prompt:'Удалить файл?',loading:'Загрузка…',
    attach:'＋ Прикрепить',send:'Отправить ✦',empty_chat:'Спросите Evruni о чём угодно.',empty_chat_hint:'Выберите модель и начните новый диалог.',
    image_title:'Генерация изображений',image_sub:'GENERATE & EDIT',image_placeholder:'Опишите изображение…',generate:'Сгенерировать ✦',
    image_info:'Генерация идёт напрямую через настроенный provider.',video_title:'Генерация видео',video_sub:'ASYNC GENERATION',
    video_placeholder:'Опишите сцену и движение…',create_video:'Создать видео ✦',video_info:'Задача будет создана в Evruni. Фактический рендер зависит от подключённого video provider.',
    audio_title:'Аудио',audio_sub:'РЕЧЬ · ТРАНСКРИБАЦИЯ',tts_title:'Озвучка текста',tts_placeholder:'Введите текст для озвучки…',
    generate_voice:'Создать озвучку',audio_ready:'Готовое аудио появится здесь.',stt_title:'Расшифровка аудио',transcribe:'Расшифровать аудио',
    transcript_ready:'Текст расшифровки появится здесь.',research_title:'Исследование',research_sub:'LONG-FORM AI',
    research_placeholder:'Что нужно исследовать?',research_button:'Исследовать ✦',research_ready:'Результат исследования появится здесь.',
    docs_title:'Knowledge Base',docs_sub:'FILES · RAG',upload_document:'Загрузить документ',choose_file:'Выбрать файл',file_not_selected:'Файл не выбран',choose_audio_file:'Выбрать аудиофайл',audio_file_not_selected:'Аудиофайл не выбран',docs_types:'TXT, MD, CSV, JSON, PDF · до 25 МБ',upload:'Загрузить',document_uploaded:'Документ загружен',upload_failed:'Загрузка не удалась',doc_needed:'Нужен документ',documents_load_failed:'Документы не загрузились',understand_this:'Разобраться с этим',understanding_document:'Evruni изучает документ…',analyzing:'Анализ…',analysis_ready:'Разбор готов',analysis_failed:'Не удалось разобрать документ',open_in_chat:'Открыть в чате',
    no_documents:'Документов пока нет.',models_title:'Каталог моделей',models_sub:'PROVIDERS · PRICING',search_models:'Поиск моделей…',
    all_types:'Все типы',ready:'готов',not_configured:'не настроен',presets_title:'Пресеты ИИ',presets_sub:'REUSABLE CONFIGURATIONS',
    preset_name:'Название пресета',system_prompt:'Системный промпт',save_preset:'Сохранить пресет',no_presets:'Пресетов пока нет.',
    sandbox_title:'Песочница API',sandbox_sub:'REQUEST BUILDER',write_prompt:'Введите запрос…',run_request:'Выполнить запрос',
    copy_curl:'Копировать cURL',response_here:'Ответ появится здесь.',mask_title:'Masking rules',mask_sub:'PRIVACY BEFORE AI',
    rule_name:'Название правила',regex:'Regex, например /\b\d{16}\b/g',replacement:'Замена',add_rule:'Добавить правило',
    no_rules:'Правил маскировки пока нет.',api_title:'Developer API',api_sub:'OPENAI-COMPATIBLE',key_name:'Название ключа',
    budget:'Бюджет (необязательно)',create_key:'Создать ключ',api_warning:'API-ключ показывается один раз. Сохраните секрет сразу. Можно задать бюджет и срок действия.',
    no_keys:'API-ключей пока нет.',usage_title:'Использование и аналитика',usage_sub:'API ACTIVITY',requests:'Запросы',input_tokens:'Входные токены',
    output_tokens:'Выходные токены',provider_cost:'Стоимость provider',no_usage:'Данных об использовании пока нет.',
    billing_title:'Пополнение',billing_sub:'ПОПОЛНЕНИЕ · ВЫБОР СПОСОБА ОПЛАТЫ',billing_amount_title:'Сумма пополнения',billing_amount_sub:'Укажите сумму пополнения в валюте баланса.',billing_total:'Итого',billing_method_title:'Способ оплаты',billing_method_sub:'Доступные способы зависят от выбранной страны.',billing_topup:'Пополнить баланс',billing_amount_invalid:'Введите сумму пополнения',billing_info:'Выберите страну оплаты, сумму пополнения и доступный способ оплаты.',billing_country_title:'Страна оплаты',billing_country_sub:'Выберите страну, из которой оформляется платёж.',credits_label:'валюта',payment_not_ready:'Оплата пока не настроена',billing_load_error:'Не удалось загрузить форму пополнения'
  },
  en:{
    profile:'Profile',account:'ACCOUNT',personal:'PERSONAL DATA',preferences:'SETTINGS',details:'Profile details',workspace:'Workspace settings',
    name:'Name',email:'Email',role:'Role',language:'Language',currency:'Balance currency',save:'Сохранить изменения',billing:'Billing & balance',
    logout:'Log out',balance:'Balance',converted:'Balance is displayed in the selected currency',saved:'Saved',updated:'Profile settings updated',
    saveError:'Could not save',profileError:'Could not load profile',
    nav_chat:'AI Chat',nav_image:'Image',days_short:'days',credits_spent:'Spent',ai_operations:'AI operations',api_requests:'API requests',usage_daily:'Daily usage',usage_models:'Usage by model',usage_projects:'Usage by project',usage_recent:'Recent operations',operations:'operations',no_project:'No project',nav_video:'Video',nav_trendlab:'Trend Lab',nav_audio:'Audio',nav_research:'Research',nav_documents:'Documents',
    nav_models:'Models',nav_presets:'Presets',nav_api:'API Keys',nav_bonuses:'Bonuses',nav_sandbox:'Sandbox',nav_projects:'Projects',nav_usage:'Usage',nav_support:'Support',nav_billing:'Billing',
    nav_admin:'Admin',profile_menu:'Profile',profile_balance:'Balance',profile_settings:'Settings',profile_billing:'Billing & balance',profile_usage:'Usage',profile_admin:'Admin panel',profile_logout:'Log out',profile_credits:'',bonuses_title:'Bonuses',bonuses_sub:'Invite users, earn a percentage of their top-ups, and complete bonus tasks.',loading:'Loading…',no_referrals:'No one yet. Share your link to see invited users here.',no_tasks:'No bonus tasks yet.',earn_referrals:'Earn with referrals',share_text:'Send your link to friends. You earn a percentage of every top-up they make.',copy:'Copy',copied:'Link copied',copy_manual:'Copy the link manually',invited:'Invited',clicks:'Clicks',earned:'Earned',commission:'Rate',step1_title:'Share your link',step1_text:'Send it to colleagues and friends',step2_title:'They register',step2_text:'Their account is created through your link',step3_title:'You earn a percentage',step3_text:'From every top-up they make',invited_title:'Invited users',tasks_title:'Bonus tasks',task_link:'Open',task_manual:'Task',open_task:'Open',submit_task:'Submit for review',completed:'Completed',pending_review:'Under review',proof_prompt:'Add proof of completion:',submitted:'Submitted for review',task_error:'Action failed',nav_logout:'Logout',chat_title:'Чат с ИИ',chat_sub:'MULTI-MODEL CHAT',choose_ai:'Choose AI',new_chat:'＋ New chat',
    chat_placeholder:'Write a message… Enter — send, Shift+Enter — new line',chat_search:'Search chats…',all_projects:'All projects',rename_chat_prompt:'New chat name',projects_title:'Projects',projects_sub:'AI PROJECTS',project_search:'Search projects…',project_new:'New project',project_name_prompt:'Project name',project_created:'Project created',project_empty:'No projects yet',project_no_description:'No description',project_chats:'chats',open:'Open',add_file:'Add file',no_files:'No files yet',file_added:'File added',file_error:'File error',delete_file_prompt:'Delete file?',loading:'Loading…',attach:'＋ Attach',send:'Send ✦',
    empty_chat:'Ask Evruni anything.',empty_chat_hint:'Choose a model and start a new dialog.',image_title:'Генерация изображений',image_sub:'GENERATE & EDIT',
    image_placeholder:'Describe the image…',generate:'Generate ✦',image_info:'Generation runs directly through the configured provider.',
    video_title:'Генерация видео',video_sub:'ASYNC GENERATION',video_placeholder:'Describe the scene and motion…',create_video:'Create video ✦',
    video_info:'A task will be created in Evruni. Actual rendering depends on the connected video provider.',audio_title:'Audio Lab',
    audio_sub:'SPEECH · TRANSCRIPTION',tts_title:'Text → Speech',tts_placeholder:'Text to synthesize…',generate_voice:'Generate voice',
    audio_ready:'Generated audio will appear here.',stt_title:'Speech → Text',transcribe:'Transcribe',transcript_ready:'Transcript will appear here.',
    research_title:'Исследование',research_sub:'LONG-FORM AI',research_placeholder:'What should we research?',research_button:'Research ✦',
    research_ready:'Research results will appear here.',docs_title:'Knowledge Base',docs_sub:'FILES · RAG',upload_document:'Upload a document',choose_audio_file:'Choose audio file',audio_file_not_selected:'No audio file selected',
    docs_types:'TXT, MD, CSV, JSON, PDF · up to 25 MB',upload:'Upload',document_uploaded:'Document uploaded',upload_failed:'Upload failed',doc_needed:'A document is required',documents_load_failed:'Documents could not be loaded',understand_this:'Understand this',understanding_document:'Evruni is reading the document…',analyzing:'Analyzing…',analysis_ready:'Analysis ready',analysis_failed:'Could not analyze the document',open_in_chat:'Open in chat',no_documents:'No documents yet.',models_title:'Каталог моделей',
    models_sub:'PROVIDERS · PRICING',search_models:'Search models…',all_types:'All types',ready:'ready',not_configured:'not configured',
    presets_title:'Пресеты ИИ',presets_sub:'REUSABLE CONFIGURATIONS',preset_name:'Preset name',system_prompt:'System prompt',
    save_preset:'Save preset',no_presets:'No presets yet.',sandbox_title:'Песочница API',sandbox_sub:'REQUEST BUILDER',write_prompt:'Write a prompt…',
    run_request:'Run request',copy_curl:'Copy cURL',response_here:'Response will appear here.',mask_title:'Masking rules',
    mask_sub:'PRIVACY BEFORE AI',rule_name:'Rule name',regex:'Regex, e.g. /\b\d{16}\b/g',replacement:'Replacement',
    add_rule:'Add rule',no_rules:'No masking rules.',api_title:'Developer API',api_sub:'OPENAI-COMPATIBLE',key_name:'Key name',
    budget:'Budget (optional)',create_key:'Create key',api_warning:'API-ключ показывается один раз. Сохраните секрет сразу. Можно задать бюджет и срок действия.',
    no_keys:'No API keys.',usage_title:'Использование и аналитика',usage_sub:'API ACTIVITY',requests:'Requests',input_tokens:'Input tokens',
    output_tokens:'Output tokens',provider_cost:'Provider cost',no_usage:'No usage yet.',billing_title:'Top up balance',billing_sub:'TOP UP · CHOOSE PAYMENT METHOD',billing_amount_title:'Top-up amount',billing_amount_sub:'Enter the top-up amount in your balance currency.',billing_total:'Total',billing_method_title:'Payment method',billing_method_sub:'Available methods depend on the selected country.',billing_topup:'Top up balance',billing_amount_invalid:'Enter a top-up amount',billing_info:'Choose your payment country, top-up amount, and available payment method.',billing_country_title:'Payment country',billing_country_sub:'Choose the country where the payment is being made.',credits_label:'currency',payment_not_ready:'Payment is not configured yet',billing_load_error:'Could not load the top-up form'
  }
};
function tr(key){const lang=(window.EVRUNI_LANG||'ru');return (EVRUNI_I18N[lang]||EVRUNI_I18N.ru)[key]||key;}

async function loadDevelopmentNotice(){
  const box=document.getElementById('devNotice');
  if(!box) return;
  try{
    const r=await fetch('/api/data.php?type=notice',{credentials:'same-origin',cache:'no-store'});
    if(!r.ok) return;
    const d=await r.json(), n=d.notice||d;
    if(n.enabled===false){box.hidden=true;return;}
    box.hidden=false;
    const lang=(window.EVRUNI_LANG||'ru')==='en'?'en':'ru';
    const title=box.querySelector('.dev-notice-copy strong');
    const text=box.querySelector('.dev-notice-copy span');
    if(title) title.textContent=n['title_'+lang]||title.textContent;
    if(text) text.textContent=n['text_'+lang]||text.textContent;
  }catch(_){}
}

function applyLanguage(lang){
  window.EVRUNI_LANG=lang==='en'?'en':'ru';
  if(window.__evruniLastAppliedLang===window.EVRUNI_LANG)return;
  window.__evruniLastAppliedLang=window.EVRUNI_LANG;
  const dict=EVRUNI_I18N[window.EVRUNI_LANG]||EVRUNI_I18N.ru;
  document.querySelectorAll('[data-i18n]').forEach(el=>{
    const key=el.getAttribute('data-i18n');
    if(key && dict[key]) el.textContent=dict[key];
  });
  document.documentElement.lang=window.EVRUNI_LANG;
}


function initLanguage(lang){applyLanguage(lang);}

async function renderProfile(){
  $('#viewTitle').textContent=tr('profile');
  try{
    const r=await api('/api/profile.php');
    const u=r.user||{}, cur=u.currency||'EUR', lang=u.language||'ru';
    const roleLabels={ai_user:'Пользователь ИИ',ai_creator:'Создатель ИИ',ai_developer:'Разработчик ИИ',ai_researcher:'Исследователь ИИ',ai_business:'Бизнес-пользователь',ai_partner:'Партнёр Evruni',ai_moderator:'Модератор ИИ',ai_admin:'Администратор Evruni'};
    const roleLabel=roleLabels[u.user_category]||((u.role||'user')==='admin'?'Администратор Evruni':'Пользователь ИИ');
    window.EVRUNI_LANG=lang;
    applyLanguage(lang);
    const sym={EUR:'€',USD:'$',GBP:'£',RUB:'₽'}[cur]||cur;
    $('#view').innerHTML=`
      <section class="profile-page">
        <div class="profile-hero">
          <div class="profile-avatar-large">${esc((u.name||u.email||'U').slice(0,1).toUpperCase())}</div>
          <div class="profile-hero-copy">
            <span class="eyebrow">${tr('account')}</span>
            <h2>${esc(u.name||'User')}</h2>
            <p>${esc(u.email||'')}</p>
          </div>
          <div class="profile-balance-card">
            <span>${tr('balance')}</span>
            <strong>${Number(u.balance||0).toFixed(2)} ${sym}</strong>
            <small>${tr('converted')} · ${esc(cur)}</small>
          </div>
        </div>
        <div class="profile-grid">
          <div class="profile-card">
            <div class="profile-card-head"><div><span class="eyebrow">${tr('personal')}</span><h3>${tr('details')}</h3></div></div>
            <label class="profile-field"><span>${tr('name')}</span><input id="profileName" value="${esc(u.name||'')}" maxlength="80"></label>
            <label class="profile-field"><span>${tr('email')}</span><input value="${esc(u.email||'')}" disabled></label>
            <label class="profile-field"><span>${tr('role')}</span><input value="${esc(roleLabel)}" disabled></label>
          </div>
          <div class="profile-card">
            <div class="profile-card-head"><div><span class="eyebrow">${tr('preferences')}</span><h3>${tr('workspace')}</h3></div></div>
            <label class="profile-field"><span>${tr('language')}</span>
              <select id="profileLanguage">
                <option value="ru" ${lang==='ru'?'selected':''}>Русский</option>
                <option value="en" ${lang==='en'?'selected':''}>English</option>
              </select>
            </label>
            <label class="profile-field"><span>${tr('currency')}</span>
              <select id="profileCurrency">
                <option value="EUR" ${cur==='EUR'?'selected':''}>EUR — €</option>
                <option value="USD" ${cur==='USD'?'selected':''}>USD — $</option>
                <option value="GBP" ${cur==='GBP'?'selected':''}>GBP — £</option>
                <option value="RUB" ${cur==='RUB'?'selected':''}>RUB — ₽</option>
              </select>
            </label>
            <p class="profile-hint">${tr('converted')}</p>
          </div>
        </div>
        <div class="profile-card profile-security-card">
          <div class="profile-card-head"><div><span class="eyebrow">${tr('account')}</span><h3>${tr('profile')}</h3></div></div>
          <div class="profile-actions">
            <button type="button" class="profile-action-btn" id="profileSave">${tr('save')}</button>
            <button type="button" class="profile-action-btn secondary" id="profileBilling">${tr('billing')}</button>
            <button type="button" class="profile-action-btn danger" id="profileLogout">${tr('logout')}</button>
          </div>
        </div>
      </section>`;
    $('#profileLanguage').onchange=async()=>{
      const newLang=$('#profileLanguage').value;
      const fd=new FormData();
      fd.append('name',$('#profileName').value.trim());
      fd.append('language',newLang);
      fd.append('currency',$('#profileCurrency').value);
      fd.append('csrf',window.EVRUNI.csrf);
      try{
        await api('/api/profile.php',{method:'POST',body:fd});
        window.EVRUNI_LANG=newLang;
        applyLanguage(newLang);
        await renderProfile();
        if(typeof toast==='function') toast(tr('saved'),tr('updated'),'success');
      }catch(e){toast(tr('saveError'),e.message,'error')}
    };
    $('#profileSave').onclick=async()=>{
      const fd=new FormData();
      fd.append('name',$('#profileName').value.trim());
      fd.append('language',$('#profileLanguage').value);
      fd.append('currency',$('#profileCurrency').value);
      fd.append('csrf',window.EVRUNI.csrf);
      setBusy($('#profileSave'),true,lang==='en'?'Saving…':'Сохранение…');
      try{
        await api('/api/profile.php',{method:'POST',body:fd});
        window.EVRUNI_LANG=$('#profileLanguage').value; applyLanguage(window.EVRUNI_LANG);
        toast(tr('saved'),tr('updated'),'success');
        setTimeout(()=>location.reload(),250);
      }catch(e){toast(tr('saveError'),e.message,'error')}
      finally{setBusy($('#profileSave'),false)}
    };
    $('#profileCurrency').onchange=async()=>{
      const fd=new FormData();
      fd.append('name',$('#profileName').value.trim());
      fd.append('language',$('#profileLanguage').value);
      fd.append('currency',$('#profileCurrency').value);
      fd.append('csrf',window.EVRUNI.csrf);
      try{
        await api('/api/profile.php',{method:'POST',body:fd});
        await refreshModelsForCurrency();
        await renderProfile();
        toast(tr('saved'),tr('updated'),'success');
      }catch(e){toast('Error',e.message,'error')}
    };
    $('#profileBilling').onclick=()=>views.billing&&views.billing();
    $('#profileLogout').onclick=(e)=>{e.preventDefault();e.stopPropagation();window.location.replace('/api/auth.php?action=logout');};
  }catch(e){
    $('#view').innerHTML=`<div class="info-box" style="margin:26px">${tr('profileError')}: ${esc(e.message)}</div>`;
  }
}

async function openProjectConfig(pid){
  try{
    const d=await api('/api/projects.php?id='+pid),p=d.project||{},presets=d.presets||[];
    let modal=document.querySelector('.project-config-modal');
    if(!modal){modal=document.createElement('div');modal.className='project-config-modal';document.body.appendChild(modal)}
    modal.innerHTML=`<div class="project-config-backdrop"></div><div class="project-config-card">
      <div class="project-config-head"><div><div class="eyebrow">ИНТЕЛЛЕКТ ПРОЕКТА</div><h3>${esc(p.name)}</h3></div><button class="ghost project-config-close">×</button></div>
      <label>Описание проекта<textarea id="pcDescription" class="big-input" style="min-height:72px">${esc(p.description||'')}</textarea></label>
      <label>Инструкции проекта<textarea id="pcInstructions" class="big-input">${esc(p.instructions||'')}</textarea></label>
      <label>Память проекта<textarea id="pcMemory" class="big-input">${esc(p.memory||'')}</textarea></label>
      <div class="project-config-row"><label>Модель AI по умолчанию<select id="pcModel">${modelOptions('text')}</select></label><label>Пресет по умолчанию<select id="pcPreset"><option value="">Нет</option>${presets.map(x=>`<option value="${Number(x.id)}">${esc(x.name)}</option>`).join('')}</select></label></div>
      <div class="project-knowledge"><div><b>База знаний</b><small>${(d.files||[]).length} файлов доступно AI проекта</small></div><span>● АКТИВНА</span></div>
      <div class="project-config-foot"><span class="muted">Контекст проекта автоматически добавляется к новым запросам AI.</span><button class="primary" id="pcSave">Сохранить изменения</button></div>
    </div>`;
    modal.classList.add('show');
    $('#pcModel').value=p.default_model_slug||'';
    $('#pcPreset').value=p.default_preset_id||'';
    const close=()=>modal.classList.remove('show');
    modal.querySelector('.project-config-close').onclick=close;modal.querySelector('.project-config-backdrop').onclick=close;
    $('#pcSave').onclick=async()=>{const b=$('#pcSave');setBusy(b,true,'Saving…');try{
      await post('/api/projects.php?id='+pid,{name:p.name,description:$('#pcDescription').value.trim(),color:p.color||'purple',instructions:$('#pcInstructions').value,memory:$('#pcMemory').value,default_model_slug:$('#pcModel').value,default_preset_id:Number($('#pcPreset').value||0)});
      toast('Проект обновлён','Настройки контекста сохранены','success');close();renderProjects();
    }catch(e){toast('Не удалось обновить проект',e.message,'error')}finally{setBusy(b,false)}};
  }catch(e){toast('Project',e.message,'error')}
}
async function renderProjects(){
  $('#view').innerHTML=shell(tr('projects_title'),tr('projects_sub'),`
    <div class="projects-toolbar">
      <input id="projectSearch" class="thread-search" placeholder="${tr('project_search')}">
      <button class="primary" id="projectNew">＋ ${tr('project_new')}</button>
    </div>
    <div id="projectGrid" class="project-grid"><div class="muted">${tr('loading')}</div></div>`);
  const grid=$('#projectGrid');
  const load=async()=>{
    try{
      const d=await api('/api/projects.php');
      const projects=d.projects||[];
      grid.innerHTML=projects.map(p=>`
        <article class="project-card" data-name="${esc(p.name)}">
          <div class="project-card-top">
            <span class="project-dot"></span>
            <div class="project-card-actions"><button class="project-open" data-id="${Number(p.id)}">${tr('open')||'Open'}</button><button class="ghost project-config" data-id="${Number(p.id)}">⚙</button></div>
          </div>
          <h3>${esc(p.name)}</h3>
          <p>${esc(p.description||tr('project_no_description'))}</p>
          <small>${Number(p.thread_count||0)} ${tr('project_chats')}</small>
          <div class="project-files-mini" data-project="${Number(p.id)}"><span class="muted">${tr('loading')}</span></div>
          <div class="project-file-actions">
            <button class="ghost project-files-btn" data-id="${Number(p.id)}">+ ${tr('add_file')}</button>
            <input class="project-file-input" data-id="${Number(p.id)}" type="file" accept=".txt,.csv,.json,.pdf,.docx" hidden multiple>
          </div>
        </article>`).join('') || `<div class="muted">${tr('project_empty')}</div>`;
      for(const p of projects){
        try{
          const d=await api('/api/projects.php?id='+Number(p.id));
          const box=document.querySelector('.project-files-mini[data-project="'+Number(p.id)+'"]');
          if(box){
            box.innerHTML=(d.files||[]).map(f=>`<div class="project-file-row"><span>▣</span><span title="${esc(f.name)}">${esc(f.name)}</span><button class="project-file-delete" data-id="${Number(f.id)}" data-project="${Number(p.id)}">×</button></div>`).join('')||`<span class="muted">${tr('no_files')}</span>`;
          }
        }catch(_){}
      }
      $$('.project-open').forEach(btn=>btn.onclick=()=>{
        const pid=Number(btn.dataset.id||0);window.__evruniProjectId=pid;
        const chatNav=document.querySelector('.nav[data-view="chat"]');if(chatNav)chatNav.click();
      });
      $$('.project-config').forEach(btn=>btn.onclick=()=>openProjectConfig(Number(btn.dataset.id||0)));
      $$('.project-files-btn').forEach(btn=>{
        btn.onclick=()=>document.querySelector('.project-file-input[data-id="'+btn.dataset.id+'"]')?.click();
      });
      $$('.project-file-input').forEach(input=>{
        input.onchange=async()=>{
          const files=[...input.files||[]];if(!files.length)return;
          for(const f of files){
            const fd=new FormData();fd.append('file',f);fd.append('project_id',input.dataset.id);fd.append('csrf',window.EVRUNI.csrf);
            try{await api('/api/documents.php',{method:'POST',body:fd});toast(tr('file_added'),f.name,'success')}
            catch(e){toast(tr('file_error'),e.message,'error')}
          }
          input.value='';load();
        };
      });
      $$('.project-file-delete').forEach(btn=>btn.onclick=async()=>{
        if(!confirm(tr('delete_file_prompt')))return;
        try{await api('/api/documents.php?id='+Number(btn.dataset.id),{method:'DELETE'});load()}
        catch(e){toast(tr('file_error'),e.message,'error')}
      });
    }catch(e){grid.innerHTML=`<div class="info-box">${esc(e.message||'Projects error')}</div>`}
  };
  $('#projectNew').onclick=async()=>{
    const name=prompt(tr('project_name_prompt'));if(!name||!name.trim())return;
    const description=prompt('Описание проекта')||'';
    try{await post('/api/projects.php',{name:name.trim(),description:description.trim()});toast(tr('project_created'),'','success');load()}
    catch(e){toast('Projects',e.message,'error')}
  };
  $('#projectSearch').oninput=()=>{
    const q=$('#projectSearch').value.trim().toLowerCase();
    $$('.project-card').forEach(card=>card.style.display=card.textContent.toLowerCase().includes(q)?'':'none');
  };
  await load();
}

async function renderBonuses(){
  $('#viewTitle').textContent=tr('nav_bonuses');
  $('#view').innerHTML=`<section class="view-card bonuses-view"><div class="view-top"><div><div class="eyebrow">EVRUNI BONUS PROGRAM</div><h2>${tr('bonuses_title')}</h2><p class="view-sub">${tr('bonuses_sub')}</p></div></div><div id="bonusesContent"><div class="info-box">${tr('loading')}</div></div></section>`;
  try{
    const d=await api('/api/referrals.php'); const sym=esc(d.currency_symbol||'€');
    const rows=(d.referrals||[]).map(x=>`<div class="bonus-row"><div><b>${esc(x.name||x.email)}</b><small>${esc(x.email)} · ${esc(x.created_at)}</small></div><strong>${money(x.earned)} ${sym}</strong></div>`).join('')||`<div class="muted">${tr('no_referrals')}</div>`;
    const tasks=(d.tasks||[]).map(t=>{const sub=t.submission||null;const status=sub?.status||'';return `<article class="bonus-task"><div class="bonus-task-icon">${t.task_type==='link'?'↗':'✦'}</div><div class="bonus-task-main"><div class="bonus-task-top"><span class="badge">${t.task_type==='link'?tr('task_link'):tr('task_manual')}</span><span class="bonus-reward">+${money(t.reward)} ${sym}</span></div><h3>${esc(window.EVRUNI_LANG==='en'&&t.title_en?t.title_en:t.title_ru)}</h3><p>${esc(window.EVRUNI_LANG==='en'&&t.description_en?t.description_en:t.description_ru)}</p><div class="bonus-task-actions">${t.target_url?`<button class="ghost task-open" data-task="${t.id}">${tr('open_task')}</button>`:''}${status==='approved'?`<span class="task-status good">✓ ${tr('completed')}</span>`:status==='pending'?`<span class="task-status">${tr('pending_review')}</span>`:`<button class="primary task-submit" data-task="${t.id}">${tr('submit_task')}</button>`}</div></div></article>`}).join('')||`<div class="muted">${tr('no_tasks')}</div>`;
    $('#bonusesContent').innerHTML=`<div class="bonus-hero"><div class="bonus-hero-copy"><div class="eyebrow">BONUSES</div><h3>${tr('earn_referrals')}</h3><p>${tr('share_text')}</p><div class="bonus-link-row"><input id="referralLink" readonly value="${esc(d.link)}"><button class="primary" id="copyReferral">${tr('copy')}</button></div></div><div class="bonus-stats"><div><b>${d.referrals}</b><span>${tr('invited')}</span></div><div><b>${d.clicks}</b><span>${tr('clicks')}</span></div><div><b>${money(d.earned)} ${sym}</b><span>${tr('earned')}</span></div><div><b>${Number(d.commission_percent).toFixed(0)}%</b><span>${tr('commission')}</span></div></div></div><div class="bonus-steps"><div><b>1</b><span><strong>${tr('step1_title')}</strong><small>${tr('step1_text')}</small></span></div><div><b>2</b><span><strong>${tr('step2_title')}</strong><small>${tr('step2_text')}</small></span></div><div><b>3</b><span><strong>${tr('step3_title')}</strong><small>${tr('step3_text')}</small></span></div></div><div class="bonus-grid"><div class="bonus-panel"><h3>${tr('invited_title')}</h3>${rows}</div><div class="bonus-panel"><h3>${tr('tasks_title')}</h3><div class="bonus-task-list">${tasks}</div></div></div>`;
    $('#copyReferral').onclick=async()=>{try{await navigator.clipboard.writeText(d.link);toast(tr('copied'),'','success')}catch(_){$('#referralLink').select();toast(tr('copy_manual'),d.link,'info')}};
    $$('.task-open').forEach(b=>b.onclick=async()=>{try{const r=await post('/api/referrals.php',{action:'task_click',task_id:b.dataset.task});if(r.url)window.open(r.url,'_blank','noopener,noreferrer')}catch(e){toast(tr('task_error'),e.message,'error')}});
    $$('.task-submit').forEach(b=>b.onclick=async()=>{const proof=prompt(tr('proof_prompt'),'');if(proof===null)return;try{await post('/api/referrals.php',{action:'submit_task',task_id:b.dataset.task,proof_text:proof});toast(tr('submitted'),'','success');renderBonuses()}catch(e){toast(tr('task_error'),e.message,'error')}});
  }catch(e){$('#bonusesContent').innerHTML=`<div class="info-box">${esc(e.message||'Bonuses unavailable')}</div>`}
}

const views={chat:renderChat,projects:renderProjects,image:renderImage,video:renderVideo,trendlab:renderTrendLab,audio:renderAudio,research:renderResearch,documents:renderDocuments,models:renderModels,presets:renderPresets,api:renderApi,sandbox:renderSandbox,usage:renderUsage,support:renderSupport,billing:renderBilling,profile:renderProfile,bonuses:renderBonuses};
$$('.nav').forEach(b=>b.onclick=async()=>{$$('.nav').forEach(x=>x.classList.remove('active'));b.classList.add('active');await views[b.dataset.view]()});
/* Evruni V9.38 support ticket notifications — unread is based on last admin message */
window.EvruniSupportNotifications=(function(){
  const KEY='evruni_support_unread_v3';
  const READ='evruni_support_read_v3';
  let timer=null;

  function read(key){try{return JSON.parse(localStorage.getItem(key)||'{}')||{}}catch(_){return {}}}
  function write(key,v){try{localStorage.setItem(key,JSON.stringify(v))}catch(_){}}
  function badge(){
    const nav=document.querySelector('.nav[data-view="support"]');
    if(!nav)return;
    let b=nav.querySelector('.support-nav-badge');
    const map=read(KEY),count=Object.keys(map).length;
    if(count>0){
      if(!b){b=document.createElement('span');b.className='support-nav-badge';nav.appendChild(b)}
      b.textContent=count>99?'99+':String(count);b.hidden=false;
    }else if(b){b.hidden=true}
  }
  function clearThread(id){
    id=String(id);
    const unread=read(KEY),readMap=read(READ);
    if(unread[id]) readMap[id]=String(unread[id]);
    delete unread[id];
    write(READ,readMap);write(KEY,unread);badge();
  }
  function clearAll(){write(KEY,{});badge()}
  function announce(){
    if(typeof toast==='function')toast(tr('support_new_reply'),tr('support_new_reply_text'),'success');
    try{if(document.hidden&&'Notification' in window&&Notification.permission==='granted')new Notification(tr('support_new_reply'),{body:tr('support_new_reply_text')})}catch(_){}
  }
  async function poll(){
    try{
      const d=await api('/api/support.php?action=threads&_notify='+Date.now());
      const list=d.threads||[],unread=read(KEY),readMap=read(READ);
      let changed=false;
      list.forEach(t=>{
        const id=String(t.id),last=String(t.last_message_id||''),role=String(t.last_author_role||'').toLowerCase();
        if(!last||role!=='admin')return;
        if(readMap[id]!==last){
          if(unread[id]!==last){unread[id]=last;changed=true;announce();}
        }
      });
      if(changed)write(KEY,unread);
      badge();
    }catch(_){badge()}
  }
  function init(){badge();if(timer)clearInterval(timer);poll();timer=setInterval(()=>{if(!document.hidden)poll()},30000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll()})}
  return {init,poll,clearThread,clearAll};
})();

(async()=>{
  renderChat();
  applyLanguage(window.EVRUNI_LANG||'ru');
  loadModels().then(()=>{
    const select=$('#chatModel');
    if(select){
      select.innerHTML=modelOptions('text');
      select.value=currentModel;
    }
  });
  const idle=window.requestIdleCallback||function(cb){setTimeout(cb,350)};idle(()=>{loadDevelopmentNotice();loadBalance();},{timeout:900});
  window.EvruniSupportNotifications.init();
})();

/* EvruniPerf: tiny client-side performance helpers */
window.EvruniPerf = {
  init(){
    document.querySelectorAll('img:not([loading])').forEach(img => img.loading='lazy');
    document.querySelectorAll('img:not([decoding])').forEach(img => img.decoding='async');
  }
};
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',()=>EvruniPerf.init());
else EvruniPerf.init();

/* Evruni profile menu */
(function(){
 const w=document.getElementById('profileMenuWrap'),t=document.getElementById('profileTrigger'),d=document.getElementById('profileDropdown');
 if(!w||!t||!d)return;
 const close=()=>w.classList.remove('open');
 t.addEventListener('click',e=>{e.stopPropagation();w.classList.toggle('open')});
 document.addEventListener('click',e=>{if(!w.contains(e.target))close()});
 d.querySelectorAll('[data-profile-action]').forEach(btn=>btn.addEventListener('click',async e=>{
   e.preventDefault();const a=btn.dataset.profileAction;close();
   if(a==='profile'&&views.profile)await views.profile();
   if(a==='billing'&&views.billing)await views.billing();
   if(a==='usage'&&views.usage)await views.usage();
   if(a==='logout')window.location.replace('/api/auth.php?action=logout');
 }));
})();
(function(){
 const c=document.getElementById('credits'),p=document.getElementById('profileCredits');
 if(!c||!p)return;
 const sync=()=>{p.textContent=c.textContent};
 new MutationObserver(sync).observe(c,{childList:true,characterData:true,subtree:true});
 sync();
})();

/* V9.24 authoritative sidebar language */
(function(){
  function applySidebarLanguage(){
    var lang=window.EVRUNI_LANG==='en'?'en':'ru';
    var dict=EVRUNI_I18N[lang]||EVRUNI_I18N.ru;
    document.querySelectorAll('.sidebar [data-i18n]').forEach(function(el){
      var key=el.getAttribute('data-i18n');
      if(dict[key]) el.textContent=dict[key];
    });
  }
  window.evruniApplySidebarLanguage=applySidebarLanguage;
  document.addEventListener('DOMContentLoaded',applySidebarLanguage);
  var oldApply=window.applyLanguage;
  if(typeof oldApply==='function'){
    window.applyLanguage=function(lang){
      oldApply(lang);
      applySidebarLanguage();
    };
  }
})();


(function(){
  try{
    if(window.i18n){
      window.i18n.ru=Object.assign(window.i18n.ru||{},{
        landing_title:'Один AI-пространство для твоих задач',
        landing_text:'Чаты, изображения, документы, исследования и проекты — в одном месте.',
        landing_pill_chat:'AI Chat',
        landing_pill_files:'Files & Projects',
        landing_pill_tools:'AI Tools',
        landing_credits_title:'Баланс для AI',
        landing_credits_text:'Пополняй баланс и используй его для AI-функций.',
        landing_credits_btn:'Пополнить баланс'
      });
      window.i18n.en=Object.assign(window.i18n.en||{},{
        landing_title:'One AI workspace for everything you do',
        landing_text:'Chats, images, documents, research and projects — all in one place.',
        landing_pill_chat:'AI Chat',
        landing_pill_files:'Files & Projects',
        landing_pill_tools:'AI Tools',
        landing_credits_title:'AI Balance',
        landing_credits_text:'Top up your balance and use it across AI features.',
        landing_credits_btn:'Top up balance'
      });
    }
  }catch(e){}
})();


(function(){
  document.addEventListener('click',function(e){
    var b=e.target.closest('.landing-pricing-btn');
    if(!b) return;
    var billing=document.querySelector('[data-view="billing"],#billing');
    if(billing){ billing.click(); return; }
    var link=document.querySelector('a[href*="billing"]');
    if(link){ link.click(); return; }
    window.location.href='/#billing';
  });
})();


document.addEventListener('DOMContentLoaded',initGlobalSearch);


(function(){
  if(!window.EVRUNI_I18N || !window.EVRUNI_I18N.ru)return;
  Object.assign(window.EVRUNI_I18N.ru,{
    choose_ai:'Выберите модель ИИ',
    image_title:'Генерация изображений',
    video_title:'Генерация видео',
    research_title:'Исследование',
    models_title:'Каталог моделей',
    presets_title:'Пресеты ИИ',
    sandbox_title:'Песочница API',
    tts_title:'Озвучка текста',
    stt_title:'Расшифровка аудио',
    image_info:'Выберите модель и укажите описание изображения.',
    video_info:'Выберите модель и укажите описание видео.',
    research_ready:'Результат исследования появится здесь.',
    audio_ready:'Голосовой результат появится здесь.',
    transcript_ready:'Расшифровка появится здесь.'
  });
})();

window.addEventListener('unhandledrejection',function(ev){
  const msg=ev&&ev.reason&&ev.reason.message?ev.reason.message:(ev&&ev.reason?String(ev.reason):'');
  if(msg){try{toast('Ошибка',msg,'error')}catch(_){}}
});

/* Evruni Center v15.82 */
(function(){
  var KEY="evruni_center_events_v1";
  function get(){try{return JSON.parse(localStorage.getItem(KEY)||"[]")}catch(e){return[]}}
  function save(a){localStorage.setItem(KEY,JSON.stringify(a.slice(-30)))}
  function render(){
    var list=document.getElementById("evruniCenterList"), badge=document.getElementById("evruniCenterBadge");
    if(!list)return;
    var a=get();
    if(!a.length){list.innerHTML='<div class="evruni-center-empty">Здесь появятся важные события.</div>';if(badge)badge.style.display="none";return}
    list.innerHTML=a.slice().reverse().map(function(x){
      return '<div class="evruni-center-item"><div class="evruni-center-item-title">'+esc(x.t)+'</div><div class="evruni-center-item-text">'+esc(x.m)+'</div><div class="evruni-center-item-time">'+esc(x.time||"")+'</div></div>';
    }).join("");
    if(badge){badge.textContent=Math.min(a.length,99);badge.style.display="flex"}
  }
  function esc(v){return String(v).replace(/[&<>"']/g,function(c){return({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[c]})}
  window.evruniCenterNotify=function(title,message){
    var a=get();a.push({t:title,m:message,time:new Date().toLocaleTimeString([], {hour:"2-digit",minute:"2-digit"})});save(a);render();
  };
  document.addEventListener("DOMContentLoaded",function(){
    var b=document.getElementById("evruniCenterBtn"),p=document.getElementById("evruniCenterPanel"),c=document.getElementById("evruniCenterClear");
    if(b)b.onclick=function(){p.hidden=!p.hidden;render()};
    if(c)c.onclick=function(){save([]);render()};
    render();
  });
})();
