<?php

declare(strict_types=1);

namespace StaffHub\Controller\Api;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\UserRepositoryInterface;
use StaffHub\Service\EmployeeService;
use StaffHub\Service\LoggerInterface;
use StaffHub\Service\ProfileService;

/**
 * JSON API for employee CRUD + avatar upload (formerly ajax/employee.php).
 */
final class EmployeeApiController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly EmployeeService $employeeService,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly UserRepositoryInterface $users,
        private readonly ProfileService $profiles,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($container);
    }

    public function handle(Request $request): Response
    {
        $action = $request->string('action');

        // upload_picture may be used by a logged-in employee for their own record;
        // every other action is admin-only (enforced by route middleware for admin
        // routes — this endpoint is auth-any, so re-check here).
        if ($action !== 'upload_picture' && !AuthService::isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        return match ($action) {
            'list' => $this->list($request),
            'get' => $this->get($request),
            'create' => $this->create($request),
            'update' => $this->update($request),
            'delete' => $this->delete($request),
            'toggle_status' => $this->toggleStatus($request),
            'check_username' => $this->checkUsername($request),
            'check_email' => $this->checkEmail($request),
            'upload_picture' => $this->uploadPicture($request),
            default => Response::json(['success' => false, 'message' => 'Unknown action.'], 400),
        };
    }

    private function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $filters = array_filter([
            'search' => trim((string) $request->query('search', '')),
            'department_id' => (string) $request->query('department_id', ''),
            'employment_status' => (string) $request->query('employment_status', ''),
            'status' => (string) $request->query('status', ''),
        ], static fn ($v) => $v !== '');

        $result = $this->employeeService->list(
            $filters,
            $page,
            8,
            (string) $request->query('sort_by', ''),
            (string) $request->query('sort_dir', 'desc'),
        );

        return Response::json(['success' => true] + $result);
    }

    private function get(Request $request): Response
    {
        $employee = $this->employees->findJoined((int) $request->query('employee_id', 0));
        if (!$employee) {
            return Response::json(['success' => false, 'message' => 'Employee not found.']);
        }
        return Response::json(['success' => true, 'employee' => $employee]);
    }

    private function create(Request $request): Response
    {
        [$data, $validator] = $this->employeeService->validatePayload($request->all(), true);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'errors' => $validator->getErrors(),
                'message' => $validator->firstError(),
            ]);
        }

        try {
            $newId = $this->employeeService->create($data);
            $this->logger->log(
                AuthService::getUserId(),
                "Created employee #{$newId} ({$data['first_name']} {$data['last_name']})",
            );
            return Response::json([
                'success' => true,
                'message' => 'Employee added successfully.',
                'employee_id' => $newId,
            ]);
        } catch (\Exception $e) {
            error_log('Employee create failed: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Failed to create employee. The username or email may already be in use.',
            ]);
        }
    }

    private function update(Request $request): Response
    {
        $employeeId = (int) $request->input('employee_id', 0);
        $existing = $this->employees->findJoined($employeeId);
        if (!$existing) {
            return Response::json(['success' => false, 'message' => 'Employee not found.']);
        }

        [$data, $validator] = $this->employeeService->validatePayload($request->all(), false, $employeeId);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'errors' => $validator->getErrors(),
                'message' => $validator->firstError(),
            ]);
        }

        $newPassword = (string) $request->input('new_password', '');
        $this->employeeService->updateWithOptionalPassword(
            $employeeId,
            $data,
            $newPassword !== '' ? $newPassword : null,
            (int) $existing['user_id'],
        );

        $this->logger->log(AuthService::getUserId(), "Updated employee #{$employeeId}");
        return Response::json(['success' => true, 'message' => 'Employee updated successfully.']);
    }

    private function delete(Request $request): Response
    {
        $employeeId = (int) $request->input('employee_id', 0);
        $existing = $this->employees->findJoined($employeeId);
        if (!$existing) {
            return Response::json(['success' => false, 'message' => 'Employee not found.']);
        }

        $success = $this->employeeService->delete($employeeId);
        if ($success) {
            $this->logger->log(
                AuthService::getUserId(),
                "Deleted employee #{$employeeId} ({$existing['first_name']} {$existing['last_name']})",
            );
        }

        return Response::json([
            'success' => $success,
            'message' => $success ? 'Employee deleted.' : 'Failed to delete employee.',
        ]);
    }

    private function toggleStatus(Request $request): Response
    {
        $employeeId = (int) $request->input('employee_id', 0);
        $active = ((string) $request->input('active', '1')) === '1';
        $success = $this->employeeService->setActive($employeeId, $active);

        if ($success) {
            $this->logger->log(
                AuthService::getUserId(),
                "Set employee #{$employeeId} to " . ($active ? 'Active' : 'Inactive'),
            );
        }

        return Response::json([
            'success' => $success,
            'message' => $success ? 'Status updated.' : 'Failed to update status.',
        ]);
    }

    private function checkUsername(Request $request): Response
    {
        $exclude = $request->query('exclude_user_id');
        $exists = $this->users->usernameExists(
            trim((string) $request->query('username', '')),
            !empty($exclude) ? (int) $exclude : null,
        );
        return Response::json(['success' => true, 'exists' => $exists]);
    }

    private function checkEmail(Request $request): Response
    {
        $exclude = $request->query('exclude_id');
        $exists = $this->employees->emailExists(
            trim((string) $request->query('email', '')),
            !empty($exclude) ? (int) $exclude : null,
        );
        return Response::json(['success' => true, 'exists' => $exists]);
    }

    private function uploadPicture(Request $request): Response
    {
        $employeeId = (int) $request->input('employee_id', 0);
        $existing = $this->employees->findJoined($employeeId);
        if (!$existing) {
            return Response::json(['success' => false, 'message' => 'Employee not found.']);
        }

        $file = $request->file('profile_picture') ?? [];
        $result = $this->profiles->uploadPicture(
            $employeeId,
            $file,
            $existing,
            AuthService::isAdmin(),
            AuthService::getEmployeeId(),
        );

        $status = $result['status'] ?? 200;
        unset($result['status']);
        return Response::json($result, $result['success'] ? 200 : $status);
    }
}
