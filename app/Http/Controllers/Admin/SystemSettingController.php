<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::orderBy('group')->orderBy('label')->get()
                                 ->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

public function update(Request $request)
{
    $settings = SystemSetting::all();
    $changed  = [];

    foreach ($settings as $setting) {
        if ($setting->type === 'boolean') {
            // Check both possible field names
            $newValue = $request->has("settings.{$setting->key}")
                ? '1'
                : '0';
        } else {
            $newValue = $request->input("settings.{$setting->key}");
        }

        // Only process if a value was submitted
        if (is_null($newValue)) {
            continue;
        }

        // Track what actually changed
        if ((string) $newValue !== (string) $setting->value) {
            $changed[$setting->key] = [
                'label' => $setting->label,
                'old'   => $setting->value,
                'new'   => $newValue,
            ];
        }

        $setting->update(['value' => $newValue]);
        Cache::forget("setting:{$setting->key}");
    }

    SystemSetting::clearCache();

    if (!empty($changed)) {
        AuditLogger::settingsChanged($changed, auth()->user());
    }

    $count = count($changed);
    $message = $count > 0
        ? "Settings saved. {$count} value(s) updated."
        : 'Settings saved. No values were changed.';

    return back()->with('success', $message);
}

}
