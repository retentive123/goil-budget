<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Subsidiary;
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

        // Get all budget entities (departments + service stations) for filter dropdown
        $departments = Department::where('is_active', true)->orderBy('entity_type')->orderBy('name')->get();

        // Get roles for filter dropdown
        $roles = Role::orderBy('name')->get();

        // Calculate online count (users active in last 15 minutes)
        $onlineCount = User::where('last_login_at', '>=', now()->subMinutes(15))->count();

        return view('admin.users.index', compact('users', 'departments', 'roles', 'onlineCount'));
    }

    public function create()
    {
        $departments  = Department::where('is_active', true)
            ->with('zone')
            ->orderBy('entity_type')
            ->orderBy('name')
            ->get();
        $subsidiaries = Subsidiary::where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('departments', 'subsidiaries', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'unique:users,email'],
            'employee_id'        => ['nullable', 'string', 'unique:users,employee_id'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'department_id'      => ['nullable', 'exists:departments,id'],
            'subsidiary_id'      => ['nullable', 'exists:subsidiaries,id'],
            'role'               => ['required', 'exists:roles,name'],
            'password'           => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'two_factor_enabled' => ['nullable', 'in:0,1'],
        ]);

        // Subsidiary and department are mutually exclusive; subsidiary takes priority if both sent
        $departmentId = filled($validated['subsidiary_id'] ?? null) ? null : ($validated['department_id'] ?? null);
        $subsidiaryId = $validated['subsidiary_id'] ?? null;

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'employee_id'          => $validated['employee_id'] ?? null,
            'phone'                => $validated['phone'] ?? null,
            'department_id'        => $departmentId,
            'subsidiary_id'        => $subsidiaryId,
            'password'             => Hash::make($validated['password']),
            'is_active'            => true,
            'two_factor_enabled'   => (bool) ($validated['two_factor_enabled'] ?? false),
            'must_change_password' => true,  // force password change on first login
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
        $departments  = Department::where('is_active', true)
            ->with('zone')
            ->orderBy('entity_type')
            ->orderBy('name')
            ->get();
        $subsidiaries = Subsidiary::where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'departments', 'subsidiaries', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'unique:users,email,' . $user->id],
            'employee_id'        => ['nullable', 'string', 'unique:users,employee_id,' . $user->id],
            'phone'              => ['nullable', 'string', 'max:20'],
            'department_id'      => ['nullable', 'exists:departments,id'],
            'subsidiary_id'      => ['nullable', 'exists:subsidiaries,id'],
            'role'               => ['required', 'exists:roles,name'],
            'is_active'          => ['nullable', 'in:0,1'],
            'two_factor_enabled' => ['nullable', 'in:0,1'],
        ]);

        $isActive = $user->id === auth()->id()
            ? $user->is_active  // cannot deactivate self
            : (bool) ($validated['is_active'] ?? $user->is_active);

        // Subsidiary and department are mutually exclusive; subsidiary takes priority if both sent
        $departmentId = filled($validated['subsidiary_id'] ?? null) ? null : ($validated['department_id'] ?? null);
        $subsidiaryId = $validated['subsidiary_id'] ?? null;

        $user->update([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'employee_id'        => $validated['employee_id'] ?? null,
            'phone'              => $validated['phone'] ?? null,
            'department_id'      => $departmentId,
            'subsidiary_id'      => $subsidiaryId,
            'is_active'          => $isActive,
            'two_factor_enabled' => (bool) ($validated['two_factor_enabled'] ?? false),
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

    // ── Bulk CSV Import ────────────────────────────────────────────────────────

    public function importTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-import-template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name','email','employee_id','phone','department_code','subsidiary_code','role','password']);
            fputcsv($handle, ['John Kwame','john.kwame@goil.com','EMP001','0244000001','HR','','department_user','']);
            fputcsv($handle, ['Ama Sarpong','ama.sarpong@goil.com','EMP002','0244000002','','GOILSUB01','department_head','']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        $departments  = Department::where('is_active', true)->orderBy('name')->get();
        $subsidiaries = Subsidiary::where('is_active', true)->orderBy('name')->get();
        $roles        = \Spatie\Permission\Models\Role::orderBy('name')->get();
        return view('admin.users.import', compact('departments', 'subsidiaries', 'roles'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $required = ['name', 'email', 'role', 'password'];
        $missing  = array_diff($required, $headers);

        if ($missing) {
            fclose($handle);
            return back()->with('error', 'CSV is missing required columns: ' . implode(', ', $missing));
        }

        $created = 0;
        $skipped = [];
        $rowNum  = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) continue; // skip blank lines

            $data = array_combine($headers, array_pad($row, count($headers), ''));

            // Basic required field check
            if (empty(trim($data['name'])) || empty(trim($data['email'])) || empty(trim($data['role']))) {
                $skipped[] = "Row {$rowNum}: name, email, and role are required.";
                continue;
            }

            if (User::where('email', trim($data['email']))->exists()) {
                $skipped[] = "Row {$rowNum}: {$data['email']} already exists — skipped.";
                continue;
            }

            if (!\Spatie\Permission\Models\Role::where('name', trim($data['role']))->exists()) {
                $skipped[] = "Row {$rowNum}: role '{$data['role']}' does not exist — skipped.";
                continue;
            }

            // Resolve department (by code or name)
            $department = null;
            if (!empty($data['department_code'] ?? '')) {
                $department = Department::where('code', trim($data['department_code']))->first();
            }

            // Resolve subsidiary (by code)
            $subsidiary = null;
            if (!empty($data['subsidiary_code'] ?? '')) {
                $subsidiary = \App\Models\Subsidiary::where('code', trim($data['subsidiary_code']))->first();
            }

            $password = trim($data['password'] ?? '') ?: 'Welcome@' . now()->year;

            $user = User::create([
                'name'                 => trim($data['name']),
                'email'                => trim($data['email']),
                'employee_id'          => trim($data['employee_id'] ?? '') ?: null,
                'phone'                => trim($data['phone'] ?? '') ?: null,
                'department_id'        => $subsidiary ? null : $department?->id,
                'subsidiary_id'        => $subsidiary?->id,
                'password'             => Hash::make($password),
                'is_active'            => true,
                'must_change_password' => true,
            ]);

            $user->assignRole(trim($data['role']));
            AuditLogger::userCreated($user, auth()->user());
            $created++;
        }

        fclose($handle);

        $msg = "Import complete — {$created} user(s) created.";
        if ($skipped) {
            $msg .= ' ' . count($skipped) . ' row(s) skipped. See details below.';
        }

        return back()
            ->with('import_success', $msg)
            ->with('import_skipped', $skipped);
    }
}
