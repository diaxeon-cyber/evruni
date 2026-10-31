<?php
/**
 * Adds Creator Tools to the authenticated Evruni sidebar without rewriting index.php.
 * Reuses the same .nav styling as the existing sidebar items.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    return;
}

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if ($requestPath !== '/') {
    return;
}

ob_start(static function (string $html): string {
    if (strpos($html, 'data-view="sandbox"') === false || strpos($html, 'href="/creator-tools.php"') !== false) {
        return $html;
    }

    $needle = '<button class="nav" data-view="sandbox"><span>◌</span> <span data-i18n="nav_sandbox">Sandbox</span></button>';
    $item = $needle . '<a class="nav" href="/creator-tools.php"><span>🎥</span> <span data-i18n="nav_creator_tools">Creator Tools</span></a>';

    return str_replace($needle, $item, $html);
});
