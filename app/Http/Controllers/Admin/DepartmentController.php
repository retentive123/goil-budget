<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\AccountCode;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100])
            ? (int) $request->per_page
            : 10;

        $query = Department::departments()->withCount('users', 'accountCodes');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sort = $request->sort ?? 'name';
        if ($sort === 'users_count') {
            $query->orderByDesc('users_count');
        } elseif (in_array($sort, ['code', 'created_at'])) {
            $query->orderBy($sort);
        } else {
            $query->orderBy('name');
        }

        $departments = $query->paginate($perPage)->withQueryString();

        $stats = [
            'active'   => Department::departments()->where('is_active', true)->count(),
            'inactive' => Department::departments()->where('is_active', false)->count(),
            'users'    => Department::departments()->withCount('users')->get()->sum('users_count'),
            'codes'    => Department::departments()->withCount('accountCodes')->get()->sum('account_codes_count'),
        ];

        return view('admin.departments.index', compact('departments', 'stats'));
    }

    public function create()
    {
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        return view('admin.departments.create', compact('zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name'],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'budget_type' => ['required','in:revenue,expense,both'],
            'zone_id'     => ['required', 'exists:zones,id'],
        ]);

        $department = Department::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} created successfully.");
    }

    public function show(Department $department)
    {
        abort_if($department->isServiceStation(), 404);
        $department->load('users.roles', 'accountCodes.category');
        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        abort_if($department->isServiceStation(), 404);
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        return view('admin.departments.edit', compact('department', 'zones'));
    }

    public function update(Request $request, Department $department)
    {
        abort_if($department->isServiceStation(), 404);
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code,' . $department->id],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'budget_type' => ['required','in:revenue,expense,both'],
            'zone_id'     => ['required', 'exists:zones,id'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} updated successfully.");
    }

    public function destroy(Department $department)
    {
        abort_if($department->isServiceStation(), 404);
        if ($department->users()->count()) {
            return back()->with('error', 'Cannot delete a department that has users assigned to it.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function massAssignForm()
    {
        $departments = Department::departments()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $codes = AccountCode::with('category')->where('is_active', true)->orderBy('code')->get();

        return view('admin.departments.mass-assign', compact('departments', 'codes'));
    }

    public function massAssign(Request $request)
    {
        $request->validate([
            'department_ids'     => ['required', 'array', 'min:1'],
            'department_ids.*'   => ['exists:departments,id'],
            'account_codes'      => ['required', 'array', 'min:1'],
            'account_codes.*'    => ['exists:account_codes,id'],
            'mode'               => ['required', 'in:add,replace'],
        ]);

        $deptIds = Department::departments()
            ->whereIn('id', $request->department_ids)
            ->pluck('id')
            ->all();
        $codeIds = $request->account_codes;
        $now     = now();

        DB::transaction(function () use ($deptIds, $codeIds, $request, $now) {
            if ($request->mode === 'replace') {
                DB::table('department_account_codes')
                    ->whereIn('department_id', $deptIds)
                    ->delete();
            }

            $rows = [];
            foreach ($deptIds as $deptId) {
                foreach ($codeIds as $codeId) {
                    $rows[] = [
                        'department_id'   => $deptId,
                        'account_code_id' => $codeId,
                        'is_active'       => true,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('department_account_codes')->insertOrIgnore($chunk);
            }
        });

        $verb = $request->mode === 'replace' ? 'Replaced codes on' : 'Added codes to';

        return redirect()->route('admin.departments.index')
            ->with('success', "{$verb} " . count($deptIds) . " department(s) — " . count($codeIds) . " code(s) applied.");
    }

    public function accountCodes(Department $department)
    {
        $assigned = $department->accountCodes()->pluck('account_codes.id')->toArray();
        $all      = AccountCode::with('category')->where('is_active', true)->orderBy('code')->get();

        return view('admin.departments.account-codes', compact('department', 'assigned', 'all'));
    }

    public function syncAccountCodes(Request $request, Department $department)
    {
        $request->validate([
            'account_codes'   => ['nullable', 'array'],
            'account_codes.*' => ['exists:account_codes,id'],
        ]);

        $department->accountCodes()->sync($request->account_codes ?? []);

        return redirect()->route('admin.departments.index')
            ->with('success', "Account codes updated for {$department->name}.");
    }
}
