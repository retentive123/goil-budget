<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EnforceSingleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $enabled = \App\Models\SystemSetting::get('single_session_only', false);
        if ($enabled !== true) {
            return $next($request);
        }

        $user      = Auth::user();
        $cacheKey  = "user_session_{$user->id}";
        $stored    = Cache::get($cacheKey);
        $current   = $request->session()->getId();

        // A different session exists — destroy the OLD one, keep the current one
        if ($stored && $stored !== $current) {
            // Delete the old session from the database session store
            DB::table('sessions')->where('id', $stored)->delete();
        }

        // ✅ Refresh the session record
        Cache::put($cacheKey, $current, now()->addHours(24));

        return $next($request);
    }
}
