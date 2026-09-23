<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Core\Database;
use StaffHub\Repository\Contract\AttendanceRepositoryInterface;
use StaffHub\Repository\Contract\DepartmentRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\PayrollRepositoryInterface;

/**
 * Aggregates stats/chart data for admin and employee dashboards.
 */
final class DashboardService
{
    public function __construct(
        private readonly Database $db,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly AttendanceRepositoryInterface $attendance,
        private readonly PayrollRepositoryInterface $payroll,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array<string, int> */
    public function adminStats(): array
    {
        $totalEmployees = $this->employees->countActive();
        $presentToday = $this->attendance->countPresentToday();
        $absentToday = max(0, $totalEmployees - $presentToday);
        $presentPct = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
        $absentPct = $totalEmployees > 0 ? round(($absentToday / $totalEmployees) * 100) : 0;

        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'present_pct' => $presentPct,
            'absent_today' => $absentToday,
            'absent_pct' => $absentPct,
            'total_attendance_records' => $this->attendance->countTotalRecords(),
            'payrolls_processed' => $this->payroll->countProcessed(),
            'total_departments' => $this->departments->countAll(),
        ];
    }

    public function recentActivity(int $limit = 8): array
    {
        return $this->logger->getRecent($limit);
    }

    public function recentClockIns(int $limit = 5): array
    {
        return $this->attendance->recentClockIns($limit);
    }

    public function recentClockOuts(int $limit = 5): array
    {
        return $this->attendance->recentClockOuts($limit);
    }

    /** @return array{labels: array<int, string>, values: array<int, int>} */
    public function dailyAttendanceChart(int $days = 7): array
    {
        $rows = $this->attendance->dailyStats($days);
        return [
            'labels' => array_map(static fn ($r) => date('M d', strtotime($r['attendance_date'])), $rows),
            'values' => array_map(static fn ($r) => (int) $r['present_count'], $rows),
        ];
    }

    /** @return array{labels: array<int, string>, values: array<int, float>} */
    public function monthlyPayrollChart(int $months = 6): array
    {
        $rows = $this->payroll->monthlyExpenses($months);
        return [
            'labels' => array_map(static fn ($r) => date('M Y', strtotime($r['month'] . '-01')), $rows),
            'values' => array_map(static fn ($r) => (float) $r['total'], $rows),
        ];
    }

    /** @return array{labels: array<int, string>, values: array<int, int>} */
    public function departmentDistributionChart(): array
    {
        $rows = $this->departments->employeeDistribution();
        return [
            'labels' => array_map(static fn ($r) => $r['department_name'], $rows),
            'values' => array_map(static fn ($r) => (int) $r['employee_count'], $rows),
        ];
    }

    /** @return array<string, mixed> */
    public function employeeStats(int $employeeId): array
    {
        $history = $this->attendance->historyForEmployee($employeeId, 30);

        $verifiedMonth = $this->db->fetch(
            "SELECT COALESCE(SUM(total_hours),0) AS h FROM attendance
             WHERE employee_id = ? AND status = 'Verified'
               AND MONTH(attendance_date) = MONTH(CURDATE())
               AND YEAR(attendance_date) = YEAR(CURDATE())",
            [$employeeId],
        );

        $lastPayroll = $this->payroll->lastForEmployee($employeeId);

        return [
            'today' => $this->attendance->findToday($employeeId),
            'recent_history' => array_slice($history, 0, 10),
            'verified_hours_this_month' => (float) ($verifiedMonth['h'] ?? 0),
            'last_payroll' => $lastPayroll,
        ];
    }
}
