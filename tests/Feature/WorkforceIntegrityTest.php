<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkforceIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_administrator_can_complete_a_pending_registered_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->post('/register', [
            'first_name' => 'Pending',
            'last_name' => 'Employee',
            'username' => 'pending.employee',
            'email' => 'pending.employee@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $pendingUser = User::query()->where('username', 'pending.employee')->firstOrFail();

        $this->actingAs($admin)->post('/admin/employees', $this->employeePayload([
            'username' => 'pending.employee',
            'email' => 'pending.employee@example.test',
        ]))->assertRedirect('/admin/employees');

        $this->assertDatabaseHas('employees', [
            'user_id' => $pendingUser->user_id,
            'employee_code' => 'EMP-90123',
        ]);
        $this->assertTrue($pendingUser->fresh()->hasRole('Employee'));
    }

    public function test_attendance_updates_recalculate_total_hours(): void
    {
        $admin = $this->administrator();
        $employee = Employee::factory()->create();
        $attendance = Attendance::create([
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00:00',
            'time_out' => '2026-10-01 12:00:00',
            'total_hours' => 4,
            'status' => 'Completed',
        ]);

        $this->actingAs($admin)->put('/admin/attendance/'.$attendance->attendance_id, [
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00:00',
            'time_out' => '2026-10-01 17:00:00',
            'status' => 'Completed',
        ])->assertRedirect('/admin/attendance');

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'total_hours' => 9,
        ]);
    }

    public function test_overlapping_payroll_periods_are_rejected(): void
    {
        $admin = $this->administrator();
        $employee = Employee::factory()->create(['basic_hourly_rate' => 100]);
        Attendance::create([
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-09-15',
            'total_hours' => 8,
            'status' => 'Verified',
        ]);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->employee_id,
            'payroll_period_start' => '2026-09-01',
            'payroll_period_end' => '2026-09-15',
            'bonuses' => 0,
            'deductions' => 0,
        ])->assertRedirect('/admin/payroll');

        $this->actingAs($admin)->from('/admin/payroll')->post('/admin/payroll', [
            'employee_id' => $employee->employee_id,
            'payroll_period_start' => '2026-09-15',
            'payroll_period_end' => '2026-09-30',
            'bonuses' => 0,
            'deductions' => 0,
        ])->assertRedirect('/admin/payroll')->assertSessionHasErrors('payroll_period_start');

        $this->assertSame(1, Payroll::query()->where('employee_id', $employee->employee_id)->count());
    }

    public function test_a_position_must_belong_to_the_selected_employee_department(): void
    {
        $admin = $this->administrator();
        $department = Department::create(['department_name' => fake()->unique()->company()]);
        $otherDepartment = Department::create(['department_name' => fake()->unique()->company()]);
        $position = Position::create([
            'position_name' => 'Accountant',
            'department_id' => $otherDepartment->department_id,
        ]);

        $this->actingAs($admin)->from('/admin/employees')->post('/admin/employees', $this->employeePayload([
            'department_id' => $department->department_id,
            'position_id' => $position->position_id,
        ]))->assertRedirect('/admin/employees')->assertSessionHasErrors('position_id');

        $this->assertDatabaseMissing('employees', ['employee_code' => 'EMP-90123']);
    }

    private function administrator(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }

    private function employeePayload(array $overrides = []): array
    {
        return [
            'employee_code' => 'EMP-90123',
            'username' => 'employee.90123',
            'first_name' => 'Employee',
            'last_name' => 'Test',
            'email' => 'employee.90123@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'Other',
            'birthdate' => '2000-01-01',
            'contact_number' => '09171234567',
            'employment_status' => 'Full-Time',
            'basic_hourly_rate' => 100,
            'date_hired' => '2026-01-01',
            ...$overrides,
        ];
    }
}
