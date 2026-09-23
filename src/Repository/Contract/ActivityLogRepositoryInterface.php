<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

interface ActivityLogRepositoryInterface
{
    public function log(?int $userId, string $activity): void;

    /** @return array<int, array<string, mixed>> */
    public function recent(int $limit = 10): array;

    /** @return array<int, array<string, mixed>> */
    public function byUser(int $userId, int $limit = 50): array;
}
