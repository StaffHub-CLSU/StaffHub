<?php

declare(strict_types=1);

namespace StaffHub\Controller\Admin;

use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Service\OrganizationService;

final class EmployeeController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly OrganizationService $org,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin/employees', [
            'pageTitle' => 'Employee Management',
            'activeNav' => 'employees',
            'departments' => $this->org->departments(),
            'positions' => $this->org->positions(),
        ]);
    }
}
