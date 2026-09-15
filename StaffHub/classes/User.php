<?php
/**
 * User
 *
 * Represents a login account (admin or employee). Handles the users table
 * CRUD and centralizes password hashing so no other class ever calls
 * password_hash()/password_verify() directly.
 */
require_once __DIR__ . '/Database.php';

class User
{
    private Database $db;

    // Properties mirror the users table -> encapsulated object state
    public ?int $userId = null;
    public string $username = '';
    public string $role = 'employee';
    public bool $isActive = true;

    public function __construct(?array $data = null)
    {
        $this->db = Database::getInstance();
        if ($data) {
            $this->hydrate($data);
        }
    }

    private function hydrate(array $row): void
    {
        $this->userId   = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $this->username = $row['username'] ?? '';
        $this->role     = $row['role'] ?? 'employee';
        $this->isActive = isset($row['is_active']) ? (bool) $row['is_active'] : true;
    }

    /** Hashes a plaintext password using bcrypt. */
    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    /** Verifies a plaintext password against a stored hash. */
    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT user_id FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeUserId !== null) {
            $sql .= " AND user_id != ?";
            $params[] = $excludeUserId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    /** Creates a new user account and returns the new user_id. */
    public function create(string $username, string $plainPassword, string $role): int
    {
        $hash = self::hashPassword($plainPassword);
        $sql = "INSERT INTO users (username, password, role, is_active) VALUES (?, ?, ?, 1)";
        return (int) $this->db->insert($sql, [$username, $hash, $role]);
    }

    public function findById(int $userId): ?array
    {
        return $this->db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public function updatePassword(int $userId, string $newPlainPassword): bool
    {
        $hash = self::hashPassword($newPlainPassword);
        return $this->db->execute("UPDATE users SET password = ? WHERE user_id = ?", [$hash, $userId]) > 0;
    }

    public function updateUsername(int $userId, string $newUsername): bool
    {
        return $this->db->execute("UPDATE users SET username = ? WHERE user_id = ?", [$newUsername, $userId]) > 0;
    }

    public function setActive(int $userId, bool $active): bool
    {
        return $this->db->execute("UPDATE users SET is_active = ? WHERE user_id = ?", [$active ? 1 : 0, $userId]) > 0;
    }

    public function delete(int $userId): bool
    {
        return $this->db->execute("DELETE FROM users WHERE user_id = ?", [$userId]) > 0;
    }
}
