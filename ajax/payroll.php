<?php
/**
 * ajax/payroll.php
 * Admin-only. Handles: preview (live computation before commit),
 * process (single employee), process_batch (all active employees),
 * and paginated list fetch.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

Authentication::requireLogin('admin', true);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$payrollModel = new Payroll();
$logger = new Logger();

switch ($action) {

    case 'preview': {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $start = $_GET['period_start'] ?? '';
        $end = $_GET['period_end'] ?? '';
        $bonuses = (float) ($_GET['bonuses'] ?? 0);
        $deductions = (float) ($_GET['deductions'] ?? 0);

        $validator = new Validator();
        $validator->required($employeeId, 'employee_id', 'Employee')
                  ->required($start, 'period_start', 'Period start')
                  ->required($end, 'period_end', 'Period end');

        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }

        echo json_encode($payrollModel->preview($employeeId, $start, $end, $bonuses, $deductions));
        break;
    }

    case 'process': {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $start = $_POST['period_start'] ?? '';
        $end = $_POST['period_end'] ?? '';
        $bonuses = (float) ($_POST['bonuses'] ?? 0);
        $deductions = (float) ($_POST['deductions'] ?? 0);

        $validator = new Validator();
        $validator->required($employeeId, 'employee_id', 'Employee')
                  ->required($start, 'period_start', 'Period start')
                  ->required($end, 'period_end', 'Period end');

        if (!empty($start) && !empty($end) && strtotime($start) > strtotime($end)) {
            $validator->addError('period_end', 'Period end date must be after the start date.');
        }

        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }

        $result = $payrollModel->process($employeeId, $start, $end, $bonuses, $deductions, Authentication::getUserId());
        if ($result['success']) {
            $logger->log(Authentication::getUserId(), "Processed payroll for employee #{$employeeId} ({$start} to {$end})");
        }
        echo json_encode($result);
        break;
    }

    case 'process_batch': {
        $start = $_POST['period_start'] ?? '';
        $end = $_POST['period_end'] ?? '';

        $validator = new Validator();
        $validator->required($start, 'period_start', 'Period start')
                  ->required($end, 'period_end', 'Period end');

        if (!empty($start) && !empty($end) && strtotime($start) > strtotime($end)) {
            $validator->addError('period_end', 'Period end date must be after the start date.');
        }

        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }

        $result = $payrollModel->processBatch($start, $end, Authentication::getUserId());
        $logger->log(Authentication::getUserId(), "Ran batch payroll for {$result['processed']} employee(s) ({$start} to {$end})");
        echo json_encode(['success' => true, 'message' => "Payroll processed for {$result['processed']} employee(s). {$result['skipped']} skipped (no verified hours).", 'result' => $result]);
        break;
    }

    case 'list': {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = array_filter([
            'employee_id' => $_GET['employee_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'period_start' => $_GET['period_start'] ?? '',
            'period_end' => $_GET['period_end'] ?? '',
        ]);
        $result = $payrollModel->search($filters, $page, 10);
        echo json_encode(['success' => true] + $result);
        break;
    }

    case 'get': {
        $id = (int) ($_GET['payroll_id'] ?? 0);
        $record = $payrollModel->getById($id);
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Payroll record not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'payroll' => $record]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
