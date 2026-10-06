<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WidgetSetting;
use Illuminate\Http\Request;

class WidgetSettingController extends Controller
{
    public function index()
    {
        $widgets = WidgetSetting::$widgets;
        $roles   = WidgetSetting::$roles;

        // Build a 2D grid: role → widget_key → is_visible
        $grid = [];
        foreach (array_keys($roles) as $role) {
            $grid[$role] = WidgetSetting::visibilityFor($role);
        }

        return view('admin.widget-settings.index', compact('widgets', 'roles', 'grid'));
    }

    public function update(Request $request)
    {
        // Input: visible[role][widget_key] = '1'
        $visible = $request->input('visible', []);

        foreach (array_keys(WidgetSetting::$roles) as $role) {
            foreach (array_keys(WidgetSetting::$widgets) as $key) {
                $isVisible = isset($visible[$role][$key]);
                WidgetSetting::updateOrCreate(
                    ['role' => $role, 'widget_key' => $key],
                    ['is_visible' => $isVisible]
                );
            }
        }

        return back()->with('success', 'Dashboard widget visibility saved.');
    }
}
