<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

interface PayrollRepositoryInterface
{
    public function findEmployeeForPayroll(int $employeeId): ?array;

    public function upsert(array $values): void;

    public function findById(int $payrollId): ?array;

    public function historyForEmployee(int $employeeId, int $limit = 24): array;

    /**
     * @param array<string, mixed> $filters
     * @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 15): array;

    public function countProcessed(): int;

    public function monthlyExpenses(int $months = 6): array;

    public function lastForEmployee(int $employeeId): ?array;
}
