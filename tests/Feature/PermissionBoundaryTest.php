<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_without_report_permission_is_forbidden_from_viewing_reports(): void
    {
        $admin = $this->administratorWithPermissions([]);

        $this->actingAs($admin)
            ->get('/admin/reports/attendance')
            ->assertForbidden();
    }

    public function test_admin_without_organization_permission_is_forbidden_from_updating_a_position(): void
    {
        $admin = $this->administratorWithPermissions([]);
        $department = Department::create(['department_name' => 'Operations']);
        $position = Position::create([
            'position_name' => 'Coordinator',
            'department_id' => $department->department_id,
        ]);

        $this->actingAs($admin)
            ->put('/admin/positions/'.$position->position_id, [
                'position_name' => 'Senior Coordinator',
                'department_id' => $department->department_id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('positions', [
            'position_id' => $position->position_id,
            'position_name' => 'Coordinator',
        ]);
    }

    private function administratorWithPermissions(array $permissions): User
    {
        $adminRole = Role::findOrCreate('Admin', 'web');
        $adminRole->syncPermissions(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'))
                ->all(),
        );

        $user = User::factory()->create();
        $user->assignRole($adminRole);

        return $user;
    }
}
