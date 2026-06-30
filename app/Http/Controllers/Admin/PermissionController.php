<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::withCount('roles')
                                 ->orderBy('name')
                                 ->get()
                                 ->groupBy(function ($p) {
                                     return ucfirst(explode(' ', $p->name)[0]);
                                 });

        $roles = Role::orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:permissions,name',
                'regex:/^[a-z0-9 ]+$/',
            ],
        ]);

        Permission::create([
            'name'       => strtolower(trim($request->name)),
            'guard_name' => 'web',
        ]);

        return back()->with('success', "Permission '{$request->name}' created.");
    }

    public function destroy(Permission $permission)
    {
        // Check if any system roles use this permission
        $roleCount = $permission->roles()->count();

        if ($roleCount > 0) {
            return back()->with('error',
                "Cannot delete — this permission is assigned to {$roleCount} role(s)."
            );
        }

        $permission->delete();

        return back()->with('success', "Permission '{$permission->name}' deleted.");
    }
}
