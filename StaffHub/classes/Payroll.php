<?php
/**
 * Payroll
 *
 * Computes and stores salary runs based only on VERIFIED attendance hours.
 * Formula: Net Salary = (Verified Hours x Hourly Rate) + Bonuses - Deductions
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Attendance.php';

class Payroll
{
    private Database $db;
    private Attendance $attendanceModel;

    public ?int $payrollId = null;
    public int $employeeId = 0;
    public string $periodStart = '';
    public string $periodEnd = '';
    public float $verifiedHours = 0.0;
    public float $hourlyRate = 0.0;
    public float $grossSalary = 0.0;
    public float $deductions = 0.0;
    public float $bonuses = 0.0;
    public float $netSalary = 0.0;

    public function __construct(?array $data = null)
    {
        $this->db = Database::getInstance();
        $this->attendanceModel = new Attendance();
        if ($data) {
            $this->payrollId     = isset($data['payroll_id']) ? (int) $data['payroll_id'] : null;
            $this->employeeId    = (int) ($data['employee_id'] ?? 0);
            $this->periodStart   = $data['payroll_period_start'] ?? '';
            $this->periodEnd     = $data['payroll_period_end'] ?? '';
            $this->verifiedHours = (float) ($data['verified_hours'] ?? 0);
            $this->hourlyRate    = (float) ($data['hourly_rate'] ?? 0);
            $this->grossSalary   = (float) ($data['gross_salary'] ?? 0);
            $this->deductions    = (float) ($data['deductions'] ?? 0);
            $this->bonuses       = (float) ($data['bonuses'] ?? 0);
            $this->netSalary     = (float) ($data['net_salary'] ?? 0);
        }
    }

    /**
     * Builds a live computation preview (not saved) — used by the AJAX
     * "payroll computation preview" feature before an admin commits a run.
     */
    public function preview(int $employeeId, string $periodStart, string $periodEnd, float $bonuses = 0, float $deductions = 0): array
    {
        $employee = $this->db->fetch(
            "SELECT employee_id, first_name, last_name, employee_code, basic_hourly_rate
             FROM employees WHERE employee_id = ?",
            [$employeeId]
        );

        if (!$employee) {
            return ['success' => false, 'message' => 'Employee not found.'];
        }

        $verifiedHours = $this->attendanceModel->getVerifiedHours($employeeId, $periodStart, $periodEnd);
        $hourlyRate = (float) $employee['basic_hourly_rate'];
        $gross = round($verifiedHours * $hourlyRate, 2);
        $net = round($gross + $bonuses - $deductions, 2);

        return [
            'success' => true,
            'employee_id' => $employeeId,
            'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
            'employee_code' => $employee['employee_code'],
            'verified_hours' => $verifiedHours,
            'hourly_rate' => $hourlyRate,
            'gross_salary' => $gross,
            'bonuses' => $bonuses,
            'deductions' => $deductions,
            'net_salary' => $net,
        ];
    }

    /**
     * Commits a payroll run for one employee. Upserts so re-processing the
     * same period for the same employee updates rather than duplicates
     * (protected by the unique_employee_period key).
     */
    public function process(int $employeeId, string $periodStart, string $periodEnd, float $bonuses, float $deductions, int $processedBy): array
    {
        $preview = $this->preview($employeeId, $periodStart, $periodEnd, $bonuses, $deductions);
        if (!$preview['success']) {
            return $preview;
        }

        if ($preview['verified_hours'] <= 0) {
            return ['success' => false, 'message' => 'This employee has no verified attendance hours for the selected period.'];
        }

        $sql = "INSERT INTO payroll
                    (employee_id, payroll_period_start, payroll_period_end, verified_hours,
                     hourly_rate, gross_salary, deductions, bonuses, net_salary, processed_by, processed_date)
                VALUES (?,?,?,?,?,?,?,?,?,?, NOW())
                ON DUPLICATE KEY UPDATE
                    verified_hours = VALUES(verified_hours),
                    hourly_rate = VALUES(hourly_rate),
                    gross_salary = VALUES(gross_salary),
                    deductions = VALUES(deductions),
                    bonuses = VALUES(bonuses),
                    net_salary = VALUES(net_salary),
                    processed_by = VALUES(processed_by),
                    processed_date = NOW()";

        $this->db->query($sql, [
            $employeeId, $periodStart, $periodEnd, $preview['verified_hours'],
            $preview['hourly_rate'], $preview['gross_salary'], $deductions, $bonuses,
            $preview['net_salary'], $processedBy,
        ]);

        return ['success' => true, 'message' => 'Payroll processed for ' . $preview['employee_name'] . '.', 'data' => $preview];
    }

    /** Runs payroll for every active employee in one go. Returns a summary. */
    public function processBatch(string $periodStart, string $periodEnd, int $processedBy, array $employeeIds = []): array
    {
        if (empty($employeeIds)) {
            $rows = $this->db->fetchAll("SELECT employee_id FROM employees WHERE is_active = 1");
            $employeeIds = array_column($rows, 'employee_id');
        }

        $processed = 0;
        $skipped = 0;
        $results = [];

        foreach ($employeeIds as $empId) {
            $result = $this->process((int) $empId, $periodStart, $periodEnd, 0, 0, $processedBy);
            if ($result['success']) {
                $processed++;
            } else {
                $skipped++;
            }
            $results[] = $result;
        }

        return ['success' => true, 'processed' => $processed, 'skipped' => $skipped, 'details' => $results];
    }

    public function getById(int $payrollId): ?array
    {
        $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code, d.department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                WHERE p.payroll_id = ?";
        return $this->db->fetch($sql, [$payrollId]);
    }

    public function getHistoryForEmployee(int $employeeId, int $limit = 24): array
    {
        $stmt = $this->db->getConnection()->prepare(
            "SELECT * FROM payroll WHERE employee_id = ? ORDER BY payroll_period_start DESC LIMIT ?"
        );
        $stmt->bindValue(1, $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = "p.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['period_start'])) {
            $conditions[] = "p.payroll_period_start >= ?";
            $params[] = $filters['period_start'];
        }
        if (!empty($filters['period_end'])) {
            $conditions[] = "p.payroll_period_end <= ?";
            $params[] = $filters['period_end'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "SELECT COUNT(*) AS total FROM payroll p JOIN employees e ON p.employee_id = e.employee_id $where";
        $total = (int) $this->db->fetch($countSql, $params)['total'];

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code, d.department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                $where
                ORDER BY p.processed_date DESC
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

    public function countProcessed(): int
    {
        return (int) $this->db->fetch("SELECT COUNT(*) AS c FROM payroll")['c'];
    }

    /** Monthly payroll expense totals for the last N months (Chart.js widget). */
    public function getMonthlyExpenses(int $months = 6): array
    {
        $sql = "SELECT DATE_FORMAT(payroll_period_start, '%Y-%m') AS month, SUM(net_salary) AS total
                FROM payroll
                WHERE payroll_period_start >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(payroll_period_start, '%Y-%m')
                ORDER BY month ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $months, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
