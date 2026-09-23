<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

interface DepartmentRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    public function find(int $id): ?array;

    public function create(string $name, string $description = ''): int;

    public function update(int $id, string $name, string $description = ''): bool;

    public function delete(int $id): bool;

    public function nameExists(string $name, ?int $excludeId = null): bool;

    /** @return array<int, array{department_name: string, employee_count: int|string}> */
    public function employeeDistribution(): array;

    public function countAll(): int;
}
