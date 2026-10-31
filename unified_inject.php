<?php
/** Evruni unified UI prepend. Existing dashboard injector only. Public landing blocks are rendered directly by index.php. */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
require_once __DIR__ . '/creator_nav_inject.php';
