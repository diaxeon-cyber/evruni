<?php
/**
 * Public landing enhancement layer.
 * Keeps the existing landing intact and adds the streamer + trends blocks.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

require_once __DIR__ . '/creator_nav_inject.php';

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!in_array($requestPath, ['/', '/index.php'], true)) {
    return;
}

ob_start(static function (string $html): string {
    if (strpos($html, 'public-page') === false || strpos($html, '/assets/public-landing.js') !== false) {
        return $html;
    }
    if (strpos($html, '</body>') === false) {
        return $html;
    }
    return str_replace(
        '</body>',
        '<script src="/assets/public-landing.js?v=1"></script></body>',
        $html
    );
});
