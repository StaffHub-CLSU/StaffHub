<?php

declare(strict_types=1);

namespace StaffHub\Controller\Report;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\DepartmentRepositoryInterface;
use StaffHub\Service\ReportService;

final class EmployeeReportController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly ReportService $reports,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request): Response
    {
        $filters = array_filter([
            'department_id' => (string) $request->query('department_id', ''),
            'employment_status' => (string) $request->query('employment_status', ''),
            'status' => (string) $request->query('status', ''),
        ], static fn ($v) => $v !== '');

        return $this->render('reports/employees', [
            'pageTitle' => 'Employee List',
            'activeNav' => 'report_employees',
            'departments' => $this->departments->all(),
            'rows' => $this->reports->employeeReport($filters),
            'filters' => [
                'department_id' => (string) $request->query('department_id', ''),
                'employment_status' => (string) $request->query('employment_status', ''),
                'status' => (string) $request->query('status', ''),
            ],
            'generatedBy' => AuthService::getFullName(),
        ]);
    }
}
