<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\PayrollPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_can_view_employees_organization_and_payroll_but_cannot_manage_them(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $this->assertTrue((new EmployeePolicy())->viewAny($manager));
        $this->assertFalse((new EmployeePolicy())->create($manager));

        $this->assertTrue((new DepartmentPolicy())->viewAny($manager));
        $this->assertFalse((new DepartmentPolicy())->create($manager));

        $this->assertTrue((new PayrollPolicy())->viewAny($manager));
        $payroll = Payroll::make(['employee_id' => Employee::factory()->create()->employee_id]);
        $this->assertFalse((new PayrollPolicy())->create($manager));
        $this->assertFalse((new PayrollPolicy())->update($manager, $payroll));
    }

    public function test_an_employee_without_organization_permission_cannot_view_departments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $employee = User::factory()->create();
        $employee->assignRole('Employee');

        $this->assertFalse((new DepartmentPolicy())->viewAny($employee));
    }
}
