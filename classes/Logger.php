<?php
/**
 * Logger
 *
 * Writes rows to the activity_logs table so admins can audit who did what
 * and when (logins, clock-ins, payroll runs, CRUD actions, etc).
 */
require_once __DIR__ . '/Database.php';

class Logger
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Records an activity for a given user (or system if $userId is null).
     */
    public function log(?int $userId, string $activity): void
    {
        $sql = "INSERT INTO activity_logs (user_id, activity, timestamp) VALUES (?, ?, NOW())";
        $this->db->insert($sql, [$userId, $activity]);
    }

    /** Returns the most recent activity log entries, newest first. */
    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT al.log_id, al.activity, al.timestamp, u.username, u.role
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.user_id
                ORDER BY al.timestamp DESC
                LIMIT ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Returns all logs for a specific user. */
    public function getByUser(int $userId, int $limit = 50): array
    {
        $sql = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
