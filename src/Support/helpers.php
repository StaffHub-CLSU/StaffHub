<?php

declare(strict_types=1);

namespace StaffHub\Support;

/**
 * Global helpers available in views and controllers.
 */

if (!function_exists('e')) {
    /** Escape a value for safe HTML output. */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    /** Format a number as Philippine peso currency. */
    function money(?float $value): string
    {
        return '₱' . number_format((float) $value, 2);
    }
}

if (!function_exists('url')) {
    /** Build an absolute application URL from a path (e.g. url('/login')). */
    function url(string $path = ''): string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
        if ($path === '' || $path === '/') {
            return $base === '' ? '/' : $base;
        }
        return $base . '/' . ltrim($path, '/');
    }
}
