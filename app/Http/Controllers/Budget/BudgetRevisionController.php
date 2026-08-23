<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetRevisionController extends Controller
{
    /**
     * Show the "start a revision" confirmation page.
     * Only allowed for approved versions that are not themselves revisions.
     */
    public function create(BudgetVersion $budgetVersion)
    {
        abort_unless(
            $budgetVersion->status === BudgetVersion::STATUS_APPROVED,
            403,
            'Only an approved budget can be revised.'
        );

        if ($budgetVersion->is_revision) {
            abort_unless(
                (bool) \App\Models\SystemSetting::get('allow_revision_of_revision', false),
                403,
                'Re-revising an already-approved revision is not currently allowed. A super-admin can enable this under System Settings → Budget.'
            );
        }

        $budgetVersion->load('department', 'subsidiary', 'period', 'lineItems.accountCode.category');

        // Warn if there's already a pending (non-approved, non-rejected) revision in progress
        $pendingRevision = BudgetVersion::where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('is_revision', true)
            ->when($budgetVersion->department_id, fn($q) => $q->where('department_id', $budgetVersion->department_id))
            ->when($budgetVersion->subsidiary_id, fn($q) => $q->where('subsidiary_id', $budgetVersion->subsidiary_id))
            ->whereNotIn('status', [BudgetVersion::STATUS_APPROVED, BudgetVersion::STATUS_REJECTED])
            ->first();

        return view('budget.revise', compact('budgetVersion', 'pendingRevision'));
    }

    /**
     * Create the revision BudgetVersion, copy all line items, redirect to entry.
     */
    public function store(Request $request, BudgetVersion $budgetVersion)
    {
        abort_unless(
            $budgetVersion->status === BudgetVersion::STATUS_APPROVED,
            403,
            'Only an approved budget can be revised.'
        );

        if ($budgetVersion->is_revision) {
            abort_unless(
                (bool) \App\Models\SystemSetting::get('allow_revision_of_revision', false),
                403,
                'Re-revising an already-approved revision is not currently allowed.'
            );
        }

        $request->validate([
            'revision_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $revision = DB::transaction(function () use ($request, $budgetVersion) {
            $nextVersion = BudgetVersion::nextVersionNumber(
                $budgetVersion->budget_period_id,
                $budgetVersion->department_id,
                $budgetVersion->subsidiary_id,
            );

            // Create the revision version
            $revision = BudgetVersion::create([
                'budget_period_id' => $budgetVersion->budget_period_id,
                'department_id'    => $budgetVersion->department_id,
                'subsidiary_id'    => $budgetVersion->subsidiary_id,
                'version_number'   => $nextVersion,
                'status'           => BudgetVersion::STATUS_DRAFT,
                'is_revision'      => true,
                'revised_from_id'  => $budgetVersion->id,
                'revision_notes'   => $request->revision_notes,
                'revised_at'       => now(),
                'revised_by'       => auth()->id(),
            ]);

            // Copy all line items from the original approved version
            // (quarterly amounts are computed accessors; only copy the 12 monthly columns)
            foreach ($budgetVersion->lineItems as $item) {
                BudgetLineItem::create([
                    'budget_version_id' => $revision->id,
                    'account_code_id'   => $item->account_code_id,
                    'line_type'         => $item->line_type,
                    'quantity'          => $item->quantity,
                    'rate'              => $item->rate,
                    'frequency'         => $item->frequency,
                    'justification'     => $item->justification,
                    'last_updated_by'   => auth()->id(),
                    'm1_amount'         => $item->m1_amount,
                    'm2_amount'         => $item->m2_amount,
                    'm3_amount'         => $item->m3_amount,
                    'm4_amount'         => $item->m4_amount,
                    'm5_amount'         => $item->m5_amount,
                    'm6_amount'         => $item->m6_amount,
                    'm7_amount'         => $item->m7_amount,
                    'm8_amount'         => $item->m8_amount,
                    'm9_amount'         => $item->m9_amount,
                    'm10_amount'        => $item->m10_amount,
                    'm11_amount'        => $item->m11_amount,
                    'm12_amount'        => $item->m12_amount,
                ]);
            }

            return $revision;
        });

        return redirect()
            ->route('budget.show', $revision)
            ->with('success',
                "Revision v{$revision->version_number} created. Line items pre-populated from the approved budget — " .
                "update the amounts and submit for approval when ready."
            );
    }
}
