<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Entity\Employee;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;

final class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function findJoined(int $employeeId): ?array
    {
        $sql = 'SELECT e.*, d.department_name, p.position_name, u.username
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN users u ON e.user_id = u.user_id
                WHERE e.employee_id = ?';
        return $this->db->fetch($sql, [$employeeId]);
    }

    public function findByUserId(int $userId): ?array
    {
        $sql = 'SELECT e.*, d.department_name, p.position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE e.user_id = ?';
        return $this->db->fetch($sql, [$userId]);
    }

    public function findEntity(int $employeeId): ?Employee
    {
        $row = $this->db->fetch('SELECT * FROM employees WHERE employee_id = ?', [$employeeId]);
        return $row ? Employee::fromRow($row) : null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT employee_id FROM employees WHERE email = ?';
        $params = [$email];
        if ($excludeId !== null) {
            $sql .= ' AND employee_id != ?';
            $params[] = $excludeId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    public function generateNextCode(): string
    {
        $row = $this->db->fetch('SELECT employee_code FROM employees ORDER BY employee_id DESC LIMIT 1');
        if (!$row) {
            return 'EMP-0001';
        }
        $lastNumber = (int) substr($row['employee_code'], 4);
        return 'EMP-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    public function insert(array $columns): int
    {
        $sql = 'INSERT INTO employees
                    (employee_code, user_id, department_id, position_id, first_name, last_name,
                     middle_name, gender, birthdate, email, contact_number, address,
                     employment_status, basic_hourly_rate, date_hired, profile_picture, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

        return (int) $this->db->insert($sql, [
            $columns['employee_code'],
            $columns['user_id'],
            $columns['department_id'] ?? null,
            $columns['position_id'] ?? null,
            $columns['first_name'],
            $columns['last_name'],
            $columns['middle_name'] ?? null,
            $columns['gender'],
            $columns['birthdate'],
            $columns['email'],
            $columns['contact_number'],
            $columns['address'] ?? null,
            $columns['employment_status'],
            $columns['basic_hourly_rate'],
            $columns['date_hired'],
            $columns['profile_picture'] ?? null,
            $columns['is_active'] ?? 1,
        ]);
    }

    public function update(int $employeeId, array $data): bool
    {
        $sql = 'UPDATE employees SET
                    department_id = ?, position_id = ?, first_name = ?, last_name = ?,
                    middle_name = ?, gender = ?, birthdate = ?, email = ?, contact_number = ?,
                    address = ?, employment_status = ?, basic_hourly_rate = ?, date_hired = ?
                WHERE employee_id = ?';

        $this->db->execute($sql, [
            $data['department_id'] ?: null,
            $data['position_id'] ?: null,
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'] ?: null,
            $data['gender'],
            $data['birthdate'],
            $data['email'],
            $data['contact_number'],
            $data['address'] ?? null,
            $data['employment_status'],
            $data['basic_hourly_rate'],
            $data['date_hired'],
            $employeeId,
        ]);

        return true;
    }

    public function updateProfilePicture(int $employeeId, string $filename): bool
    {
        return $this->db->execute(
            'UPDATE employees SET profile_picture = ? WHERE employee_id = ?',
            [$filename, $employeeId],
        ) > 0;
    }

    public function setActive(int $employeeId, bool $active): bool
    {
        return $this->db->execute(
            'UPDATE employees SET is_active = ? WHERE employee_id = ?',
            [$active ? 1 : 0, $employeeId],
        ) > 0;
    }

    public function delete(int $employeeId): bool
    {
        return $this->db->execute('DELETE FROM employees WHERE employee_id = ?', [$employeeId]) > 0;
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 10, string $sortBy = '', string $sortDir = 'desc'): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $countSql = "SELECT COUNT(*) AS total FROM employees e {$where}";
        $total = (int) ($this->db->fetch($countSql, $params)['total'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sortMap = [
            'name' => 'e.first_name',
            'code' => 'e.employee_code',
            'department' => 'd.department_name',
            'employment_status' => 'e.employment_status',
            'rate' => 'e.basic_hourly_rate',
            'date_hired' => 'e.date_hired',
        ];
        $orderColumn = $sortMap[$sortBy] ?? 'e.employee_id';
        $orderDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT e.*, d.department_name, p.position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                {$where}
                ORDER BY {$orderColumn} {$orderDir}
                LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->db->fetchAll($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
            'sort_by' => $sortBy,
            'sort_dir' => $orderDir,
        ];
    }

    /** @return array{0: string, 1: array<mixed>} */
    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.email LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.department_id = ?';
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['employment_status'])) {
            $conditions[] = 'e.employment_status = ?';
            $params[] = $filters['employment_status'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = 'e.is_active = ?';
            $params[] = $filters['status'] === 'active' ? 1 : 0;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    public function countActive(): int
    {
        return (int) ($this->db->fetch('SELECT COUNT(*) AS c FROM employees WHERE is_active = 1')['c'] ?? 0);
    }

    public function countAll(): int
    {
        return (int) ($this->db->fetch('SELECT COUNT(*) AS c FROM employees')['c'] ?? 0);
    }
}
