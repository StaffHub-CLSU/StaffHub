<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function clock(Employee $employee, string $action): Attendance
    {
        return DB::transaction(function () use ($employee, $action): Attendance {
            $attendance = Attendance::query()->whereBelongsTo($employee)->whereDate('attendance_date', today())->lockForUpdate()->first();

            if ($action === 'in') {
                if ($attendance !== null) {
                    throw ValidationException::withMessages(['attendance' => 'You are already clocked in today.']);
                }

                return Attendance::create(['employee_id' => $employee->employee_id, 'attendance_date' => today(), 'time_in' => now(), 'status' => 'Incomplete']);
            }

            if ($attendance?->time_in === null || $attendance->time_out !== null) {
                throw ValidationException::withMessages(['attendance' => 'No active clock-in was found for today.']);
            }

            $attendance->update(['time_out' => now(), 'total_hours' => round($attendance->time_in->diffInSeconds(now()) / 3600, 2), 'status' => 'Completed']);

            return $attendance->fresh();
        });
    }

    public function verify(Attendance $attendance): Attendance
    {
        $attendance->update(['status' => 'Verified']);

        return $attendance->fresh();
    }
}
