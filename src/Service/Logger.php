<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Repository\Contract\ActivityLogRepositoryInterface;

/**
 * Audit trail writer/reader for activity_logs.
 */
final class Logger implements LoggerInterface
{
    public function __construct(
        private readonly ActivityLogRepositoryInterface $logs,
    ) {
    }

    public function log(?int $userId, string $activity): void
    {
        $this->logs->log($userId, $activity);
    }

    public function getRecent(int $limit = 10): array
    {
        return $this->logs->recent($limit);
    }

    public function getByUser(int $userId, int $limit = 50): array
    {
        return $this->logs->byUser($userId, $limit);
    }
}
