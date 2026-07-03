<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip login, logout, and 2FA routes to avoid redirect loops
        if ($request->routeIs('login', 'logout', '2fa.*', 'password.*')) {
            return $next($request);
        }

        $user          = Auth::user();
        $globalEnabled = (bool) \App\Models\SystemSetting::get('two_factor_enabled', false);
        $userEnabled   = (bool) $user->two_factor_enabled;
        $needs2FA      = $globalEnabled || $userEnabled;

        if (!$needs2FA) {
            return $next($request);
        }

        // setup complete only when the user confirmed a valid code (two_factor_confirmed_at set)
        $setupComplete = !empty($user->two_factor_secret) && !empty($user->two_factor_confirmed_at);

        if (!$setupComplete) {
            // Redirect to setup — don't log out, they need to stay authenticated to save the secret
            if (!$request->routeIs('2fa.*', 'logout', 'password.*')) {
                return redirect()->route('2fa.setup')
                    ->with('info', 'Two-factor authentication is required for your account. Please complete the setup below.');
            }
            return $next($request);
        }

        // Setup complete but not verified this session — force re-login
        if (!session('2fa_verified_' . $user->id)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Please log in again and verify your identity with your authenticator app.');
        }

        return $next($request);
    }
}
