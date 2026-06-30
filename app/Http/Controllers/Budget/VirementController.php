<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Virement;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;

class VirementController extends Controller
{
    public function __construct(
        protected NotificationService $notifier
    ) {}

    // List virements for the current user's department
    public function index()
    {
        $user      = auth()->user();
        $virements = Virement::with(
                'fromLineItem.accountCode',
                'toLineItem.accountCode',
                'requestedBy',
                'approvedBy'
            )
            ->when(
                !$user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin']),
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('virements.index', compact('virements'));
    }

    // Show the create virement form
    public function create()
    {
        $user          = auth()->user();
        $currentPeriod = BudgetPeriod::current();

        if (!$currentPeriod) {
            return redirect()->route('virements.index')
                ->with('error', 'No active budget period.');
        }

        // Get the dept's approved budget version for the current period
        $version = BudgetVersion::where('budget_period_id', $currentPeriod->id)
                                ->where('department_id', $user->department_id)
                                ->where('status', BudgetVersion::STATUS_APPROVED)
                                ->orderByDesc('version_number')
                                ->first();

        if (!$version) {
            return redirect()->route('virements.index')
                ->with('error', 'Your department does not have an approved budget for the current period. Virements can only be made against an approved budget.');
        }

        $lineItems = $version->lineItems()
                             ->with('accountCode.category')
                             ->get();

        return view('virements.create', compact('version', 'lineItems', 'currentPeriod'));
    }

    // Store a new virement request
    public function store(Request $request)
    {
        $user          = auth()->user();
        $currentPeriod = BudgetPeriod::current();

        $request->validate([
            'from_line_item_id' => ['required', 'exists:budget_line_items,id'],
            'to_line_item_id'   => ['required', 'exists:budget_line_items,id', 'different:from_line_item_id'],
            'amount'            => ['required', 'numeric', 'min:1'],
            'justification'     => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $fromItem = BudgetLineItem::findOrFail($request->from_line_item_id);
        $toItem   = BudgetLineItem::findOrFail($request->to_line_item_id);

        // Ensure both line items belong to the same dept's approved version
        $version = BudgetVersion::where('department_id', $user->department_id)
                                ->where('status', BudgetVersion::STATUS_APPROVED)
                                ->find($fromItem->budget_version_id);

        if (!$version) {
            return back()->with('error', 'Invalid line item selection.');
        }

        // Enforce 10% rule
        $maxAllowed = $fromItem->maxVirementAmount();
        if ($request->amount > $maxAllowed) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => "Amount exceeds the 10% virement limit. Maximum allowed from this account: {{ currency() }} " . number_format($maxAllowed, 2)
                ]);
        }

        // Check if total virements already requested from this line item
        // don't exceed the 10% cap in aggregate
        $alreadyRequested = Virement::where('from_line_item_id', $fromItem->id)
                                    ->whereIn('status', ['pending', 'approved'])
                                    ->sum('amount');

        if (($alreadyRequested + $request->amount) > $fromItem->total_amount * 0.10) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount' => "This request would exceed the cumulative 10% virement limit for this account. Already requested: {{ currency() }} " . number_format($alreadyRequested, 2)
                ]);
        }

        DB::transaction(function () use ($request, $user, $currentPeriod, $fromItem) {
            $virement = Virement::create([
                'budget_period_id'  => $currentPeriod->id,
                'department_id'     => $user->department_id,
                'from_line_item_id' => $request->from_line_item_id,
                'to_line_item_id'   => $request->to_line_item_id,
                'amount'            => $request->amount,
                'justification'     => $request->justification,
                'status'            => 'pending',
                'requested_by'      => $user->id,
            ]);

            AuditLogger::virementRequested($virement->fresh()->load('department','fromLineItem.accountCode','toLineItem.accountCode'));

            // Notify finance reviewers
            $this->notifyFinance($virement);
        });

        return redirect()->route('virements.index')
            ->with('success', 'Virement request submitted successfully. Finance has been notified.');
    }

    // Show a single virement
    public function show(Virement $virement)
    {
        $user = auth()->user();

        // Department users can only see their own dept's virements
        if (!$user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin'])) {
            if ($virement->department_id !== $user->department_id) {
                abort(403);
            }
        }

        $virement->load(
            'department',
            'fromLineItem.accountCode.category',
            'toLineItem.accountCode.category',
            'requestedBy',
            'approvedBy'
        );

        return view('virements.show', compact('virement'));
    }

    // List pending virements for Finance to action
    public function pending()
    {
        $pending = Virement::with(
                'department',
                'fromLineItem.accountCode',
                'toLineItem.accountCode',
                'requestedBy'
            )
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('virements.pending', compact('pending'));
    }

    // Approve a virement
    public function approve(Request $request, Virement $virement)
    {
        if ($virement->status !== 'pending') {
            return back()->with('error', 'This virement has already been actioned.');
        }

        // ── Segregation ──
        \App\Services\SegregationService::check(
            $virement->requested_by,
            'approve this virement'
        );

        $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $virement) {
            $virement->update([
                'status'           => 'approved',
                'approved_by'      => auth()->id(),
                'approval_comments'=> $request->comments,
                'approved_at'      => now(),
            ]);

            // Adjust line item amounts
            $fromItem = $virement->fromLineItem;
            $toItem   = $virement->toLineItem;

            // Deduct from source — spread proportionally across quarters
            $this->adjustLineItem($fromItem, -$virement->amount);
            $this->adjustLineItem($toItem,    $virement->amount);

            // Notify department
            $this->notifyDepartmentVirement($virement, 'approved');
        });

        AuditLogger::virementApproved($virement->fresh()->load('department'));

        return redirect()->route('virements.pending')
            ->with('success', 'Virement approved and budget adjusted.');
    }



    // Reject a virement
    public function reject(Request $request, Virement $virement)
    {
        if ($virement->status !== 'pending') {
            return back()->with('error', 'This virement has already been actioned.');
        }

        // ── Segregation ──
        \App\Services\SegregationService::check(
            $virement->requested_by,
            'reject this virement'
        );

        $request->validate([
            'comments' => ['required', 'string', 'max:500'],
        ]);

        $virement->update([
            'status'            => 'rejected',
            'approved_by'       => auth()->id(),
            'approval_comments' => $request->comments,
            'approved_at'       => now(),
        ]);

        $this->notifyDepartmentVirement($virement, 'rejected');

        AuditLogger::virementRejected($virement->fresh()->load('department'));

        return redirect()->route('virements.pending')
            ->with('success', 'Virement rejected. Department has been notified.');
    }

    // Spread virement amount proportionally across quarters
    private function adjustLineItem(BudgetLineItem $item, float $amount): void
    {
        $total = $item->total_amount ?: 1;

        $item->update([
            'q1_amount' => max(0, $item->q1_amount + ($amount * ($item->q1_amount / $total))),
            'q2_amount' => max(0, $item->q2_amount + ($amount * ($item->q2_amount / $total))),
            'q3_amount' => max(0, $item->q3_amount + ($amount * ($item->q3_amount / $total))),
            'q4_amount' => max(0, $item->q4_amount + ($amount * ($item->q4_amount / $total))),
        ]);
    }

    private function notifyFinance(Virement $virement): void
    {

    if (!\App\Models\SystemSetting::get('notify_finance_on_virement', true)) {
        return;
    }
        $finance = \App\Models\User::role('finance_reviewer')
                                   ->where('is_active', true)
                                   ->get();

        foreach ($finance as $user) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $user->id,
                'type'            => 'virement_pending',
                'subject'         => "Virement request — {$virement->department->name}",
                'message'         => "{$virement->department->name} has requested a virement of {{ currency() }} " . number_format($virement->amount, 2) . ". Please review.",
                'notifiable_id'   => $virement->id,
                'notifiable_type' => Virement::class,
            ]);
        }
    }

    private function notifyDepartmentVirement(Virement $virement, string $decision): void
    {

    if (!\App\Models\SystemSetting::get('notify_on_virement', true)) {
        return;
    }
        $members = \App\Models\User::where('department_id', $virement->department_id)
                                   ->where('is_active', true)
                                   ->get();

        $subject = $decision === 'approved'
            ? 'Virement Approved'
            : 'Virement Rejected';

        $message = $decision === 'approved'
            ? "Your virement request of {{ currency() }} " . number_format($virement->amount, 2) . " has been approved. Budget lines have been adjusted."
            : "Your virement request of {{ currency() }} " . number_format($virement->amount, 2) . " has been rejected. Reason: {$virement->approval_comments}";

        foreach ($members as $member) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $member->id,
                'type'            => "virement_{$decision}",
                'subject'         => $subject,
                'message'         => $message,
                'notifiable_id'   => $virement->id,
                'notifiable_type' => Virement::class,
            ]);
        }
    }
}
