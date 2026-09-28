<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRequest;
use App\Http\Requests\PositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        return Inertia::render('Admin/Organization', ['departments' => Department::with('positions')->orderBy('department_name')->get(), 'positions' => Position::with('department')->orderBy('position_name')->get()]);
    }

    public function storeDepartment(OrganizationRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return to_route('admin.organization.index')->with('success', 'Department created.');
    }

    public function updateDepartment(OrganizationRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);
        $department->update($request->validated());

        return to_route('admin.organization.index')->with('success', 'Department updated.');
    }

    public function destroyDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);
        $department->delete();

        return to_route('admin.organization.index')->with('success', 'Department archived from active assignment.');
    }

    public function storePosition(PositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        return to_route('admin.organization.index')->with('success', 'Position created.');
    }

    public function updatePosition(PositionRequest $request, Position $position): RedirectResponse
    {
        $this->authorize('update', Department::class);
        $position->update($request->validated());

        return to_route('admin.organization.index')->with('success', 'Position updated.');
    }

    public function destroyPosition(Request $request, Position $position): RedirectResponse
    {
        $this->authorize('delete', Department::class);
        $position->delete();

        return to_route('admin.organization.index')->with('success', 'Position deleted.');
    }
}
