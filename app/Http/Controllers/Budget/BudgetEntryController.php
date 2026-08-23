<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\BudgetLineItem;
use App\Models\Subsidiary;
use App\Models\SystemSetting;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetEntryController extends Controller
{
    public function __construct(
        protected BudgetCalculationService $calculator
    ) {}

    // Show the department's (or subsidiary's) budget for the current period
    public function index(Request $request)
    {
        $user    = auth()->user();
        $periods = BudgetPeriod::orderByDesc('year')->orderByDesc('id')->get();

        // Resolve the selected period: URL param → current open → latest overall
        if ($request->period_id) {
            $currentPeriod = BudgetPeriod::find($request->period_id);
        } else {
            $currentPeriod = BudgetPeriod::current()
                ?? $periods->first();
        }

        if (!$currentPeriod && $periods->isEmpty()) {
            return view('budget.no-period');
        }

        // All versions for this user's entity in the selected period, newest first
        $allVersions = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->when($user->subsidiary_id,
                    fn($q) => $q->where('subsidiary_id', $user->subsidiary_id),
                    fn($q) => $q->where('department_id', $user->department_id)
                )
                ->with('originalVersion', 'submittedBy')
                ->withCount('lineItems')
                ->orderByDesc('version_number')
                ->get()
            : collect();

        // Latest version drives the primary action card
        $version = $allVersions->first();

        // Cross-period history — one row per period that has any version
        // Use DB aggregates to avoid N+1 on line items
        $historyVersions = BudgetVersion::when($user->subsidiary_id,
                               fn($q) => $q->where('subsidiary_id', $user->subsidiary_id),
                               fn($q) => $q->where('department_id', $user->department_id)
                           )
                           ->with('period')
                           ->withSum('lineItems as line_total', 'total_amount')
                           ->orderByDesc('version_number')
                           ->get();

        $periodHistory = $historyVersions
            ->groupBy('budget_period_id')
            ->map(function ($vv) {
                $approved = $vv->where('status', 'approved')->first(); // already sorted desc
                $latest   = $vv->first();
                return [
                    'period'   => $latest->period,
                    'latest'   => $latest,
                    'approved' => $approved,
                    'count'    => $vv->count(),
                    'total'    => (float) ($approved?->line_total ?? $latest->line_total ?? 0),
                ];
            })
            ->sortByDesc(fn($r) => $r['period']?->year)
            ->values();

        return view('budget.index', compact(
            'currentPeriod', 'version', 'allVersions',
            'user', 'periods', 'periodHistory'
        ));
    }

    // Start a new budget version for the current period
    public function start(Request $request)
    {
        $user = auth()->user();

        // Resolve which entity this user belongs to
        $isSubsidiary = $user->isSubsidiaryUser();
        $entityId     = $isSubsidiary ? $user->subsidiary_id : $user->department_id;

        if (!$entityId) {
            return back()->with('error', 'Your account is not assigned to a department or subsidiary. Please contact the administrator.');
        }

        $currentPeriod = BudgetPeriod::current();

        if (!$currentPeriod) {
            return back()->with('error', 'No active budget period.');
        }

        // Guard: if a draft already exists (e.g. double-click), redirect to it
        $existingDraft = BudgetVersion::where('budget_period_id', $currentPeriod->id)
            ->when($isSubsidiary,
                fn($q) => $q->where('subsidiary_id', $entityId),
                fn($q) => $q->where('department_id', $entityId)
            )
            ->where('status', BudgetVersion::STATUS_DRAFT)
            ->orderByDesc('version_number')
            ->first();

        if ($existingDraft) {
            return redirect()->route('budget.show', $existingDraft)
                ->with('info', "You already have a draft budget (v{$existingDraft->version_number}). Continue editing it below.");
        }

        $canCreate = $isSubsidiary
            ? BudgetVersion::canCreateNew($currentPeriod->id, null, $entityId)
            : BudgetVersion::canCreateNew($currentPeriod->id, $entityId);

        if (!$canCreate) {
            return back()->with('error', 'Maximum of ' . BudgetVersion::maxVersions() . ' budget versions reached for this period.');
        }

        // Find the most recent previous version to copy figures from
        $previousVersion = BudgetVersion::where('budget_period_id', $currentPeriod->id)
            ->when($isSubsidiary,
                fn($q) => $q->where('subsidiary_id', $entityId),
                fn($q) => $q->where('department_id', $entityId)
            )
            ->whereIn('status', [BudgetVersion::STATUS_REJECTED, BudgetVersion::STATUS_APPROVED])
            ->orderByDesc('version_number')
            ->with('lineItems')
            ->first();

        $nextNum = $isSubsidiary
            ? BudgetVersion::nextVersionNumber($currentPeriod->id, null, $entityId)
            : BudgetVersion::nextVersionNumber($currentPeriod->id, $entityId);

        $version = DB::transaction(function () use ($user, $currentPeriod, $isSubsidiary, $entityId, $nextNum, $previousVersion) {
            $version = BudgetVersion::create([
                'budget_period_id' => $currentPeriod->id,
                'department_id'    => $isSubsidiary ? null : $entityId,
                'subsidiary_id'    => $isSubsidiary ? $entityId : null,
                'version_number'   => $nextNum,
                'status'           => BudgetVersion::STATUS_DRAFT,
                'submitted_by'     => null,
            ]);

            // Copy figures from the previous version if one exists, so the
            // user starts from the last submitted numbers rather than blanks.
            if ($previousVersion && $previousVersion->lineItems->isNotEmpty()) {
                foreach ($previousVersion->lineItems as $item) {
                    BudgetLineItem::create([
                        'budget_version_id' => $version->id,
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
            }

            // Sync any newly-assigned account codes (adds missing rows, does not overwrite copied amounts)
            $this->calculator->populateLineItems($version);

            return $version;
        });

        $msg = $previousVersion
            ? "Budget v{$version->version_number} created with figures copied from v{$previousVersion->version_number}. Update what's changed and resubmit."
            : "Budget v{$version->version_number} created. Start entering your figures.";

        return redirect()->route('budget.show', $version)->with('success', $msg);
    }

    // Show the budget entry form
    public function show(BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        // Sync any account codes assigned to the dept after this version was created
        if ($budgetVersion->isEditable()) {
            $this->calculator->populateLineItems($budgetVersion);
        }

        $budgetVersion->load('lineItems.accountCode.category', 'period', 'department', 'subsidiary.category', 'originalVersion');

        $summary     = $this->calculator->summaryByCategory($budgetVersion);
        $grandTotals = $this->calculator->grandTotals($budgetVersion);
        $entryMode   = $budgetVersion->period->entry_mode ?? 'quarterly';

        // Calc mode settings — read from the budget period's own snapshot
        $period        = $budgetVersion->period;
        $calcMode      = $period->calcMode();
        $adminSetsRate = $period->adminSetsRate();
        $adminSetsFreq = $period->adminSetsFreq();

        // Revenue categories first, then expense
        $revenueTypes = ['revenue', 'both'];
        uasort($summary, function ($a, $b) use ($revenueTypes) {
            $typeA = $a['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            $typeB = $b['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            return (in_array($typeA, $revenueTypes) ? 0 : 1) <=> (in_array($typeB, $revenueTypes) ? 0 : 1);
        });

        return view('budget.show', compact(
            'budgetVersion', 'summary', 'grandTotals', 'entryMode',
            'calcMode', 'adminSetsRate', 'adminSetsFreq'
        ));
    }

    // Show the P&L-style budget entry form
    public function showPnl(BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        if ($budgetVersion->isEditable()) {
            $this->calculator->populateLineItems($budgetVersion);
        }

        $budgetVersion->load('lineItems.accountCode.category', 'period', 'department', 'subsidiary.category', 'originalVersion');

        $grandTotals = $this->calculator->grandTotals($budgetVersion);

        $period = $budgetVersion->period;
        $prevPeriod = \App\Models\BudgetPeriod::where('year', $period->year - 1)
            ->orderByDesc('id')->first()
            ?? \App\Models\BudgetPeriod::where('id', '<', $period->id)
                ->orderByDesc('year')->orderByDesc('id')->first();

        $pnlData = $this->calculator->buildPnlData($budgetVersion, $prevPeriod);

        return view('budget.show-pnl', compact('budgetVersion', 'grandTotals', 'prevPeriod', 'pnlData'));
    }

    // Save line item amounts (auto-save via AJAX)
    public function save(Request $request, BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        if (!$budgetVersion->isEditable()) {
            return response()->json(['error' => 'This budget is no longer editable.'], 403);
        }

        $justificationRules = explode('|', SystemSetting::get('require_justification', false)
            ? 'required|string|max:500'
            : 'nullable|string|max:500');

        $period        = $budgetVersion->period;
        $calcMode      = $period->calcMode();
        $adminSetsRate = $period->adminSetsRate();
        $adminSetsFreq = $period->adminSetsFreq();

        if ($calcMode !== 'none') {
            // ── Qty × Rate [× Freq] mode ──────────────────────────────────────────
            $request->validate([
                'items'          => ['required', 'array'],
                'items.*.id'     => ['required', 'exists:budget_line_items,id'],
                'items.*.qty'    => ['required', 'numeric', 'min:0'],
                'items.*.rate'   => ['required', 'numeric', 'min:0'],
                'items.*.freq'   => ['required', 'numeric', 'min:0'],
                'items.*.notes'  => $justificationRules,
            ]);

            // Pre-load admin-set rates/freqs in one query so the loop doesn't do N+1
            $itemIds       = collect($request->items)->pluck('id');
            $adminSnapshots = $adminSetsRate || $adminSetsFreq
                ? \App\Models\BudgetLineItem::whereIn('id', $itemIds)
                    ->get(['id', 'rate', 'frequency'])
                    ->keyBy('id')
                    ->map(fn($i) => ['rate' => $i->rate, 'frequency' => $i->frequency])
                : collect();

            DB::transaction(function () use ($request, $budgetVersion, $calcMode,
                                             $adminSetsRate, $adminSetsFreq, $adminSnapshots) {
                foreach ($request->items as $d) {
                    $qty = (float) $d['qty'];

                    // Honour admin locks — ignore whatever the client sent
                    $snap = $adminSnapshots->get($d['id']);
                    $rate = $adminSetsRate
                        ? (float) ($snap['rate'] ?? 1)
                        : (float) $d['rate'];
                    $freq = $calcMode === 'qty_rate_freq'
                        ? ($adminSetsFreq ? (float) ($snap['frequency'] ?? 1) : (float) $d['freq'])
                        : 1.0;
                    if ($freq <= 0) $freq = 1.0;

                    $total = $qty * $rate * $freq;

                    [$m1,$m2,$m3,$m4,$m5,$m6,$m7,$m8,$m9,$m10,$m11,$m12]
                        = $this->spreadAnnual($total);

                    BudgetLineItem::where('id', $d['id'])
                        ->where('budget_version_id', $budgetVersion->id)
                        ->update([
                            'quantity'    => $qty,
                            'rate'        => $rate,
                            'frequency'   => $freq,
                            'm1_amount'   => $m1,  'm2_amount'  => $m2,  'm3_amount'  => $m3,
                            'm4_amount'   => $m4,  'm5_amount'  => $m5,  'm6_amount'  => $m6,
                            'm7_amount'   => $m7,  'm8_amount'  => $m8,  'm9_amount'  => $m9,
                            'm10_amount'  => $m10, 'm11_amount' => $m11, 'm12_amount' => $m12,
                            'justification'   => $d['notes'] ?? null,
                            'last_updated_by' => auth()->id(),
                        ]);
                }
            });

        } else {
            // ── Direct-amount modes (monthly or quarterly) ────────────────────────
            $mode = $budgetVersion->period->entry_mode ?? 'quarterly';

            if ($mode === 'monthly') {
                $request->validate([
                    'items'       => ['required', 'array'],
                    'items.*.id'  => ['required', 'exists:budget_line_items,id'],
                    'items.*.m1'  => ['required', 'numeric', 'min:0'],
                    'items.*.m2'  => ['required', 'numeric', 'min:0'],
                    'items.*.m3'  => ['required', 'numeric', 'min:0'],
                    'items.*.m4'  => ['required', 'numeric', 'min:0'],
                    'items.*.m5'  => ['required', 'numeric', 'min:0'],
                    'items.*.m6'  => ['required', 'numeric', 'min:0'],
                    'items.*.m7'  => ['required', 'numeric', 'min:0'],
                    'items.*.m8'  => ['required', 'numeric', 'min:0'],
                    'items.*.m9'  => ['required', 'numeric', 'min:0'],
                    'items.*.m10' => ['required', 'numeric', 'min:0'],
                    'items.*.m11' => ['required', 'numeric', 'min:0'],
                    'items.*.m12' => ['required', 'numeric', 'min:0'],
                    'items.*.notes' => $justificationRules,
                ]);

                DB::transaction(function () use ($request, $budgetVersion) {
                    foreach ($request->items as $d) {
                        BudgetLineItem::where('id', $d['id'])
                            ->where('budget_version_id', $budgetVersion->id)
                            ->update([
                                'm1_amount'  => $d['m1'],  'm2_amount'  => $d['m2'],  'm3_amount'  => $d['m3'],
                                'm4_amount'  => $d['m4'],  'm5_amount'  => $d['m5'],  'm6_amount'  => $d['m6'],
                                'm7_amount'  => $d['m7'],  'm8_amount'  => $d['m8'],  'm9_amount'  => $d['m9'],
                                'm10_amount' => $d['m10'], 'm11_amount' => $d['m11'], 'm12_amount' => $d['m12'],
                                'justification'   => $d['notes'] ?? null,
                                'last_updated_by' => auth()->id(),
                            ]);
                    }
                });

            } else {
                // Quarterly mode: validate Q1–Q4, spread each quarter equally across 3 months
                $request->validate([
                    'items'         => ['required', 'array'],
                    'items.*.id'    => ['required', 'exists:budget_line_items,id'],
                    'items.*.q1'    => ['required', 'numeric', 'min:0'],
                    'items.*.q2'    => ['required', 'numeric', 'min:0'],
                    'items.*.q3'    => ['required', 'numeric', 'min:0'],
                    'items.*.q4'    => ['required', 'numeric', 'min:0'],
                    'items.*.notes' => $justificationRules,
                ]);

                DB::transaction(function () use ($request, $budgetVersion) {
                    foreach ($request->items as $d) {
                        [$m1, $m2, $m3]    = $this->spreadQuarter((float) $d['q1']);
                        [$m4, $m5, $m6]    = $this->spreadQuarter((float) $d['q2']);
                        [$m7, $m8, $m9]    = $this->spreadQuarter((float) $d['q3']);
                        [$m10, $m11, $m12] = $this->spreadQuarter((float) $d['q4']);

                        BudgetLineItem::where('id', $d['id'])
                            ->where('budget_version_id', $budgetVersion->id)
                            ->update([
                                'm1_amount'  => $m1,  'm2_amount'  => $m2,  'm3_amount'  => $m3,
                                'm4_amount'  => $m4,  'm5_amount'  => $m5,  'm6_amount'  => $m6,
                                'm7_amount'  => $m7,  'm8_amount'  => $m8,  'm9_amount'  => $m9,
                                'm10_amount' => $m10, 'm11_amount' => $m11, 'm12_amount' => $m12,
                                'justification'   => $d['notes'] ?? null,
                                'last_updated_by' => auth()->id(),
                            ]);
                    }
                });
            }
        }

        $grandTotals = $this->calculator->grandTotals($budgetVersion->fresh());

        return response()->json([
            'success'     => true,
            'saved_at'    => now()->format('H:i:s'),
            'grand_total' => number_format($grandTotals['total'], 2),
            'totals'      => $grandTotals,
        ]);
    }

    // Spread a quarterly total equally into 3 months (remainder in last month)
    private function spreadQuarter(float $total): array
    {
        $third = round($total / 3, 2);
        return [$third, $third, round($total - $third * 2, 2)];
    }

    // Spread an annual total equally into 12 months (remainder in month 12)
    private function spreadAnnual(float $total): array
    {
        $share = round($total / 12, 2);
        $last  = round($total - $share * 11, 2);
        return [$share, $share, $share, $share, $share, $share,
                $share, $share, $share, $share, $share, $last];
    }

    // Ensure only the owning department/subsidiary can access this version
    private function authorizeBudgetAccess(BudgetVersion $version): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['finance_reviewer', 'gceo', 'board', 'bdu_admin', 'super_admin'])) {
            return; // These roles can view all
        }

        // Subsidiary user → must match subsidiary_id
        if ($user->isSubsidiaryUser()) {
            if ((int) $version->subsidiary_id !== (int) $user->subsidiary_id) {
                abort(403, 'You do not have access to this budget.');
            }
            return;
        }

        // Department user → must match department_id
        if ((int) $version->department_id !== (int) $user->department_id) {
            abort(403, 'You do not have access to this budget.');
        }
    }
}
