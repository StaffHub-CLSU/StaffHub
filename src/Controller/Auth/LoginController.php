<?php

declare(strict_types=1);

namespace StaffHub\Controller\Auth;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Support\Validator;
use function StaffHub\Support\url;

final class LoginController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly AuthService $auth,
    ) {
        parent::__construct($container);
    }

    public function show(Request $request): Response
    {
        return $this->renderStandalone('auth/login', [
            'error' => '',
            'fieldErrors' => [],
            'oldUsername' => '',
        ]);
    }

    public function login(Request $request): Response
    {
        $username = $request->string('username');
        $password = (string) ($request->input('password') ?? '');

        $validator = new Validator();
        $validator->required($username, 'username', 'Username')
            ->required($password, 'password', 'Password');

        if ($validator->fails()) {
            return $this->renderStandalone('auth/login', [
                'error' => '',
                'fieldErrors' => $validator->getErrors(),
                'oldUsername' => $username,
            ]);
        }

        $result = $this->auth->login($username, $password);
        if ($result['success']) {
            $target = ($result['role'] ?? '') === 'admin' ? '/admin' : '/employee';
            return Response::redirect(url($target));
        }

        return $this->renderStandalone('auth/login', [
            'error' => $result['message'],
            'fieldErrors' => [],
            'oldUsername' => $username,
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect(url('/login'));
    }
}
