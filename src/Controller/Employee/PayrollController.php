<?php

declare(strict_types=1);

namespace StaffHub\Controller\Employee;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Service\PayrollService;

final class PayrollController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly PayrollService $payroll,
    ) {
        parent::__construct($container);
    }

    public function history(Request $request): Response
    {
        $employeeId = (int) AuthService::getEmployeeId();
        $employee = $this->employees->findJoined($employeeId);

        return $this->render('employee/payroll_history', [
            'pageTitle' => 'Payroll History',
            'activeNav' => 'payroll',
            'employee' => $employee,
            'history' => $this->payroll->historyForEmployee($employeeId, 24),
        ]);
    }
}
