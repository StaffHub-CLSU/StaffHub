<?php

declare(strict_types=1);

namespace StaffHub\Controller\Employee;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Service\AttendanceService;

final class AttendanceController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly AttendanceService $attendance,
    ) {
        parent::__construct($container);
    }

    public function history(Request $request): Response
    {
        $employeeId = (int) AuthService::getEmployeeId();
        $page = max(1, (int) $request->query('page', 1));

        $filters = array_filter([
            'employee_id' => $employeeId,
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'status' => (string) $request->query('status', ''),
        ], static fn ($v) => $v !== '' && $v !== null);

        $result = $this->attendance->search($filters, $page, 12);

        return $this->render('employee/attendance_history', [
            'pageTitle' => 'Attendance History',
            'activeNav' => 'attendance',
            'result' => $result,
            'page' => $page,
            'filters' => [
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
                'status' => (string) $request->query('status', ''),
            ],
        ]);
    }
}
