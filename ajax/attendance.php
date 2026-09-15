<?php
/**
 * ajax/attendance.php
 * Handles: clock_in, clock_out (employee), verify / verify_range (admin),
 * and paginated/filtered attendance list fetch (admin).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

Authentication::requireLogin(null, true);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$attendanceModel = new Attendance();
$logger = new Logger();

switch ($action) {

    case 'clock_in': {
        $employeeId = Authentication::getEmployeeId();
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'Only employees can clock in.']);
            exit;
        }
        $result = $attendanceModel->clockIn($employeeId);
        if ($result['success']) {
            $logger->log(Authentication::getUserId(), 'Clocked in');
        }
        echo json_encode($result);
        break;
    }

    case 'clock_out': {
        $employeeId = Authentication::getEmployeeId();
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'Only employees can clock out.']);
            exit;
        }
        $result = $attendanceModel->clockOut($employeeId);
        if ($result['success']) {
            $logger->log(Authentication::getUserId(), 'Clocked out');
        }
        echo json_encode($result);
        break;
    }

    case 'verify': {
        Authentication::requireLogin('admin', true);
        $attendanceId = (int) ($_POST['attendance_id'] ?? 0);
        $success = $attendanceModel->verify($attendanceId);
        if ($success) {
            $logger->log(Authentication::getUserId(), "Verified attendance record #$attendanceId");
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Attendance record verified.' : 'Unable to verify this record (it may not be Completed yet).']);
        break;
    }

    case 'verify_range': {
        Authentication::requireLogin('admin', true);
        $start = $_POST['date_from'] ?? '';
        $end = $_POST['date_to'] ?? '';
        $validator = new Validator();
        $validator->required($start, 'date_from', 'Start date')->required($end, 'date_to', 'End date');
        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }
        $count = $attendanceModel->verifyRange($start, $end);
        $logger->log(Authentication::getUserId(), "Bulk-verified $count attendance records ($start to $end)");
        echo json_encode(['success' => true, 'message' => "$count record(s) verified.", 'count' => $count]);
        break;
    }

    case 'list': {
        Authentication::requireLogin('admin', true);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'date' => $_GET['date'] ?? '',
            'month' => $_GET['month'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        $result = $attendanceModel->search(array_filter($filters), $page, 10);

        // Render badge + row HTML server-side to keep the AJAX response simple for the front-end.
        foreach ($result['data'] as &$row) {
            $row['status_badge'] = badgeForStatus($row['status']);
            $row['employee_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $row['time_in_fmt'] = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '—';
            $row['time_out_fmt'] = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '—';
            $row['date_fmt'] = date('M d, Y', strtotime($row['attendance_date']));
        }

        echo json_encode(['success' => true] + $result);
        break;
    }

    case 'today_status': {
        $employeeId = Authentication::getEmployeeId();
        if (!$employeeId) {
            echo json_encode(['success' => false]);
            exit;
        }
        $today = $attendanceModel->getTodayRecord($employeeId);
        echo json_encode(['success' => true, 'today' => $today]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}

function badgeForStatus(string $status): string
{
    return match ($status) {
        'Verified' => 'sh-badge-verified',
        'Completed' => 'sh-badge-completed',
        default => 'sh-badge-incomplete',
    };
}
