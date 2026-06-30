<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetActual;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use Illuminate\Http\Request;

class ActualsController extends Controller
{
    // GET /api/actuals
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = BudgetActual::with('department','accountCode.category')
            ->when(!$user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin']),
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->when($request->period_id,    fn($q) => $q->where('budget_period_id', $request->period_id))
            ->when($request->department_id,fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->month,        fn($q) => $q->where('month', $request->month))
            ->when($request->year,         fn($q) => $q->where('year',  $request->year))
            ->orderBy('year')->orderBy('month');

        $actuals = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'data' => $actuals->items(),
            'meta' => ['total' => $actuals->total()],
        ]);
    }

    // POST /api/actuals
    public function store(Request $request)
    {

         // Segregation check if confirming directly via API
        if ($request->status === 'confirmed' && \App\Services\SegregationService::enabled()) {
            // Check that this user didn't record the existing drafts
            $existingRecorders = \App\Models\BudgetActual::where('department_id', $request->department_id)
                ->where('budget_period_id', $request->budget_period_id)
                ->where('status', 'draft')
                ->pluck('recorded_by')
                ->unique();

            if ($existingRecorders->contains($request->user()->id)) {
                return response()->json([
                    'message' => 'Segregation of duties: you cannot confirm actuals you recorded.',
                ], 403);
            }
        }

        $request->validate([
            'budget_period_id'   => ['required','exists:budget_periods,id'],
            'department_id'      => ['required','exists:departments,id'],
            'month'              => ['required','integer','min:1','max:12'],
            'year'               => ['required','integer'],
            'entries'            => ['required','array'],
            'entries.*.account_code_id' => ['required','exists:account_codes,id'],
            'entries.*.amount'          => ['required','numeric','min:0'],
            'entries.*.reference'       => ['nullable','string'],
        ]);

        $version = BudgetVersion::where('budget_period_id', $request->budget_period_id)
            ->where('department_id', $request->department_id)
            ->where('status','approved')
            ->first();

        if (!$version) {
            return response()->json(['message' => 'No approved budget found.'], 422);
        }

        $saved = [];
        foreach ($request->entries as $entry) {
            $lineItem = $version->lineItems()
                ->where('account_code_id', $entry['account_code_id'])
                ->first();

            if (!$lineItem) continue;

            $actual = BudgetActual::updateOrCreate(
                [
                    'budget_line_item_id' => $lineItem->id,
                    'month'               => $request->month,
                    'year'                => $request->year,
                ],
                [
                    'budget_period_id' => $request->budget_period_id,
                    'department_id'    => $request->department_id,
                    'account_code_id'  => $entry['account_code_id'],
                    'amount'           => $entry['amount'],
                    'reference'        => $entry['reference'] ?? null,
                    'recorded_by'      => $request->user()->id,
                    'status'           => 'draft',
                ]
            );

            $saved[] = $actual;
        }

        return response()->json([
            'message' => count($saved).' actuals saved.',
            'data'    => $saved,
        ], 201);
    }
}
