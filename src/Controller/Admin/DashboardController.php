<?php

declare(strict_types=1);

namespace StaffHub\Controller\Admin;

use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Service\DashboardService;

final class DashboardController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly DashboardService $dashboard,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'stats' => $this->dashboard->adminStats(),
            'recentActivity' => $this->dashboard->recentActivity(8),
            'recentClockIns' => $this->dashboard->recentClockIns(5),
            'recentClockOuts' => $this->dashboard->recentClockOuts(5),
            'dailyAttendance' => $this->dashboard->dailyAttendanceChart(7),
            'monthlyPayroll' => $this->dashboard->monthlyPayrollChart(6),
            'deptDistribution' => $this->dashboard->departmentDistributionChart(),
        ]);
    }
}
