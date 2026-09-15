<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'staffhub_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application-wide settings
define('APP_NAME', 'StaffHub');

// APP_URL is derived from the current request so avatars and uploads resolve
// correctly regardless of which host/folder the app is served from
// (localhost/StaffHub_v2_updated/StaffHub, StaffHub.test, StaffHub_v2_updated.test, ...).
$shScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$shDocRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : null;
$shAppDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$shAppPath = '';
if ($shDocRoot && stripos($shAppDir, rtrim($shDocRoot, '/')) === 0) {
    $shAppPath = substr($shAppDir, strlen(rtrim($shDocRoot, '/')));
}
define('APP_URL', $shScheme . '://' . $shHost . $shAppPath);
define('UPLOAD_DIR', __DIR__ . '/../uploads/profile_pictures/');
define('UPLOAD_URL', APP_URL . '/uploads/profile_pictures/');
