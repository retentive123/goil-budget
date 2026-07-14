<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\AccountCode;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceStationController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::serviceStations()->with('zone')->withCount('users', 'accountCodes');

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $perPage  = in_array((int) $request->per_page, [10, 25, 50, 100])
            ? (int) $request->per_page
            : 10;
        $stations = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $zones    = Zone::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'active'   => Department::serviceStations()->where('is_active', true)->count(),
            'inactive' => Department::serviceStations()->where('is_active', false)->count(),
            'users'    => Department::serviceStations()->withCount('users')->get()->sum('users_count'),
            'codes'    => Department::serviceStations()->withCount('accountCodes')->get()->sum('account_codes_count'),
        ];

        return view('admin.service-stations.index', compact('stations', 'zones', 'stats'));
    }

    public function create()
    {
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        return view('admin.service-stations.create', compact('zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name'],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'zone_id'     => ['required', 'exists:zones,id'],
            'budget_type' => ['required', 'in:revenue,expense,both'],
        ]);

        $station = Department::create([
            ...$validated,
            'is_active'   => true,
            'entity_type' => 'service_station',
        ]);

        return redirect()->route('admin.service-stations.show', $station)
            ->with('success', "Service station \"{$station->name}\" created successfully.");
    }

    public function show(Department $serviceStation)
    {
        $serviceStation->load('users.roles', 'accountCodes.category', 'zone');
        return view('admin.service-stations.show', ['station' => $serviceStation]);
    }

    public function edit(Department $serviceStation)
    {
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        return view('admin.service-stations.edit', ['station' => $serviceStation, 'zones' => $zones]);
    }

    public function update(Request $request, Department $serviceStation)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name,' . $serviceStation->id],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code,' . $serviceStation->id],
            'description' => ['nullable', 'string'],
            'zone_id'     => ['required', 'exists:zones,id'],
            'is_active'   => ['boolean'],
            'budget_type' => ['required', 'in:revenue,expense,both'],
        ]);

        $serviceStation->update($validated);

        return redirect()->route('admin.service-stations.show', $serviceStation)
            ->with('success', "Service station \"{$serviceStation->name}\" updated.");
    }

    public function destroy(Department $serviceStation)
    {
        if ($serviceStation->users()->count()) {
            return back()->with('error', 'Cannot delete a service station that has users assigned to it.');
        }

        $serviceStation->update(['is_active' => false]);
        $serviceStation->delete();

        return redirect()->route('admin.service-stations.index')
            ->with('success', 'Service station deleted.');
    }

    public function massAssignForm()
    {
        $stations = Department::serviceStations()
            ->where('is_active', true)
            ->with('zone')
            ->orderBy('name')
            ->get();
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        $codes = AccountCode::with('category')->where('is_active', true)->orderBy('code')->get();

        return view('admin.service-stations.mass-assign', compact('stations', 'zones', 'codes'));
    }

    public function massAssign(Request $request)
    {
        $request->validate([
            'station_ids'     => ['required', 'array', 'min:1'],
            'station_ids.*'   => ['exists:departments,id'],
            'account_codes'   => ['required', 'array', 'min:1'],
            'account_codes.*' => ['exists:account_codes,id'],
            'mode'            => ['required', 'in:add,replace'],
        ]);

        $stationIds = Department::serviceStations()
            ->whereIn('id', $request->station_ids)
            ->pluck('id')
            ->all();
        $codeIds = $request->account_codes;
        $now     = now();

        DB::transaction(function () use ($stationIds, $codeIds, $request, $now) {
            if ($request->mode === 'replace') {
                DB::table('department_account_codes')
                    ->whereIn('department_id', $stationIds)
                    ->delete();
            }

            $rows = [];
            foreach ($stationIds as $stationId) {
                foreach ($codeIds as $codeId) {
                    $rows[] = [
                        'department_id'   => $stationId,
                        'account_code_id' => $codeId,
                        'is_active'       => true,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('department_account_codes')->insertOrIgnore($chunk);
            }
        });

        $verb = $request->mode === 'replace' ? 'Replaced codes on' : 'Added codes to';

        return redirect()->route('admin.service-stations.index')
            ->with('success', "{$verb} " . count($stationIds) . " service station(s) — " . count($codeIds) . " code(s) applied.");
    }

    public function accountCodes(Department $serviceStation)
    {
        $assigned = $serviceStation->accountCodes()->pluck('account_codes.id')->toArray();
        $all      = AccountCode::with('category')->where('is_active', true)->orderBy('code')->get();

        return view('admin.service-stations.account-codes', [
            'station'  => $serviceStation,
            'assigned' => $assigned,
            'all'      => $all,
        ]);
    }

    public function syncAccountCodes(Request $request, Department $serviceStation)
    {
        $request->validate([
            'account_codes'   => ['nullable', 'array'],
            'account_codes.*' => ['exists:account_codes,id'],
        ]);

        $serviceStation->accountCodes()->sync($request->account_codes ?? []);

        return redirect()->route('admin.service-stations.show', $serviceStation)
            ->with('success', "Account codes updated for \"{$serviceStation->name}\".");
    }
}
