<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Repository\Contract\ActivityLogRepositoryInterface;

final class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function log(?int $userId, string $activity): void
    {
        $this->db->insert(
            'INSERT INTO activity_logs (user_id, activity, timestamp) VALUES (?, ?, NOW())',
            [$userId, $activity],
        );
    }

    public function recent(int $limit = 10): array
    {
        $sql = 'SELECT al.log_id, al.activity, al.timestamp, u.username, u.role
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.user_id
                ORDER BY al.timestamp DESC
                LIMIT ?';
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function byUser(int $userId, int $limit = 50): array
    {
        $sql = 'SELECT * FROM activity_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?';
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
