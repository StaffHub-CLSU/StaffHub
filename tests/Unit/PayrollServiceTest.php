<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_deductions_that_exceed_gross_salary_plus_bonuses(): void
    {
        $employee = Employee::factory()->create(['basic_hourly_rate' => 100]);
        Attendance::create([
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-09-10',
            'total_hours' => 8,
            'status' => 'Verified',
        ]);
        $processor = User::factory()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Deductions cannot exceed the gross salary plus bonuses.');

        (new PayrollService())->calculate($employee, [
            'payroll_period_start' => '2026-09-01',
            'payroll_period_end' => '2026-09-15',
            'bonuses' => 0,
            'deductions' => 900,
        ], $processor);
    }

    public function test_it_allows_deductions_equal_to_gross_salary_plus_bonuses(): void
    {
        $employee = Employee::factory()->create(['basic_hourly_rate' => 100]);
        Attendance::create([
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-09-10',
            'total_hours' => 8,
            'status' => 'Verified',
        ]);
        $processor = User::factory()->create();

        $payroll = (new PayrollService())->calculate($employee, [
            'payroll_period_start' => '2026-09-01',
            'payroll_period_end' => '2026-09-15',
            'bonuses' => 0,
            'deductions' => 800,
        ], $processor);

        $this->assertSame('0.00', $payroll->net_salary);
    }
}
