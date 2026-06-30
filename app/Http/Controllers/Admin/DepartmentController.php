<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\AccountCode;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('users', 'accountCodes')
                                 ->orderBy('name')
                                 ->paginate(20);

        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name'],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'budget_type' => ['required','in:revenue,expense,both'],
        ]);

        $department = Department::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} created successfully.");
    }

    public function show(Department $department)
    {
        $department->load('users', 'accountCodes.category');
        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code,' . $department->id],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'budget_type' => ['required','in:revenue,expense,both'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} updated successfully.");
    }

    public function destroy(Department $department)
    {
        if ($department->users()->count()) {
            return back()->with('error', 'Cannot delete a department that has users assigned to it.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
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
