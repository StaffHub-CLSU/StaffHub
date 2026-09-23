<?php

declare(strict_types=1);

use StaffHub\Auth\AuthMiddleware;
use StaffHub\Controller\Admin;
use StaffHub\Controller\Api;
use StaffHub\Controller\Auth\LoginController;
use StaffHub\Controller\Employee;
use StaffHub\Controller\Report;
use StaffHub\Core\Router;
use function StaffHub\Support\url;

/**
 * Application route table.
 *
 * Page routes use the layout shell; API routes return JSON.
 * Middleware: AuthMiddleware::requireLogin($role) / requireAny() / guest().
 */

/** @var Router $router */

// ----- Auth -----
$router->get('/login', [LoginController::class, 'show'], [AuthMiddleware::guest()]);
$router->post('/login', [LoginController::class, 'login'], [AuthMiddleware::guest()]);
$router->get('/logout', [LoginController::class, 'logout']);

// Root → role dashboard
$router->get('/', [LoginController::class, 'show'], [
    static function ($request): ?\StaffHub\Core\Response {
        if (\StaffHub\Auth\AuthService::isLoggedIn()) {
            $target = \StaffHub\Auth\AuthService::isAdmin() ? '/admin' : '/employee';
            return \StaffHub\Core\Response::redirect(url($target));
        }
        return \StaffHub\Core\Response::redirect(url('/login'));
    },
]);

// ----- Admin pages -----
$router->get('/admin', [Admin\DashboardController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);
$router->get('/admin/employees', [Admin\EmployeeController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);
$router->get('/admin/departments', [Admin\DepartmentController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);
$router->get('/admin/attendance', [Admin\AttendanceController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);
$router->get('/admin/payroll', [Admin\PayrollController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);

// ----- Reports -----
$router->get('/reports/attendance', [Report\AttendanceReportController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);
$router->get('/reports/payroll', [Report\PayrollReportController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);
$router->get('/reports/employees', [Report\EmployeeReportController::class, 'index'], [AuthMiddleware::requireLogin('admin')]);

// ----- Employee portal -----
$router->get('/employee', [Employee\DashboardController::class, 'index'], [AuthMiddleware::requireLogin('employee')]);
$router->get('/employee/attendance', [Employee\AttendanceController::class, 'history'], [AuthMiddleware::requireLogin('employee')]);
$router->get('/employee/payroll', [Employee\PayrollController::class, 'history'], [AuthMiddleware::requireLogin('employee')]);
$router->get('/employee/profile', [Employee\ProfileController::class, 'show'], [AuthMiddleware::requireLogin('employee')]);
$router->post('/employee/profile', [Employee\ProfileController::class, 'update'], [AuthMiddleware::requireLogin('employee')]);
$router->post('/employee/profile/password', [Employee\ProfileController::class, 'changePassword'], [AuthMiddleware::requireLogin('employee')]);

// ----- JSON API -----
// employees: admin for most actions; upload_picture allowed for any logged-in user
// (self-or-admin enforced inside the controller).
$router->get('/api/employees', [Api\EmployeeApiController::class, 'handle'], [AuthMiddleware::requireAny()]);
$router->post('/api/employees', [Api\EmployeeApiController::class, 'handle'], [AuthMiddleware::requireAny()]);

$router->get('/api/attendance', [Api\AttendanceApiController::class, 'handle'], [AuthMiddleware::requireAny()]);
$router->post('/api/attendance', [Api\AttendanceApiController::class, 'handle'], [AuthMiddleware::requireAny()]);

$router->get('/api/payroll', [Api\PayrollApiController::class, 'handle'], [AuthMiddleware::requireLogin('admin')]);
$router->post('/api/payroll', [Api\PayrollApiController::class, 'handle'], [AuthMiddleware::requireLogin('admin')]);

$router->get('/api/departments', [Api\DepartmentApiController::class, 'handle'], [AuthMiddleware::requireLogin('admin')]);
$router->post('/api/departments', [Api\DepartmentApiController::class, 'handle'], [AuthMiddleware::requireLogin('admin')]);

$router->get('/api/search', [Api\SearchApiController::class, 'handle'], [AuthMiddleware::requireAny()]);
$router->post('/api/search', [Api\SearchApiController::class, 'handle'], [AuthMiddleware::requireAny()]);
