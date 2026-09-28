<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view') || $user->can('own-attendance.manage');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.view') || $attendance->employee_id === $user->employee?->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.manage') || $user->can('own-attendance.manage');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.manage') || $attendance->employee_id === $user->employee?->employee_id;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.manage');
    }

    public function verify(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.manage') && $attendance->status === 'Completed';
    }
}
