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

final class AttendanceReportController extends Controller
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
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'status' => (string) $request->query('status', ''),
        ], static fn ($v) => $v !== '');

        return $this->render('reports/attendance', [
            'pageTitle' => 'Attendance Report',
            'activeNav' => 'report_attendance',
            'departments' => $this->departments->all(),
            'employeesList' => $this->employees->search([], 1, 500)['data'],
            'rows' => $this->reports->attendanceReport($filters),
            'filters' => [
                'employee_id' => (string) $request->query('employee_id', ''),
                'department_id' => (string) $request->query('department_id', ''),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
                'status' => (string) $request->query('status', ''),
            ],
            'generatedBy' => AuthService::getFullName(),
        ]);
    }
}
