<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

        // ✅ If this is a different session, log out the user
        if ($stored && $stored !== $current) {
            // Clear the stored session
            Cache::forget($cacheKey);

            // Logout the user from this session
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account was signed in on another device. ' .
                           'You have been logged out of this session.',
            ]);
        }

        // ✅ Refresh the session record
        Cache::put($cacheKey, $current, now()->addHours(24));

        return $next($request);
    }
}
