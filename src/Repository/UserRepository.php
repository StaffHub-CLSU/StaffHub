<?php

declare(strict_types=1);

namespace StaffHub\Repository;

use StaffHub\Core\Database;
use StaffHub\Entity\User;
use StaffHub\Repository\Contract\UserRepositoryInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly Database $db,
    ) {
    }

    public function findById(int $userId): ?User
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE user_id = ?', [$userId]);
        return $row ? User::fromRow($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE username = ?', [$username]);
        return $row ? User::fromRow($row) : null;
    }

    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        $sql = 'SELECT user_id FROM users WHERE username = ?';
        $params = [$username];
        if ($excludeUserId !== null) {
            $sql .= ' AND user_id != ?';
            $params[] = $excludeUserId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    public function create(string $username, string $plainPassword, string $role): int
    {
        $hash = User::hashPassword($plainPassword);
        return (int) $this->db->insert(
            'INSERT INTO users (username, password, role, is_active) VALUES (?, ?, ?, 1)',
            [$username, $hash, $role],
        );
    }

    public function updatePassword(int $userId, string $newPlainPassword): bool
    {
        $hash = User::hashPassword($newPlainPassword);
        return $this->db->execute('UPDATE users SET password = ? WHERE user_id = ?', [$hash, $userId]) > 0;
    }

    public function updateUsername(int $userId, string $newUsername): bool
    {
        return $this->db->execute('UPDATE users SET username = ? WHERE user_id = ?', [$newUsername, $userId]) > 0;
    }

    public function setActive(int $userId, bool $active): bool
    {
        return $this->db->execute(
            'UPDATE users SET is_active = ? WHERE user_id = ?',
            [$active ? 1 : 0, $userId],
        ) > 0;
    }

    public function delete(int $userId): bool
    {
        return $this->db->execute('DELETE FROM users WHERE user_id = ?', [$userId]) > 0;
    }
}
