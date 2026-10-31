(()=>{
'use strict';
if(location.pathname!=='/creator-tools.php')return;
const OBS_KEY='evruniCreatorObsSocket';let rid=0,mutedSource='';
const wait=async()=>{for(let i=0;i<40;i++){const ws=window[OBS_KEY];if(ws&&ws.readyState===1)return ws;await new Promise(r=>setTimeout(r,250))}throw new Error('OBS WebSocket не подключён')};
const req=(type,data={})=>wait().then(ws=>new Promise((resolve,reject)=>{const id='fg_'+(++rid)+'_'+Date.now(),tm=setTimeout(()=>reject(new Error('OBS timeout')),4000),fn=e=>{try{const m=JSON.parse(e.data);if(m.op===7&&m.d?.requestId===id){clearTimeout(tm);ws.removeEventListener('message',fn);m.d.requestStatus?.result?resolve(m.d.responseData||{}):reject(new Error(m.d.requestStatus?.comment||'OBS error'))}}catch(_){}};ws.addEventListener('message',fn);ws.send(JSON.stringify({op:6,d:{requestType:type,requestId:id,requestData:data}}))}));
async function setMute(source,on){if(!source)return;try{await req('SetInputMute',{inputName:source,inputMuted:!!on});}catch(_){try{await req('SetSourceFilterEnabled',{sourceName:source,filterName:'Evruni Movie Guard',filterEnabled:!!on})}catch(__){}}}
window.addEventListener('evruni:film-guard',async e=>{const d=e.detail||{},source=String(d.audioSource||document.querySelector('#obsMic')?.value||'').trim();if(!source)return;if(d.mute){if(mutedSource!==source){await setMute(source,true);mutedSource=source}}else if(mutedSource){await setMute(mutedSource,false);mutedSource=''}});
})();
