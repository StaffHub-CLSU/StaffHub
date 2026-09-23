<?php

declare(strict_types=1);

namespace StaffHub\Controller\Api;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Service\OrganizationService;

/**
 * JSON API for department/position CRUD (formerly ajax/department.php).
 */
final class DepartmentApiController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly OrganizationService $org,
    ) {
        parent::__construct($container);
    }

    public function handle(Request $request): Response
    {
        $action = $request->string('action');
        $userId = AuthService::getUserId();

        return match ($action) {
            'list_departments' => Response::json([
                'success' => true,
                'departments' => $this->org->departments(),
            ]),
            'list_positions' => Response::json([
                'success' => true,
                'positions' => $this->org->positions(),
            ]),
            'create_department' => Response::json($this->org->createDepartment(
                $request->string('department_name'),
                $request->string('description'),
                $userId,
            )),
            'update_department' => Response::json($this->org->updateDepartment(
                (int) $request->input('department_id', 0),
                $request->string('department_name'),
                $request->string('description'),
                $userId,
            )),
            'delete_department' => Response::json($this->org->deleteDepartment(
                (int) $request->input('department_id', 0),
            )),
            'create_position' => Response::json($this->org->createPosition(
                $request->string('position_name'),
                $request->input('department_id') ? (int) $request->input('department_id') : null,
                $userId,
            )),
            'update_position' => Response::json($this->org->updatePosition(
                (int) $request->input('position_id', 0),
                $request->string('position_name'),
                $request->input('department_id') ? (int) $request->input('department_id') : null,
                $userId,
            )),
            'delete_position' => Response::json($this->org->deletePosition(
                (int) $request->input('position_id', 0),
            )),
            default => Response::json(['success' => false, 'message' => 'Unknown action.'], 400),
        };
    }
}
