<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function calculate(Employee $employee, array $data, User $processor): Payroll
    {
        return DB::transaction(function () use ($employee, $data, $processor): Payroll {
            $hasOverlappingPayroll = Payroll::query()
                ->whereBelongsTo($employee)
                ->where('payroll_period_start', '<=', $data['payroll_period_end'])
                ->where('payroll_period_end', '>=', $data['payroll_period_start'])
                ->where(function ($query) use ($data): void {
                    $query->where('payroll_period_start', '!=', $data['payroll_period_start'])
                        ->orWhere('payroll_period_end', '!=', $data['payroll_period_end']);
                })
                ->exists();

            if ($hasOverlappingPayroll) {
                throw ValidationException::withMessages([
                    'payroll_period_start' => 'The selected period overlaps an existing payroll.',
                ]);
            }

            $hours = (float) Attendance::query()->whereBelongsTo($employee)->where('status', 'Verified')->whereBetween('attendance_date', [$data['payroll_period_start'], $data['payroll_period_end']])->sum('total_hours');

            if ($hours <= 0) {
                throw ValidationException::withMessages(['employee_id' => 'The selected period has no verified attendance hours.']);
            }

            $rate = (float) $employee->basic_hourly_rate;
            $gross = round($hours * $rate, 2);
            $bonuses = (float) ($data['bonuses'] ?? 0);
            $deductions = (float) ($data['deductions'] ?? 0);

            if ($deductions > $gross + $bonuses) {
                throw ValidationException::withMessages([
                    'deductions' => 'Deductions cannot exceed the gross salary plus bonuses.',
                ]);
            }

            return Payroll::updateOrCreate(
                ['employee_id' => $employee->employee_id, 'payroll_period_start' => $data['payroll_period_start'], 'payroll_period_end' => $data['payroll_period_end']],
                ['verified_hours' => $hours, 'hourly_rate' => $rate, 'gross_salary' => $gross, 'bonuses' => $bonuses, 'deductions' => $deductions, 'net_salary' => $gross + $bonuses - $deductions, 'processed_by' => $processor->user_id, 'processed_date' => now()]
            );
        });
    }
}
