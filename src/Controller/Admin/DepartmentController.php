<?php

declare(strict_types=1);

namespace StaffHub\Controller\Admin;

use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;

final class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->render('admin/departments', [
            'pageTitle' => 'Departments & Positions',
            'activeNav' => 'departments',
        ]);
    }
}
