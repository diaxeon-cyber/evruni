<?php
/**
 * Lightweight navigation bridge for Creator Tools.
 * Keeps index.php unchanged and only touches the dashboard markup on the home page.
 */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || ($_SERVER['REQUEST_URI'] ?? '/') !== '/') {
    return;
}

ob_start(static function (string $html): string {
    if (strpos($html, 'data-view="sandbox"') === false || strpos($html, '/creator-tools.php') !== false) {
        return $html;
    }

    $needle = '<button class="nav" data-view="sandbox"><span>◌</span> <span data-i18n="nav_sandbox">Sandbox</span></button>';
    $item = $needle . '<a class="nav" href="/creator-tools.php"><span>🎥</span> <span>Creator Tools</span></a>';
    return str_replace($needle, $item, $html, $count) === $html && $count === 0 ? $html : str_replace($needle, $item, $html, $count);
});
