<?php

declare(strict_types=1);

namespace StaffHub\Service;

interface LoggerInterface
{
    public function log(?int $userId, string $activity): void;

    /** @return array<int, array<string, mixed>> */
    public function getRecent(int $limit = 10): array;

    /** @return array<int, array<string, mixed>> */
    public function getByUser(int $userId, int $limit = 50): array;
}
