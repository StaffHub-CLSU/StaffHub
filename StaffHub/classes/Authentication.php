<?php
/**
 * Authentication
 *
 * Handles login/logout, session lifecycle and role-based access control.
 * Composed with a User object rather than extending it (favor composition),
 * since "Authentication" is a behavior/service, not a "kind of" User.
 */
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Logger.php';

class Authentication
{
    private User $userModel;
    private Logger $logger;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new User();
        $this->logger = new Logger();
    }

    /**
     * Attempts to log a user in. Returns an array: ['success' => bool, 'message' => string]
     */
    public function login(string $username, string $password): array
    {
        $userRow = $this->userModel->findByUsername($username);

        if (!$userRow) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        if (!(bool) $userRow['is_active']) {
            return ['success' => false, 'message' => 'This account has been deactivated. Contact an administrator.'];
        }

        if (!User::verifyPassword($password, $userRow['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Regenerate session id on privilege change to prevent session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id']  = (int) $userRow['user_id'];
        $_SESSION['username'] = $userRow['username'];
        $_SESSION['role']     = $userRow['role'];

        // If this account belongs to an employee, also stash the employee_id.
        if ($userRow['role'] === 'employee') {
            $db = Database::getInstance();
            $emp = $db->fetch("SELECT employee_id, first_name, last_name FROM employees WHERE user_id = ?", [$userRow['user_id']]);
            if ($emp) {
                $_SESSION['employee_id'] = (int) $emp['employee_id'];
                $_SESSION['full_name'] = $emp['first_name'] . ' ' . $emp['last_name'];
            }
        } else {
            $_SESSION['full_name'] = 'Administrator';
        }

        $this->logger->log((int) $userRow['user_id'], "Logged in as {$userRow['role']}");

        return ['success' => true, 'message' => 'Login successful.', 'role' => $userRow['role']];
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->logger->log((int) $_SESSION['user_id'], 'Logged out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public static function getRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::getRole() === 'admin';
    }

    public static function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getEmployeeId(): ?int
    {
        return $_SESSION['employee_id'] ?? null;
    }

    public static function getFullName(): string
    {
        return $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User');
    }

    /**
     * Guard used at the top of every protected page. Redirects to login
     * (or returns 403 for AJAX) if the visitor is not logged in, and
     * optionally enforces a required role.
     */
    public static function requireLogin(?string $requiredRole = null, bool $isAjax = false): void
    {
        if (!self::isLoggedIn()) {
            if ($isAjax) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
                exit;
            }
            header('Location: ' . (self::isDeepPath() ? '../index.php' : 'index.php'));
            exit;
        }

        if ($requiredRole !== null && self::getRole() !== $requiredRole) {
            if ($isAjax) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied.']);
                exit;
            }
            http_response_code(403);
            die('<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;">403 - Access Denied</h2>');
        }
    }

    private static function isDeepPath(): bool
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        return (bool) preg_match('#/(admin|employee|ajax|reports)/#', $script);
    }
}
