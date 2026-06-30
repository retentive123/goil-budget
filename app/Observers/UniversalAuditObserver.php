<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Models\SystemAuditLog;

class UniversalAuditObserver
{
    // Fields we never want to log (sensitive or noisy)
    protected array $hidden = [
        'password', 'remember_token', 'two_factor_secret',
        'updated_at', 'created_at',
    ];

    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $this->filtered($model->getAttributes()));
    }

    // In UniversalAuditObserver::updated()

    public function updated(Model $model): void
    {
        $changes  = $model->getChanges();
        $original = $model->getOriginal();

        $meaningful = array_diff_key($changes, array_flip($this->hidden));
        if (empty($meaningful)) return;

        // Throttle noisy autosave models — only log once per 2 minutes per record
        if ($model instanceof \App\Models\BudgetLineItem) {
            $cacheKey = "audit_throttle_lineitem_{$model->id}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) return;
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 120);
        }

        $old = [];
        foreach (array_keys($meaningful) as $key) {
            $old[$key] = $original[$key] ?? null;
        }

        $this->log($model, 'updated', $this->filtered($old), $this->filtered($meaningful));
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $this->filtered($model->getOriginal()), null);
    }

    private function filtered(array $attrs): array
    {
        return collect($attrs)->except($this->hidden)->toArray();
    }

    private function log(Model $model, string $action, ?array $old, ?array $new): void
    {
        // Avoid recursive logging — never log the audit log table itself
        if ($model instanceof SystemAuditLog) return;

        $modelName = class_basename($model);
        $label     = $this->resolveLabel($model);

        SystemAuditLog::record(
            event:   strtolower($modelName) . '_' . $action,
            module:  $this->resolveModule($modelName),
            action:  $action,
            options: [
                'subject_type'  => get_class($model),
                'subject_id'    => $model->getKey(),
                'subject_label' => $label,
                'old_values'    => $old,
                'new_values'    => $new,
                'severity'      => $this->resolveSeverity($modelName, $action),
            ]
        );
    }

    private function resolveLabel(Model $model): string
    {
        foreach (['name', 'title', 'code', 'subject', 'filename'] as $field) {
            if (isset($model->$field)) return (string) $model->$field;
        }
        return class_basename($model) . ' #' . $model->getKey();
    }

    private function resolveModule(string $modelName): string
    {
        return match (true) {
            str_contains($modelName, 'Budget')   => 'budget',
            str_contains($modelName, 'Virement') => 'virement',
            str_contains($modelName, 'Actual')   => 'actuals',
            str_contains($modelName, 'User')     => 'admin',
            str_contains($modelName, 'Role')     => 'admin',
            str_contains($modelName, 'Setting')  => 'settings',
            str_contains($modelName, 'Account')  => 'admin',
            str_contains($modelName, 'Department') => 'admin',
            default => 'system',
        };
    }

    private function resolveSeverity(string $modelName, string $action): string
    {
        if ($action === 'deleted') return 'critical';
        if (in_array($modelName, ['SystemSetting', 'Role', 'User'])) return 'warning';
        return 'info';
    }
}
