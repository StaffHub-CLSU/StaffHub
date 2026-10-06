<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 *
 * Default: a completed attendance record for today (Present).
 *
 * States available:
 *  - present()    → time_in & time_out set, status = Present, hours computed
 *  - absent()     → no time_in / time_out, status = Absent, total_hours = 0
 *  - late()       → time_in after 9 AM, status = Late, hours computed
 *  - halfDay()    → roughly 4 hours worked, status = Half-Day
 *  - incomplete() → time_in set, time_out = null, status = Incomplete
 *  - forDate(Carbon|string) → set a specific attendance_date
 */
class AttendanceFactory extends Factory
{
    protected $table = 'attendance';

    protected $model = Attendance::class;

    /** Standard shift start time (8:00 AM). */
    private const SHIFT_START = '08:00:00';

    /** Standard shift end time (5:00 PM). */
    private const SHIFT_END = '17:00:00';

    public function definition(): array
    {
        $date    = today()->toDateString();
        $timeIn  = Carbon::parse("{$date} " . self::SHIFT_START);
        $timeOut = Carbon::parse("{$date} " . self::SHIFT_END);

        return [
            'employee_id'     => Employee::factory(),
            'attendance_date' => $date,
            'time_in'         => $timeIn,
            'time_out'        => $timeOut,
            'total_hours'     => round($timeOut->diffInMinutes($timeIn) / 60, 2),
            'status'          => 'Present',
        ];
    }

    // ─── Attendance-status states ──────────────────────────────────────────────

    /**
     * Standard present day: 8 AM–5 PM, 9 hours.
     */
    public function present(): static
    {
        return $this->state(function (array $attributes) {
            $date    = $attributes['attendance_date'] ?? today()->toDateString();
            $timeIn  = Carbon::parse("{$date} " . self::SHIFT_START);
            $timeOut = Carbon::parse("{$date} " . self::SHIFT_END);

            return [
                'time_in'     => $timeIn,
                'time_out'    => $timeOut,
                'total_hours' => round($timeOut->diffInMinutes($timeIn) / 60, 2),
                'status'      => 'Present',
            ];
        });
    }

    /**
     * Absent — no clock-in or clock-out recorded.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_in'     => null,
            'time_out'    => null,
            'total_hours' => 0,
            'status'      => 'Absent',
        ]);
    }

    /**
     * Late arrival — time_in between 9:01 AM and 11:00 AM.
     */
    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $date    = $attributes['attendance_date'] ?? today()->toDateString();
            $timeIn  = Carbon::parse("{$date} " . fake()->time('H:i:s', strtotime("{$date} 11:00:00")))
                ->setTime(fake()->numberBetween(9, 10), fake()->numberBetween(1, 59));
            $timeOut = Carbon::parse("{$date} " . self::SHIFT_END);

            return [
                'time_in'     => $timeIn,
                'time_out'    => $timeOut,
                'total_hours' => round($timeOut->diffInMinutes($timeIn) / 60, 2),
                'status'      => 'Late',
            ];
        });
    }

    /**
     * Half-day — approximately 4 hours worked (AM shift only).
     */
    public function halfDay(): static
    {
        return $this->state(function (array $attributes) {
            $date    = $attributes['attendance_date'] ?? today()->toDateString();
            $timeIn  = Carbon::parse("{$date} " . self::SHIFT_START);
            $timeOut = Carbon::parse("{$date} 12:00:00");

            return [
                'time_in'     => $timeIn,
                'time_out'    => $timeOut,
                'total_hours' => round($timeOut->diffInMinutes($timeIn) / 60, 2),
                'status'      => 'Half-Day',
            ];
        });
    }

    /**
     * Incomplete — clocked in but no clock-out recorded yet.
     */
    public function incomplete(): static
    {
        return $this->state(function (array $attributes) {
            $date   = $attributes['attendance_date'] ?? today()->toDateString();
            $timeIn = Carbon::parse("{$date} " . self::SHIFT_START);

            return [
                'time_in'     => $timeIn,
                'time_out'    => null,
                'total_hours' => 0,
                'status'      => 'Incomplete',
            ];
        });
    }

    // ─── Date helper ──────────────────────────────────────────────────────────

    /**
     * Set a specific attendance date.
     *
     * Usage: Attendance::factory()->forDate('2026-09-15')->create()
     *
     * @param  Carbon|string  $date
     */
    public function forDate(Carbon|string $date): static
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;

        return $this->state(fn (array $attributes) => [
            'attendance_date' => $dateString,
        ]);
    }
}
