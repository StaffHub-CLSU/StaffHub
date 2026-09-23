<?php

declare(strict_types=1);

namespace StaffHub\Controller\Employee;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Service\DashboardService;

final class DashboardController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DashboardService $dashboard,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request): Response
    {
        $employeeId = (int) AuthService::getEmployeeId();
        $employee = $this->employees->findJoined($employeeId);

        if (!$employee) {
            return Response::html(
                '<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;">Employee record not found.</h2>',
                404,
            );
        }

        return $this->render('employee/dashboard', [
            'pageTitle' => 'My Dashboard',
            'activeNav' => 'dashboard',
            'employee' => $employee,
            'stats' => $this->dashboard->employeeStats($employeeId),
        ]);
    }
}
