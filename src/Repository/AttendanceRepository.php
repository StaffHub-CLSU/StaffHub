<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Entity\Attendance;
use StaffHub\Repository\Contract\AttendanceRepositoryInterface;

final class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function findToday(int $employeeId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()',
            [$employeeId],
        );
    }

    public function findTodayEntity(int $employeeId): ?Attendance
    {
        $row = $this->findToday($employeeId);
        return $row ? Attendance::fromRow($row) : null;
    }

    public function insertClockIn(int $employeeId): void
    {
        $this->db->insert(
            "INSERT INTO attendance (employee_id, attendance_date, time_in, status)
             VALUES (?, CURDATE(), NOW(), 'Incomplete')",
            [$employeeId],
        );
    }

    public function updateClockOut(int $attendanceId, float $totalHours): void
    {
        $this->db->execute(
            "UPDATE attendance SET time_out = NOW(), total_hours = ?, status = 'Completed' WHERE attendance_id = ?",
            [$totalHours, $attendanceId],
        );
    }

    public function verify(int $attendanceId): bool
    {
        return $this->db->execute(
            "UPDATE attendance SET status = 'Verified' WHERE attendance_id = ? AND status = 'Completed'",
            [$attendanceId],
        ) > 0;
    }

    public function verifyRange(string $startDate, string $endDate): int
    {
        return $this->db->execute(
            "UPDATE attendance SET status = 'Verified'
             WHERE status = 'Completed' AND attendance_date BETWEEN ? AND ?",
            [$startDate, $endDate],
        );
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'a.employee_id = ?';
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.department_id = ?';
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['date'])) {
            $conditions[] = 'a.attendance_date = ?';
            $params[] = $filters['date'];
        }
        if (!empty($filters['month'])) {
            $conditions[] = "DATE_FORMAT(a.attendance_date, '%Y-%m') = ?";
            $params[] = $filters['month'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'a.attendance_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'a.attendance_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'a.status = ?';
            $params[] = $filters['status'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "SELECT COUNT(*) AS total FROM attendance a
                     JOIN employees e ON a.employee_id = e.employee_id {$where}";
        $total = (int) ($this->db->fetch($countSql, $params)['total'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT a.*, e.employee_code, e.first_name, e.last_name, d.department_name
                FROM attendance a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                {$where}
                ORDER BY a.attendance_date DESC, a.time_in DESC
                LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->db->fetchAll($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ];
    }

    public function historyForEmployee(int $employeeId, int $limit = 30): array
    {
        $stmt = $this->db->getConnection()->prepare(
            'SELECT * FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT ?'
        );
        $stmt->bindValue(1, $employeeId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function verifiedHours(int $employeeId, string $startDate, string $endDate): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(total_hours), 0) AS total
             FROM attendance
             WHERE employee_id = ? AND status = 'Verified' AND attendance_date BETWEEN ? AND ?",
            [$employeeId, $startDate, $endDate],
        );
        return (float) ($row['total'] ?? 0);
    }

    public function countPresentToday(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(DISTINCT employee_id) AS c FROM attendance WHERE attendance_date = CURDATE() AND time_in IS NOT NULL",
        )['c'] ?? 0);
    }

    public function countTotalRecords(): int
    {
        return (int) ($this->db->fetch('SELECT COUNT(*) AS c FROM attendance')['c'] ?? 0);
    }

    public function dailyStats(int $days = 7): array
    {
        $sql = 'SELECT attendance_date, COUNT(DISTINCT employee_id) AS present_count
                FROM attendance
                WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY attendance_date
                ORDER BY attendance_date ASC';
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $days, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function recentClockIns(int $limit = 5): array
    {
        $sql = 'SELECT a.time_in, e.first_name, e.last_name, e.employee_code, e.profile_picture
                FROM attendance a JOIN employees e ON a.employee_id = e.employee_id
                WHERE a.time_in IS NOT NULL ORDER BY a.time_in DESC LIMIT ?';
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function recentClockOuts(int $limit = 5): array
    {
        $sql = 'SELECT a.time_out, e.first_name, e.last_name, e.employee_code, e.profile_picture
                FROM attendance a JOIN employees e ON a.employee_id = e.employee_id
                WHERE a.time_out IS NOT NULL ORDER BY a.time_out DESC LIMIT ?';
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
