<?php
/**
 * Dashboard
 *
 * Aggregates statistics from Employee, Attendance, Payroll, and Department
 * into the widget data needed by admin/dashboard.php. Keeps that page
 * script thin -- it just calls one method and renders.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/Attendance.php';
require_once __DIR__ . '/Payroll.php';
require_once __DIR__ . '/Department.php';
require_once __DIR__ . '/Logger.php';

class Dashboard
{
    private Database $db;
    private Employee $employeeModel;
    private Attendance $attendanceModel;
    private Payroll $payrollModel;
    private Department $departmentModel;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->employeeModel = new Employee();
        $this->attendanceModel = new Attendance();
        $this->payrollModel = new Payroll();
        $this->departmentModel = new Department();
        $this->logger = new Logger();
    }

    public function getAdminStats(): array
    {
        $totalEmployees = $this->employeeModel->countActive();
        $presentToday = $this->attendanceModel->countPresentToday();
        $absentToday = max(0, $totalEmployees - $presentToday);
        $presentPct = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
        $absentPct = $totalEmployees > 0 ? round(($absentToday / $totalEmployees) * 100) : 0;

        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'present_pct' => $presentPct,
            'absent_today' => $absentToday,
            'absent_pct' => $absentPct,
            'total_attendance_records' => $this->attendanceModel->countTotalRecords(),
            'payrolls_processed' => $this->payrollModel->countProcessed(),
            'total_departments' => count($this->departmentModel->getAll()),
        ];
    }

    public function getRecentActivity(int $limit = 8): array
    {
        return $this->logger->getRecent($limit);
    }

    public function getRecentClockIns(int $limit = 5): array
    {
        return $this->attendanceModel->getRecentClockIns($limit);
    }

    public function getRecentClockOuts(int $limit = 5): array
    {
        return $this->attendanceModel->getRecentClockOuts($limit);
    }

    public function getDailyAttendanceChartData(int $days = 7): array
    {
        $rows = $this->attendanceModel->getDailyAttendanceStats($days);
        return [
            'labels' => array_map(fn($r) => date('M d', strtotime($r['attendance_date'])), $rows),
            'values' => array_map(fn($r) => (int) $r['present_count'], $rows),
        ];
    }

    public function getMonthlyPayrollChartData(int $months = 6): array
    {
        $rows = $this->payrollModel->getMonthlyExpenses($months);
        return [
            'labels' => array_map(fn($r) => date('M Y', strtotime($r['month'] . '-01')), $rows),
            'values' => array_map(fn($r) => (float) $r['total'], $rows),
        ];
    }

    public function getDepartmentDistributionChartData(): array
    {
        $rows = $this->departmentModel->getEmployeeDistribution();
        return [
            'labels' => array_map(fn($r) => $r['department_name'], $rows),
            'values' => array_map(fn($r) => (int) $r['employee_count'], $rows),
        ];
    }

    /** Stats for an individual employee's personal dashboard. */
    public function getEmployeeStats(int $employeeId): array
    {
        $attendanceHistory = $this->attendanceModel->getHistoryForEmployee($employeeId, 30);
        $totalVerifiedThisMonth = $this->db->fetch(
            "SELECT COALESCE(SUM(total_hours),0) AS h FROM attendance
             WHERE employee_id = ? AND status = 'Verified' AND MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE())",
            [$employeeId]
        )['h'];

        $lastPayroll = $this->db->fetch(
            "SELECT * FROM payroll WHERE employee_id = ? ORDER BY processed_date DESC LIMIT 1",
            [$employeeId]
        );

        return [
            'today' => $this->attendanceModel->getTodayRecord($employeeId),
            'recent_history' => array_slice($attendanceHistory, 0, 10),
            'verified_hours_this_month' => (float) $totalVerifiedThisMonth,
            'last_payroll' => $lastPayroll,
        ];
    }
}
