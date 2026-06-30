<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    public function index()
    {
        $isDown = app()->isDownForMaintenance();
        $secret = session('maintenance_secret');

        return view('admin.maintenance.index', compact('isDown', 'secret'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'message' => ['nullable', 'string', 'max:255'],
            'retry'   => ['nullable', 'integer', 'min:10', 'max:600'],
        ]);

        $secret = Str::random(32);

        Artisan::call('down', [
            '--secret'  => $secret,
            '--retry'   => $request->retry ?? 60,
            '--refresh' => 30,
        ]);

        session(['maintenance_secret' => $secret]);

        \App\Services\AuditLogger::record(
            'maintenance_enabled', 'system', 'updated',
            [
                'subject_label' => 'Maintenance Mode',
                'meta'          => ['message' => $request->message],
                'severity'      => 'critical',
            ]
        );

        $bypassUrl = url("/{$secret}");

        return back()->with('success',
            "Maintenance mode enabled. Bypass URL (save this — share only with admins): {$bypassUrl}"
        )->with('bypass_url', $bypassUrl);
    }

    public function disable()
    {
        Artisan::call('up');

        \App\Services\AuditLogger::record(
            'maintenance_disabled', 'system', 'updated',
            ['subject_label' => 'Maintenance Mode', 'severity' => 'critical']
        );

        return back()->with('success', 'Maintenance mode disabled. The system is live again.');
    }
}
