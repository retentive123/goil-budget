<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAuditLog extends Model
{
    protected $fillable = [
        'user_id', 'event', 'module', 'action',
        'subject_type', 'subject_id', 'subject_label',
        'old_values', 'new_values', 'meta',
        'ip_address', 'user_agent', 'severity',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta'       => 'array',
    ];

    const MODULES = [
        'auth'       => 'Authentication',
        'budget'     => 'Budget',
        'approval'   => 'Approvals',
        'virement'   => 'Virements',
        'actuals'    => 'Actuals',
        'admin'      => 'Administration',
        'reports'    => 'Reports',
        'settings'   => 'Settings',
        'system'     => 'System',
    ];

    const SEVERITIES = [
        'info'     => ['label' => 'Info',     'color' => '#3B82F6', 'bg' => '#DBEAFE'],
        'warning'  => ['label' => 'Warning',  'color' => '#D97706', 'bg' => '#FEF3C7'],
        'critical' => ['label' => 'Critical', 'color' => '#DC2626', 'bg' => '#FEE2E2'],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function severityConfig(): array
    {
        return self::SEVERITIES[$this->severity] ?? self::SEVERITIES['info'];
    }

    public function moduleLabel(): string
    {
        return self::MODULES[$this->module] ?? ucfirst($this->module);
    }

    // Static logger — call from anywhere in the app
    public static function record(
        string  $event,
        string  $module,
        string  $action,
        array   $options = []
    ): self {
        $request = request();

        return self::create([
            'user_id'       => $options['user_id']  ?? auth()->id(),
            'event'         => $event,
            'module'        => $module,
            'action'        => $action,
            'subject_type'  => $options['subject_type']  ?? null,
            'subject_id'    => $options['subject_id']    ?? null,
            'subject_label' => $options['subject_label'] ?? null,
            'old_values'    => $options['old_values']    ?? null,
            'new_values'    => $options['new_values']    ?? null,
            'meta'          => $options['meta']          ?? null,
            'ip_address'    => $request?->ip(),
            'user_agent'    => $request?->userAgent(),
            'severity'      => $options['severity']      ?? 'info',
        ]);
    }
}
