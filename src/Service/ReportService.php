<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Repository\Contract\AttendanceRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\PayrollRepositoryInterface;

/**
 * Read-side helpers for printable admin reports (capped at 1000 rows).
 */
final class ReportService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly AttendanceRepositoryInterface $attendance,
        private readonly PayrollRepositoryInterface $payroll,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function attendanceReport(array $filters): array
    {
        return $this->attendance->search($filters, 1, 1000)['data'];
    }

    /** @return array{rows: array<int, array<string, mixed>>, total_gross: float, total_net: float} */
    public function payrollReport(array $filters): array
    {
        $rows = $this->payroll->search($filters, 1, 1000)['data'];
        return [
            'rows' => $rows,
            'total_gross' => (float) array_sum(array_column($rows, 'gross_salary')),
            'total_net' => (float) array_sum(array_column($rows, 'net_salary')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function employeeReport(array $filters): array
    {
        return $this->employees->search($filters, 1, 1000)['data'];
    }
}
