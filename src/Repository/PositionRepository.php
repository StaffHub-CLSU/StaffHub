<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Repository\Contract\PositionRepositoryInterface;

final class PositionRepository implements PositionRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function all(): array
    {
        $sql = 'SELECT p.*, d.department_name
                FROM positions p
                LEFT JOIN departments d ON p.department_id = d.department_id
                ORDER BY p.position_name ASC';
        return $this->db->fetchAll($sql);
    }

    public function byDepartment(int $departmentId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM positions WHERE department_id = ? ORDER BY position_name ASC',
            [$departmentId],
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM positions WHERE position_id = ?', [$id]);
    }

    public function create(string $name, ?int $departmentId): int
    {
        return (int) $this->db->insert(
            'INSERT INTO positions (position_name, department_id) VALUES (?, ?)',
            [$name, $departmentId],
        );
    }

    public function update(int $id, string $name, ?int $departmentId): bool
    {
        return $this->db->execute(
            'UPDATE positions SET position_name = ?, department_id = ? WHERE position_id = ?',
            [$name, $departmentId, $id],
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM positions WHERE position_id = ?', [$id]) > 0;
    }
}
