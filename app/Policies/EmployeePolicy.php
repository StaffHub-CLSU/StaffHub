<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view') || $user->employee?->is($employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.manage') || ($user->can('profile.manage') && $user->employee?->is($employee));
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.manage') && ! $user->employee?->is($employee);
    }
}
