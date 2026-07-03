@extends('layouts.app')
@section('title', 'Set Up Two-Factor Authentication')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="chart-card">
    <h5 class="fw-bold mb-1">Set Up Two-Factor Authentication</h5>
    <p class="text-muted small mb-4">
        Scan the QR code below with an authenticator app
        (Google Authenticator, Authy, Microsoft Authenticator).
        Then enter the 6-digit code to confirm.
    </p>

    {{-- QR Code --}}
    <div class="text-center mb-4">
        <div style="background:#fff;border:1px solid var(--border);
                    border-radius:12px;padding:20px;display:inline-block">
            {!! QrCode::size(200)->generate($qrCodeUrl) !!}
        </div>
        <div style="font-size:12px;color:var(--slate);margin-top:8px">
            Scan this QR code with your authenticator app
        </div>
    </div>

    {{-- Confirm code --}}
    <form method="POST" action="{{ route('2fa.enable') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">
                Enter the 6-digit code from your app
            </label>
            <input type="text" name="code"
                   class="form-control @error('code') is-invalid @enderror"
                   placeholder="000000"
                   maxlength="6"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   autofocus>
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm"
                    style="background:var(--navy);color:#fff;border-radius:8px;padding:8px 20px">
                Confirm & Enable 2FA
            </button>
            <a href="{{ route('dashboard') }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:8px 20px">
                Cancel
            </a>
        </div>
    </form>
</div>

{{-- Disable 2FA — only shown to users with explicit permission --}}
@if(Auth::user()->two_factor_enabled && Auth::user()->can('disable two factor'))
<div class="chart-card mt-4" style="border-left:4px solid #F43F5E">
    <div style="font-size:13px;font-weight:600;color:#991B1B;margin-bottom:8px">
        Disable Two-Factor Authentication
    </div>
    <p class="small text-muted mb-3">
        Enter your current password to disable 2FA.
        This reduces the security of your account.
    </p>
    <form method="POST" action="{{ route('2fa.disable') }}">
        @csrf
        <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <input type="password" name="password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror"
                       placeholder="Your current password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-sm btn-danger" style="border-radius:8px">
                Disable 2FA
            </button>
        </div>
    </form>
</div>
@elseif(Auth::user()->two_factor_enabled)
<div class="chart-card mt-4" style="border-left:4px solid #94A3B8">
    <div class="d-flex gap-2 align-items-center">
        <i class="fas fa-lock" style="color:#94A3B8"></i>
        <span style="font-size:13px;color:#64748B">
            Two-factor authentication is active. Contact your administrator if you need it removed.
        </span>
    </div>
</div>
@endif

</div>
</div>
@endsection
