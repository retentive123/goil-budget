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

class BudgetSubmissionController extends Controller
{
    public function __construct(
        protected NotificationService      $notifier,
        protected BudgetCalculationService $calculator
    ) {}

    public function confirm(BudgetVersion $budgetVersion)
    {
        if ($budgetVersion->department_id !== auth()->user()->department_id) {
            abort(403);
        }

        if (!$budgetVersion->isEditable()) {
            return redirect()->route('budget.show', $budgetVersion)
                ->with('error', 'This budget has already been submitted.');
        }

        $grandTotals = $this->calculator->grandTotals($budgetVersion);
        $budgetVersion->load('period', 'department', 'lineItems.accountCode.category');

        return view('budget.confirm', compact('budgetVersion', 'grandTotals'));
    }

    public function submit(Request $request, BudgetVersion $budgetVersion)
    {
        if ($budgetVersion->department_id !== auth()->user()->department_id) {
            abort(403);
        }

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
}
