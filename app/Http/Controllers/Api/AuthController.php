<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'       => ['required','email'],
            'password'    => ['required'],
            'device_name' => ['nullable','string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuditLogger::loginFailed($request->email);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account deactivated.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);
        AuditLogger::login($user);

        $token = $user->createToken(
            $request->device_name ?? 'api-token',
            ['*']
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'department' => $user->department?->name,
                'roles'      => $user->getRoleNames(),
                'must_change_password' => $this->mustChangePassword($user),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('department','roles');

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'employee_id' => $user->employee_id,
            'department'  => $user->department?->only('id','name','code'),
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'last_login'  => $user->last_login_at?->toISOString(),
        ]);
    }

    // Helper:
private function mustChangePassword(User $user): bool
{
    $days = (int) \App\Models\SystemSetting::get('force_password_change_days', 0);
    if ($days === 0) return false;
    if (!$user->password_changed_at) return true;
    return $user->password_changed_at->diffInDays(now()) >= $days;
}

}
