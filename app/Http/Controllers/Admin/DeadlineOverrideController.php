<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubmissionDeadlineOverride;
use App\Models\BudgetPeriod;
use App\Models\Department;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;

class DeadlineOverrideController extends Controller
{
    public function __construct(
        protected BudgetCalculationService $calculator
    ) {}

    public function index(Request $request)
    {
        $period    = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current();

        $periods   = BudgetPeriod::orderByDesc('year')->get();
        $overrides = SubmissionDeadlineOverride::with('department','grantedBy','requestedBy')
            ->when($period, fn($q) => $q->where('budget_period_id', $period->id))
            ->orderByDesc('created_at')
            ->get();

        $deadlineDays = (int) \App\Models\SystemSetting::get('budget_entry_deadline_days', 0);
        $deadline     = $period && $period->opened_at && $deadlineDays > 0
            ? $period->opened_at->addDays($deadlineDays)
            : null;

        // Departments without an override that are past deadline
        $departments = Department::where('is_active', true)->orderBy('name')->get()
            ->map(function ($dept) use ($period, $deadline, $overrides) {
                $override      = $overrides->firstWhere('department_id', $dept->id);
                $deadlineInfo  = $period
                    ? $this->calculator->isDeadlinePassed($period->id, $dept->id)
                    : ['passed' => false, 'deadline' => null, 'has_override' => false];

                return [
                    'dept'         => $dept,
                    'override'     => $override,
                    'deadline_info'=> $deadlineInfo,
                ];
            });

        return view('admin.deadline-overrides.index', compact(
            'period','periods','overrides','departments','deadline','deadlineDays'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'budget_period_id' => ['required','exists:budget_periods,id'],
            'department_id'    => ['required','exists:departments,id'],
            'reason'           => ['required','string','min:10','max:1000'],
            'new_deadline'     => ['nullable','date','after:now'],
            'expires_at'       => ['nullable','date','after:now'],
        ]);

        SubmissionDeadlineOverride::updateOrCreate(
            [
                'budget_period_id' => $request->budget_period_id,
                'department_id'    => $request->department_id,
            ],
            [
                'granted_by'   => auth()->id(),
                'reason'       => $request->reason,
                'new_deadline' => $request->new_deadline ?? null,
                'expires_at'   => $request->expires_at ?? now()->addDays(7),
                'is_active'    => true,
            ]
        );

        $dept   = Department::find($request->department_id);
        $period = BudgetPeriod::find($request->budget_period_id);

        // Notify dept users
        \App\Models\User::where('department_id', $dept->id)
            ->where('is_active', true)
            ->get()
            ->each(function ($user) use ($dept, $period, $request) {
                \App\Models\BudgetNotification::create([
                    'user_id'         => $user->id,
                    'type'            => 'deadline_override_granted',
                    'subject'         => 'Submission deadline extended — ' . $period->name,
                    'message'         => "An extension has been granted for {$dept->name} to submit " .
                                        "their budget for {$period->name}. " .
                                        ($request->new_deadline
                                            ? "New deadline: " . \Carbon\Carbon::parse($request->new_deadline)->format('d M Y H:i') . "."
                                            : "Please submit as soon as possible."),
                    'notifiable_id'   => $user->id,
                    'notifiable_type' => \App\Models\User::class,
                ]);
            });

        \App\Services\AuditLogger::record(
            'deadline_override_granted', 'admin', 'updated',
            [
                'subject_label' => "{$dept->name} — {$period->name}",
                'meta'          => ['reason' => $request->reason],
                'severity'      => 'warning',
            ]
        );

        return back()->with('success',
            "Deadline override granted for {$dept->name}. They have been notified."
        );
    }

    public function revoke(SubmissionDeadlineOverride $override)
    {
        $override->update(['is_active' => false]);

        \App\Services\AuditLogger::record(
            'deadline_override_revoked', 'admin', 'updated',
            [
                'subject_label' => $override->department->name,
                'severity'      => 'warning',
            ]
        );

        return back()->with('success', 'Override revoked.');
    }
}
