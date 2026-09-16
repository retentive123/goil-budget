<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemAuditLog::with('user')
            ->when($request->user_id,  fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->module,   fn($q) => $q->where('module', $request->module))
            ->when($request->event,    fn($q) => $q->where('event', $request->event))
            ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
            ->when($request->search,   fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('subject_label', 'like', "%{$request->search}%")
                   ->orWhere('event', 'like', "%{$request->search}%");
            }))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at');

        $perPage = in_array((int) $request->get('per_page', 10), [10, 25, 50, 100])
            ? (int) $request->per_page
            : 10;
        $logs = $query->paginate($perPage)->withQueryString();

        // Stats for the summary strip
        $stats = [
            'today'    => SystemAuditLog::whereDate('created_at', today())->count(),
            'week'     => SystemAuditLog::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'critical' => SystemAuditLog::where('severity','critical')
                            ->whereDate('created_at', today())->count(),
            'logins'   => SystemAuditLog::where('event','user_login')
                            ->whereDate('created_at', today())->count(),
        ];

        $users    = User::orderBy('name')->get(['id','name']);
        $modules  = SystemAuditLog::MODULES;
        $events   = SystemAuditLog::distinct()->orderBy('event')->pluck('event');
        $severities = SystemAuditLog::SEVERITIES;

        return view('admin.audit-log.index', compact(
            'logs','stats','users','modules','events','severities'
        ));
    }

    public function show(int $id)
    {
        $log = SystemAuditLog::with('user')->findOrFail($id);
        return view('admin.audit-log.show', compact('log'));
    }

    public function purge(Request $request)
    {
        $infoMonths    = (int) \App\Models\SystemSetting::get('audit_retain_info_months',    24);
        $warningMonths = (int) \App\Models\SystemSetting::get('audit_retain_warning_months', 24);
        $keepCritical  = (bool) \App\Models\SystemSetting::get('audit_log_keep_critical', true);

        $deletedInfo    = SystemAuditLog::where('severity', 'info')
                            ->where('created_at', '<', now()->subMonths($infoMonths))
                            ->delete();

        $deletedWarning = SystemAuditLog::where('severity', 'warning')
                            ->where('created_at', '<', now()->subMonths($warningMonths))
                            ->delete();

        $deletedCritical = 0;
        if (!$keepCritical) {
            $deletedCritical = SystemAuditLog::where('severity', 'critical')
                                ->where('created_at', '<', now()->subMonths(max($infoMonths, $warningMonths)))
                                ->delete();
        }

        $total = $deletedInfo + $deletedWarning + $deletedCritical;

        \App\Services\AuditLogger::record('audit_log_purged', 'admin', 'deleted', [
            'subject_label' => "Manual purge: {$total} records deleted",
            'new_values'    => [
                'info_deleted'     => $deletedInfo,
                'warning_deleted'  => $deletedWarning,
                'critical_deleted' => $deletedCritical,
            ],
            'severity' => 'warning',
        ]);

        return back()->with('success',
            "Audit log purged — {$deletedInfo} info, {$deletedWarning} warning, {$deletedCritical} critical records removed. Total: {$total}."
        );
    }

    public function export(Request $request)
    {
        $logs = SystemAuditLog::with('user')
            ->when($request->module,   fn($q) => $q->where('module', $request->module))
            ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'audit-log-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date/Time','User','Module','Event','Action',
                'Subject','Severity','IP Address','Old Values','New Values','Meta'
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('d M Y H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->moduleLabel(),
                    $log->event,
                    $log->action,
                    $log->subject_label ?? '—',
                    strtoupper($log->severity),
                    $log->ip_address ?? '—',
                    $log->old_values ? json_encode($log->old_values) : '—',
                    $log->new_values ? json_encode($log->new_values) : '—',
                    $log->meta       ? json_encode($log->meta)       : '—',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
