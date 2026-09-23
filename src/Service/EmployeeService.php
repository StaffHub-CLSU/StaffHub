<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Core\Database;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\UserRepositoryInterface;
use StaffHub\Support\Validator;

/**
 * Employee business rules: validation + transactional create/update/delete.
 */
final class EmployeeService
{
    public function __construct(
        private readonly Database $db,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly UserRepositoryInterface $users,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Shared validation for create/update employee payloads.
     *
     * @return array{0: array<string, mixed>, 1: Validator}
     */
    public function validatePayload(array $post, bool $isCreate, ?int $excludeId = null): array
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

        if ($this->employees->emailExists($data['email'], $excludeId)) {
            $validator->addError('email', 'This email address is already registered to another employee.');
        }

        if ($isCreate) {
            $data['username'] = trim($post['username'] ?? '');
            $data['password'] = $post['password'] ?? '';

            $validator->required($data['username'], 'username', 'Username')
                ->required($data['password'], 'password', 'Password')
                ->passwordStrength($data['password'], 'password');

            if ($data['username'] !== '' && $this->users->usernameExists($data['username'])) {
                $validator->addError('username', 'This username is already taken.');
            }
        }

        return [$data, $validator];
    }

    /**
     * Create linked User + Employee inside one transaction.
     *
     * @throws \Exception
     */
    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $userId = $this->users->create($data['username'], $data['password'], 'employee');

            $employeeId = $this->employees->insert([
                'employee_code' => $this->employees->generateNextCode(),
                'user_id' => $userId,
                'department_id' => $data['department_id'] ?: null,
                'position_id' => $data['position_id'] ?: null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?: null,
                'gender' => $data['gender'],
                'birthdate' => $data['birthdate'],
                'email' => $data['email'],
                'contact_number' => $data['contact_number'],
                'address' => $data['address'] ?? null,
                'employment_status' => $data['employment_status'],
                'basic_hourly_rate' => $data['basic_hourly_rate'],
                'date_hired' => $data['date_hired'],
                'profile_picture' => $data['profile_picture'] ?? null,
                'is_active' => 1,
            ]);

            $this->db->commit();
            return $employeeId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $employeeId, array $data): bool
    {
        return $this->employees->update($employeeId, $data);
    }

    public function updateWithOptionalPassword(int $employeeId, array $data, ?string $newPassword, int $userId): bool
    {
        $ok = $this->employees->update($employeeId, $data);
        if ($ok && $newPassword !== null && $newPassword !== '') {
            $this->users->updatePassword($userId, $newPassword);
        }
        return $ok;
    }

    public function setActive(int $employeeId, bool $active): bool
    {
        $row = $this->employees->findJoined($employeeId);
        if (!$row) {
            return false;
        }
        $this->employees->setActive($employeeId, $active);
        return $this->users->setActive((int) $row['user_id'], $active);
    }

    public function delete(int $employeeId): bool
    {
        $row = $this->employees->findJoined($employeeId);
        if (!$row) {
            return false;
        }
        // Cascades employee/attendance/payroll via FK on users delete.
        return $this->users->delete((int) $row['user_id']);
    }

    /** @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int, sort_by: string, sort_dir: string} */
    public function list(array $filters, int $page, int $perPage, string $sortBy, string $sortDir): array
    {
        return $this->employees->search($filters, $page, $perPage, $sortBy, $sortDir);
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }
}
