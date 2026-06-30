<?php

namespace App\Services;

use App\Models\SystemBackup;
use App\Models\SystemAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupService
{
    protected string $backupDisk = 'local';
    protected string $backupPath = 'backups';

    public function create(
        string $type     = 'manual',
        string $notes    = '',
        ?int   $userId   = null
    ): SystemBackup {

        $filename = 'goil-budget-backup-' .
                    now()->format('Y-m-d-His') . '-' .
                    Str::random(6) . '.sql';

        $backup = SystemBackup::create([
            'filename'   => $filename,
            'path'       => $this->backupPath . '/' . $filename,
            'type'       => $type,
            'status'     => 'running',
            'notes'      => $notes,
            'created_by' => $userId ?? auth()->id(),
        ]);

        try {
            $sql = $this->dumpDatabase();

            Storage::disk($this->backupDisk)
                   ->put($backup->path, $sql);

            $size = Storage::disk($this->backupDisk)->size($backup->path);

            $backup->update([
                'status'       => 'completed',
                'size_bytes'   => $size,
                'completed_at' => now(),
            ]);

            SystemAuditLog::record(
                event: 'backup_created',
                module: 'system',
                action: 'created',
                options: [
                    'subject_type' => SystemBackup::class,
                    'subject_id' => $backup->id,
                    'subject_label' => $filename,
                    'meta' => [
                        'type' => $type,
                        'size' => $backup->sizeForHumans(),
                    ],
                    'severity' => 'info',
                ]
            );

        } catch (\Exception $e) {
            $backup->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            \Log::error('Backup failed: ' . $e->getMessage());
        }

        return $backup->fresh();
    }

    public function download(SystemBackup $backup): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk($this->backupDisk)->exists($backup->path)) {
            abort(404, 'Backup file not found.');
        }

        return Storage::disk($this->backupDisk)->download(
            $backup->path,
            $backup->filename
        );
    }

    public function delete(SystemBackup $backup): void
    {
        if (Storage::disk($this->backupDisk)->exists($backup->path)) {
            Storage::disk($this->backupDisk)->delete($backup->path);
        }

        SystemAuditLog::record(
            event: 'backup_deleted',
            module: 'system',
            action: 'deleted',
            options: [
                'subject_type' => SystemBackup::class,
                'subject_id' => $backup->id,
                'subject_label' => $backup->filename,
                'severity' => 'warning',
            ]
        );

        $backup->delete();
    }

    public function pruneOld(int $keepCount = 10): int
    {
        $old = SystemBackup::where('status', 'completed')
            ->orderByDesc('created_at')
            ->skip($keepCount)
            ->take(PHP_INT_MAX)
            ->get();

        $deleted = 0;
        foreach ($old as $backup) {
            $this->delete($backup);
            $deleted++;
        }

        return $deleted;
    }

    private function dumpDatabase(): string
    {
        $config   = config('database.connections.' . config('database.default'));
        $host     = $config['host'];
        $port     = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Collect all table data via Laravel DB
        $sql  = "-- GOIL Budget Tool Backup\n";
        $sql .= "-- Generated: " . now()->toISOString() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        foreach ($tables as $tableObj) {
            $table = $tableObj->$tableKey;

            // Create table SQL
            $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSQL   = $createTable[0]->{'Create Table'};
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSQL . ";\n\n";

            // Row data
            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) continue;

            $sql .= "INSERT INTO `{$table}` VALUES\n";
            $chunks = $rows->chunk(500);

            foreach ($chunks as $chunkIdx => $chunk) {
                $values = $chunk->map(function ($row) {
                    $cols = array_map(function ($val) {
                        if (is_null($val)) return 'NULL';
                        return "'" . addslashes((string) $val) . "'";
                    }, (array) $row);
                    return '(' . implode(',', $cols) . ')';
                })->implode(",\n");

                $sql .= $values;
                $sql .= ($chunkIdx < $chunks->count() - 1) ? ",\n" : ";\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }
}
