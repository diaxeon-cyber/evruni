<?php
/**
 * Evruni UI enhancement layer.
 * Adds Creator Tools navigation/bridge and a compact collapsible sidebar.
 * Video/audio never pass through this PHP layer.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

ob_start(static function (string $html) use ($requestPath): string {
    if ($requestPath === '/') {
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
    try{
      const saved=localStorage.getItem(KEY);
      collapsed=saved===null ? true : saved==='1';
    }catch(_){ }
    const apply=()=>{
      sidebar.classList.toggle('sidebar-collapsed',collapsed);
      const btn=sidebar.querySelector('.sidebar-collapse-toggle');
      if(btn){btn.textContent=collapsed?'':'Свернуть меню';btn.setAttribute('aria-label',collapsed?'Развернуть меню':'Свернуть меню');btn.title=collapsed?'Развернуть меню':'Свернуть меню'}
    };
    let btn=sidebar.querySelector('.sidebar-collapse-toggle');
    if(!btn){
      btn=document.createElement('button');
      btn.type='button';
      btn.className='sidebar-collapse-toggle';
      const logo=sidebar.querySelector('.logo');
      if(logo)logo.insertAdjacentElement('afterend',btn); else sidebar.prepend(btn);
      btn.addEventListener('click',()=>{
        collapsed=!collapsed;
        try{localStorage.setItem(KEY,collapsed?'1':'0')}catch(_){ }
        apply();
      });
    }
    apply();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
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
    }
    return $html;
});
