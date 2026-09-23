<?php

declare(strict_types=1);

namespace StaffHub\Controller\Employee;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Service\ProfileService;
use function StaffHub\Support\url;

final class ProfileController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ProfileService $profiles,
    ) {
        parent::__construct($container);
    }

    public function show(Request $request): Response
    {
        return $this->renderProfile($request, '', '');
    }

    public function update(Request $request): Response
    {
        $employeeId = (int) AuthService::getEmployeeId();
        $employee = $this->employees->findJoined($employeeId);
        if (!$employee) {
            return Response::redirect(url('/employee/profile'));
        }

        $result = $this->profiles->updateContact(
            $employeeId,
            $employee,
            $request->string('contact_number'),
            $request->string('address'),
            $request->string('email'),
        );

        if (!$result['success']) {
            return $this->renderProfile($request, '', $result['message']);
        }

        return $this->renderProfile($request, $result['message'], '');
    }

    public function changePassword(Request $request): Response
    {
        $employeeId = (int) AuthService::getEmployeeId();
        $employee = $this->employees->findJoined($employeeId);
        if (!$employee) {
            return Response::redirect(url('/employee/profile'));
        }

        $result = $this->profiles->changePassword(
            (int) $employee['user_id'],
            (string) $request->input('current_password', ''),
            (string) $request->input('new_password', ''),
            (string) $request->input('confirm_password', ''),
        );

        if (!$result['success']) {
            return $this->renderProfile($request, '', $result['message']);
        }

        return $this->renderProfile($request, $result['message'], '');
    }

    private function renderProfile(Request $request, string $successMsg, string $errorMsg): Response
    {
        $employeeId = (int) AuthService::getEmployeeId();
        $employee = $this->employees->findJoined($employeeId);

        if (!$employee) {
            return Response::html(
                '<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;">Employee record not found.</h2>',
                404,
            );
        }

        return $this->render('employee/profile', [
            'pageTitle' => 'My Profile',
            'activeNav' => 'profile',
            'employee' => $employee,
            'employeeId' => $employeeId,
            'successMsg' => $successMsg,
            'errorMsg' => $errorMsg,
        ]);
    }
}
