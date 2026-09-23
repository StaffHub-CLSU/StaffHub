<?php

declare(strict_types=1);

namespace StaffHub\Repository\Contract;

use StaffHub\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $userId): ?User;

    public function findByUsername(string $username): ?User;

    public function usernameExists(string $username, ?int $excludeUserId = null): bool;

    public function create(string $username, string $plainPassword, string $role): int;

    public function updatePassword(int $userId, string $newPlainPassword): bool;

    public function updateUsername(int $userId, string $newUsername): bool;

    public function setActive(int $userId, bool $active): bool;

    public function delete(int $userId): bool;
}
