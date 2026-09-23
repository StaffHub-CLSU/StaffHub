<?php

declare(strict_types=1);

namespace StaffHub\Controller\Admin;

use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\DepartmentRepositoryInterface;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;

final class PayrollController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly EmployeeRepositoryInterface $employees,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin/payroll', [
            'pageTitle' => 'Salary Processing',
            'activeNav' => 'payroll',
            'employeesList' => $this->employees->search(['status' => 'active'], 1, 500)['data'],
            'departments' => $this->departments->all(),
        ]);
    }
}
