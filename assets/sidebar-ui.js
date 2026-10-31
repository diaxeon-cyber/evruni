(function(){
  function init(){
    const sidebar=document.querySelector('.sidebar');
    if(!sidebar)return;
    const KEY='evruni_sidebar_collapsed_v1';
    let collapsed=true;
    try{
      const saved=localStorage.getItem(KEY);
      collapsed=saved===null ? true : saved==='1';
    }catch(_){ }

    if(!document.getElementById('evruni-sidebar-ui')){
      const style=document.createElement('style');
      style.id='evruni-sidebar-ui';
      style.textContent=`
        .sidebar{transition:width .24s ease,flex-basis .24s ease,padding .24s ease}
        .sidebar.sidebar-collapsed{width:84px;flex-basis:84px;padding-left:10px;padding-right:10px}
        .sidebar.sidebar-collapsed .logo{justify-content:center;margin-left:0;margin-right:0;padding-left:4px;padding-right:4px}
        .sidebar.sidebar-collapsed .logo-copy{display:none}
        .sidebar.sidebar-collapsed .nav{justify-content:center;padding-left:8px;padding-right:8px;gap:0}
        .sidebar.sidebar-collapsed .nav>span:not(:first-child){display:none}
        .sidebar.sidebar-collapsed .side-bottom a{justify-content:center;padding-left:8px;padding-right:8px;gap:0}
        .sidebar.sidebar-collapsed .side-bottom a>span:not(:first-child){display:none}
        .sidebar-collapse-toggle{width:100%;margin:0 0 10px;padding:8px 10px;border:1px solid rgba(157,170,204,.12);border-radius:12px;background:rgba(255,255,255,.025);color:#8f9ab2;cursor:pointer;transition:.2s ease;font-size:12px}
        .sidebar-collapse-toggle:hover{color:#fff;border-color:rgba(159,139,255,.3);background:rgba(141,112,255,.08)}
        .sidebar.sidebar-collapsed .sidebar-collapse-toggle{font-size:0;padding:8px 4px}
        .sidebar.sidebar-collapsed .sidebar-collapse-toggle:after{content:'›';font-size:18px;line-height:1}
        .creator-tools-nav{overflow:visible!important}
        .creator-beta-badge{margin-left:auto;padding:3px 6px;border-radius:999px;border:1px solid rgba(103,232,255,.22);background:linear-gradient(135deg,rgba(139,92,246,.18),rgba(56,189,248,.12));color:#91eaff;font-size:8px;font-weight:800;letter-spacing:.1em;line-height:1}
        .sidebar.sidebar-collapsed .creator-beta-badge{display:none}
      `;
      document.head.appendChild(style);
    }

    let creator=document.querySelector('.nav[data-view="creator-tools"]');
    if(creator){
      creator.classList.add('creator-tools-nav');
      if(!creator.querySelector('.creator-beta-badge')){
        const badge=document.createElement('b');
        badge.className='creator-beta-badge';
        badge.textContent='BETA';
        creator.appendChild(badge);
      }
    }

    let btn=sidebar.querySelector('.sidebar-collapse-toggle');
    if(!btn){
      btn=document.createElement('button');
      btn.type='button';
      btn.className='sidebar-collapse-toggle';
      const logo=sidebar.querySelector('.logo');
      if(logo)logo.insertAdjacentElement('afterend',btn);else sidebar.prepend(btn);
      btn.addEventListener('click',()=>{
        collapsed=!collapsed;
        try{localStorage.setItem(KEY,collapsed?'1':'0')}catch(_){ }
        apply();
      });
    }

    function apply(){
      sidebar.classList.toggle('sidebar-collapsed',collapsed);
      btn.textContent=collapsed?'':'Свернуть меню';
      btn.setAttribute('aria-label',collapsed?'Развернуть меню':'Свернуть меню');
      btn.title=collapsed?'Развернуть меню':'Свернуть меню';
    }
    apply();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
