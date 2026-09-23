<?php

declare(strict_types=1);

namespace StaffHub\Controller\Report;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\DepartmentRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Service\ReportService;

final class PayrollReportController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ReportService $reports,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request): Response
    {
        $filters = array_filter([
            'employee_id' => (string) $request->query('employee_id', ''),
            'department_id' => (string) $request->query('department_id', ''),
            'period_start' => (string) $request->query('period_start', ''),
            'period_end' => (string) $request->query('period_end', ''),
        ], static fn ($v) => $v !== '');

        $report = $this->reports->payrollReport($filters);

        return $this->render('reports/payroll', [
            'pageTitle' => 'Payroll Report',
            'activeNav' => 'report_payroll',
            'departments' => $this->departments->all(),
            'employeesList' => $this->employees->search([], 1, 500)['data'],
            'rows' => $report['rows'],
            'totalGross' => $report['total_gross'],
            'totalNet' => $report['total_net'],
            'filters' => [
                'employee_id' => (string) $request->query('employee_id', ''),
                'department_id' => (string) $request->query('department_id', ''),
                'period_start' => (string) $request->query('period_start', ''),
                'period_end' => (string) $request->query('period_end', ''),
            ],
            'generatedBy' => AuthService::getFullName(),
        ]);
    }
}
