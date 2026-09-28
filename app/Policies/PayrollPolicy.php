<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view') || $user->can('own-payroll.view');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.view') || $payroll->employee_id === $user->employee?->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.manage');
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.manage');
    }
}
