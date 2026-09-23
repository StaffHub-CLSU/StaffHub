<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Repository\Contract\DepartmentRepositoryInterface;

final class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function all(): array
    {
        return $this->db->fetchAll('SELECT * FROM departments ORDER BY department_name ASC');
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM departments WHERE department_id = ?', [$id]);
    }

    public function create(string $name, string $description = ''): int
    {
        return (int) $this->db->insert(
            'INSERT INTO departments (department_name, description) VALUES (?, ?)',
            [$name, $description],
        );
    }

    public function update(int $id, string $name, string $description = ''): bool
    {
        return $this->db->execute(
            'UPDATE departments SET department_name = ?, description = ? WHERE department_id = ?',
            [$name, $description, $id],
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM departments WHERE department_id = ?', [$id]) > 0;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT department_id FROM departments WHERE department_name = ?';
        $params = [$name];
        if ($excludeId !== null) {
            $sql .= ' AND department_id != ?';
            $params[] = $excludeId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    public function employeeDistribution(): array
    {
        $sql = 'SELECT d.department_name, COUNT(e.employee_id) AS employee_count
                FROM departments d
                LEFT JOIN employees e ON e.department_id = d.department_id AND e.is_active = 1
                GROUP BY d.department_id, d.department_name
                ORDER BY d.department_name ASC';
        return $this->db->fetchAll($sql);
    }

    public function countAll(): int
    {
        return count($this->all());
    }
}
