<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemSetting;

class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $timeoutMinutes = (int) SystemSetting::get('session_timeout_minutes', 60);

            // Sync the cookie lifetime so the browser cookie expiry matches the admin setting.
            // StartSession stamps the cookie on the response after this middleware runs,
            // so updating the config here is picked up before the cookie is written.
            config(['session.lifetime' => $timeoutMinutes]);

            $lastActivity = session('last_activity_at');

            if ($lastActivity && now()->diffInMinutes($lastActivity) >= $timeoutMinutes) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your session expired due to inactivity. Please log in again.']);
            }

            session(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
