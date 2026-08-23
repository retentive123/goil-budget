<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subsidiary;
use App\Models\SubsidiaryCategory;
use App\Models\AccountCode;
use Illuminate\Http\Request;

class SubsidiaryController extends Controller
{
    public function index(Request $request)
    {
        $categories = SubsidiaryCategory::orderBy('sort_order')->orderBy('name')->get();

        $query = Subsidiary::with('category')->withCount('accountCodes', 'users');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('code', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('subsidiary_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $subsidiaries = $query->orderBy('sort_order')->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.subsidiaries.index', compact('subsidiaries', 'categories'));
    }

    public function create()
    {
        $categories = SubsidiaryCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.subsidiaries.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subsidiary_category_id' => ['required', 'exists:subsidiary_categories,id'],
            'name'                   => ['required', 'string', 'max:150'],
            'code'                   => ['required', 'string', 'max:20', 'unique:subsidiaries,code', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'description'            => ['nullable', 'string', 'max:500'],
            'is_active'              => ['nullable', 'boolean'],
            'sort_order'             => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $subsidiary = Subsidiary::create($data);

        return redirect()->route('admin.subsidiaries.show', $subsidiary)
            ->with('success', "Subsidiary \"{$subsidiary->name}\" created.");
    }

    public function show(Subsidiary $subsidiary)
    {
        $subsidiary->loadCount('accountCodes', 'users', 'budgetVersions');
        $subsidiary->load(['category', 'accountCodes.category']);
        $assignedCodes = $subsidiary->accountCodes()->with('category')->orderBy('account_codes.code')->get();
        return view('admin.subsidiaries.show', compact('subsidiary', 'assignedCodes'));
    }

    public function edit(Subsidiary $subsidiary)
    {
        $categories = SubsidiaryCategory::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.subsidiaries.edit', compact('subsidiary', 'categories'));
    }

    public function update(Request $request, Subsidiary $subsidiary)
    {
        $data = $request->validate([
            'subsidiary_category_id' => ['required', 'exists:subsidiary_categories,id'],
            'name'                   => ['required', 'string', 'max:150'],
            'code'                   => ['required', 'string', 'max:20', 'unique:subsidiaries,code,' . $subsidiary->id, 'regex:/^[A-Za-z0-9_\-]+$/'],
            'description'            => ['nullable', 'string', 'max:500'],
            'is_active'              => ['nullable', 'boolean'],
            'sort_order'             => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? $subsidiary->sort_order;

        $subsidiary->update($data);

        return redirect()->route('admin.subsidiaries.show', $subsidiary)
            ->with('success', "Subsidiary \"{$subsidiary->name}\" updated.");
    }

    public function destroy(Subsidiary $subsidiary)
    {
        if ($subsidiary->users()->exists()) {
            return back()->with('error', "Cannot delete \"{$subsidiary->name}\" — users are assigned to it.");
        }
        if ($subsidiary->budgetVersions()->exists()) {
            return back()->with('error', "Cannot delete \"{$subsidiary->name}\" — it has budget versions on record.");
        }

        $subsidiary->delete();

        return redirect()->route('admin.subsidiaries.index')
            ->with('success', "Subsidiary deleted.");
    }

    // ── Account Code Assignment ────────────────────────────────────────────

    public function accountCodes(Subsidiary $subsidiary)
    {
        $assigned = $subsidiary->accountCodes()->pluck('account_codes.id')->toArray();
        $all      = AccountCode::with('category')->where('is_active', true)->orderBy('code')->get();

        return view('admin.subsidiaries.account-codes', compact('subsidiary', 'assigned', 'all'));
    }

    public function syncAccountCodes(Request $request, Subsidiary $subsidiary)
    {
        $request->validate([
            'account_codes'   => ['nullable', 'array'],
            'account_codes.*' => ['exists:account_codes,id'],
        ]);

        $subsidiary->accountCodes()->sync($request->account_codes ?? []);

        return redirect()->route('admin.subsidiaries.account-codes', $subsidiary)
            ->with('success', "Account codes updated for {$subsidiary->name}.");
    }

    public function exportAccountCodes(Subsidiary $subsidiary)
    {
        $codes = $subsidiary->accountCodes()
            ->with('category')
            ->orderBy('account_codes.code')
            ->get();

        $filename = 'subsidiary-codes-' . \Illuminate\Support\Str::slug($subsidiary->name) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($codes, $subsidiary) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Subsidiary Code Assignment Report']);
            fputcsv($out, ['Subsidiary:', $subsidiary->name . ' (' . $subsidiary->code . ')']);
            fputcsv($out, ['Category:', $subsidiary->category->name ?? '—']);
            fputcsv($out, ['Generated:', now()->format('d M Y H:i')]);
            fputcsv($out, ['Total Codes Assigned:', $codes->count()]);
            fputcsv($out, []);
            fputcsv($out, ['Code', 'Name', 'Category', 'Status', 'Description']);

            foreach ($codes as $code) {
                fputcsv($out, [
                    $code->code,
                    $code->name,
                    $code->category->name ?? '—',
                    $code->is_active ? 'Active' : 'Inactive',
                    $code->description ?? '',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
