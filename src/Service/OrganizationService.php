<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Repository\Contract\DepartmentRepositoryInterface;
use StaffHub\Repository\Contract\PositionRepositoryInterface;
use StaffHub\Support\Validator;

/**
 * Department + position business rules (name uniqueness, validation).
 */
final class OrganizationService
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $departments,
        private readonly PositionRepositoryInterface $positions,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function departments(): array
    {
        return $this->departments->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function positions(): array
    {
        return $this->positions->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function positionsByDepartment(int $departmentId): array
    {
        return $departmentId > 0
            ? $this->positions->byDepartment($departmentId)
            : $this->positions->all();
    }

    /** @return array{success: bool, message: string, department_id?: int} */
    public function createDepartment(string $name, string $description, ?int $userId): array
    {
        $validator = new Validator();
        $validator->required($name, 'department_name', 'Department name');
        if ($this->departments->nameExists($name)) {
            $validator->addError('department_name', 'A department with this name already exists.');
        }
        if ($validator->fails()) {
            return ['success' => false, 'message' => (string) $validator->firstError()];
        }

        $id = $this->departments->create($name, $description);
        $this->logger->log($userId, "Created department: {$name}");
        return ['success' => true, 'message' => 'Department added.', 'department_id' => $id];
    }

    /** @return array{success: bool, message: string} */
    public function updateDepartment(int $id, string $name, string $description, ?int $userId): array
    {
        $validator = new Validator();
        $validator->required($name, 'department_name', 'Department name');
        if ($this->departments->nameExists($name, $id)) {
            $validator->addError('department_name', 'A department with this name already exists.');
        }
        if ($validator->fails()) {
            return ['success' => false, 'message' => (string) $validator->firstError()];
        }

        $this->departments->update($id, $name, $description);
        $this->logger->log($userId, "Updated department #{$id}");
        return ['success' => true, 'message' => 'Department updated.'];
    }

    /** @return array{success: bool, message: string} */
    public function deleteDepartment(int $id): array
    {
        $ok = $this->departments->delete($id);
        return [
            'success' => $ok,
            'message' => $ok
                ? 'Department deleted.'
                : 'Unable to delete this department. Remove or reassign its employees/positions first.',
        ];
    }

    /** @return array{success: bool, message: string, position_id?: int} */
    public function createPosition(string $name, ?int $departmentId, ?int $userId): array
    {
        $validator = new Validator();
        $validator->required($name, 'position_name', 'Position name');
        if ($validator->fails()) {
            return ['success' => false, 'message' => (string) $validator->firstError()];
        }

        $id = $this->positions->create($name, $departmentId);
        $this->logger->log($userId, "Created position: {$name}");
        return ['success' => true, 'message' => 'Position added.', 'position_id' => $id];
    }

    /** @return array{success: bool, message: string} */
    public function updatePosition(int $id, string $name, ?int $departmentId, ?int $userId): array
    {
        $validator = new Validator();
        $validator->required($name, 'position_name', 'Position name');
        if ($validator->fails()) {
            return ['success' => false, 'message' => (string) $validator->firstError()];
        }

        $this->positions->update($id, $name, $departmentId);
        $this->logger->log($userId, "Updated position #{$id}");
        return ['success' => true, 'message' => 'Position updated.'];
    }

    /** @return array{success: bool, message: string} */
    public function deletePosition(int $id): array
    {
        $ok = $this->positions->delete($id);
        return [
            'success' => $ok,
            'message' => $ok
                ? 'Position deleted.'
                : 'Unable to delete this position. Remove or reassign its employees first.',
        ];
    }
}
