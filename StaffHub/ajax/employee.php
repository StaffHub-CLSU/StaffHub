<?php
/**
 * ajax/employee.php
 * Admin-only. Handles: list (search/filter/paginate), get (single record for
 * edit modal), create, update, delete, toggle_status, upload_picture.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

Authentication::requireLogin(null, true);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Only 'upload_picture' may be used by a logged-in employee for their own
// record; every other action in this file is admin-only.
if ($action !== 'upload_picture') {
    Authentication::requireLogin('admin', true);
}

$employeeModel = new Employee();
$userModel = new User();
$logger = new Logger();

switch ($action) {

    case 'list': {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'department_id' => $_GET['department_id'] ?? '',
            'employment_status' => $_GET['employment_status'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        $sortBy = $_GET['sort_by'] ?? '';
        $sortDir = $_GET['sort_dir'] ?? 'desc';
        $result = $employeeModel->getAll(array_filter($filters, fn($v) => $v !== ''), $page, 8, $sortBy, $sortDir);
        echo json_encode(['success' => true] + $result);
        break;
    }

    case 'get': {
        $id = (int) ($_GET['employee_id'] ?? 0);
        $employee = $employeeModel->getById($id);
        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'employee' => $employee]);
        break;
    }

    case 'create': {
        [$data, $validator] = validateEmployeePayload($_POST, true, $employeeModel, $userModel);

        if ($validator->fails()) {
            echo json_encode(['success' => false, 'errors' => $validator->getErrors(), 'message' => $validator->firstError()]);
            exit;
        }

        try {
            $newId = $employeeModel->create($data);
            $logger->log(Authentication::getUserId(), "Created employee #{$newId} ({$data['first_name']} {$data['last_name']})");
            echo json_encode(['success' => true, 'message' => 'Employee added successfully.', 'employee_id' => $newId]);
        } catch (Exception $e) {
            error_log('Employee create failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create employee. The username or email may already be in use.']);
        }
        break;
    }

    case 'update': {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $existing = $employeeModel->getById($employeeId);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }

        [$data, $validator] = validateEmployeePayload($_POST, false, $employeeModel, $userModel, $employeeId);

        if ($validator->fails()) {
            echo json_encode(['success' => false, 'errors' => $validator->getErrors(), 'message' => $validator->firstError()]);
            exit;
        }

        $employeeModel->update($employeeId, $data);

        // Optional password change
        if (!empty($_POST['new_password'])) {
            $userModel->updatePassword((int) $existing['user_id'], $_POST['new_password']);
        }

        $logger->log(Authentication::getUserId(), "Updated employee #{$employeeId}");
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully.']);
        break;
    }

    case 'delete': {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $existing = $employeeModel->getById($employeeId);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }
        $success = $employeeModel->delete($employeeId);
        if ($success) {
            $logger->log(Authentication::getUserId(), "Deleted employee #{$employeeId} ({$existing['first_name']} {$existing['last_name']})");
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Employee deleted.' : 'Failed to delete employee.']);
        break;
    }

    case 'toggle_status': {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $active = ($_POST['active'] ?? '1') === '1';
        $success = $employeeModel->setActive($employeeId, $active);
        if ($success) {
            $logger->log(Authentication::getUserId(), "Set employee #{$employeeId} to " . ($active ? 'Active' : 'Inactive'));
        }
        echo json_encode(['success' => $success, 'message' => $success ? 'Status updated.' : 'Failed to update status.']);
        break;
    }

    case 'check_username': {
        $exists = $userModel->usernameExists(trim($_GET['username'] ?? ''), !empty($_GET['exclude_user_id']) ? (int) $_GET['exclude_user_id'] : null);
        echo json_encode(['success' => true, 'exists' => $exists]);
        break;
    }

    case 'check_email': {
        $exists = $employeeModel->emailExists(trim($_GET['email'] ?? ''), !empty($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : null);
        echo json_encode(['success' => true, 'exists' => $exists]);
        break;
    }

    case 'upload_picture': {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $existing = $employeeModel->getById($employeeId);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }

        // Employees may only update their own picture; admins may update any.
        if (!Authentication::isAdmin() && Authentication::getEmployeeId() !== $employeeId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You may only update your own profile picture.']);
            exit;
        }
        if (empty($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No valid file uploaded.']);
            exit;
        }

        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);

        if (!isset($allowed[$mime])) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.']);
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image must be smaller than 2MB.']);
            exit;
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $filename = 'emp_' . $employeeId . '_' . time() . '.' . $allowed[$mime];
        $destination = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
            exit;
        }

        // Clean up previous picture
        if (!empty($existing['profile_picture']) && file_exists(UPLOAD_DIR . $existing['profile_picture'])) {
            @unlink(UPLOAD_DIR . $existing['profile_picture']);
        }

        $employeeModel->updateProfilePicture($employeeId, $filename);
        echo json_encode(['success' => true, 'message' => 'Profile picture updated.', 'filename' => $filename, 'url' => UPLOAD_URL . $filename]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}

/**
 * Shared validation for create/update employee payloads.
 * Returns [dataArray, Validator]
 */
function validateEmployeePayload(array $post, bool $isCreate, Employee $employeeModel, User $userModel, ?int $excludeId = null): array
{
    $validator = new Validator();

    $data = [
        'first_name' => trim($post['first_name'] ?? ''),
        'last_name' => trim($post['last_name'] ?? ''),
        'middle_name' => trim($post['middle_name'] ?? ''),
        'gender' => $post['gender'] ?? '',
        'birthdate' => $post['birthdate'] ?? '',
        'email' => trim($post['email'] ?? ''),
        'contact_number' => trim($post['contact_number'] ?? ''),
        'address' => trim($post['address'] ?? ''),
        'department_id' => $post['department_id'] ?? '',
        'position_id' => $post['position_id'] ?? '',
        'employment_status' => $post['employment_status'] ?? 'Full-Time',
        'basic_hourly_rate' => $post['basic_hourly_rate'] ?? '',
        'date_hired' => $post['date_hired'] ?? '',
    ];

    $validator->required($data['first_name'], 'first_name', 'First name')
              ->required($data['last_name'], 'last_name', 'Last name')
              ->required($data['gender'], 'gender', 'Gender')
              ->required($data['birthdate'], 'birthdate', 'Birthdate')
              ->date($data['birthdate'], 'birthdate', 'Birthdate')
              ->required($data['email'], 'email', 'Email')
              ->email($data['email'], 'email')
              ->required($data['contact_number'], 'contact_number', 'Contact number')
              ->required($data['employment_status'], 'employment_status', 'Employment status')
              ->required($data['basic_hourly_rate'], 'basic_hourly_rate', 'Hourly rate')
              ->numeric($data['basic_hourly_rate'], 'basic_hourly_rate', 'Hourly rate')
              ->min($data['basic_hourly_rate'], 0, 'basic_hourly_rate', 'Hourly rate')
              ->required($data['date_hired'], 'date_hired', 'Date hired')
              ->date($data['date_hired'], 'date_hired', 'Date hired');

    if ($employeeModel->emailExists($data['email'], $excludeId)) {
        $validator->addError('email', 'This email address is already registered to another employee.');
    }

    if ($isCreate) {
        $data['username'] = trim($post['username'] ?? '');
        $data['password'] = $post['password'] ?? '';

        $validator->required($data['username'], 'username', 'Username')
                  ->required($data['password'], 'password', 'Password')
                  ->passwordStrength($data['password'], 'password');

        if ($data['username'] !== '' && $userModel->usernameExists($data['username'])) {
            $validator->addError('username', 'This username is already taken.');
        }
    }

    return [$data, $validator];
}
