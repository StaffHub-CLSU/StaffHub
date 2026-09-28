<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['employees.view', 'employees.manage', 'organization.manage', 'attendance.view', 'attendance.manage', 'payroll.view', 'payroll.manage', 'reports.view', 'profile.manage', 'own-attendance.manage', 'own-payroll.view'];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $admin = Role::findOrCreate('Admin', 'web');
        $employee = Role::findOrCreate('Employee', 'web');
        $admin->syncPermissions($permissions);
        $employee->syncPermissions(['profile.manage', 'own-attendance.manage', 'own-payroll.view']);
        if (app()->environment(['local', 'testing'])) {
            $user = User::firstOrCreate(['username' => 'admin'], ['name' => 'StaffHub Admin', 'email' => 'admin@staffhub.test', 'password' => 'Password123!', 'is_active' => true]);
            $user->forceFill(['name' => 'StaffHub Admin', 'is_active' => true])->save();
            $user->syncRoles([$admin]);
        }
    }
}
