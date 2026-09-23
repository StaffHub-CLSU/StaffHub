<?php

declare(strict_types=1);

namespace StaffHub\Controller\Api;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Service\DashboardService;
use StaffHub\Service\OrganizationService;

/**
 * Lightweight lookups (formerly ajax/search.php).
 */
final class SearchApiController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly DashboardService $dashboard,
        private readonly OrganizationService $org,
        private readonly EmployeeRepositoryInterface $employees,
    ) {
        parent::__construct($container);
    }

    public function handle(Request $request): Response
    {
        $action = $request->string('action');

        return match ($action) {
            'dashboard_stats' => $this->dashboardStats(),
            'positions_by_department' => Response::json([
                'success' => true,
                'positions' => $this->org->positionsByDepartment((int) $request->query('department_id', 0)),
            ]),
            'employees_quick' => $this->employeesQuick($request),
            default => Response::json(['success' => false, 'message' => 'Unknown action.'], 400),
        };
    }

    private function dashboardStats(): Response
    {
        if (!AuthService::isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        return Response::json([
            'success' => true,
            'stats' => $this->dashboard->adminStats(),
            'recent_activity' => $this->dashboard->recentActivity(8),
            'recent_clock_ins' => $this->dashboard->recentClockIns(5),
            'recent_clock_outs' => $this->dashboard->recentClockOuts(5),
        ]);
    }

    private function employeesQuick(Request $request): Response
    {
        if (!AuthService::isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $term = trim((string) $request->query('term', ''));
        $result = $this->employees->search($term ? ['search' => $term] : [], 1, 20);

        return Response::json(['success' => true, 'employees' => $result['data']]);
    }
}
