<?php
/**
 * Evruni UI enhancement layer.
 * Adds Creator Tools navigation, top profile menu and a compact collapsible sidebar.
 * Video/audio never pass through this PHP layer.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

ob_start(static function (string $html) use ($requestPath): string {
    if (in_array($requestPath, ['/', '/index.php'], true)) {
        if (strpos($html, 'data-view="sandbox"') !== false && strpos($html, 'data-view="creator-tools"') === false) {
            $needle = '<button class="nav" data-view="sandbox"><span>◌</span> <span data-i18n="nav_sandbox">Sandbox</span></button>';
            $item = $needle . '<button class="nav creator-tools-nav" type="button" data-view="creator-tools"><span>🎥</span> <span data-i18n="nav_creator_tools">Creator Tools</span><b class="creator-beta-badge">BETA</b></button>';
            $html = str_replace($needle, $item, $html);
        }

        if (strpos($html, 'creator-beta-badge') === false && strpos($html, 'data-view="creator-tools"') !== false) {
            $html = preg_replace(
                '/(<[^>]*data-view="creator-tools"[^>]*>.*?<span[^>]*data-i18n="nav_creator_tools"[^>]*>.*?<\/span>)(?!\s*<b class="creator-beta-badge")/s',
                '$1<b class="creator-beta-badge">BETA</b>',
                $html,
                1
            ) ?? $html;
        }

        $profileCss = <<<'CSS'
<style id="evruni-profile-ui">
.evruni-top-profile{position:relative;display:flex;align-items:center;flex:0 0 auto}
.evruni-profile-trigger{display:flex;align-items:center;gap:9px;min-width:150px;padding:8px 10px 8px 8px;border:1px solid rgba(157,170,204,.14);border-radius:15px;background:rgba(12,16,29,.72);color:#eef1ff;cursor:pointer;backdrop-filter:blur(14px);box-shadow:0 12px 35px rgba(0,0,0,.18);transition:.2s ease}
.evruni-profile-trigger:hover{border-color:rgba(159,139,255,.34);background:rgba(20,25,43,.88);transform:translateY(-1px)}
.evruni-profile-avatar{width:32px;height:32px;display:grid;place-items:center;border-radius:11px;background:linear-gradient(145deg,#9b7cff,#5d3ce0 58%,#2fd8ff);color:#fff;font-size:14px;font-weight:900;box-shadow:0 0 18px rgba(108,76,255,.25)}
.evruni-profile-copy{display:grid;gap:2px;text-align:left;min-width:0;flex:1}.evruni-profile-copy strong{font-size:12px;line-height:1}.evruni-profile-copy small{font-size:10px;color:#77839d;line-height:1.2}.evruni-profile-chevron{color:#9b8bff;font-size:15px;transition:transform .2s ease}.evruni-profile-box.open .evruni-profile-chevron{transform:rotate(180deg)}
.evruni-profile-dropdown{position:absolute;right:0;top:calc(100% + 9px);width:205px;padding:7px;border:1px solid rgba(157,170,204,.16);border-radius:15px;background:rgba(10,14,27,.96);box-shadow:0 20px 55px rgba(0,0,0,.42);backdrop-filter:blur(18px);z-index:100;opacity:0;visibility:hidden;transform:translateY(-5px);transition:.18s ease}
.evruni-profile-box.open .evruni-profile-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
.evruni-profile-dropdown button,.evruni-profile-dropdown a{display:flex;align-items:center;gap:9px;width:100%;padding:10px 11px;border:0;border-radius:10px;background:transparent;color:#b8c0d3;text-decoration:none;text-align:left;cursor:pointer;font-size:12px}.evruni-profile-dropdown button:hover,.evruni-profile-dropdown a:hover{background:rgba(141,112,255,.10);color:#fff}.evruni-profile-divider{height:1px;background:rgba(157,170,204,.10);margin:5px 3px}
@media(max-width:700px){.evruni-profile-copy{display:none}.evruni-profile-trigger{min-width:0;padding:7px}.evruni-profile-dropdown{right:0}.main header{gap:8px}.main h1{font-size:27px}}
</style>
CSS;
        if (strpos($html, 'id="evruni-profile-ui"') === false && strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', $profileCss . '</head>', $html);
        }

        if (strpos($html, 'id="evruni-profile-box"') === false && strpos($html, '</header>') !== false) {
            $profile = <<<'HTML'
<div class="evruni-top-profile" id="evruni-profile-box">
  <button type="button" class="evruni-profile-trigger" id="evruni-profile-trigger" aria-haspopup="true" aria-expanded="false">
    <span class="evruni-profile-avatar">E</span>
    <span class="evruni-profile-copy"><strong>Профиль</strong><small>Аккаунт Evruni</small></span>
    <span class="evruni-profile-chevron">⌄</span>
  </button>
  <div class="evruni-profile-dropdown" role="menu">
    <button type="button" data-profile-view="profile">◉ Профиль</button>
    <button type="button" data-profile-view="usage">◌ Использование</button>
    <button type="button" data-profile-view="billing">₽ Баланс и оплата</button>
    <div class="evruni-profile-divider"></div>
    <a href="/logout.php">↪ Выйти</a>
  </div>
</div>
HTML;
            $html = preg_replace('/<\/header>/', $profile . '</header>', $html, 1) ?? $html;
        }

        $uiCss = <<<'CSS'
<style id="evruni-sidebar-ui">
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
@media(max-width:900px){.sidebar.sidebar-collapsed{width:84px;flex-basis:84px}}
</style>
CSS;

        if (strpos($html, 'id="evruni-sidebar-ui"') === false && strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', $uiCss . '</head>', $html);
        }

        $uiJs = <<<'JS'
<script id="evruni-sidebar-ui-js">
(function(){
  function init(){
    const sidebar=document.querySelector('.sidebar');
    if(!sidebar)return;
    const KEY='evruni_sidebar_collapsed_v1';
    let collapsed=true;
    try{const saved=localStorage.getItem(KEY);collapsed=saved===null?true:saved==='1'}catch(_){ }
    const apply=()=>{
      sidebar.classList.toggle('sidebar-collapsed',collapsed);
      const btn=sidebar.querySelector('.sidebar-collapse-toggle');
      if(btn){btn.textContent=collapsed?'':'Свернуть меню';btn.setAttribute('aria-label',collapsed?'Развернуть меню':'Свернуть меню');btn.title=collapsed?'Развернуть меню':'Свернуть меню'}
    };
    let btn=sidebar.querySelector('.sidebar-collapse-toggle');
    if(!btn){
      btn=document.createElement('button');btn.type='button';btn.className='sidebar-collapse-toggle';
      const logo=sidebar.querySelector('.logo');if(logo)logo.insertAdjacentElement('afterend',btn);else sidebar.prepend(btn);
      btn.addEventListener('click',()=>{collapsed=!collapsed;try{localStorage.setItem(KEY,collapsed?'1':'0')}catch(_){ }apply()});
    }
    apply();
  }
  function profileInit(){
    const box=document.getElementById('evruni-profile-box'),trigger=document.getElementById('evruni-profile-trigger');
    if(!box||!trigger)return;
    trigger.addEventListener('click',e=>{e.stopPropagation();const open=!box.classList.contains('open');box.classList.toggle('open',open);trigger.setAttribute('aria-expanded',open?'true':'false')});
    box.querySelectorAll('[data-profile-view]').forEach(btn=>btn.addEventListener('click',()=>{const view=btn.dataset.profileView;box.classList.remove('open');document.querySelector('.nav[data-view="'+view+'"]')?.click()}));
    document.addEventListener('click',e=>{if(!e.target.closest('#evruni-profile-box'))box.classList.remove('open')});
  }
  function initAll(){init();profileInit()}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initAll,{once:true});else initAll();
})();
</script>
JS;
        if (strpos($html, 'id="evruni-sidebar-ui-js"') === false && strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', $uiJs . '</body>', $html);
        }

        if (strpos($html, '/assets/creator-tools-view.js') === false && strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', '<script src="/assets/creator-tools-view.js?v=2"></script></body>', $html);
        }
        return $html;
    }

    if ($requestPath === '/creator-tools.php') {
        $html = str_replace('citeturn531270search2turn531270search0', '', $html);
        if (strpos($html, '/assets/creator-tools-bridge.js') === false && strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', '<script src="/assets/creator-tools-bridge.js?v=3"></script></head>', $html);
        }
        if (strpos($html, '/assets/creator-film-bridge.js') === false && strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', '<script src="/assets/creator-film-bridge.js?v=1"></script></body>', $html);
        }
        if (strpos($html, '/assets/creator-film-guard.js') === false && strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', '<script src="/assets/creator-film-guard.js?v=1"></script></body>', $html);
        }
    }
    return $html;
});
