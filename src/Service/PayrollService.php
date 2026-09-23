<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Entity\Payroll;
use StaffHub\Repository\Contract\AttendanceRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\PayrollRepositoryInterface;
use StaffHub\Support\Validator;

/**
 * Salary processing from VERIFIED attendance hours only.
 * Net = (Verified Hours × Rate) + Bonuses − Deductions
 */
final class PayrollService
{
    public function __construct(
        private readonly PayrollRepositoryInterface $payroll,
        private readonly AttendanceRepositoryInterface $attendance,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Live computation preview (not saved).
     *
     * @return array<string, mixed>
     */
    public function preview(int $employeeId, string $periodStart, string $periodEnd, float $bonuses = 0, float $deductions = 0): array
    {
        $employee = $this->payroll->findEmployeeForPayroll($employeeId);

        if (!$employee) {
            return ['success' => false, 'message' => 'Employee not found.'];
        }

        $verifiedHours = $this->attendance->verifiedHours($employeeId, $periodStart, $periodEnd);
        $hourlyRate = (float) $employee['basic_hourly_rate'];
        $totals = Payroll::compute($verifiedHours, $hourlyRate, $bonuses, $deductions);

        return [
            'success' => true,
            'employee_id' => $employeeId,
            'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
            'employee_code' => $employee['employee_code'],
            'verified_hours' => $verifiedHours,
            'hourly_rate' => $hourlyRate,
            'gross_salary' => $totals['gross_salary'],
            'bonuses' => $bonuses,
            'deductions' => $deductions,
            'net_salary' => $totals['net_salary'],
        ];
    }

    /** @return array<string, mixed> */
    public function process(int $employeeId, string $periodStart, string $periodEnd, float $bonuses, float $deductions, int $processedBy): array
    {
        $preview = $this->preview($employeeId, $periodStart, $periodEnd, $bonuses, $deductions);
        if (!$preview['success']) {
            return $preview;
        }

        if ($preview['verified_hours'] <= 0) {
            return ['success' => false, 'message' => 'This employee has no verified attendance hours for the selected period.'];
        }

        $this->payroll->upsert([
            'employee_id' => $employeeId,
            'payroll_period_start' => $periodStart,
            'payroll_period_end' => $periodEnd,
            'verified_hours' => $preview['verified_hours'],
            'hourly_rate' => $preview['hourly_rate'],
            'gross_salary' => $preview['gross_salary'],
            'deductions' => $deductions,
            'bonuses' => $bonuses,
            'net_salary' => $preview['net_salary'],
            'processed_by' => $processedBy,
        ]);

        return [
            'success' => true,
            'message' => 'Payroll processed for ' . $preview['employee_name'] . '.',
            'data' => $preview,
        ];
    }

    /** @return array{success: bool, processed: int, skipped: int, details: array<int, array<string, mixed>>} */
    public function processBatch(string $periodStart, string $periodEnd, int $processedBy, array $employeeIds = []): array
    {
        if ($employeeIds === []) {
            $active = $this->employees->search(['status' => 'active'], 1, 10000);
            $employeeIds = array_column($active['data'], 'employee_id');
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

    /** @return array<string, mixed> */
    public function validatePeriod(?string $start, ?string $end): array
    {
        $validator = new Validator();
        $validator->required($start ?? '', 'period_start', 'Period start')
            ->required($end ?? '', 'period_end', 'Period end');

        if (!empty($start) && !empty($end) && strtotime($start) > strtotime($end)) {
            $validator->addError('period_end', 'Period end date must be after the start date.');
        }

        return [
            'fails' => $validator->fails(),
            'message' => $validator->firstError(),
            'errors' => $validator->getErrors(),
        ];
    }

    /** @return array<string, mixed> */
    public function search(array $filters, int $page, int $perPage): array
    {
        return $this->payroll->search($filters, $page, $perPage);
    }

    public function find(int $payrollId): ?array
    {
        return $this->payroll->findById($payrollId);
    }

    public function historyForEmployee(int $employeeId, int $limit = 24): array
    {
        return $this->payroll->historyForEmployee($employeeId, $limit);
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }
}
