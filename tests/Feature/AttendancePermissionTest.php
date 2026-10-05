<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendancePermissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_with_attendance_permission_can_record_attendance(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.manage']);
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->post('/admin/attendance', [
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00',
            'time_out' => '2026-10-01 17:00',
            'status' => 'Completed',
        ])->assertRedirect('/admin/attendance');

        $record = Attendance::query()->whereBelongsTo($employee)->firstOrFail();
        $this->assertSame('2026-10-01', $record->attendance_date->toDateString());
        $this->assertSame('Completed', $record->status);
    }

    public function test_admin_with_attendance_permission_can_update_an_attendance_record(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.manage']);
        $attendance = $this->attendanceRecord(['status' => 'Completed', 'time_in' => '2026-10-01 08:00:00', 'time_out' => '2026-10-01 12:00:00']);

        $this->actingAs($admin)->put('/admin/attendance/'.$attendance->attendance_id, [
            'employee_id' => $attendance->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00',
            'time_out' => '2026-10-01 17:00',
            'status' => 'Completed',
        ])->assertRedirect('/admin/attendance');

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'total_hours' => 9,
        ]);
    }

    public function test_admin_with_attendance_permission_can_verify_completed_attendance(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.manage']);
        $attendance = $this->attendanceRecord(['status' => 'Completed']);

        $this->actingAs($admin)->from('/admin/attendance')->post('/admin/attendance/'.$attendance->attendance_id.'/verify')
            ->assertRedirect('/admin/attendance');

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Verified',
        ]);
    }

    public function test_admin_without_attendance_permission_is_forbidden_from_recording_attendance(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.view']);
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->post('/admin/attendance', [
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00',
            'time_out' => '2026-10-01 17:00',
            'status' => 'Completed',
        ])->assertForbidden();

        $this->assertDatabaseMissing('attendance', ['employee_id' => $employee->employee_id]);
    }

    public function test_admin_without_attendance_permission_is_forbidden_from_updating_an_attendance_record(): void
    {
        $admin = $this->administratorWithPermissions([]);
        $attendance = $this->attendanceRecord(['status' => 'Completed', 'total_hours' => 4]);

        $this->actingAs($admin)->put('/admin/attendance/'.$attendance->attendance_id, [
            'employee_id' => $attendance->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00',
            'time_out' => '2026-10-01 17:00',
            'status' => 'Verified',
        ])->assertForbidden();

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Completed',
            'total_hours' => 4,
        ]);
    }

    public function test_admin_without_attendance_permission_is_forbidden_from_deleting_an_attendance_record(): void
    {
        $admin = $this->administratorWithPermissions([]);
        $attendance = $this->attendanceRecord(['status' => 'Completed']);

        $this->actingAs($admin)->delete('/admin/attendance/'.$attendance->attendance_id)->assertForbidden();

        $this->assertDatabaseHas('attendance', ['attendance_id' => $attendance->attendance_id]);
    }

    public function test_admin_without_attendance_permission_is_forbidden_from_verifying_an_attendance_record(): void
    {
        $admin = $this->administratorWithPermissions([]);
        $attendance = $this->attendanceRecord(['status' => 'Completed']);

        $this->actingAs($admin)->post('/admin/attendance/'.$attendance->attendance_id.'/verify')->assertForbidden();

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Completed',
        ]);
    }

    public function test_employee_without_attendance_permission_is_forbidden_from_admin_attendance_actions(): void
    {
        $employeeUser = $this->userWithPermissions('Employee', ['profile.manage']);
        $employee = Employee::factory()->create(['user_id' => $employeeUser->user_id]);
        $attendance = $this->attendanceRecord(['employee_id' => $employee->employee_id, 'status' => 'Completed']);

        $this->actingAs($employeeUser)->post('/admin/attendance', [
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-02',
            'status' => 'Completed',
        ])->assertForbidden();

        $this->actingAs($employeeUser)->put('/admin/attendance/'.$attendance->attendance_id, [
            'employee_id' => $attendance->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00',
            'time_out' => '2026-10-01 17:00',
            'status' => 'Verified',
        ])->assertForbidden();

        $this->actingAs($employeeUser)->post('/admin/attendance/'.$attendance->attendance_id.'/verify')->assertForbidden();
        $this->actingAs($employeeUser)->delete('/admin/attendance/'.$attendance->attendance_id)->assertForbidden();

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Completed',
        ]);
        $this->assertDatabaseMissing('attendance', ['attendance_date' => '2026-10-02']);
    }

    public function test_verification_is_restricted_to_completed_attendance(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.manage']);
        $attendance = $this->attendanceRecord(['status' => 'Incomplete']);

        $this->actingAs($admin)->post('/admin/attendance/'.$attendance->attendance_id.'/verify')->assertForbidden();

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Incomplete',
        ]);
    }

    public function test_attendance_cannot_be_marked_verified_through_the_store_endpoint(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.manage']);
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->from('/admin/attendance')->post('/admin/attendance', [
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-02',
            'time_in' => '2026-10-02 08:00',
            'status' => 'Verified',
        ])->assertRedirect('/admin/attendance')->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('attendance', ['employee_id' => $employee->employee_id]);
    }

    public function test_incomplete_attendance_cannot_be_marked_verified_through_the_update_endpoint(): void
    {
        $admin = $this->administratorWithPermissions(['attendance.manage']);
        $attendance = $this->attendanceRecord(['status' => 'Incomplete']);

        $this->actingAs($admin)->from('/admin/attendance')->put('/admin/attendance/'.$attendance->attendance_id, [
            'employee_id' => $attendance->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => '2026-10-01 08:00',
            'status' => 'Verified',
        ])->assertRedirect('/admin/attendance')->assertSessionHasErrors('status');

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Incomplete',
        ]);
    }

    public function test_guests_are_redirected_to_login_before_modifying_attendance(): void
    {
        $attendance = $this->attendanceRecord(['status' => 'Completed']);

        $this->post('/admin/attendance/'.$attendance->attendance_id.'/verify')->assertRedirect('/login');
        $this->delete('/admin/attendance/'.$attendance->attendance_id)->assertRedirect('/login');

        $this->assertDatabaseHas('attendance', [
            'attendance_id' => $attendance->attendance_id,
            'status' => 'Completed',
        ]);
    }

    private function attendanceRecord(array $overrides = []): Attendance
    {
        $employee = Employee::factory()->create();

        return Attendance::create([
            'employee_id' => $employee->employee_id,
            'attendance_date' => '2026-10-01',
            'time_in' => null,
            'time_out' => null,
            'total_hours' => 0,
            'status' => 'Incomplete',
            ...$overrides,
        ]);
    }

    private function administratorWithPermissions(array $permissions): User
    {
        return $this->userWithPermissions('Admin', $permissions);
    }

    private function userWithPermissions(string $roleName, array $permissions): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'))
                ->all(),
        );

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
