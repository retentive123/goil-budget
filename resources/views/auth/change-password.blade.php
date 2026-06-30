@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        {{-- Change Password Card --}}
        <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            {{-- Card Header with Icon --}}
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 48px; height: 48px; background: #E65C00; color: #fff; font-size: 20px;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: var(--navy);">Change Password</h5>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-info-circle"></i>
                            Update your password to keep your account secure
                        </p>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="card-body p-4">

                {{-- Password Strength Indicator (Optional) --}}
                <div class="alert alert-info small py-2 px-3 mb-4" style="border-radius: 10px; background: #F0F7FF; border-color: #B8D4F0;">
                    <i class="bi bi-key"></i>
                    Password must be at least 8 characters long and include a mix of letters, numbers, and symbols.
                </div>

                <form method="POST" action="{{ route('password.update') }}" id="passwordForm">
                    @csrf

                    {{-- Current Password --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase tracking-wide" style="color: var(--slate); letter-spacing: 0.5px;">
                            <i class="bi bi-lock"></i> Current Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;">
                                <i class="bi bi-key text-muted"></i>
                            </span>
                            <input type="password" name="current_password"
                                   class="form-control border-start-0 @error('current_password') is-invalid @enderror"
                                   id="current_password"
                                   placeholder="Enter your current password"
                                   style="border-radius: 0 10px 10px 0; padding: 10px 15px;">
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    style="border-radius: 0 10px 10px 0; border-left: none;"
                                    data-target="current_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback d-block mt-1 small">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- New Password --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase tracking-wide" style="color: var(--slate); letter-spacing: 0.5px;">
                            <i class="bi bi-shield"></i> New Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;">
                                <i class="bi bi-plus-circle text-muted"></i>
                            </span>
                            <input type="password" name="password"
                                   class="form-control border-start-0 @error('password') is-invalid @enderror"
                                   id="new_password"
                                   placeholder="Enter new password"
                                   style="border-radius: 0 10px 10px 0; padding: 10px 15px;">
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    style="border-radius: 0 10px 10px 0; border-left: none;"
                                    data-target="new_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block mt-1 small">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror

                        {{-- Password Strength Bar --}}
                        <div class="mt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="progress flex-grow-1" style="height: 4px; border-radius: 4px;">
                                    <div id="passwordStrengthBar" class="progress-bar" role="progressbar"
                                         style="width: 0%; background: #dc3545; border-radius: 4px; transition: all 0.3s ease;"></div>
                                </div>
                                <span id="passwordStrengthText" class="ms-2 small text-muted" style="min-width: 60px;">Weak</span>
                            </div>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase tracking-wide" style="color: var(--slate); letter-spacing: 0.5px;">
                            <i class="bi bi-check-circle"></i> Confirm New Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;">
                                <i class="bi bi-check2-circle text-muted"></i>
                            </span>
                            <input type="password" name="password_confirmation"
                                   class="form-control border-start-0"
                                   id="confirm_password"
                                   placeholder="Confirm new password"
                                   style="border-radius: 0 10px 10px 0; padding: 10px 15px;">
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    style="border-radius: 0 10px 10px 0; border-left: none;"
                                    data-target="confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatchMessage" class="mt-1 small d-none">
                            <i class="bi bi-check-circle text-success"></i> Passwords match
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 mt-4 pt-2 border-top">
                        <button type="submit" class="btn px-4"
                                style="background: #E65C00; color: #fff; border-radius: 10px; padding: 10px 30px; font-weight: 600; transition: all 0.3s ease;">
                            <i class="bi bi-save"></i> Update Password
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4"
                           style="border-radius: 10px; padding: 10px 30px;">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- 2FA Section --}}
        <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px; border-left: 4px solid {{ Auth::user()->two_factor_enabled ? '#10B981' : '#F59E0B' }};">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center rounded-circle"
                             style="width: 44px; height: 44px; background: {{ Auth::user()->two_factor_enabled ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)' }};">
                            <span style="font-size: 20px;">
                                @if(Auth::user()->two_factor_enabled) 🔐 @else 🔓 @endif
                            </span>
                        </div>
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: var(--navy);">
                                Two-Factor Authentication
                                <span class="badge ms-2" style="background: {{ Auth::user()->two_factor_enabled ? '#10B981' : '#F59E0B' }}; color: #fff; font-size: 10px; padding: 3px 10px;">
                                    {{ Auth::user()->two_factor_enabled ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </div>
                            <div style="font-size: 13px; color: var(--slate); margin-top: 2px;">
                                @if(Auth::user()->two_factor_enabled)
                                    <i class="bi bi-check-circle text-success"></i>
                                    Enabled {{ Auth::user()->two_factor_confirmed_at?->diffForHumans() }}
                                @else
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                    Not enabled - <strong>Recommended</strong>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('2fa.setup') }}"
                       class="btn btn-sm px-4 py-2"
                       style="background: {{ Auth::user()->two_factor_enabled ? '#F1F5F9' : '#E65C00' }};
                              color: {{ Auth::user()->two_factor_enabled ? 'var(--slate)' : '#fff' }};
                              border-radius: 10px;
                              border: {{ Auth::user()->two_factor_enabled ? '1px solid var(--border)' : 'none' }};
                              font-weight: 600;
                              transition: all 0.3s ease;
                              text-decoration: none;">
                        <i class="bi bi-{{ Auth::user()->two_factor_enabled ? 'gear' : 'shield-plus' }}"></i>
                        {{ Auth::user()->two_factor_enabled ? 'Manage 2FA' : 'Enable 2FA' }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Security Tips --}}
        <!--<div class="mt-3 p-3 bg-light rounded-3" style="border-radius: 12px; border: 1px solid var(--border);">
            <div class="d-flex gap-2 align-items-start">
                <i class="bi bi-shield-check text-success mt-1" style="font-size: 18px;"></i>
                <div>
                    <div style="font-size: 12px; font-weight: 600; color: var(--navy);">Security Tips</div>
                    <ul class="list-unstyled small text-muted mb-0" style="font-size: 12px;">
                        <li><i class="bi bi-dot"></i> Use a unique password that you don't use elsewhere</li>
                        <li><i class="bi bi-dot"></i> Enable 2FA for an extra layer of security</li>
                        <li><i class="bi bi-dot"></i> Avoid using common words or personal information</li>
                    </ul>
                </div>
            </div>
        </div>-->

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Toggle Password Visibility ──
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });

    // ── Password Strength Checker ──
    const newPassword = document.getElementById('new_password');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');

    function checkPasswordStrength(password) {
        let score = 0;

        if (password.length >= 8) score++;
        if (password.match(/[a-z]/)) score++;
        if (password.match(/[A-Z]/)) score++;
        if (password.match(/[0-9]/)) score++;
        if (password.match(/[^a-zA-Z0-9]/)) score++;

        const strengthMap = {
            0: { text: 'Very Weak', color: '#dc3545', width: '10%' },
            1: { text: 'Weak', color: '#dc3545', width: '25%' },
            2: { text: 'Fair', color: '#ffc107', width: '45%' },
            3: { text: 'Good', color: '#17a2b8', width: '65%' },
            4: { text: 'Strong', color: '#28a745', width: '85%' },
            5: { text: 'Very Strong', color: '#28a745', width: '100%' },
        };

        return strengthMap[score] || strengthMap[0];
    }

    newPassword.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        strengthBar.style.width = strength.width;
        strengthBar.style.background = strength.color;
        strengthText.textContent = strength.text;
        strengthText.style.color = strength.color;
    });

    // ── Password Match Checker ──
    const confirmPassword = document.getElementById('confirm_password');
    const matchMessage = document.getElementById('passwordMatchMessage');

    function checkPasswordMatch() {
        const password = newPassword.value;
        const confirm = confirmPassword.value;

        if (confirm.length === 0) {
            matchMessage.classList.add('d-none');
            return;
        }

        if (password === confirm) {
            matchMessage.classList.remove('d-none');
            matchMessage.innerHTML = '<i class="bi bi-check-circle text-success"></i> Passwords match';
            matchMessage.style.color = '#28a745';
        } else {
            matchMessage.classList.remove('d-none');
            matchMessage.innerHTML = '<i class="bi bi-exclamation-circle text-danger"></i> Passwords do not match';
            matchMessage.style.color = '#dc3545';
        }
    }

    confirmPassword.addEventListener('input', checkPasswordMatch);
    newPassword.addEventListener('input', function() {
        if (confirmPassword.value.length > 0) {
            checkPasswordMatch();
        }
    });

    // ── Form Submit Validation ──
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const password = newPassword.value;
        const confirm = confirmPassword.value;

        if (password !== confirm) {
            e.preventDefault();
            matchMessage.classList.remove('d-none');
            matchMessage.innerHTML = '<i class="bi bi-exclamation-circle text-danger"></i> Passwords do not match. Please fix before submitting.';
            matchMessage.style.color = '#dc3545';
            confirmPassword.focus();
        }
    });
});
</script>

<style>
    .tracking-wide {
        letter-spacing: 0.5px;
    }

    .form-control:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .btn-primary:hover {
        background: #C44D00 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 92, 0, 0.3);
    }

    .btn-outline-secondary:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }

    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08) !important;
    }
</style>
@endpush
@endsection
