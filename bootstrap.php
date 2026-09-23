<?php

/**
 * Application bootstrap: error handling, config, autoloading, DI wiring, session.
 * Required once by public/index.php (front controller).
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ----- Autoloading -----
// Prefer Composer's autoloader when present; fall back to a PSR-4 loader
// so the app runs even without `composer install` (no external deps today).
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'StaffHub\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once __DIR__ . '/src/Support/helpers.php';
require_once __DIR__ . '/config/database.php';

// ----- Timezone -----
date_default_timezone_set('Asia/Manila');

// ----- Session -----
StaffHub\Auth\AuthService::startSession();

// ----- Container wiring -----
use StaffHub\Auth\AuthService;
use StaffHub\Core\Container;
use StaffHub\Core\Database;
use StaffHub\Core\Router;
use StaffHub\Repository\ActivityLogRepository;
use StaffHub\Repository\AttendanceRepository;
use StaffHub\Repository\Contract\ActivityLogRepositoryInterface;
use StaffHub\Repository\Contract\AttendanceRepositoryInterface;
use StaffHub\Repository\Contract\DepartmentRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\PayrollRepositoryInterface;
use StaffHub\Repository\Contract\PositionRepositoryInterface;
use StaffHub\Repository\Contract\UserRepositoryInterface;
use StaffHub\Repository\DepartmentRepository;
use StaffHub\Repository\EmployeeRepository;
use StaffHub\Repository\PayrollRepository;
use StaffHub\Repository\PositionRepository;
use StaffHub\Repository\UserRepository;
use StaffHub\Service\Logger;
use StaffHub\Service\LoggerInterface;

$container = new Container();
// Self-bind so controllers receive the configured container, not a fresh empty one.
$container->set(Container::class, $container);

// Shared PDO wrapper built from config constants.
$container->set(Database::class, static function (): Database {
    return new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET);
});

// Repository interfaces → concrete implementations (shared).
$container->set(UserRepositoryInterface::class, UserRepository::class);
$container->set(EmployeeRepositoryInterface::class, EmployeeRepository::class);
$container->set(AttendanceRepositoryInterface::class, AttendanceRepository::class);
$container->set(PayrollRepositoryInterface::class, PayrollRepository::class);
$container->set(DepartmentRepositoryInterface::class, DepartmentRepository::class);
$container->set(PositionRepositoryInterface::class, PositionRepository::class);
$container->set(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
$container->set(LoggerInterface::class, Logger::class);
$container->set(AuthService::class, AuthService::class);

// Router with the full route table.
$router = new Router();
require __DIR__ . '/src/Route/routes.php';

return new StaffHub\Core\Application($container, $router);
