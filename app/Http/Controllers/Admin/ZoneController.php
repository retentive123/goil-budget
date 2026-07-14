<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::withCount(['departments', 'serviceStations'])
                     ->orderBy('name')
                     ->get();
        return view('admin.zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.zones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:zones,name'],
            'code'        => ['required', 'string', 'max:20', 'unique:zones,code'],
            'description' => ['nullable', 'string'],
        ]);

        $zone = Zone::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.zones.index')
            ->with('success', "Zone \"{$zone->name}\" created successfully.");
    }

    public function show(Zone $zone)
    {
        $zone->load(['departments', 'serviceStations']);
        return view('admin.zones.show', compact('zone'));
    }

    public function edit(Zone $zone)
    {
        return view('admin.zones.edit', compact('zone'));
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:zones,name,' . $zone->id],
            'code'        => ['required', 'string', 'max:20', 'unique:zones,code,' . $zone->id],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        $zone->update($validated);

        return redirect()->route('admin.zones.index')
            ->with('success', "Zone \"{$zone->name}\" updated.");
    }

    public function destroy(Zone $zone)
    {
        if ($zone->allEntities()->count()) {
            return back()->with('error', 'Cannot delete a zone that has departments or service stations assigned to it.');
        }

        $zone->delete();

        return redirect()->route('admin.zones.index')
            ->with('success', 'Zone deleted.');
    }
}
