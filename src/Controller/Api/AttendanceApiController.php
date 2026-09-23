<?php

declare(strict_types=1);

namespace StaffHub\Controller\Api;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Service\AttendanceService;
use StaffHub\Service\LoggerInterface;

/**
 * JSON API for clock in/out, verification, attendance list (formerly ajax/attendance.php).
 */
final class AttendanceApiController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly AttendanceService $attendance,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($container);
    }

    public function handle(Request $request): Response
    {
        $action = $request->string('action');

        // Admin-only actions
        if (in_array($action, ['verify', 'verify_range', 'list'], true) && !AuthService::isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        return match ($action) {
            'clock_in' => $this->clockIn(),
            'clock_out' => $this->clockOut(),
            'verify' => $this->verify($request),
            'verify_range' => $this->verifyRange($request),
            'list' => $this->list($request),
            'today_status' => $this->todayStatus(),
            default => Response::json(['success' => false, 'message' => 'Unknown action.'], 400),
        };
    }

    private function clockIn(): Response
    {
        $employeeId = AuthService::getEmployeeId();
        if (!$employeeId) {
            return Response::json(['success' => false, 'message' => 'Only employees can clock in.']);
        }

        $result = $this->attendance->clockIn($employeeId);
        if ($result['success']) {
            $this->logger->log(AuthService::getUserId(), 'Clocked in');
        }
        return Response::json($result);
    }

    private function clockOut(): Response
    {
        $employeeId = AuthService::getEmployeeId();
        if (!$employeeId) {
            return Response::json(['success' => false, 'message' => 'Only employees can clock out.']);
        }

        $result = $this->attendance->clockOut($employeeId);
        if ($result['success']) {
            $this->logger->log(AuthService::getUserId(), 'Clocked out');
        }
        return Response::json($result);
    }

    private function verify(Request $request): Response
    {
        $attendanceId = (int) $request->input('attendance_id', 0);
        $success = $this->attendance->verify($attendanceId);

        if ($success) {
            $this->logger->log(AuthService::getUserId(), "Verified attendance record #{$attendanceId}");
        }

        return Response::json([
            'success' => $success,
            'message' => $success
                ? 'Attendance record verified.'
                : 'Unable to verify this record (it may not be Completed yet).',
        ]);
    }

    private function verifyRange(Request $request): Response
    {
        $result = $this->attendance->verifyRange(
            $request->string('date_from'),
            $request->string('date_to'),
        );

        if ($result['success']) {
            $this->logger->log(
                AuthService::getUserId(),
                "Bulk-verified {$result['count']} attendance records ({$request->string('date_from')} to {$request->string('date_to')})",
            );
        }

        return Response::json($result);
    }

    private function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $filters = array_filter([
            'employee_id' => (string) $request->query('employee_id', ''),
            'department_id' => (string) $request->query('department_id', ''),
            'date' => (string) $request->query('date', ''),
            'month' => (string) $request->query('month', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'status' => (string) $request->query('status', ''),
        ]);

        $result = $this->attendance->search($filters, $page, 10);

        foreach ($result['data'] as &$row) {
            $row['status_badge'] = match ($row['status']) {
                'Verified' => 'sh-badge-verified',
                'Completed' => 'sh-badge-completed',
                default => 'sh-badge-incomplete',
            };
            $row['employee_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $row['time_in_fmt'] = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '—';
            $row['time_out_fmt'] = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '—';
            $row['date_fmt'] = date('M d, Y', strtotime($row['attendance_date']));
        }
        unset($row);

        return Response::json(['success' => true] + $result);
    }

    private function todayStatus(): Response
    {
        $employeeId = AuthService::getEmployeeId();
        if (!$employeeId) {
            return Response::json(['success' => false]);
        }

        $today = $this->attendance->today($employeeId);
        return Response::json([
            'success' => true,
            'today' => $today ? $today->toArray() : null,
        ]);
    }
}
