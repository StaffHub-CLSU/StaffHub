<?php

namespace App\Policies;

use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view') || $user->can('organization.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('organization.manage');
    }
}
