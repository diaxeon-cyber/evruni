(()=>{
'use strict';
if(location.pathname!=='/creator-tools.php') return;
const show=(msg)=>{const d=document.createElement('div');d.style.cssText='position:fixed;left:16px;right:16px;bottom:16px;z-index:99999;padding:12px 14px;border-radius:12px;background:#24101a;color:#fff;border:1px solid #ff5d7a;font:13px system-ui';d.textContent=msg;document.body.appendChild(d)};
const load=(src,key)=>new Promise((resolve,reject)=>{
  if(document.querySelector('script[data-'+key+']')) return resolve();
  const s=document.createElement('script');
  s.src=src;
  s.async=false;
  s.setAttribute('data-'+key,'1');
  s.onload=()=>resolve();
  s.onerror=()=>reject(new Error(src+' failed to load'));
  document.head.appendChild(s);
});
const start=async()=>{
  try{
    await load('/assets/creator-integrations.js?v=5','creator-studio-loader');
    await load('/assets/donation-hub.js?v=2','donation-hub-loader');
  }catch(e){show('Creator Studio: не удалось загрузить модуль. '+e.message)}
};
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true});
else start();
})();
