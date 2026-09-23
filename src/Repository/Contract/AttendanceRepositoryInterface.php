<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

use StaffHub\Entity\Attendance;

interface AttendanceRepositoryInterface
{
    public function findToday(int $employeeId): ?array;

    public function findTodayEntity(int $employeeId): ?Attendance;

    public function insertClockIn(int $employeeId): void;

    public function updateClockOut(int $attendanceId, float $totalHours): void;

    public function verify(int $attendanceId): bool;

    public function verifyRange(string $startDate, string $endDate): int;

    /**
     * @param array<string, mixed> $filters
     * @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 15): array;

    public function historyForEmployee(int $employeeId, int $limit = 30): array;

    public function verifiedHours(int $employeeId, string $startDate, string $endDate): float;

    public function countPresentToday(): int;

    public function countTotalRecords(): int;

    public function dailyStats(int $days = 7): array;

    public function recentClockIns(int $limit = 5): array;

    public function recentClockOuts(int $limit = 5): array;
}
