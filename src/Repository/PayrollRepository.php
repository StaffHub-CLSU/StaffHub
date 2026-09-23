<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Repository\Contract\PayrollRepositoryInterface;

final class PayrollRepository implements PayrollRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function findEmployeeForPayroll(int $employeeId): ?array
    {
        return $this->db->fetch(
            'SELECT employee_id, first_name, last_name, employee_code, basic_hourly_rate
             FROM employees WHERE employee_id = ?',
            [$employeeId],
        );
    }

    public function upsert(array $v): void
    {
        $sql = 'INSERT INTO payroll
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
                    processed_date = NOW()';

        $this->db->query($sql, [
            $v['employee_id'],
            $v['payroll_period_start'],
            $v['payroll_period_end'],
            $v['verified_hours'],
            $v['hourly_rate'],
            $v['gross_salary'],
            $v['deductions'],
            $v['bonuses'],
            $v['net_salary'],
            $v['processed_by'],
        ]);
    }

    public function findById(int $payrollId): ?array
    {
        $sql = 'SELECT p.*, e.first_name, e.last_name, e.employee_code, d.department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                WHERE p.payroll_id = ?';
        return $this->db->fetch($sql, [$payrollId]);
    }

    public function historyForEmployee(int $employeeId, int $limit = 24): array
    {
        $stmt = $this->db->getConnection()->prepare(
            'SELECT * FROM payroll WHERE employee_id = ? ORDER BY payroll_period_start DESC LIMIT ?'
        );
        $stmt->bindValue(1, $employeeId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'p.employee_id = ?';
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.department_id = ?';
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['period_start'])) {
            $conditions[] = 'p.payroll_period_start >= ?';
            $params[] = $filters['period_start'];
        }
        if (!empty($filters['period_end'])) {
            $conditions[] = 'p.payroll_period_end <= ?';
            $params[] = $filters['period_end'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "SELECT COUNT(*) AS total FROM payroll p JOIN employees e ON p.employee_id = e.employee_id {$where}";
        $total = (int) ($this->db->fetch($countSql, $params)['total'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code, d.department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                {$where}
                ORDER BY p.processed_date DESC
                LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->db->fetchAll($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ];
    }

    public function countProcessed(): int
    {
        return (int) ($this->db->fetch('SELECT COUNT(*) AS c FROM payroll')['c'] ?? 0);
    }

    public function monthlyExpenses(int $months = 6): array
    {
        $sql = "SELECT DATE_FORMAT(payroll_period_start, '%Y-%m') AS month, SUM(net_salary) AS total
                FROM payroll
                WHERE payroll_period_start >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(payroll_period_start, '%Y-%m')
                ORDER BY month ASC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $months, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function lastForEmployee(int $employeeId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM payroll WHERE employee_id = ? ORDER BY processed_date DESC LIMIT 1',
            [$employeeId],
        );
    }
}
