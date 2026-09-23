<?php

declare(strict_types=1);

namespace StaffHub\Entity;

/**
 * One employee work day. Status lifecycle: Incomplete → Completed → Verified.
 */
final class Attendance
{
    public const STATUS_INCOMPLETE = 'Incomplete';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_VERIFIED = 'Verified';

    private ?int $attendanceId = null;
    private int $employeeId = 0;
    private string $attendanceDate = '';
    private ?string $timeIn = null;
    private ?string $timeOut = null;
    private float $totalHours = 0.0;
    private string $status = self::STATUS_INCOMPLETE;

    public static function fromRow(array $row): self
    {
        $a = new self();
        $a->attendanceId = isset($row['attendance_id']) ? (int) $row['attendance_id'] : null;
        $a->employeeId = (int) ($row['employee_id'] ?? 0);
        $a->attendanceDate = $row['attendance_date'] ?? '';
        $a->timeIn = $row['time_in'] ?? null;
        $a->timeOut = $row['time_out'] ?? null;
        $a->totalHours = isset($row['total_hours']) ? (float) $row['total_hours'] : 0.0;
        $a->status = $row['status'] ?? self::STATUS_INCOMPLETE;
        return $a;
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'sh-badge-verified',
            self::STATUS_COMPLETED => 'sh-badge-completed',
            default => 'sh-badge-incomplete',
        };
    }

    public function isClockedIn(): bool
    {
        return $this->timeIn !== null && $this->timeOut === null;
    }

    public function isCompleted(): bool
    {
        return $this->timeOut !== null;
    }

    public function getAttendanceId(): ?int
    {
        return $this->attendanceId;
    }

    public function setAttendanceId(?int $id): self
    {
        $this->attendanceId = $id;
        return $this;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): self
    {
        $this->employeeId = $employeeId;
        return $this;
    }

    public function getAttendanceDate(): string
    {
        return $this->attendanceDate;
    }

    public function setAttendanceDate(string $date): self
    {
        $this->attendanceDate = $date;
        return $this;
    }

    public function getTimeIn(): ?string
    {
        return $this->timeIn;
    }

    public function setTimeIn(?string $timeIn): self
    {
        $this->timeIn = $timeIn;
        return $this;
    }

    public function getTimeOut(): ?string
    {
        return $this->timeOut;
    }

    public function setTimeOut(?string $timeOut): self
    {
        $this->timeOut = $timeOut;
        return $this;
    }

    public function getTotalHours(): float
    {
        return $this->totalHours;
    }

    public function setTotalHours(float $hours): self
    {
        $this->totalHours = $hours;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'attendance_id'   => $this->attendanceId,
            'employee_id'     => $this->employeeId,
            'attendance_date' => $this->attendanceDate,
            'time_in'         => $this->timeIn,
            'time_out'        => $this->timeOut,
            'total_hours'     => $this->totalHours,
            'status'          => $this->status,
        ];
    }
}
