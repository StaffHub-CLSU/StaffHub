<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 *
 * Default: a semi-monthly payroll record for the most recent completed period.
 *
 * Notes:
 *  - gross_salary is computed from verified_hours * hourly_rate
 *  - net_salary   is computed as gross_salary - deductions + bonuses
 *  - processed_by is left null by default; callers may set it to a User id.
 *
 * States available:
 *  - firstHalf()  → covers the 1st–15th of the current month
 *  - secondHalf() → covers the 16th–end of the current month
 *  - withBonus()  → adds a random bonus between PHP 500 and PHP 5,000
 *  - withDeductions() → adds government-mandated-style deductions
 */
class PayrollFactory extends Factory
{
    protected $table = 'payroll';

    protected $model = Payroll::class;

    public function definition(): array
    {
        $employee    = Employee::factory()->make();
        $hourlyRate  = (float) ($employee->basic_hourly_rate ?? fake()->randomFloat(2, 75, 350));
        $hours       = fake()->randomFloat(2, 70, 96); // semi-monthly hours
        $gross       = round($hours * $hourlyRate, 2);
        $deductions  = 0.00;
        $bonuses     = 0.00;
        $net         = round($gross - $deductions + $bonuses, 2);

        // Default to the most recently completed semi-monthly period
        $now   = Carbon::now();
        $start = $now->day > 15
            ? $now->copy()->startOfMonth()
            : $now->copy()->startOfMonth()->subMonth()->setDay(16);
        $end = $now->day > 15
            ? $now->copy()->setDay(15)
            : $now->copy()->startOfMonth()->subMonth()->endOfMonth();

        return [
            'employee_id'          => Employee::factory(),
            'payroll_period_start' => $start->toDateString(),
            'payroll_period_end'   => $end->toDateString(),
            'verified_hours'       => $hours,
            'hourly_rate'          => $hourlyRate,
            'gross_salary'         => $gross,
            'deductions'           => $deductions,
            'bonuses'              => $bonuses,
            'net_salary'           => $net,
            'processed_by'         => null,
        ];
    }

    // ─── Period states ─────────────────────────────────────────────────────────

    /**
     * First-half payroll period (1st–15th of the current month).
     */
    public function firstHalf(): static
    {
        return $this->state(function (array $attributes) {
            $now = Carbon::now();

            return [
                'payroll_period_start' => $now->copy()->startOfMonth()->toDateString(),
                'payroll_period_end'   => $now->copy()->startOfMonth()->setDay(15)->toDateString(),
            ];
        });
    }

    /**
     * Second-half payroll period (16th–end of the current month).
     */
    public function secondHalf(): static
    {
        return $this->state(function (array $attributes) {
            $now = Carbon::now();

            return [
                'payroll_period_start' => $now->copy()->startOfMonth()->setDay(16)->toDateString(),
                'payroll_period_end'   => $now->copy()->endOfMonth()->toDateString(),
            ];
        });
    }

    // ─── Compensation states ───────────────────────────────────────────────────

    /**
     * Add a random bonus (PHP 500–PHP 5,000) and recompute net_salary.
     */
    public function withBonus(): static
    {
        return $this->state(function (array $attributes) {
            $bonuses = fake()->randomFloat(2, 500.00, 5000.00);
            $net     = round(
                (float) $attributes['gross_salary'] - (float) $attributes['deductions'] + $bonuses,
                2
            );

            return [
                'bonuses'    => $bonuses,
                'net_salary' => $net,
            ];
        });
    }

    /**
     * Apply government-mandated-style deductions (SSS, PhilHealth, Pag-IBIG)
     * and recompute net_salary.
     */
    public function withDeductions(): static
    {
        return $this->state(function (array $attributes) {
            $gross = (float) $attributes['gross_salary'];

            // Approximate Philippine statutory deductions (rough %):
            $sss      = round($gross * 0.045, 2);  // SSS ≈ 4.5 %
            $philHealth = round($gross * 0.025, 2); // PhilHealth ≈ 2.5 %
            $pagIbig  = 100.00;                     // Pag-IBIG fixed PHP 100

            $deductions = round($sss + $philHealth + $pagIbig, 2);
            $net        = round($gross - $deductions + (float) $attributes['bonuses'], 2);

            return [
                'deductions' => $deductions,
                'net_salary' => $net,
            ];
        });
    }
}
