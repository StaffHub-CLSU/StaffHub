<?php

declare(strict_types=1);

namespace StaffHub\Core;

use StaffHub\Auth\AuthService;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;

/**
 * Base controller: view rendering + shared layout data for authenticated shells.
 */
abstract class Controller
{
    public function __construct(
        protected readonly Container $container,
    ) {
    }

    /**
     * Render a view inside the shared layout (header + content + footer).
     *
     * @param array<string, mixed> $data View variables; may include pageTitle, activeNav, extraScripts
     */
    protected function render(string $view, array $data = []): Response
    {
        $data += $this->layoutData();
        $data['contentView'] = $view;

        return Response::html($this->evaluate('layouts/shell', $data));
    }

    /**
     * Render a standalone view (no layout shell) — e.g. the login page.
     *
     * @param array<string, mixed> $data
     */
    protected function renderStandalone(string $view, array $data = []): Response
    {
        return Response::html($this->evaluate($view, $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function evaluate(string $template, array $data): string
    {
        $file = dirname(__DIR__, 2) . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View [{$template}] not found at {$file}.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        $role = AuthService::getRole();
        $isAdmin = $role === 'admin';
        $profilePicture = null;

        if (!$isAdmin && AuthService::getEmployeeId()) {
            $employeeRepo = $this->container->get(EmployeeRepositoryInterface::class);
            $employee = $employeeRepo->findJoined(AuthService::getEmployeeId());
            $profilePicture = $employee['profile_picture'] ?? null;
        }

        return [
            'pageTitle'      => defined('APP_NAME') ? APP_NAME : 'StaffHub',
            'activeNav'      => '',
            'extraScripts'   => '',
            'role'           => $role,
            'isAdmin'        => $isAdmin,
            'fullName'       => AuthService::getFullName(),
            'profilePicture' => $profilePicture,
            'rootPath'       => '/',
            'basePath'       => $isAdmin ? '/admin' : '/employee',
        ];
    }
}
