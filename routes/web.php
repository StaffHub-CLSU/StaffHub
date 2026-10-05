<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('dashboard') : to_route('login'))->name('home');
Route::get('/dashboard', DashboardController::class)->middleware('active-auth')->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['active-auth', 'role:Admin,Manager'])->group(function (): void {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('organization', [OrganizationController::class, 'index'])->name('organization.index');
    Route::post('departments', [OrganizationController::class, 'storeDepartment'])->name('departments.store');
    Route::put('departments/{department}', [OrganizationController::class, 'updateDepartment'])->name('departments.update');
    Route::delete('departments/{department}', [OrganizationController::class, 'destroyDepartment'])->name('departments.destroy');
    Route::post('positions', [OrganizationController::class, 'storePosition'])->name('positions.store');
    Route::put('positions/{position}', [OrganizationController::class, 'updatePosition'])->name('positions.update');
    Route::delete('positions/{position}', [OrganizationController::class, 'destroyPosition'])->name('positions.destroy');

    Route::resource('attendance', AttendanceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('attendance/{attendance}/verify', [AttendanceController::class, 'verify'])->name('attendance.verify');
    Route::resource('payroll', PayrollController::class)->only(['index', 'store', 'destroy']);

    Route::get('reports/{type}', [AdminController::class, 'report'])->whereIn('type', ['attendance', 'payroll', 'employees'])->name('reports.show');
});

Route::prefix('employee')->name('employee.')->middleware(['active-auth', 'role:Employee'])->group(function (): void {
    Route::get('/', [EmployeePortalController::class, 'dashboard'])->name('dashboard');
    Route::get('attendance', [EmployeePortalController::class, 'attendance'])->name('attendance.index');
    Route::post('attendance/{action}', [EmployeePortalController::class, 'clock'])->whereIn('action', ['in', 'out'])->name('attendance.clock');
    Route::get('payroll', [EmployeePortalController::class, 'payroll'])->name('payroll.index');
    Route::get('profile', [EmployeePortalController::class, 'profile'])->name('profile.show');
    Route::put('profile', [EmployeePortalController::class, 'updateProfile'])->name('profile.update');
});
