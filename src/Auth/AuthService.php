<?php

declare(strict_types=1);

namespace StaffHub\Auth;

use StaffHub\Repository\Contract\ActivityLogRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\UserRepositoryInterface;
use StaffHub\Entity\User;

/**
 * Login/logout and session lifecycle. Static accessors read the session
 * without requiring a container (used by middleware and layouts).
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ActivityLogRepositoryInterface $logs,
    ) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @return array{success: bool, message: string, role?: string}
     */
    public function login(string $username, string $password): array
    {
        $user = $this->users->findByUsername($username);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        if (!$user->isActive()) {
            return ['success' => false, 'message' => 'This account has been deactivated. Contact an administrator.'];
        }

        if (!User::verifyPassword($password, $user->getPasswordHash())) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Regenerate session id on privilege change to prevent session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user->getUserId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['role'] = $user->getRole();

        if ($user->getRole() === 'employee') {
            $employee = $this->employees->findByUserId((int) $user->getUserId());
            if ($employee) {
                $_SESSION['employee_id'] = (int) $employee['employee_id'];
                $_SESSION['full_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            }
        } else {
            $_SESSION['full_name'] = 'Administrator';
        }

        $this->logs->log((int) $user->getUserId(), "Logged in as {$user->getRole()}");

        return ['success' => true, 'message' => 'Login successful.', 'role' => $user->getRole()];
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->logs->log((int) $_SESSION['user_id'], 'Logged out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    // ----- Static session accessors (no DI needed) -----

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    public static function getRole(): ?string
    {
        self::startSession();
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::getRole() === 'admin';
    }

    public static function getUserId(): ?int
    {
        self::startSession();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function getEmployeeId(): ?int
    {
        self::startSession();
        return isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : null;
    }

    public static function getFullName(): string
    {
        self::startSession();
        return $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User');
    }
}
