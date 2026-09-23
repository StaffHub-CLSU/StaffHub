<?php

declare(strict_types=1);

namespace StaffHub\Auth;

use StaffHub\Core\Request;
use StaffHub\Core\Response;
use function StaffHub\Support\url;

/**
 * Route middleware factories. Return a callable that yields ?Response
 * (null continues to the controller).
 */
final class AuthMiddleware
{
    /**
     * Require a logged-in user; optionally enforce a role.
     * AJAX/API requests get JSON 401/403; pages redirect to /login or show 403.
     */
    public static function requireLogin(?string $role = null): callable
    {
        return static function (Request $request) use ($role): ?Response {
            if (!AuthService::isLoggedIn()) {
                if ($request->wantsJson()) {
                    return Response::json(['success' => false, 'message' => 'Not authenticated.'], 401);
                }
                return Response::redirect(url('/login'));
            }

            if ($role !== null && AuthService::getRole() !== $role) {
                if ($request->wantsJson()) {
                    return Response::json(['success' => false, 'message' => 'Access denied.'], 403);
                }
                return Response::html(
                    '<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;">403 - Access Denied</h2>',
                    403,
                );
            }

            return null;
        };
    }

    /** Any authenticated user (admin or employee). */
    public static function requireAny(): callable
    {
        return self::requireLogin(null);
    }

    /** Guests only — bounce already-logged-in users to their dashboard. */
    public static function guest(): callable
    {
        return static function (Request $request): ?Response {
            if (AuthService::isLoggedIn()) {
                $target = AuthService::isAdmin() ? '/admin' : '/employee';
                return Response::redirect(url($target));
            }
            return null;
        };
    }
}
