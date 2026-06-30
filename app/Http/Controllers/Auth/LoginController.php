<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Cache\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(protected SsoService $ssoService) {}

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $maxAttempts = (int) \App\Models\SystemSetting::get('max_login_attempts', 5);
        $lockoutMins = (int) \App\Models\SystemSetting::get('login_lockout_minutes', 15);
        $throttleKey = 'login.' . $request->ip() . '.' . $request->email;
        $limiter     = app(RateLimiter::class);

        if ($limiter->tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = $limiter->availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in ' . ceil($seconds/60) . ' min(s).',
            ]);
        }

        // ── Try SSO first ──
        $user = null;
        //  Get boolean values directly
        $ssoEnabled = \App\Models\SystemSetting::get('sso_enabled', false);
        $ssoAttempted = false;
        $ssoError = null;

        // Check if SSO is enabled (already a boolean)
        if ($ssoEnabled === true) {
            $ssoAttempted = true;
            try {
                $user = $this->ssoService->attempt($credentials['email'], $credentials['password']);

                if ($user === null) {
                    \Illuminate\Support\Facades\Log::info('SSO authentication failed for: ' . $credentials['email']);
                }
            } catch (\Exception $e) {
                $ssoError = $e->getMessage();
                \Illuminate\Support\Facades\Log::warning('SSO exception: ' . $e->getMessage());

                // ✅ Get fallback value (already a boolean)
                $fallbackEnabled = \App\Models\SystemSetting::get('sso_fallback_to_local', true);

                if ($fallbackEnabled === false) {
                    $limiter->hit($throttleKey, $lockoutMins * 60);
                    throw ValidationException::withMessages([
                        'email' => 'Network authentication failed. Contact Admin support.',
                    ]);
                }
                \Illuminate\Support\Facades\Log::info('SSO failed, falling back to local auth for: ' . $credentials['email']);
            }
        }

        // ── Local auth fallback ──
        if (!$user) {
            $localUser = \App\Models\User::where('email', $credentials['email'])->first();

            if ($localUser && !$localUser->is_active) {
                $limiter->hit($throttleKey, $lockoutMins * 60);
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated.',
                ]);
            }

            if (!Auth::attempt($credentials, $request->boolean('remember'))) {
                $limiter->hit($throttleKey, $lockoutMins * 60);
                AuditLogger::loginFailed($credentials['email']);

                if ($ssoAttempted) {
                    if ($ssoError) {
                        throw ValidationException::withMessages([
                            'email' => 'SSO connection failed and local credentials do not match. Please try again.',
                        ]);
                    }
                    throw ValidationException::withMessages([
                        'email' => 'SSO authentication failed and local credentials do not match our records.',
                    ]);
                }

                throw ValidationException::withMessages([
                    'email' => 'These credentials do not match our records.',
                ]);
            }

            $user = Auth::user();

            if ($ssoAttempted) {
                \Illuminate\Support\Facades\Log::info('SSO failed but local auth succeeded for: ' . $credentials['email']);
            }
        } else {
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated.',
                ]);
            }
            Auth::login($user, $request->boolean('remember'));
        }

        if (!Auth::check()) {
            throw ValidationException::withMessages([
                'email' => 'Authentication failed. Please try again.',
            ]);
        }

        $limiter->clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        AuditLogger::login($user);

        $this->enforceSingleSession($request, $user);

        if ($this->requires2FA($user)) {
            session(['2fa_user_id' => $user->id, '2fa_remember' => $request->boolean('remember')]);
            Auth::logout();
            return redirect()->route('2fa.show');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLogger::logout($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    private function enforceSingleSession(Request $request, $user): void
    {
        // ✅ Get boolean value directly
        $singleSession = \App\Models\SystemSetting::get('single_session_only', false);

        if ($singleSession !== true) return;

        $previousSession = \Illuminate\Support\Facades\Cache::get("user_session_{$user->id}");
        if ($previousSession && $previousSession !== $request->session()->getId()) {
            \Illuminate\Support\Facades\Cache::forget("user_session_{$user->id}");
        }

        \Illuminate\Support\Facades\Cache::put(
            "user_session_{$user->id}",
            $request->session()->getId(),
            now()->addHours(24)
        );
    }

    private function requires2FA($user): bool
    {
        // ✅ Get boolean value directly
        $global2FA = \App\Models\SystemSetting::get('two_factor_enabled', false);

        return $global2FA === true && !session()->has('2fa_verified_' . $user->id);
    }
}
