<?php

namespace App\Http\Controllers;

use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\Department;
use App\Models\BudgetActual;
use App\Exports\BudgetTemplateExport;
use App\Exports\ActualsTemplateExport;
use App\Exports\AccountCategoryExport;
use App\Exports\AccountCodeExport;
use App\Imports\BudgetImport;
use App\Imports\ActualsImport;
use App\Imports\AccountCategoryImport;
use App\Imports\AccountCodeImport;
use App\Services\BudgetCalculationService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ImportExportController extends Controller
{
    // ── Budget Template Download ──────────────────────
    public function downloadBudgetTemplate(BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        $budgetVersion->load('lineItems.accountCode.category','department','period');

        $filename = 'budget-' .
            str($budgetVersion->department->name)->slug() . '-' .
            $budgetVersion->period->year . '-v' .
            $budgetVersion->version_number . '.xlsx';

        \App\Services\AuditLogger::reportExported('budget_template','xlsx', auth()->user());

        return Excel::download(new BudgetTemplateExport($budgetVersion), $filename);
    }

    // ── Budget Upload ─────────────────────────────────
    public function uploadBudget(Request $request, BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        if (!$budgetVersion->isEditable()) {
            return back()->with('error', 'This budget is no longer editable.');
        }

        $request->validate([
            'file' => ['required','file','mimes:xlsx,xls','max:5120'],
        ]);

        $import = new BudgetImport($budgetVersion);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', 'File could not be read: '.$e->getMessage());
        }

        if (!empty($import->errors)) {
            return back()
                ->with('warning', 'Imported with errors. '.$import->imported.' rows saved.')
                ->with('import_errors', $import->errors);
        }

        \App\Services\AuditLogger::record(
            'budget_imported', 'budget', 'updated',
            ['subject_label' => "Budget v{$budgetVersion->version_number} imported from Excel"]
        );

        return redirect()
            ->route('budget.show', $budgetVersion)
            ->with('success', $import->imported.' line items updated from Excel.');
    }

    // ── Actuals Template Download ─────────────────────
    public function downloadActualsTemplate(Request $request)
    {
        $request->validate([
            'budget_version_id' => ['required','exists:budget_versions,id'],
            'month'             => ['required','integer','min:1','max:12'],
            'year'              => ['required','integer'],
        ]);

        $version = BudgetVersion::with('lineItems.accountCode.category','department','period')
            ->findOrFail($request->budget_version_id);

        $month    = (int) $request->month;
        $year     = (int) $request->year;
        $monthName = BudgetActual::MONTHS[$month];

        $filename = 'actuals-' .
            str($version->department->name)->slug() . '-' .
            strtolower($monthName) . '-' . $year . '.xlsx';

        return Excel::download(
            new ActualsTemplateExport($version, $month, $year),
            $filename
        );
    }

    // ── Actuals Upload ────────────────────────────────
    public function uploadActuals(Request $request)
    {
        $request->validate([
            'file'              => ['required','file','mimes:xlsx,xls','max:5120'],
            'budget_version_id' => ['required','exists:budget_versions,id'],
            'month'             => ['required','integer','min:1','max:12'],
            'year'              => ['required','integer'],
        ]);

        $version = BudgetVersion::findOrFail($request->budget_version_id);
        $month   = (int) $request->month;
        $year    = (int) $request->year;

        $import  = new ActualsImport($version, $month, $year);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', 'File could not be read: '.$e->getMessage());
        }

        if (!empty($import->errors)) {
            return back()
                ->with('warning', $import->imported.' rows imported with errors.')
                ->with('import_errors', $import->errors);
        }

        $monthName = BudgetActual::MONTHS[$month];

        return redirect()
            ->route('actuals.entry', [
                'period_id'     => $version->budget_period_id,
                'department_id' => $version->department_id,
                'month'         => $month,
                'year'          => $year,
            ])
            ->with('success', "{$import->imported} actuals imported for {$monthName} {$year}.");
    }

    // ── Category Template + Import ────────────────────
    public function downloadCategoryTemplate()
    {
        return Excel::download(new AccountCategoryExport(), 'account-categories-template.xlsx');
    }

    public function uploadCategories(Request $request)
    {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,xls,csv','max:5120'],
        ]);

        $import = new AccountCategoryImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', 'File error: '.$e->getMessage());
        }

        $msg = "Imported {$import->imported} new, updated {$import->updated} existing categories.";

        if (!empty($import->errors)) {
            return back()->with('warning', $msg)->with('import_errors', $import->errors);
        }

        return redirect()->route('admin.account-categories.index')->with('success', $msg);
    }

    // ── Account Code Template + Import ───────────────
    public function downloadCodeTemplate()
    {
        return Excel::download(new AccountCodeExport(), 'account-codes-template.xlsx');
    }

    public function uploadCodes(Request $request)
    {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,xls,csv','max:5120'],
        ]);

        $import = new AccountCodeImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', 'File error: '.$e->getMessage());
        }

        $msg = "Imported {$import->imported} new, updated {$import->updated} existing codes.";

        if (!empty($import->errors)) {
            return back()->with('warning', $msg)->with('import_errors', $import->errors);
        }

        return redirect()->route('admin.account-codes.index')->with('success', $msg);
    }

    private function authorizeBudgetAccess(BudgetVersion $version): void
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['finance_reviewer','gceo','board','bdu_admin','super_admin'])) return;
        if ($version->department_id !== $user->department_id) abort(403);
    }
}
