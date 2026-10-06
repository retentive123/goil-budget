<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WidgetSetting extends Model
{
    protected $fillable = ['role', 'widget_key', 'is_visible', 'display_order'];

    protected $casts = ['is_visible' => 'boolean'];

    /**
     * All defined dashboard widgets with their default visibility per role.
     */
    public static array $widgets = [
        'budget_health'      => 'Budget Submission Health',
        'financial_overview' => 'Financial Overview (KPI cards)',
        'analytics'          => 'Analytics Charts',
        'budget_timeline'    => 'Budget Timeline',
        'submissions_table'  => 'Submissions & Approvals Table',
        'dept_view'          => 'Department Budget View',
        'recent_virements'   => 'Recent Virements',
        'recent_actuals'     => 'Recent Actuals',
    ];

    public static array $roles = [
        'super_admin'       => 'Super Admin',
        'bdu_admin'         => 'BDU Admin',
        'finance_reviewer'  => 'Finance Reviewer',
        'department_head'   => 'Department Head',
        'budget_officer'    => 'Budget Officer',
        'gceo'              => 'GCEO',
        'board'             => 'Board',
    ];

    /**
     * Return a flat map of widget_key => is_visible for a given role.
     * Falls back to true for any widget not explicitly configured.
     */
    public static function visibilityFor(string $role): array
    {
        $rows = static::where('role', $role)->pluck('is_visible', 'widget_key');
        $result = [];
        foreach (array_keys(static::$widgets) as $key) {
            $result[$key] = $rows->has($key) ? (bool) $rows[$key] : true;
        }
        return $result;
    }
}
