<?php
/**
 * Adds Creator Tools to the authenticated Evruni sidebar and injects its small
 * browser-side OBS bridge. Video/audio never pass through this PHP layer.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

ob_start(static function (string $html) use ($requestPath): string {
    if ($requestPath === '/') {
        if (strpos($html, 'data-view="sandbox"') !== false && strpos($html, 'data-view="creator-tools"') === false) {
            $needle = '<button class="nav" data-view="sandbox"><span>◌</span> <span data-i18n="nav_sandbox">Sandbox</span></button>';
            $item = $needle . '<button class="nav creator-tools-nav" type="button" data-view="creator-tools"><span>🎥</span> <span data-i18n="nav_creator_tools">Creator Tools</span></button>';
            $html = str_replace($needle, $item, $html);
        }
        if (strpos($html, '/assets/creator-tools-view.js') === false && strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', '<script src="/assets/creator-tools-view.js?v=2"></script></body>', $html);
        }
        return $html;
    }

    if ($requestPath === '/creator-tools.php' && strpos($html, '/assets/creator-tools-bridge.js') === false && strpos($html, '</head>') !== false) {
        $html = str_replace('</head>', '<script src="/assets/creator-tools-bridge.js?v=1"></script></head>', $html);
    }
    return $html;
});
