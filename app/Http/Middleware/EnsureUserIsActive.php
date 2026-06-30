<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
{
    if (Auth::check()) {
        $user = Auth::user();

        // Deactivated account
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // Password expiry check
        $expiryDays = (int) \App\Models\SystemSetting::get('force_password_change_days', 0);

        if ($expiryDays > 0 && $user->password_changed_at) {
            $daysSinceChange = $user->password_changed_at->diffInDays(now());

            if ($daysSinceChange >= $expiryDays) {
                // Allow access to password change page only
                if (!$request->routeIs('password.change') &&
                    !$request->routeIs('password.update') &&
                    !$request->routeIs('logout')) {

                    return redirect()->route('password.change')
                        ->with('warning', "Your password has expired after {$expiryDays} days. Please change it to continue.");
                }
            }
        }
    }

    return $next($request);
}
}
