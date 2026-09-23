<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Entity\Attendance;
use StaffHub\Repository\Contract\AttendanceRepositoryInterface;
use StaffHub\Support\Validator;

/**
 * Smart timekeeping: clock in/out rules, verification, stats.
 * Only Verified rows are eligible for payroll.
 */
final class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendance,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array{success: bool, message: string} */
    public function clockIn(int $employeeId): array
    {
        $existing = $this->attendance->findToday($employeeId);

        if ($existing) {
            if ($existing['time_in'] && !$existing['time_out']) {
                return ['success' => false, 'message' => 'You are already clocked in. Please clock out first.'];
            }
            if ($existing['time_out']) {
                return ['success' => false, 'message' => 'You have already completed your attendance for today.'];
            }
        }

        $this->attendance->insertClockIn($employeeId);

        return ['success' => true, 'message' => 'Clocked in successfully at ' . date('h:i A') . '.'];
    }

    /** @return array{success: bool, message: string, total_hours?: float} */
    public function clockOut(int $employeeId): array
    {
        $existing = $this->attendance->findToday($employeeId);

        if (!$existing || !$existing['time_in']) {
            return ['success' => false, 'message' => 'No active clock-in found for today.'];
        }
        if ($existing['time_out']) {
            return ['success' => false, 'message' => 'You have already clocked out today.'];
        }

        $entity = Attendance::fromRow($existing);
        $timeIn = new \DateTime($entity->getTimeIn());
        $timeOut = new \DateTime();
        $totalHours = round(($timeOut->getTimestamp() - $timeIn->getTimestamp()) / 3600, 2);

        $this->attendance->updateClockOut((int) $existing['attendance_id'], $totalHours);

        return [
            'success' => true,
            'message' => 'Clocked out successfully at ' . date('h:i A') . ". Total hours: {$totalHours}h.",
            'total_hours' => $totalHours,
        ];
    }

    public function verify(int $attendanceId): bool
    {
        return $this->attendance->verify($attendanceId);
    }

    /** @return array{success: bool, message: string, count?: int} */
    public function verifyRange(string $start, string $end): array
    {
        $validator = new Validator();
        $validator->required($start, 'date_from', 'Start date')
            ->required($end, 'date_to', 'End date');

        if ($validator->fails()) {
            return ['success' => false, 'message' => (string) $validator->firstError()];
        }

        $count = $this->attendance->verifyRange($start, $end);
        return ['success' => true, 'message' => "{$count} record(s) verified.", 'count' => $count];
    }

    /** @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int} */
    public function search(array $filters, int $page, int $perPage): array
    {
        return $this->attendance->search($filters, $page, $perPage);
    }

    public function today(int $employeeId): ?Attendance
    {
        return $this->attendance->findTodayEntity($employeeId);
    }

    public function history(int $employeeId, int $limit = 30): array
    {
        return $this->attendance->historyForEmployee($employeeId, $limit);
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }
}
