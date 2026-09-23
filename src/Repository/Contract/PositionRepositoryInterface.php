<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

interface PositionRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array<int, array<string, mixed>> */
    public function byDepartment(int $departmentId): array;

    public function find(int $id): ?array;

    public function create(string $name, ?int $departmentId): int;

    public function update(int $id, string $name, ?int $departmentId): bool;

    public function delete(int $id): bool;
}
