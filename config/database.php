<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'staffhub_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application-wide settings
define('APP_NAME', 'StaffHub');

// APP_URL is derived from the front controller's directory so assets, uploads,
// and redirects resolve correctly whether the document root is public/ or the
// project folder (with the root .htaccess passthrough).
$shScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$shScriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$shScriptDir = $shScriptDir === '/' || $shScriptDir === '.' ? '' : rtrim($shScriptDir, '/');
define('APP_URL', $shScheme . '://' . $shHost . $shScriptDir);

// Profile pictures live under public/ so they are web-accessible.
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/profile_pictures/');
define('UPLOAD_URL', APP_URL . '/uploads/profile_pictures/');
