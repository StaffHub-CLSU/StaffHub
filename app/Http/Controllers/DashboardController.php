<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return to_route('admin.dashboard');
        }

        if (! $user->hasRole('Employee') || $user->employee === null) {
            return Inertia::render('Auth/PendingAccount');
        }

        return to_route('employee.dashboard');
    }
}
