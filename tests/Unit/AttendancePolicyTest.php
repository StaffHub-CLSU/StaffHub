<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Policies\AttendancePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_without_attendance_manage_permission_cannot_update_their_own_record(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->user_id]);
        $attendance = Attendance::create([
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-01',
            'status' => 'Incomplete',
        ]);

        $canUpdate = (new AttendancePolicy())->update($user, $attendance);

        $this->assertFalse($canUpdate);
    }
}
