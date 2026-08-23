<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\Zone;
use App\Models\AccountCode;
use App\Models\AccountCategory;
use App\Models\BudgetPeriod;
use App\Models\BudgetVersion;
use App\Models\Subsidiary;
use App\Models\SubsidiaryCategory;

class SearchController extends Controller
{
    /**
     * Global search — returns JSON groups of results.
     * Results are scoped to what the current user can access.
     */
    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['groups' => [], 'total' => 0]);
        }

        $user   = auth()->user();
        $isAdmin = $user->can('manage users');
        $groups  = [];

        // ── Users (admin only) ─────────────────────────────────────────
        if ($isAdmin) {
            $users = User::query()
                ->where(function ($query) use ($q) {
                    $query->where('name',        'like', "%{$q}%")
                          ->orWhere('email',       'like', "%{$q}%")
                          ->orWhere('employee_id', 'like', "%{$q}%");
                })
                ->limit(8)
                ->get(['id', 'name', 'email', 'is_active', 'employee_id']);

            if ($users->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Users',
                    'icon'  => 'fas fa-users',
                    'color' => '#6366F1',
                    'items' => $users->map(fn ($u) => [
                        'title'    => $u->name,
                        'subtitle' => ($u->employee_id ? "#{$u->employee_id} · " : '') . $u->email,
                        'badge'    => $u->is_active ? null : 'Inactive',
                        'badge_color' => '#EF4444',
                        'url'      => route('admin.users.edit', $u->id),
                        'icon'     => 'fas fa-user',
                    ])->values(),
                ];
            }
        }

        // ── Departments (admin sees all; dept user sees only their own) ─
        $deptQuery = Department::query()
            ->where('entity_type', '!=', 'service_station')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('code', 'like', "%{$q}%");
            });

        if (!$isAdmin) {
            // Scope to user's own department
            $deptQuery->where('id', $user->department_id ?? 0);
        }

        $depts = $deptQuery->limit(8)->get(['id', 'name', 'code', 'is_active']);

        if ($depts->isNotEmpty()) {
            $groups[] = [
                'label' => 'Departments',
                'icon'  => 'fas fa-building',
                'color' => '#0EA5E9',
                'items' => $depts->map(fn ($d) => [
                    'title'    => $d->name,
                    'subtitle' => $d->code,
                    'badge'    => $d->is_active ? null : 'Inactive',
                    'badge_color' => '#EF4444',
                    'url'      => $isAdmin ? route('admin.departments.edit', $d->id) : null,
                    'icon'     => 'fas fa-building',
                ])->values(),
            ];
        }

        // ── Service Stations ────────────────────────────────────────────
        $stationQuery = Department::query()
            ->where('entity_type', 'service_station')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('code', 'like', "%{$q}%");
            });

        if (!$isAdmin) {
            $stationQuery->where('id', $user->department_id ?? 0);
        }

        $stations = $stationQuery->with('zone')->limit(8)->get(['id', 'name', 'code', 'is_active', 'zone_id']);

        if ($stations->isNotEmpty()) {
            $groups[] = [
                'label' => 'Service Stations',
                'icon'  => 'fas fa-gas-pump',
                'color' => '#F59E0B',
                'items' => $stations->map(fn ($s) => [
                    'title'    => $s->name,
                    'subtitle' => $s->code . ($s->zone ? ' · ' . $s->zone->name : ''),
                    'badge'    => $s->is_active ? null : 'Inactive',
                    'badge_color' => '#EF4444',
                    'url'      => $isAdmin ? route('admin.service-stations.edit', $s->id) : null,
                    'icon'     => 'fas fa-gas-pump',
                ])->values(),
            ];
        }

        // ── Subsidiaries ────────────────────────────────────────────────
        $subQuery = Subsidiary::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('code', 'like', "%{$q}%");
            });

        if (!$isAdmin) {
            $subQuery->where('id', $user->subsidiary_id ?? 0);
        }

        $subsidiaries = $subQuery->with('category')->limit(8)->get(['id', 'name', 'code', 'is_active', 'subsidiary_category_id']);

        if ($subsidiaries->isNotEmpty()) {
            $groups[] = [
                'label' => 'Subsidiaries',
                'icon'  => 'fas fa-diagram-project',
                'color' => '#8B5CF6',
                'items' => $subsidiaries->map(fn ($s) => [
                    'title'    => $s->name,
                    'subtitle' => $s->code . ($s->category ? ' · ' . $s->category->name : ''),
                    'badge'    => $s->is_active ? null : 'Inactive',
                    'badge_color' => '#EF4444',
                    'url'      => $isAdmin ? route('admin.subsidiaries.edit', $s->id) : null,
                    'icon'     => 'fas fa-diagram-project',
                ])->values(),
            ];
        }

        // ── Zones (admin only) ─────────────────────────────────────────
        if ($isAdmin) {
            $zones = Zone::query()
                ->where('name', 'like', "%{$q}%")
                ->limit(6)
                ->get(['id', 'name', 'is_active']);

            if ($zones->isNotEmpty()) {
                $groups[] = [
                    'label' => 'Zones',
                    'icon'  => 'fas fa-map-marker-alt',
                    'color' => '#10B981',
                    'items' => $zones->map(fn ($z) => [
                        'title'    => $z->name,
                        'subtitle' => 'Zone',
                        'badge'    => $z->is_active ? null : 'Inactive',
                        'badge_color' => '#EF4444',
                        'url'      => route('admin.zones.edit', $z->id),
                        'icon'     => 'fas fa-map-marker-alt',
                    ])->values(),
                ];
            }
        }

        // ── Account Codes (all users, admin gets edit link) ─────────────
        $codes = AccountCode::query()
            ->with('category')
            ->where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get(['id', 'code', 'name', 'is_active', 'account_category_id']);

        if ($codes->isNotEmpty()) {
            $groups[] = [
                'label' => 'Account Codes',
                'icon'  => 'fas fa-hashtag',
                'color' => '#F97316',
                'items' => $codes->map(fn ($c) => [
                    'title'    => $c->name,
                    'subtitle' => $c->code . ($c->category ? ' · ' . $c->category->name : ''),
                    'badge'    => $c->is_active ? null : 'Inactive',
                    'badge_color' => '#EF4444',
                    'url'      => $isAdmin ? route('admin.account-codes.edit', $c->id) : '#',
                    'icon'     => 'fas fa-hashtag',
                ])->values(),
            ];
        }

        // ── Account Categories (all users) ──────────────────────────────
        $cats = AccountCategory::query()
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'name', 'budget_type', 'is_active']);

        if ($cats->isNotEmpty()) {
            $groups[] = [
                'label' => 'Account Categories',
                'icon'  => 'fas fa-folder',
                'color' => '#64748B',
                'items' => $cats->map(fn ($c) => [
                    'title'    => $c->name,
                    'subtitle' => ucfirst(str_replace('_', ' ', $c->budget_type ?? '')),
                    'badge'    => $c->is_active ? null : 'Inactive',
                    'badge_color' => '#EF4444',
                    'url'      => $isAdmin ? route('admin.account-categories.edit', $c->id) : '#',
                    'icon'     => 'fas fa-folder',
                ])->values(),
            ];
        }

        // ── Budget Periods (admin gets edit link; all users can see) ────
        $periods = BudgetPeriod::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('year',  'like', "%{$q}%");
            })
            ->limit(5)
            ->get(['id', 'name', 'year', 'status']);

        if ($periods->isNotEmpty()) {
            $groups[] = [
                'label' => 'Budget Periods',
                'icon'  => 'fas fa-calendar-alt',
                'color' => '#0F172A',
                'items' => $periods->map(fn ($p) => [
                    'title'    => $p->name,
                    'subtitle' => 'FY ' . $p->year . ' · ' . ucfirst($p->status ?? 'Closed'),
                    'badge'    => ($p->status === 'open') ? 'Open' : null,
                    'badge_color' => '#10B981',
                    'url'      => $isAdmin ? route('admin.budget-periods.edit', $p->id) : route('budget.index'),
                    'icon'     => 'fas fa-calendar-alt',
                ])->values(),
            ];
        }

        // ── Budget Versions (own versions for non-admin) ─────────────────
        if (!$isAdmin) {
            $versions = BudgetVersion::query()
                ->with(['budgetPeriod'])
                ->where(function ($q2) use ($user) {
                    $q2->where('department_id', $user->department_id)
                       ->orWhere('subsidiary_id', $user->subsidiary_id);
                })
                ->whereHas('budgetPeriod', function ($pq) use ($q) {
                    $pq->where('name', 'like', "%{$q}%")
                       ->orWhere('year', 'like', "%{$q}%");
                })
                ->limit(5)
                ->get(['id', 'budget_period_id', 'status', 'version_number', 'is_revision']);

            if ($versions->isNotEmpty()) {
                $statusColors = [
                    'draft'        => '#94A3B8',
                    'submitted'    => '#3B82F6',
                    'under_review' => '#F59E0B',
                    'approved'     => '#10B981',
                    'rejected'     => '#EF4444',
                ];
                $groups[] = [
                    'label' => 'My Budgets',
                    'icon'  => 'fas fa-briefcase',
                    'color' => '#1B2A4A',
                    'items' => $versions->map(fn ($v) => [
                        'title'       => $v->budgetPeriod?->name ?? 'Budget',
                        'subtitle'    => 'v' . $v->version_number . ($v->is_revision ? ' (Revision)' : ''),
                        'badge'       => ucfirst(str_replace('_', ' ', $v->status)),
                        'badge_color' => $statusColors[$v->status] ?? '#94A3B8',
                        'url'         => route('budget.show', $v->id),
                        'icon'        => 'fas fa-briefcase',
                    ])->values(),
                ];
            }
        }

        $total = collect($groups)->sum(fn ($g) => count($g['items']));

        return response()->json(compact('groups', 'total'));
    }
}
