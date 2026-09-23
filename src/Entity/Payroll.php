<?php

declare(strict_types=1);

namespace StaffHub\Entity;

/**
 * Payroll run for one employee over a period.
 * Net = (Verified Hours × Rate) + Bonuses − Deductions
 */
final class Payroll
{
    private ?int $payrollId = null;
    private int $employeeId = 0;
    private string $periodStart = '';
    private string $periodEnd = '';
    private float $verifiedHours = 0.0;
    private float $hourlyRate = 0.0;
    private float $grossSalary = 0.0;
    private float $deductions = 0.0;
    private float $bonuses = 0.0;
    private float $netSalary = 0.0;

    public static function fromRow(array $row): self
    {
        $p = new self();
        $p->payrollId = isset($row['payroll_id']) ? (int) $row['payroll_id'] : null;
        $p->employeeId = (int) ($row['employee_id'] ?? 0);
        $p->periodStart = $row['payroll_period_start'] ?? '';
        $p->periodEnd = $row['payroll_period_end'] ?? '';
        $p->verifiedHours = (float) ($row['verified_hours'] ?? 0);
        $p->hourlyRate = (float) ($row['hourly_rate'] ?? 0);
        $p->grossSalary = (float) ($row['gross_salary'] ?? 0);
        $p->deductions = (float) ($row['deductions'] ?? 0);
        $p->bonuses = (float) ($row['bonuses'] ?? 0);
        $p->netSalary = (float) ($row['net_salary'] ?? 0);
        return $p;
    }

    /**
     * Pure domain calculation used by preview and process.
     *
     * @return array{gross_salary: float, net_salary: float}
     */
    public static function compute(float $verifiedHours, float $hourlyRate, float $bonuses, float $deductions): array
    {
        $gross = round($verifiedHours * $hourlyRate, 2);
        $net = round($gross + $bonuses - $deductions, 2);
        return [
            'gross_salary' => $gross,
            'net_salary'   => $net,
        ];
    }

    public function getPayrollId(): ?int
    {
        return $this->payrollId;
    }

    public function setPayrollId(?int $id): self
    {
        $this->payrollId = $id;
        return $this;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): self
    {
        $this->employeeId = $employeeId;
        return $this;
    }

    public function getPeriodStart(): string
    {
        return $this->periodStart;
    }

    public function setPeriodStart(string $periodStart): self
    {
        $this->periodStart = $periodStart;
        return $this;
    }

    public function getPeriodEnd(): string
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(string $periodEnd): self
    {
        $this->periodEnd = $periodEnd;
        return $this;
    }

    public function getVerifiedHours(): float
    {
        return $this->verifiedHours;
    }

    public function setVerifiedHours(float $hours): self
    {
        $this->verifiedHours = $hours;
        return $this;
    }

    public function getHourlyRate(): float
    {
        return $this->hourlyRate;
    }

    public function setHourlyRate(float $rate): self
    {
        $this->hourlyRate = $rate;
        return $this;
    }

    public function getGrossSalary(): float
    {
        return $this->grossSalary;
    }

    public function setGrossSalary(float $gross): self
    {
        $this->grossSalary = $gross;
        return $this;
    }

    public function getDeductions(): float
    {
        return $this->deductions;
    }

    public function setDeductions(float $deductions): self
    {
        $this->deductions = $deductions;
        return $this;
    }

    public function getBonuses(): float
    {
        return $this->bonuses;
    }

    public function setBonuses(float $bonuses): self
    {
        $this->bonuses = $bonuses;
        return $this;
    }

    public function getNetSalary(): float
    {
        return $this->netSalary;
    }

    public function setNetSalary(float $net): self
    {
        $this->netSalary = $net;
        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'payroll_id'          => $this->payrollId,
            'employee_id'         => $this->employeeId,
            'payroll_period_start' => $this->periodStart,
            'payroll_period_end'  => $this->periodEnd,
            'verified_hours'      => $this->verifiedHours,
            'hourly_rate'         => $this->hourlyRate,
            'gross_salary'        => $this->grossSalary,
            'deductions'          => $this->deductions,
            'bonuses'             => $this->bonuses,
            'net_salary'          => $this->netSalary,
        ];
    }
}
