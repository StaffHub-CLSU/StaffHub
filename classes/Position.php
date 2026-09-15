<?php
/**
 * Position
 *
 * CRUD wrapper for the positions table. Positions optionally belong to
 * a department (FK), used to populate dependent dropdowns in the UI.
 */
require_once __DIR__ . '/Database.php';

class Position
{
    private Database $db;

    public int $positionId = 0;
    public string $positionName = '';
    public ?int $departmentId = null;

    public function __construct(?array $data = null)
    {
        $this->db = Database::getInstance();
        if ($data) {
            $this->positionId   = (int) ($data['position_id'] ?? 0);
            $this->positionName = $data['position_name'] ?? '';
            $this->departmentId = isset($data['department_id']) ? (int) $data['department_id'] : null;
        }
    }

    public function getAll(): array
    {
        $sql = "SELECT p.*, d.department_name
                FROM positions p
                LEFT JOIN departments d ON p.department_id = d.department_id
                ORDER BY p.position_name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getByDepartment(int $departmentId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM positions WHERE department_id = ? ORDER BY position_name ASC",
            [$departmentId]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM positions WHERE position_id = ?", [$id]);
    }

    public function create(string $name, ?int $departmentId): int
    {
        return (int) $this->db->insert(
            "INSERT INTO positions (position_name, department_id) VALUES (?, ?)",
            [$name, $departmentId]
        );
    }

    public function update(int $id, string $name, ?int $departmentId): bool
    {
        return $this->db->execute(
            "UPDATE positions SET position_name = ?, department_id = ? WHERE position_id = ?",
            [$name, $departmentId, $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM positions WHERE position_id = ?", [$id]) > 0;
    }
}
