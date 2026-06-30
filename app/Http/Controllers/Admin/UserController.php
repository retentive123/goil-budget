<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use App\Services\AuditLogger;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['department', 'roles']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('employee_id', 'LIKE', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('name')->paginate(20);

        // Get departments for filter dropdown
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        // Get roles for filter dropdown
        $roles = Role::orderBy('name')->get();

        // Calculate online count (users active in last 15 minutes)
        $onlineCount = User::where('last_login_at', '>=', now()->subMinutes(15))->count();

        return view('admin.users.index', compact('users', 'departments', 'roles', 'onlineCount'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles       = Role::orderBy('name')->get();

        return view('admin.users.create', compact('departments', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'employee_id'   => ['nullable', 'string', 'unique:users,employee_id'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role'          => ['required', 'exists:roles,name'],
            'password'      => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'employee_id'   => $validated['employee_id'] ?? null,
            'phone'         => $validated['phone'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'password'      => Hash::make($validated['password']),
            'is_active'     => true,
        ]);

        $user->assignRole($validated['role']);

        AuditLogger::userCreated($user, auth()->user());

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    public function show(User $user)
    {
        $user->load('department', 'roles');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles       = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'departments', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email,' . $user->id],
            'employee_id'   => ['nullable', 'string', 'unique:users,employee_id,' . $user->id],
            'phone'         => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role'          => ['required', 'exists:roles,name'],
        ]);

        $user->update([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'employee_id'   => $validated['employee_id'] ?? null,
            'phone'         => $validated['phone'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        AuditLogger::roleAssigned($user, $validated['role'], auth()->user());

        if (auth()->user()->hasRole('super_admin') && $request->has('direct_permissions')) {
            $user->syncPermissions($request->direct_permissions ?? []);
        } elseif (auth()->user()->hasRole('super_admin') && !$request->has('direct_permissions')) {
            $user->syncPermissions([]); // clear all direct permissions
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        if (!$user->is_active) {
        AuditLogger::userDeactivated($user, auth()->user());
        }

        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user->syncRoles([$request->role]);

        return back()->with('success', 'Role updated successfully.');
    }
}
