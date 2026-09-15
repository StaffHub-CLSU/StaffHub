<?php
/**
 * ajax/search.php
 * Lightweight lookups used throughout the UI: dashboard stat refresh,
 * dependent "positions by department" dropdown, and quick employee search
 * (e.g. for the payroll employee picker).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

Authentication::requireLogin(null, true);

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'dashboard_stats': {
        Authentication::requireLogin('admin', true);
        $dashboard = new Dashboard();
        echo json_encode([
            'success' => true,
            'stats' => $dashboard->getAdminStats(),
            'recent_activity' => $dashboard->getRecentActivity(8),
            'recent_clock_ins' => $dashboard->getRecentClockIns(5),
            'recent_clock_outs' => $dashboard->getRecentClockOuts(5),
        ]);
        break;
    }

    case 'positions_by_department': {
        $departmentId = (int) ($_GET['department_id'] ?? 0);
        $positionModel = new Position();
        $positions = $departmentId > 0 ? $positionModel->getByDepartment($departmentId) : $positionModel->getAll();
        echo json_encode(['success' => true, 'positions' => $positions]);
        break;
    }

    case 'employees_quick': {
        Authentication::requireLogin('admin', true);
        $term = trim($_GET['term'] ?? '');
        $employeeModel = new Employee();
        $result = $employeeModel->getAll($term ? ['search' => $term] : [], 1, 20);
        echo json_encode(['success' => true, 'employees' => $result['data']]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
