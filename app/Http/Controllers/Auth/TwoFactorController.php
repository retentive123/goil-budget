<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;
use App\Models\User;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ── Show 2FA setup page ───────────────────────────
    public function setup(Request $request)
    {
        $user = Auth::user();

        if (!$user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->update(['two_factor_secret' => $secret]);
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            \App\Models\SystemSetting::get('company_name', 'GOIL'),
            $user->email,
            $user->two_factor_secret
        );

        return view('auth.2fa.setup', compact('qrCodeUrl'));
    }

    // ── Enable 2FA after scanning QR ────────────────
    public function enable(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user  = Auth::user();
        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        $user->update([
            'two_factor_enabled'      => true,
            'two_factor_confirmed_at' => now(),
        ]);

        \App\Services\AuditLogger::record(
            '2fa_enabled', 'auth', 'updated',
            ['subject_label' => $user->name, 'severity' => 'warning']
        );

        return redirect()->route('dashboard')
            ->with('success', 'Two-factor authentication enabled.');
    }

    // ── Disable 2FA ───────────────────────────────────
    public function disable(Request $request)
    {
        $request->validate(['password' => ['required']]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user = Auth::user();

        $user->update([
            'two_factor_secret'       => null,
            'two_factor_enabled'      => false,
            'two_factor_confirmed_at' => null,
        ]);

        \App\Services\AuditLogger::record(
            '2fa_disabled', 'auth', 'updated',
            ['subject_label' => $user->name, 'severity' => 'critical']
        );

        return redirect()->route('dashboard')
            ->with('success', '2FA has been disabled.');
    }

    // ── Show 2FA challenge on login ───────────────────
    public function show()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.2fa.challenge');
    }

    // ── Verify 2FA code on login ──────────────────────
    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        $code       = str_replace(' ', '', $request->code);
        $tsKey      = "totp_ts_{$user->id}";
        $lastTs     = Cache::get($tsKey);

        // verifyKeyNewer rejects codes at or before the last-used timestamp,
        // preventing replay within the ~30-90 second TOTP window.
        $newTs = $this->google2fa->verifyKeyNewer(
            $user->two_factor_secret,
            $code,
            $lastTs
        );

        if ($newTs === false) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        // Record the timestamp so this code cannot be replayed
        Cache::put($tsKey, $newTs, now()->addMinutes(5));

        // ✅ Clear 2FA session data
        session()->forget(['2fa_user_id', '2fa_remember']);

        // ✅ Mark 2FA as verified for this session
        session(['2fa_verified_' . $user->id => true]);

        // ✅ Login the user
        Auth::login($user, session('2fa_remember', false));
        $request->session()->regenerate();

        // ✅ Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // ✅ Enforce single session AFTER successful 2FA verification
        $this->enforceSingleSession($request, $user);

        return redirect()->intended(route('dashboard'));
    }

    // ── Resend 2FA code (optional) ───────────────────
    public function resend(Request $request)
    {
        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        // You can implement resend logic here if needed
        // For TOTP-based 2FA (Google Authenticator), there's no "resend"
        // since the code changes every 30 seconds automatically.

        return back()->with('info', 'The code changes every 30 seconds. Please check your authenticator app.');
    }

    /**
     * Enforce single session for the user
     */
    private function enforceSingleSession(Request $request, $user): void
    {
        $singleSession = \App\Models\SystemSetting::get('single_session_only', false);

        if ($singleSession !== true) {
            return;
        }

        $cacheKey = "user_session_{$user->id}";
        $currentSessionId = $request->session()->getId();
        $previousSession = Cache::get($cacheKey);

        // ✅ If there's an existing session for this user, invalidate it
        if ($previousSession && $previousSession !== $currentSessionId) {
            // The old session will be invalidated by the middleware
            Cache::forget($cacheKey);
        }

        // ✅ Store the current session ID
        Cache::put(
            $cacheKey,
            $currentSessionId,
            now()->addHours(24)
        );
    }
}
