<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\BudgetLineItem;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetCalculationService $calculator
    ) {}

    // GET /api/budgets — list budgets for current user's dept or all
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = BudgetVersion::with('department','period')
            ->when(!$user->hasAnyRole(['finance_reviewer','gceo','board','bdu_admin','super_admin']),
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->when($request->period_id,    fn($q) => $q->where('budget_period_id', $request->period_id))
            ->when($request->department_id,fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->status,       fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $budgets = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $budgets->items(),
            'meta' => [
                'total'        => $budgets->total(),
                'per_page'     => $budgets->perPage(),
                'current_page' => $budgets->currentPage(),
                'last_page'    => $budgets->lastPage(),
            ],
        ]);
    }

    // GET /api/budgets/{id} — budget detail with line items
    public function show(Request $request, BudgetVersion $budget)
    {
        $this->authorizeAccess($request->user(), $budget);

        $budget->load('department','period','lineItems.accountCode.category','submittedBy');

        $summary     = $this->calculator->summaryByCategory($budget);
        $grandTotals = $this->calculator->grandTotals($budget);

        return response()->json([
            'id'             => $budget->id,
            'version_number' => $budget->version_number,
            'status'         => $budget->status,
            'department'     => $budget->department->only('id','name','code'),
            'period'         => $budget->period->only('id','name','year','status'),
            'submitted_by'   => $budget->submittedBy?->only('id','name','email'),
            'submitted_at'   => $budget->submitted_at?->toISOString(),
            'grand_totals'   => $grandTotals,
            'categories'     => collect($summary)->map(fn($cat,$name) => [
                'name'  => $name,
                'q1'    => $cat['q1'],
                'q2'    => $cat['q2'],
                'q3'    => $cat['q3'],
                'q4'    => $cat['q4'],
                'total' => $cat['total'],
                'items' => collect($cat['items'])->map(fn($item) => [
                    'id'            => $item->id,
                    'account_code'  => $item->accountCode->code,
                    'account_name'  => $item->accountCode->name,
                    'q1'            => $item->q1_amount,
                    'q2'            => $item->q2_amount,
                    'q3'            => $item->q3_amount,
                    'q4'            => $item->q4_amount,
                    'total'         => $item->total_amount,
                    'justification' => $item->justification,
                ]),
            ])->values(),
        ]);
    }

    // PATCH /api/budgets/{id}/line-items — update amounts
    public function updateLineItems(Request $request, BudgetVersion $budget)
    {
        $this->authorizeAccess($request->user(), $budget);

        if (!$budget->isEditable()) {
            return response()->json(['message' => 'Budget is not editable.'], 422);
        }

        $mode = $budget->period->entry_mode ?? 'quarterly';

        if ($mode === 'monthly') {
            $request->validate([
                'items'       => ['required','array'],
                'items.*.id'  => ['required','exists:budget_line_items,id'],
                'items.*.m1'  => ['required','numeric','min:0'],
                'items.*.m2'  => ['required','numeric','min:0'],
                'items.*.m3'  => ['required','numeric','min:0'],
                'items.*.m4'  => ['required','numeric','min:0'],
                'items.*.m5'  => ['required','numeric','min:0'],
                'items.*.m6'  => ['required','numeric','min:0'],
                'items.*.m7'  => ['required','numeric','min:0'],
                'items.*.m8'  => ['required','numeric','min:0'],
                'items.*.m9'  => ['required','numeric','min:0'],
                'items.*.m10' => ['required','numeric','min:0'],
                'items.*.m11' => ['required','numeric','min:0'],
                'items.*.m12' => ['required','numeric','min:0'],
                'items.*.notes' => ['nullable','string'],
            ]);

            foreach ($request->items as $d) {
                BudgetLineItem::where('id', $d['id'])->where('budget_version_id', $budget->id)->update([
                    'm1_amount'  => $d['m1'],  'm2_amount'  => $d['m2'],  'm3_amount'  => $d['m3'],
                    'm4_amount'  => $d['m4'],  'm5_amount'  => $d['m5'],  'm6_amount'  => $d['m6'],
                    'm7_amount'  => $d['m7'],  'm8_amount'  => $d['m8'],  'm9_amount'  => $d['m9'],
                    'm10_amount' => $d['m10'], 'm11_amount' => $d['m11'], 'm12_amount' => $d['m12'],
                    'justification' => $d['notes'] ?? null, 'last_updated_by' => $request->user()->id,
                ]);
            }
        } else {
            $request->validate([
                'items'         => ['required','array'],
                'items.*.id'    => ['required','exists:budget_line_items,id'],
                'items.*.q1'    => ['required','numeric','min:0'],
                'items.*.q2'    => ['required','numeric','min:0'],
                'items.*.q3'    => ['required','numeric','min:0'],
                'items.*.q4'    => ['required','numeric','min:0'],
                'items.*.notes' => ['nullable','string'],
            ]);

            foreach ($request->items as $d) {
                $spread = fn(float $q) => [round($q/3, 2), round($q/3, 2), round($q - round($q/3, 2)*2, 2)];
                [$m1,$m2,$m3]    = $spread((float)$d['q1']);
                [$m4,$m5,$m6]    = $spread((float)$d['q2']);
                [$m7,$m8,$m9]    = $spread((float)$d['q3']);
                [$m10,$m11,$m12] = $spread((float)$d['q4']);

                BudgetLineItem::where('id', $d['id'])->where('budget_version_id', $budget->id)->update([
                    'm1_amount'  => $m1,  'm2_amount'  => $m2,  'm3_amount'  => $m3,
                    'm4_amount'  => $m4,  'm5_amount'  => $m5,  'm6_amount'  => $m6,
                    'm7_amount'  => $m7,  'm8_amount'  => $m8,  'm9_amount'  => $m9,
                    'm10_amount' => $m10, 'm11_amount' => $m11, 'm12_amount' => $m12,
                    'justification' => $d['notes'] ?? null, 'last_updated_by' => $request->user()->id,
                ]);
            }
        }

        return response()->json([
            'message'     => 'Line items updated.',
            'grand_totals'=> $this->calculator->grandTotals($budget->fresh()),
        ]);
    }

    // POST /api/budgets/{id}/submit
    public function submit(Request $request, BudgetVersion $budget)
    {
        $this->authorizeAccess($request->user(), $budget);

        if (!$budget->isEditable()) {
            return response()->json(['message' => 'Budget cannot be submitted.'], 422);
        }

        $budget->update([
            'status'           => BudgetVersion::STATUS_SUBMITTED,
            'submitted_by'     => $request->user()->id,
            'submitted_at'     => now(),
            'submission_notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Budget submitted successfully.',
            'status'  => $budget->status,
        ]);
    }

    // GET /api/periods — list budget periods
    public function periods()
    {
        $periods = BudgetPeriod::orderByDesc('year')->get()->map(fn($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'year'       => $p->year,
            'start_date' => $p->start_date->toDateString(),
            'end_date'   => $p->end_date->toDateString(),
            'status'     => $p->status,
            'is_current' => $p->status === 'open',
        ]);

        return response()->json(['data' => $periods]);
    }

    private function authorizeAccess($user, BudgetVersion $budget): void
    {
        if ($user->hasAnyRole(['finance_reviewer','gceo','board','bdu_admin','super_admin'])) return;
        if ($budget->department_id !== $user->department_id) abort(403);
    }
}
