<?php
/**
 * layouts/shell.php — page frame: header + content view + footer.
 * Expects: $contentView, plus layout vars ($pageTitle, $activeNav, $extraScripts, …).
 */
use function StaffHub\Support\e;

require __DIR__ . '/header.php';
require dirname(__DIR__) . '/' . $contentView . '.php';
require __DIR__ . '/footer.php';
