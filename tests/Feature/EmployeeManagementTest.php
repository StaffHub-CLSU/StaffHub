<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_cannot_access_administration_routes(): void
    {
        $employee = $this->userWithRole('Employee', []);

        $this->actingAs($employee)->get('/admin/employees')->assertForbidden();
    }

    public function test_admin_can_create_an_employee_and_account(): void
    {
        $admin = $this->userWithRole('Admin', ['employees.manage', 'employees.view']);
        $payload = [
            'employee_code' => 'EMP-10001',
            'username' => 'new.employee',
            'first_name' => 'New',
            'last_name' => 'Employee',
            'email' => 'new.employee@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'Other',
            'birthdate' => '2000-01-01',
            'contact_number' => '09171234567',
            'employment_status' => 'Full-Time',
            'basic_hourly_rate' => 100,
            'date_hired' => '2026-01-01',
        ];

        $this->actingAs($admin)->post('/admin/employees', $payload)->assertRedirect('/admin/employees');

        $this->assertDatabaseHas('users', ['username' => 'new.employee']);
        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-10001']);
        $this->assertTrue(User::where('username', 'new.employee')->firstOrFail()->hasRole('Employee'));
    }

    public function test_employee_creation_requires_the_core_fields(): void
    {
        $admin = $this->userWithRole('Admin', ['employees.manage']);

        $employeeCount = Employee::count();

        $this->actingAs($admin)->post('/admin/employees', [])->assertSessionHasErrors(['employee_code', 'username', 'first_name', 'last_name', 'email', 'password']);
        $this->assertDatabaseCount('employees', $employeeCount);
    }

    private function userWithRole(string $roleName, array $permissions): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
