<?php
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Evruni Creator Overlay</title>
<style>
*{box-sizing:border-box}html,body{margin:0;width:100%;height:100%;overflow:hidden;background:transparent;font-family:Inter,Arial,sans-serif;color:#fff}body{pointer-events:none}.wrap{position:relative;width:100%;height:100%}.toast{position:absolute;left:50%;top:8%;transform:translate(-50%,-20px) scale(.96);min-width:320px;max-width:min(760px,88vw);padding:18px 22px;border:1px solid rgba(255,255,255,.16);border-radius:18px;background:linear-gradient(135deg,rgba(18,22,38,.96),rgba(50,26,91,.94));box-shadow:0 18px 60px rgba(0,0,0,.35),0 0 35px rgba(92,70,255,.18);opacity:0;transition:.22s ease;display:flex;gap:13px;align-items:center}.toast.show{opacity:1;transform:translate(-50%,0) scale(1)}.ico{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;background:linear-gradient(135deg,#36d7ff,#805cff);font-size:20px;flex:0 0 auto}.body b{display:block;font-size:15px;margin-bottom:4px}.body span{display:block;color:#c7d0e4;font-size:12px;line-height:1.35}.chat{position:absolute;right:3%;bottom:5%;display:flex;flex-direction:column;align-items:flex-end;gap:7px}.msg{max-width:min(520px,45vw);padding:9px 12px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(7,10,18,.82);box-shadow:0 10px 30px rgba(0,0,0,.22);font-size:13px;opacity:0;transform:translateY(8px);transition:.18s ease}.msg.show{opacity:1;transform:translateY(0)}.cover{position:absolute;inset:0;display:none;place-items:center;background:rgba(5,7,12,.96);backdrop-filter:blur(8px)}.cover.show{display:grid}.coverbox{text-align:center;padding:28px}.coverbox strong{display:block;font-size:25px}.coverbox span{display:block;margin-top:7px;color:#aab5cb;font-size:13px}.badge{position:absolute;left:3%;top:4%;padding:7px 10px;border-radius:999px;background:rgba(8,12,22,.76);border:1px solid rgba(57,216,255,.3);color:#7fe9ff;font-size:10px;opacity:.8}
</style>
</head>
<body>
<div class="wrap"><div class="badge" id="badge">EVRUNI CREATOR</div><div class="toast" id="toast"><div class="ico" id="ico">✦</div><div class="body"><b id="title">Evruni</b><span id="text"></span></div></div><div class="chat" id="chat"></div><div class="cover" id="cover"><div class="coverbox"><strong>🔞 Контент скрыт</strong><span>Creator Censor включён</span></div></div></div>
<script>
(()=>{
const qs=new URLSearchParams(location.hash.slice(1));
const host=qs.get('host')||'127.0.0.1',port=qs.get('port')||'4455',pass=qs.get('pass')||'';
let ws=null,seq=0,identified=false;
const $=s=>document.querySelector(s),toast=$('#toast'),chat=$('#chat'),cover=$('#cover');
function bytes(a){let s='';a.forEach(x=>s+=String.fromCharCode(x));return btoa(s)}
async function sha(s){return new Uint8Array(await crypto.subtle.digest('SHA-256',new TextEncoder().encode(s)))}
async function auth(p,s,c){const secret=bytes(await sha(p+s));return bytes(await sha(secret+c))}
function showToast(icon,title,text,ms=4200){$('#ico').textContent=icon||'✦';$('#title').textContent=title||'Evruni';$('#text').textContent=text||'';toast.classList.add('show');clearTimeout(showToast.t);showToast.t=setTimeout(()=>toast.classList.remove('show'),ms)}
function showChat(text,user){const d=document.createElement('div');d.className='msg';d.textContent=(user?user+': ':'')+text;chat.appendChild(d);requestAnimationFrame(()=>d.classList.add('show'));setTimeout(()=>{d.classList.remove('show');setTimeout(()=>d.remove(),220)},6500);while(chat.children.length>4)chat.firstElementChild.remove()}
function handle(e){if(!e||typeof e!=='object')return;const t=e.type||'';if(t==='donation')showToast('💰','Новый донат',e.text||'Спасибо!',Number(e.duration)||5000);else if(t==='alert')showToast(e.icon||'🔔',e.title||'Evruni',e.text||'',Number(e.duration)||4200);else if(t==='chat')showChat(e.text||'',e.user||'');else if(t==='moderation')showToast(e.action==='delete'?'🛡️':'ℹ️','AI Moderator',e.text||'',3500);else if(t==='ai')showToast('🧠',e.title||'Evruni AI',e.text||'',Number(e.duration)||6500);else if(t==='censor')cover.classList.toggle('show',e.enabled!==false);else if(t==='clear') {toast.classList.remove('show');chat.innerHTML='';cover.classList.remove('show')}}
function connect(){
try{ws=new WebSocket('ws://'+host+':'+port,'obswebsocket.json')}catch(e){return}
ws.onmessage=async ev=>{let m;try{m=JSON.parse(ev.data)}catch(e){return}if(m.op===0){const d=m.d||{},id={rpcVersion:1};if(d.authentication){if(!pass){showToast('⚠️','OBS overlay','Нужен пароль obs-websocket',5000);return}id.authentication=await auth(pass,d.authentication.salt,d.authentication.challenge)}ws.send(JSON.stringify({op:1,d:id}))}else if(m.op===2){identified=true;showToast('✦','Evruni','Overlay подключён к OBS',1800)}else if(m.op===5&&m.d?.eventType==='CustomEvent'){const data=m.d.eventData?.eventData||m.d.eventData;if(data?.eventName==='EvruniCreator'||data?.type)handle(data?.eventData||data)}};
ws.onclose=()=>{identified=false;setTimeout(connect,2500)};ws.onerror=()=>{};
}
connect();
window.addEventListener('pagehide',()=>{try{ws?.close()}catch(e){}});
})();
</script>
</body></html>
