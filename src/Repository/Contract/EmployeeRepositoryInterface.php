<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

use StaffHub\Entity\Employee;

interface EmployeeRepositoryInterface
{
    public function findJoined(int $employeeId): ?array;

    public function findByUserId(int $userId): ?array;

    public function findEntity(int $employeeId): ?Employee;

    public function emailExists(string $email, ?int $excludeId = null): bool;

    public function generateNextCode(): string;

    public function insert(array $columns): int;

    public function update(int $employeeId, array $data): bool;

    public function updateProfilePicture(int $employeeId, string $filename): bool;

    public function setActive(int $employeeId, bool $active): bool;

    public function delete(int $employeeId): bool;

    /**
     * @param array<string, mixed> $filters
     * @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int, sort_by: string, sort_dir: string}
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 10, string $sortBy = '', string $sortDir = 'desc'): array;

    public function countActive(): int;

    public function countAll(): int;
}
