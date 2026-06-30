<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users', 'permissions')
                     ->orderBy('name')
                     ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($p) {
            // Group by the first word e.g. "manage", "view", "create"
            return ucfirst(explode(' ', $p->name)[0]);
        });

        return view('admin.roles.create', compact('permissions'));
    }

public function store(Request $request)
{
    $request->validate([
        'name'                => ['required','string','max:100','unique:roles,name','regex:/^[a-z0-9_]+$/'],
        'scope'               => ['required','in:all,own'],
        'can_partial_approve' => ['boolean'],
        'can_reduce_amounts'  => ['boolean'],
        'description'         => ['nullable','string','max:500'],
        'permissions'         => ['nullable','array'],
        'permissions.*'       => ['exists:permissions,name'],
    ]);

    $role = Role::create([
        'name'                => $request->name,
        'guard_name'          => 'web',
        'scope'               => $request->scope,
        'can_partial_approve' => $request->boolean('can_partial_approve'),
        'can_reduce_amounts'  => $request->boolean('can_reduce_amounts'),
        'description'         => $request->description,
    ]);

    if ($request->permissions) {
        $role->syncPermissions($request->permissions);
    }

    return redirect()->route('admin.roles.index')
        ->with('success', "Role '{$role->name}' created.");
}



    public function show(Role $role)
    {
        $role->load('permissions');

        $users = User::role($role->name)
                     ->with('department')
                     ->orderBy('name')
                     ->get();

        return view('admin.roles.show', compact('role', 'users'));
    }

    public function edit(Role $role)
    {
        $assignedPermissions = $role->permissions->pluck('name')->toArray();

        $permissions = Permission::orderBy('name')->get()->groupBy(function ($p) {
            return ucfirst(explode(' ', $p->name)[0]);
        });

        return view('admin.roles.edit', compact('role', 'permissions', 'assignedPermissions'));
    }

public function update(Request $request, Role $role)
{
    $systemRoles = [
        'super_admin','department_user','department_head',
        'finance_reviewer','gceo','board','bdu_admin',
    ];

    $request->validate([
        'name'                => ['required','string','max:100','unique:roles,name,'.$role->id,'regex:/^[a-z0-9_]+$/'],
        'scope'               => ['required','in:all,own'],
        'can_partial_approve' => ['boolean'],
        'can_reduce_amounts'  => ['boolean'],
        'description'         => ['nullable','string','max:500'],
        'permissions'         => ['nullable','array'],
        'permissions.*'       => ['exists:permissions,name'],
    ]);

    $updateData = [
        'scope'               => $request->scope,
        'can_partial_approve' => $request->boolean('can_partial_approve'),
        'can_reduce_amounts'  => $request->boolean('can_reduce_amounts'),
        'description'         => $request->description,
    ];

    if (!in_array($role->name, $systemRoles)) {
        $updateData['name'] = $request->name;
    }

    $role->update($updateData);
    $role->syncPermissions($request->permissions ?? []);

    return redirect()->route('admin.roles.index')
        ->with('success', "Role '{$role->name}' updated.");
}
    public function destroy(Role $role)
    {
        $systemRoles = [
            'super_admin','department_user','department_head',
            'finance_reviewer','gceo','board','bdu_admin'
        ];

        if (in_array($role->name, $systemRoles)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->count()) {
            return back()->with('error',
                "Cannot delete role '{$role->name}' — it is assigned to " .
                $role->users()->count() . " user(s). Reassign them first."
            );
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' deleted.");
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', "Permissions updated for '{$role->name}'.");
    }
}
