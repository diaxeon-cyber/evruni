(()=>{
'use strict';
if(location.pathname!=='/creator-tools.php') return;
const show=(msg)=>{const d=document.createElement('div');d.style.cssText='position:fixed;left:16px;right:16px;bottom:16px;z-index:99999;padding:12px 14px;border-radius:12px;background:#24101a;color:#fff;border:1px solid #ff5d7a;font:13px system-ui';d.textContent=msg;document.body.appendChild(d)};
const load=(src,id)=>new Promise((resolve,reject)=>{
 const old=document.getElementById(id);
 if(old){resolve();return}
 const s=document.createElement('script');s.id=id;s.src=src;s.async=false;s.onload=resolve;s.onerror=()=>reject(new Error(src+' failed to load'));(document.head||document.documentElement).appendChild(s);
});
const start=async()=>{try{await load('/assets/creator-integrations.js?v=8','evruni_creator_studio_loader');await load('/assets/donation-hub.js?v=5','evruni_donation_hub_loader')}catch(e){show('Creator Studio: не удалось загрузить модуль. '+e.message)}};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
