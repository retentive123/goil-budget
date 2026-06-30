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
        $this->info('Starting backup...');

        $backup = $backupService->create(
            type:   $this->option('type'),
            notes:  'Automated scheduled backup',
            userId: null
        );

        if ($backup->status === 'completed') {
            $this->info('Backup completed: ' . $backup->filename);
            $this->info('Size: ' . $backup->sizeForHumans());

            // Prune old backups — keep last 10
            $keepCount = (int) \App\Models\SystemSetting::get('backup_keep_count', 10);
            $pruned    = $backupService->pruneOld($keepCount);

            if ($pruned > 0) {
                $this->info("Pruned {$pruned} old backup(s).");
            }
        } else {
            $this->error('Backup failed: ' . $backup->error_message);
        }
    }
}
