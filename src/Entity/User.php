<?php

declare(strict_types=1);

namespace StaffHub\Entity;

/**
 * Login account (admin or employee). Pure domain state — no SQL.
 */
final class User
{
    private ?int $userId = null;
    private string $username = '';
    private string $role = 'employee';
    private bool $isActive = true;
    private string $passwordHash = '';

    public static function fromRow(array $row): self
    {
        $user = new self();
        $user->userId = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $user->username = $row['username'] ?? '';
        $user->role = $row['role'] ?? 'employee';
        $user->isActive = isset($row['is_active']) ? (bool) $row['is_active'] : true;
        $user->passwordHash = $row['password'] ?? '';
        return $user;
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $active): self
    {
        $this->isActive = $active;
        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $hash): self
    {
        $this->passwordHash = $hash;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user_id'   => $this->userId,
            'username'  => $this->username,
            'role'      => $this->role,
            'is_active' => $this->isActive ? 1 : 0,
        ];
    }
}
