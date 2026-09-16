<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\ApprovalStage;
use App\Services\NotificationService;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\AuditLogger;
use App\Models\SystemSetting;

class BudgetSubmissionController extends Controller
{
    public function __construct(
        protected NotificationService      $notifier,
        protected BudgetCalculationService $calculator
    ) {}

    public function confirm(BudgetVersion $budgetVersion)
    {
        $this->authorizeVersionAccess($budgetVersion);

        if (!$budgetVersion->isEditable()) {
            return redirect()->route('budget.show', $budgetVersion)
                ->with('error', 'This budget has already been submitted.');
        }

        $grandTotals = $this->calculator->grandTotals($budgetVersion);
        $budgetVersion->load('period', 'department', 'subsidiary', 'lineItems.accountCode.category');

        $period     = $budgetVersion->period;
        $prevPeriod = \App\Models\BudgetPeriod::where('year', $period->year - 1)
            ->orderByDesc('id')->first()
            ?? \App\Models\BudgetPeriod::where('id', '<', $period->id)
                ->orderByDesc('year')->orderByDesc('id')->first();

        $pnlData = $this->calculator->buildPnlData($budgetVersion, $prevPeriod);

        return view('budget.confirm', compact('budgetVersion', 'grandTotals', 'prevPeriod', 'pnlData'));
    }

    public function submit(Request $request, BudgetVersion $budgetVersion)
    {
        $this->authorizeVersionAccess($budgetVersion);

        if (!$budgetVersion->isEditable()) {
            return redirect()->route('budget.show', $budgetVersion)
                ->with('error', 'This budget has already been submitted.');
        }

         // ── Enforce deadline ──
        $deadlineCheck = $this->calculator->isDeadlinePassed(
            $budgetVersion->budget_period_id,
            $budgetVersion->department_id
        );

        if ($deadlineCheck['passed']) {
            $deadline    = $deadlineCheck['deadline']?->format('d M Y H:i');
            $hasOverride = $deadlineCheck['has_override'];

            return redirect()->route('budget.show', $budgetVersion)
                ->with('error',
                    "Submission deadline" .
                    ($deadline ? " ({$deadline})" : "") .
                    " has passed. " .
                    ($hasOverride
                        ? "Your extended deadline has also passed."
                        : "Contact Finance or Admin to request a deadline extension."
                    )
                );
        }

        $request->validate([
            'submission_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // ── Guard: if manual period split is ON, every line item must be balanced ──
        if ((bool) SystemSetting::get('manual_period_split', false)) {
            $calcMode  = SystemSetting::get('budget_entry_calc_mode', 'none');
            $entryMode = SystemSetting::get('budget_entry_mode', 'quarterly');

            if ($calcMode !== 'none') {
                $budgetVersion->load('lineItems');
                $imbalanced = [];

                foreach ($budgetVersion->lineItems as $item) {
                    $computed = round((float) $item->total_amount, 2);
                    $splitSum = $entryMode === 'monthly'
                        ? round(array_sum(array_map(
                            fn($n) => (float) ($item->{"m{$n}_amount"} ?? 0), range(1, 12)
                          )), 2)
                        : round(
                            (float) $item->q1_amount + (float) $item->q2_amount +
                            (float) $item->q3_amount + (float) $item->q4_amount, 2
                          );

                    if (abs($splitSum - $computed) > 0.02) {
                        $imbalanced[] = $item->accountCode->code ?? "item #{$item->id}";
                    }
                }

                if (!empty($imbalanced)) {
                    return redirect()->route('budget.show', $budgetVersion)
                        ->with('error',
                            'Cannot submit: the following line items have unbalanced period splits — ' .
                            implode(', ', $imbalanced) . '. ' .
                            'Each item\'s splits must add up to its total before submission.'
                        );
                }
            }
        }

        DB::transaction(function () use ($request, $budgetVersion) {
            $budgetVersion->update([
                'status'           => BudgetVersion::STATUS_SUBMITTED,
                'submission_notes' => $request->submission_notes,
                'submitted_by'     => auth()->id(),
                'submitted_at'     => now(),
            ]);

            AuditLogger::budgetSubmitted($budgetVersion->fresh()->load('department','period','lineItems'));

            $firstStage = ApprovalStage::where('order', 1)->first();
            if ($firstStage) {
                $this->notifier->notifyApprovers($budgetVersion, $firstStage);
            }
        });

        //  Clear dashboard cache
        if (config('cache.default') === 'redis') {
            Cache::tags(['dashboard'])->flush();
        } else {
            Cache::forget("dashboard.finance.{$budgetVersion->budget_period_id}.*");
        }

        return redirect()->route('budget.index')
            ->with('success', "Budget v{$budgetVersion->version_number} submitted successfully. Your department head has been notified.");
    }

    /**
     * Reopen a rejected budget version so it can be edited and resubmitted.
     * Sets status back to draft and redirects to the entry form.
     */
    public function reopen(BudgetVersion $budgetVersion)
    {
        $this->authorizeVersionAccess($budgetVersion);

        abort_unless(
            $budgetVersion->status === BudgetVersion::STATUS_REJECTED,
            403,
            'Only rejected budgets can be reopened.'
        );

        DB::transaction(function () use ($budgetVersion) {
            // Clear previous approval decisions so the version goes through
            // a full fresh approval cycle on resubmission (avoids unique-key
            // violation on approval_decisions.ad_version_stage_unique).
            $budgetVersion->approvalDecisions()->delete();

            $budgetVersion->update(['status' => BudgetVersion::STATUS_DRAFT]);

            AuditLogger::record(
                'budget_reopened',
                'budget_version',
                'updated',
                ['subject_label' => "v{$budgetVersion->version_number} — {$budgetVersion->ownerName()}"]
            );
        });

        return redirect()
            ->route('budget.show', $budgetVersion)
            ->with('success',
                "Version {$budgetVersion->version_number} reopened for editing. " .
                "Update the figures and resubmit for approval."
            );
    }

    /**
     * Abort 403 unless the authenticated user owns this budget version.
     * Finance / admin roles bypass this check.
     */
    private function authorizeVersionAccess(BudgetVersion $version): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['finance_reviewer', 'gceo', 'board', 'bdu_admin', 'super_admin'])) {
            return;
        }

        if ($user->isSubsidiaryUser()) {
            abort_unless((int) $version->subsidiary_id === (int) $user->subsidiary_id, 403);
        } else {
            abort_unless((int) $version->department_id === (int) $user->department_id, 403);
        }
    }
}
