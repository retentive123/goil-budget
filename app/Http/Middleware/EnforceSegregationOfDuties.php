<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SegregationService;

class EnforceSegregationOfDuties
{
    // Routes that need segregation checks and which model field holds the submitter
    protected array $routeChecks = [
        'approvals.decide'          => ['model' => \App\Models\BudgetVersion::class,      'param' => 'budgetVersion', 'field' => 'submitted_by',  'action' => 'approve this budget'],
        'virements.approve'         => ['model' => \App\Models\Virement::class,           'param' => 'virement',      'field' => 'requested_by',  'action' => 'approve this virement'],
        'virements.reject'          => ['model' => \App\Models\Virement::class,           'param' => 'virement',      'field' => 'requested_by',  'action' => 'reject this virement'],
        'supplementary.approve'     => ['model' => \App\Models\SupplementaryBudget::class,'param' => 'supplementary', 'field' => 'requested_by',  'action' => 'approve this supplementary request'],
        'supplementary.reject'      => ['model' => \App\Models\SupplementaryBudget::class,'param' => 'supplementary', 'field' => 'requested_by',  'action' => 'reject this supplementary request'],
        'actuals.confirm'           => null, // Handled directly in controller
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!SegregationService::enabled()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && isset($this->routeChecks[$routeName])) {
            $check = $this->routeChecks[$routeName];

            if ($check !== null) {
                $model    = $check['model'];
                $param    = $check['param'];
                $field    = $check['field'];
                $action   = $check['action'];

                $record = $request->route($param);

                if ($record && $record instanceof $model) {
                    SegregationService::check($record->$field, $action);
                }
            }
        }

        return $next($request);
    }
}
