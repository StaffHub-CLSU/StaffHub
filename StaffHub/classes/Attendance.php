<?php
/**
 * Attendance
 *
 * The "Smart Timekeeping" engine. Handles clock in/out, automatically
 * computes hours worked, prevents duplicate/overlapping sessions, and
 * flags incomplete records. Only rows with status = 'Verified' are ever
 * eligible for payroll computation (see Payroll::computeForEmployee()).
 */
require_once __DIR__ . '/Database.php';

class Attendance
{
    private Database $db;

    public ?int $attendanceId = null;
    public int $employeeId = 0;
    public string $attendanceDate = '';
    public ?string $timeIn = null;
    public ?string $timeOut = null;
    public float $totalHours = 0.0;
    public string $status = 'Incomplete';

    public function __construct(?array $data = null)
    {
        $this->db = Database::getInstance();
        if ($data) {
            $this->attendanceId   = isset($data['attendance_id']) ? (int) $data['attendance_id'] : null;
            $this->employeeId     = (int) ($data['employee_id'] ?? 0);
            $this->attendanceDate = $data['attendance_date'] ?? '';
            $this->timeIn         = $data['time_in'] ?? null;
            $this->timeOut        = $data['time_out'] ?? null;
            $this->totalHours     = isset($data['total_hours']) ? (float) $data['total_hours'] : 0.0;
            $this->status         = $data['status'] ?? 'Incomplete';
        }
    }

    /** Returns today's attendance row for an employee, or null if none yet. */
    public function getTodayRecord(int $employeeId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()",
            [$employeeId]
        );
    }

    /**
     * Clocks an employee in. Prevents a second time-in on a day that
     * already has an open (no time-out) or already-completed session.
     */
    public function clockIn(int $employeeId): array
    {
        $existing = $this->getTodayRecord($employeeId);

        if ($existing) {
            if ($existing['time_in'] && !$existing['time_out']) {
                return ['success' => false, 'message' => 'You are already clocked in. Please clock out first.'];
            }
            if ($existing['time_out']) {
                return ['success' => false, 'message' => 'You have already completed your attendance for today.'];
            }
        }

        $sql = "INSERT INTO attendance (employee_id, attendance_date, time_in, status)
                VALUES (?, CURDATE(), NOW(), 'Incomplete')";
        $this->db->insert($sql, [$employeeId]);

        return ['success' => true, 'message' => 'Clocked in successfully at ' . date('h:i A') . '.'];
    }

    /**
     * Clocks an employee out, computes total hours worked, and marks the
     * record Completed (verified later by an admin, or auto-verified —
     * see verify()/verifyAll()).
     */
    public function clockOut(int $employeeId): array
    {
        $existing = $this->getTodayRecord($employeeId);

        if (!$existing) {
            return ['success' => false, 'message' => 'No active clock-in found for today.'];
        }
        if (!$existing['time_in']) {
            return ['success' => false, 'message' => 'No active clock-in found for today.'];
        }
        if ($existing['time_out']) {
            return ['success' => false, 'message' => 'You have already clocked out today.'];
        }

        $timeIn = new DateTime($existing['time_in']);
        $timeOut = new DateTime(); // now
        $diffSeconds = $timeOut->getTimestamp() - $timeIn->getTimestamp();
        $totalHours = round($diffSeconds / 3600, 2);

        $sql = "UPDATE attendance SET time_out = NOW(), total_hours = ?, status = 'Completed'
                WHERE attendance_id = ?";
        $this->db->execute($sql, [$totalHours, $existing['attendance_id']]);

        return [
            'success' => true,
            'message' => 'Clocked out successfully at ' . date('h:i A') . ". Total hours: {$totalHours}h.",
            'total_hours' => $totalHours,
        ];
    }

    /** Admin action: marks a Completed record as Verified (payroll-eligible). */
    public function verify(int $attendanceId): bool
    {
        return $this->db->execute(
            "UPDATE attendance SET status = 'Verified' WHERE attendance_id = ? AND status = 'Completed'",
            [$attendanceId]
        ) > 0;
    }

    /** Bulk-verifies every Completed record within a date range (used before payroll runs). */
    public function verifyRange(string $startDate, string $endDate): int
    {
        return $this->db->execute(
            "UPDATE attendance SET status = 'Verified'
             WHERE status = 'Completed' AND attendance_date BETWEEN ? AND ?",
            [$startDate, $endDate]
        );
    }

    /**
     * Searchable/filterable attendance history for the admin Attendance page.
     * Filters: employee_id, department_id, date, month (YYYY-MM), date_from, date_to
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = "a.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['date'])) {
            $conditions[] = "a.attendance_date = ?";
            $params[] = $filters['date'];
        }
        if (!empty($filters['month'])) {
            $conditions[] = "DATE_FORMAT(a.attendance_date, '%Y-%m') = ?";
            $params[] = $filters['month'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = "a.attendance_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "a.attendance_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "a.status = ?";
            $params[] = $filters['status'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "SELECT COUNT(*) AS total FROM attendance a
                     JOIN employees e ON a.employee_id = e.employee_id $where";
        $total = (int) $this->db->fetch($countSql, $params)['total'];

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT a.*, e.employee_code, e.first_name, e.last_name, d.department_name
                FROM attendance a
                JOIN employees e ON a.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                $where
                ORDER BY a.attendance_date DESC, a.time_in DESC
                LIMIT $perPage OFFSET $offset";

        $rows = $this->db->fetchAll($sql, $params);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Attendance history for a single employee (used on the employee dashboard).
     * Uses bindValue with PDO::PARAM_INT for LIMIT since native prepared
     * statements (EMULATE_PREPARES=false) require correctly typed params.
     */
    public function getHistoryForEmployee(int $employeeId, int $limit = 30): array
    {
        $stmt = $this->db->getConnection()->prepare(
            "SELECT * FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT ?"
        );
        $stmt->bindValue(1, $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Sum of VERIFIED hours for an employee within a date range (used by Payroll). */
    public function getVerifiedHours(int $employeeId, string $startDate, string $endDate): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(total_hours), 0) AS total
             FROM attendance
             WHERE employee_id = ? AND status = 'Verified' AND attendance_date BETWEEN ? AND ?",
            [$employeeId, $startDate, $endDate]
        );
        return (float) $row['total'];
    }

    public function countPresentToday(): int
    {
        return (int) $this->db->fetch(
            "SELECT COUNT(DISTINCT employee_id) AS c FROM attendance WHERE attendance_date = CURDATE() AND time_in IS NOT NULL"
        )['c'];
    }

    public function countTotalRecords(): int
    {
        return (int) $this->db->fetch("SELECT COUNT(*) AS c FROM attendance")['c'];
    }

    /** Attendance counts for the last N days, for the Chart.js "Daily Attendance" widget. */
    public function getDailyAttendanceStats(int $days = 7): array
    {
        $sql = "SELECT attendance_date, COUNT(DISTINCT employee_id) AS present_count
                FROM attendance
                WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY attendance_date
                ORDER BY attendance_date ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentClockIns(int $limit = 5): array
    {
        $sql = "SELECT a.time_in, e.first_name, e.last_name, e.employee_code, e.profile_picture
                FROM attendance a JOIN employees e ON a.employee_id = e.employee_id
                WHERE a.time_in IS NOT NULL ORDER BY a.time_in DESC LIMIT ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentClockOuts(int $limit = 5): array
    {
        $sql = "SELECT a.time_out, e.first_name, e.last_name, e.employee_code, e.profile_picture
                FROM attendance a JOIN employees e ON a.employee_id = e.employee_id
                WHERE a.time_out IS NOT NULL ORDER BY a.time_out DESC LIMIT ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
