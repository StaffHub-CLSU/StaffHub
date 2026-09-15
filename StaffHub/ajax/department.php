<?php
/**
 * ajax/department.php
 * Admin-only. CRUD for departments and positions (used by departments.php).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

Authentication::requireLogin('admin', true);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$departmentModel = new Department();
$positionModel = new Position();
$logger = new Logger();

switch ($action) {

    case 'list_departments': {
        echo json_encode(['success' => true, 'departments' => $departmentModel->getAll()]);
        break;
    }

    case 'list_positions': {
        echo json_encode(['success' => true, 'positions' => $positionModel->getAll()]);
        break;
    }

    case 'create_department': {
        $name = trim($_POST['department_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $validator = new Validator();
        $validator->required($name, 'department_name', 'Department name');
        if ($departmentModel->nameExists($name)) {
            $validator->addError('department_name', 'A department with this name already exists.');
        }
        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }
        $id = $departmentModel->create($name, $description);
        $logger->log(Authentication::getUserId(), "Created department: $name");
        echo json_encode(['success' => true, 'message' => 'Department added.', 'department_id' => $id]);
        break;
    }

    case 'update_department': {
        $id = (int) ($_POST['department_id'] ?? 0);
        $name = trim($_POST['department_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $validator = new Validator();
        $validator->required($name, 'department_name', 'Department name');
        if ($departmentModel->nameExists($name, $id)) {
            $validator->addError('department_name', 'A department with this name already exists.');
        }
        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }
        $departmentModel->update($id, $name, $description);
        $logger->log(Authentication::getUserId(), "Updated department #$id");
        echo json_encode(['success' => true, 'message' => 'Department updated.']);
        break;
    }

    case 'delete_department': {
        $id = (int) ($_POST['department_id'] ?? 0);
        $success = $departmentModel->delete($id);
        echo json_encode(['success' => $success, 'message' => $success ? 'Department deleted.' : 'Unable to delete this department. Remove or reassign its employees/positions first.']);
        break;
    }

    case 'create_position': {
        $name = trim($_POST['position_name'] ?? '');
        $deptId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
        $validator = new Validator();
        $validator->required($name, 'position_name', 'Position name');
        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }
        $id = $positionModel->create($name, $deptId);
        $logger->log(Authentication::getUserId(), "Created position: $name");
        echo json_encode(['success' => true, 'message' => 'Position added.', 'position_id' => $id]);
        break;
    }

    case 'update_position': {
        $id = (int) ($_POST['position_id'] ?? 0);
        $name = trim($_POST['position_name'] ?? '');
        $deptId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
        $validator = new Validator();
        $validator->required($name, 'position_name', 'Position name');
        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->firstError()]);
            exit;
        }
        $positionModel->update($id, $name, $deptId);
        $logger->log(Authentication::getUserId(), "Updated position #$id");
        echo json_encode(['success' => true, 'message' => 'Position updated.']);
        break;
    }

    case 'delete_position': {
        $id = (int) ($_POST['position_id'] ?? 0);
        $success = $positionModel->delete($id);
        echo json_encode(['success' => $success, 'message' => $success ? 'Position deleted.' : 'Unable to delete this position. Remove or reassign its employees first.']);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
