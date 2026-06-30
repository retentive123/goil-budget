<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemBackup;
use App\Models\SystemAuditLog;  
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService) {}

    public function index()
    {
        $backups = SystemBackup::with('createdBy')
                               ->orderByDesc('created_at')
                               ->paginate(20);

        $totalSize = SystemBackup::where('status', 'completed')->sum('size_bytes');

        $nextScheduled = $this->nextScheduledTime();

        return view('admin.backups.index', compact('backups', 'totalSize', 'nextScheduled'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $backup = $this->backupService->create(
            type:  'manual',
            notes: $request->notes ?? 'Manual backup by ' . auth()->user()->name,
        );

        if ($backup->status === 'failed') {
            return back()->with('error', 'Backup failed: ' . $backup->error_message);
        }

        return back()->with('success',
            "Backup created successfully. Size: {$backup->sizeForHumans()}"
        );
    }

    public function download(SystemBackup $backup)
    {
        if ($backup->status !== 'completed') {
            return back()->with('error', 'Backup is not available for download.');
        }

        SystemAuditLog::record(
            event: 'backup_downloaded',
            module: 'system',
            action: 'exported',
            options: [
                'subject_type' => SystemBackup::class,
                'subject_id' => $backup->id,
                'subject_label' => $backup->filename,
                'severity' => 'warning',
            ]
        );

        return $this->backupService->download($backup);
    }

    public function destroy(SystemBackup $backup)
    {
        $this->backupService->delete($backup);
        return back()->with('success', "Backup '{$backup->filename}' deleted.");
    }

    private function nextScheduledTime(): string
    {
        $frequency = \App\Models\SystemSetting::get('backup_frequency', 'daily');
        return match($frequency) {
            'weekly'  => 'Next Sunday at 01:00',
            'monthly' => 'Next 1st of month at 02:00',
            default   => 'Tonight at 02:00',
        };
    }
}
