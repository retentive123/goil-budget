<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Redirect users who must change their password before they can do anything else.
 * Set `must_change_password = true` on a user record to trigger this.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip these routes to avoid redirect loops
        if ($request->routeIs(
            'password.change', 'password.update',
            'logout', 'login', '2fa.*', 'password.*'
        )) {
            return $next($request);
        }

        if (Auth::user()->must_change_password) {
            return redirect()->route('password.change')
                ->with('force_password_change', true);
        }

        return $next($request);
    }
}
