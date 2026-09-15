<?php
/**
 * bootstrap.php
 * Central include file: loads configuration and every class file once.
 * Every page and AJAX endpoint should require this instead of individual classes.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0); // never show raw PHP errors to end users
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Authentication.php';
require_once __DIR__ . '/../classes/Department.php';
require_once __DIR__ . '/../classes/Position.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Attendance.php';
require_once __DIR__ . '/../classes/Payroll.php';
require_once __DIR__ . '/../classes/Dashboard.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Small helper: escape output for safe HTML rendering. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Small helper: format a number as PHP currency (PHP peso by default). */
function money(?float $value): string
{
    return '₱' . number_format((float) $value, 2);
}
