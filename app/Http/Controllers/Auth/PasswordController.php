<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Services\AuditLogger;

class PasswordController extends Controller
{
    public function showChangeForm()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors([
                'current_password' => 'The current password you entered is incorrect.',
            ]);
        }

        if (Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors([
                'password' => 'Your new password cannot be the same as your current password.',
            ]);
        }

        Auth::user()->update([
            'password' => Hash::make($request->password),
            'password_changed_at'=> now(),
        ]);

        AuditLogger::passwordChanged(Auth::user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Password changed successfully. Please log in with your new password.');
    }
}
