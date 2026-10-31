(function(){
  function init(){
    const sidebar=document.querySelector('.sidebar'),app=document.querySelector('.app');
    if(!sidebar)return;
    const KEY='evruni_sidebar_collapsed_v3';
    let collapsed=true;
    try{const saved=localStorage.getItem(KEY);collapsed=saved===null?true:saved==='1'}catch(_){ }
    if(!document.getElementById('evruni-sidebar-ui')){
      const style=document.createElement('style');style.id='evruni-sidebar-ui';style.textContent=`
      .sidebar{transition:width .34s cubic-bezier(.22,1,.36,1),min-width .34s cubic-bezier(.22,1,.36,1),flex-basis .34s cubic-bezier(.22,1,.36,1),padding .34s ease,transform .34s ease;will-change:width}
      .app{transition:grid-template-columns .34s cubic-bezier(.22,1,.36,1)}
      .app.sidebar-menu-collapsed{grid-template-columns:84px minmax(0,1fr)!important}
      .sidebar.sidebar-collapsed{width:84px!important;min-width:84px!important;flex-basis:84px!important;padding-left:10px!important;padding-right:10px!important}
      .sidebar .logo-copy,.sidebar .nav>span:not(:first-child),.sidebar .side-bottom a>span:not(:first-child){transition:opacity .18s ease,transform .24s ease,max-width .34s ease}
      .sidebar.sidebar-collapsed .logo{justify-content:center;margin:0;padding:0 4px}.sidebar.sidebar-collapsed .logo-copy{opacity:0;transform:translateX(-8px);max-width:0;overflow:hidden}
      .sidebar.sidebar-collapsed .nav{justify-content:center;padding-left:8px;padding-right:8px;gap:0}.sidebar.sidebar-collapsed .nav>span:not(:first-child),.sidebar.sidebar-collapsed .side-bottom a>span:not(:first-child){opacity:0;transform:translateX(-8px);max-width:0;overflow:hidden}
      .sidebar-collapse-toggle{width:100%;height:36px;margin:0 0 10px;padding:8px 10px;border:1px solid rgba(157,170,204,.12);border-radius:12px;background:rgba(255,255,255,.025);color:#8f9ab2;cursor:pointer;transition:all .24s ease}.sidebar-collapse-toggle:hover{color:#fff;border-color:rgba(159,139,255,.3);background:rgba(141,112,255,.08);transform:translateY(-1px)}
      .sidebar.sidebar-collapsed .sidebar-collapse-toggle{font-size:0;padding:8px 4px}.sidebar.sidebar-collapsed .sidebar-collapse-toggle:after{content:'›';font-size:20px;line-height:1}.creator-tools-nav{overflow:visible!important}.creator-beta-badge{margin-left:auto;padding:3px 6px;border-radius:999px;border:1px solid rgba(103,232,255,.22);background:linear-gradient(135deg,rgba(139,92,246,.18),rgba(56,189,248,.12));color:#91eaff;font-size:8px;font-weight:800;letter-spacing:.1em;line-height:1}.sidebar.sidebar-collapsed .creator-beta-badge{display:none}
      `;document.head.appendChild(style)
    }
    let creator=document.querySelector('.nav[data-view="creator-tools"]');
    if(creator){creator.classList.add('creator-tools-nav');if(!creator.querySelector('.creator-beta-badge')){const badge=document.createElement('b');badge.className='creator-beta-badge';badge.textContent='BETA';creator.appendChild(badge)}}
    let btn=sidebar.querySelector('.sidebar-collapse-toggle');
    if(!btn){btn=document.createElement('button');btn.type='button';btn.className='sidebar-collapse-toggle';const logo=sidebar.querySelector('.logo');if(logo)logo.insertAdjacentElement('afterend',btn);else sidebar.prepend(btn);btn.addEventListener('click',()=>{collapsed=!collapsed;try{localStorage.setItem(KEY,collapsed?'1':'0')}catch(_){ }apply()})}
    function apply(){sidebar.classList.toggle('sidebar-collapsed',collapsed);if(app)app.classList.toggle('sidebar-menu-collapsed',collapsed);btn.textContent=collapsed?'':'Свернуть меню';btn.setAttribute('aria-label',collapsed?'Развернуть меню':'Свернуть меню');btn.title=collapsed?'Развернуть меню':'Свернуть меню'}
    apply()
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();