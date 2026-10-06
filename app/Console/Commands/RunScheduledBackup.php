<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunScheduledBackup extends Command
{
    protected $signature   = 'backup:run {--type=scheduled}';
    protected $description = 'Run a scheduled database backup';

    public function handle(BackupService $backupService): void
    {
        if (!\App\Models\SystemSetting::get('backup_enabled', true)) {
            $this->info('Scheduled backup skipped — backups are disabled in System Settings.');
            return;
        }

        // Respect the backup_frequency setting (daily / weekly / monthly)
        $frequency = \App\Models\SystemSetting::get('backup_frequency', 'daily');

        $shouldRun = match ($frequency) {
            'weekly'  => now()->dayOfWeek === 0,   // Sunday
            'monthly' => now()->day === 1,          // 1st of month
            default   => true,                      // daily — always run
        };

        if (!$shouldRun) {
            $this->info("Scheduled backup skipped — frequency is '{$frequency}', not due today.");
            return;
        }

        $this->info('Starting backup...');

        $backup = $backupService->create(
            type:   $this->option('type'),
            notes:  'Automated scheduled backup',
            userId: null
        );

        $notifyEmail = \App\Models\SystemSetting::get('backup_notify_email');

        if ($backup->status === 'completed') {
            $this->info('Backup completed: ' . $backup->filename);
            $this->info('Size: ' . $backup->sizeForHumans());

            // Prune old backups
            $keepCount = (int) \App\Models\SystemSetting::get('backup_keep_count', 10);
            $pruned    = $backupService->pruneOld($keepCount);

            if ($pruned > 0) {
                $this->info("Pruned {$pruned} old backup(s).");
            }

            // Success notification email
            if ($notifyEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        "Scheduled backup completed successfully.\n\n"
                        . "File: {$backup->filename}\n"
                        . "Size: {$backup->sizeForHumans()}\n"
                        . "Time: " . now()->format('d M Y H:i') . "\n"
                        . ($pruned > 0 ? "Old backups pruned: {$pruned}\n" : '')
                        . "\nThis is an automated notification from GOIL Budget.",
                        fn($msg) => $msg
                            ->to($notifyEmail)
                            ->subject('✓ Scheduled backup completed — GOIL Budget')
                    );
                } catch (\Exception $e) {
                    $this->warn('Could not send completion email: ' . $e->getMessage());
                }
            }
        } else {
            $this->error('Backup failed: ' . $backup->error_message);

            // Failure notification email (also handled by emailOutputOnFailure in console.php,
            // but this ensures backup_notify_email is always used regardless of that setting)
            if ($notifyEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        "Scheduled backup FAILED.\n\n"
                        . "Error: {$backup->error_message}\n"
                        . "Time: " . now()->format('d M Y H:i') . "\n\n"
                        . "Please check the server and run a manual backup.\n\n"
                        . "This is an automated notification from GOIL Budget.",
                        fn($msg) => $msg
                            ->to($notifyEmail)
                            ->subject('✗ Scheduled backup FAILED — GOIL Budget')
                    );
                } catch (\Exception $e) {
                    $this->warn('Could not send failure email: ' . $e->getMessage());
                }
            }
        }
    }
}
