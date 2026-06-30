<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStage;
use App\Models\BudgetVersion;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ApprovalStageController extends Controller
{
    public function index()
    {
        $stages = ApprovalStage::orderBy('order')->get();
        $roles  = Role::orderBy('name')->get();

        // Count how many active budget versions are in each stage
        $stageBudgetCounts = [];
        foreach ($stages as $stage) {
            $stageBudgetCounts[$stage->id] = \App\Models\ApprovalDecision::where('approval_stage_id', $stage->id)
                ->whereHas('budgetVersion', fn($q) => $q->whereIn('status', ['submitted','under_review']))
                ->count();
        }

        return view('admin.approval-stages.index', compact('stages','roles','stageBudgetCounts'));
    }

    public function create()
    {
        $roles          = Role::orderBy('name')->get();
        $maxOrder       = ApprovalStage::max('order') ?? 0;
        $nextOrder      = $maxOrder + 1;

        return view('admin.approval-stages.create', compact('roles','nextOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required','string','max:100'],
            'role_name' => ['required','exists:roles,name'],
            'order'     => ['required','integer','min:1'],
            'is_active' => ['boolean'],
        ]);

        // Shift existing stages down if inserting in the middle
        if (ApprovalStage::where('order', $validated['order'])->exists()) {
            ApprovalStage::where('order', '>=', $validated['order'])
                ->increment('order');
        }

        ApprovalStage::create([
            'name'      => $validated['name'],
            'role_name' => $validated['role_name'],
            'order'     => $validated['order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->reorderSequential();

        \App\Services\AuditLogger::settingsChanged(
            ['approval_stage_added' => $validated['name']],
            auth()->user()
        );

        return redirect()->route('admin.approval-stages.index')
            ->with('success', "Stage \"{$validated['name']}\" added. Review the order below.");
    }

    public function edit(ApprovalStage $approvalStage)
    {
        $roles      = Role::orderBy('name')->get();
        $maxOrder   = ApprovalStage::max('order') ?? 1;

        return view('admin.approval-stages.edit', compact('approvalStage','roles','maxOrder'));
    }

    public function update(Request $request, ApprovalStage $approvalStage)
    {
        $validated = $request->validate([
            'name'      => ['required','string','max:100'],
            'role_name' => ['required','exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        $approvalStage->update([
            'name'      => $validated['name'],
            'role_name' => $validated['role_name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        \App\Services\AuditLogger::settingsChanged(
            ['approval_stage_updated' => $approvalStage->name],
            auth()->user()
        );

        return redirect()->route('admin.approval-stages.index')
            ->with('success', "Stage \"{$approvalStage->name}\" updated.");
    }

    public function destroy(ApprovalStage $approvalStage)
    {
        // Prevent deletion if budgets are actively at this stage
        $inProgress = \App\Models\ApprovalDecision::where('approval_stage_id', $approvalStage->id)
            ->whereHas('budgetVersion', fn($q) =>
                $q->whereIn('status', ['submitted','under_review'])
            )
            ->exists();

        if ($inProgress) {
            return back()->with('error',
                "Cannot delete \"{$approvalStage->name}\" — there are budgets currently at this stage. " .
                "Wait for them to be processed or reassign them first."
            );
        }

        $name = $approvalStage->name;
        $approvalStage->delete();

        $this->reorderSequential();

        \App\Services\AuditLogger::settingsChanged(
            ['approval_stage_deleted' => $name],
            auth()->user()
        );

        return redirect()->route('admin.approval-stages.index')
            ->with('success', "Stage \"{$name}\" deleted. Order has been updated.");
    }

    public function moveUp(ApprovalStage $approvalStage)
    {
        $prev = ApprovalStage::where('order', '<', $approvalStage->order)
                             ->orderByDesc('order')
                             ->first();

        if ($prev) {
            $this->swapOrder($approvalStage, $prev);
        }

        return back()->with('success', "Stage moved up.");
    }

    public function moveDown(ApprovalStage $approvalStage)
    {
        $next = ApprovalStage::where('order', '>', $approvalStage->order)
                             ->orderBy('order')
                             ->first();

        if ($next) {
            $this->swapOrder($approvalStage, $next);
        }

        return back()->with('success', "Stage moved down.");
    }

    // Drag-and-drop reorder via AJAX
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => ['required','array'],
            'order.*' => ['integer','exists:approval_stages,id'],
        ]);

        foreach ($request->order as $position => $id) {
            ApprovalStage::where('id', $id)->update(['order' => $position + 1]);
        }

        \App\Services\AuditLogger::settingsChanged(
            ['approval_stages_reordered' => true],
            auth()->user()
        );

        return response()->json(['success' => true]);
    }

    private function swapOrder(ApprovalStage $a, ApprovalStage $b): void
    {
        $tempOrder = $a->order;
        $a->update(['order' => $b->order]);
        $b->update(['order' => $tempOrder]);
    }

    // Ensure order is always 1,2,3,4... with no gaps
    private function reorderSequential(): void
    {
        $stages = ApprovalStage::orderBy('order')->get();
        foreach ($stages as $idx => $stage) {
            $stage->update(['order' => $idx + 1]);
        }
    }
}
