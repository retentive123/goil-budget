<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign In — {{ \App\Models\SystemSetting::get('app_name', 'GOIL Budget Tool') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            font-family: 'Inter', system-ui, sans-serif;
            background: #0F0F0F;
            overflow: hidden;
        }

        /* ── Left panel ── */
        .left-panel {
            width: 55%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            overflow: hidden;
            background: linear-gradient(135deg, #1a0a00 0%, #2d1200 40%, #E65C00 100%);
        }

        /* Animated background circles */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            opacity: .12;
            animation: pulse 6s ease-in-out infinite;
        }

        .bg-circle-1 {
            width: 600px; height: 600px;
            background: #E65C00;
            top: -200px; left: -150px;
            animation-delay: 0s;
        }

        .bg-circle-2 {
            width: 400px; height: 400px;
            background: #FF8C42;
            bottom: -100px; right: -100px;
            animation-delay: 2s;
        }

        .bg-circle-3 {
            width: 250px; height: 250px;
            background: #FFB347;
            top: 50%; left: 55%;
            animation-delay: 4s;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: .12; }
            50%       { transform: scale(1.08); opacity: .18; }
        }

        /* Grid texture overlay */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .left-content {
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 80px;
        }

        .brand-icon {
            width: 48px; height: 48px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            backdrop-filter: blur(8px);
        }

        .brand-text-main {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.3px;
        }

        .brand-text-sub {
            font-size: 11px;
            color: rgba(255,255,255,.5);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 1px;
        }

        .hero-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero-eyebrow {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: #FFB347;
            margin-bottom: 16px;
        }

        .hero-headline {
            font-size: 42px;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -.8px;
            margin-bottom: 20px;
        }

        .hero-headline span {
            color: #FFB347;
        }

        .hero-desc {
            font-size: 15px;
            color: rgba(255,255,255,.6);
            line-height: 1.7;
            max-width: 380px;
            margin-bottom: 48px;
        }

        /* Feature pills */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 100px;
            padding: 7px 14px;
            font-size: 12px;
            color: rgba(255,255,255,.8);
            backdrop-filter: blur(8px);
        }

        .pill-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #FFB347;
            flex-shrink: 0;
        }

        /* Stats strip */
        .stats-strip {
            display: flex;
            gap: 32px;
            position: relative;
            z-index: 2;
        }

        .stat-item { }

        .stat-number {
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }

        .stat-label {
            font-size: 11px;
            color: rgba(255,255,255,.45);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,.1);
            align-self: stretch;
        }

        /* ── Right panel ── */
        .right-panel {
            width: 45%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #E65C00, #FF8C42, #FFB347);
        }

        .login-box {
            width: 100%;
            max-width: 400px;
        }

        .login-greeting {
            font-size: 28px;
            font-weight: 800;
            color: #0F0F0F;
            letter-spacing: -.5px;
            margin-bottom: 6px;
        }

        .login-subtext {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 36px;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 16px;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            padding: 12px 14px 12px 42px;
            font-size: 14px;
            color: #0F0F0F;
            background: #F8FAFC;
            outline: none;
            transition: all .2s;
            font-family: inherit;
        }

        .form-input:focus {
            border-color: #E65C00;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(230,92,0,.08);
        }

        .form-input.is-invalid {
            border-color: #F43F5E;
            background: #FFF5F5;
        }

        .form-input::placeholder { color: #CBD5E1; }

        .invalid-msg {
            font-size: 12px;
            color: #F43F5E;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94A3B8;
            font-size: 16px;
            padding: 0;
            line-height: 1;
        }

        .pw-toggle:hover { color: #E65C00; }

        /* Remember row */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-check input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #E65C00;
            cursor: pointer;
        }

        .remember-check span {
            font-size: 13px;
            color: #64748B;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #E65C00, #FF8C42);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: .2px;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.15), transparent);
            opacity: 0;
            transition: opacity .2s;
        }

        .btn-submit:hover::after { opacity: 1; }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(230,92,0,.35);
        }
        .btn-submit:active { transform: translateY(0); }

        /* Alert */
        .alert-msg {
            background: #FFF5F5;
            border: 1px solid #FECDD3;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #991B1B;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-success {
            background: #F0FDF4;
            border-color: #BBF7D0;
            color: #065F46;
        }

        /* Footer */
        .login-footer {
            position: absolute;
            bottom: 28px;
            left: 0; right: 0;
            text-align: center;
            font-size: 12px;
            color: #CBD5E1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; }
        }

        /* Loading state */
        .btn-submit.loading {
            pointer-events: none;
            opacity: .8;
        }

        .spinner {
            display: inline-block;
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Decorative number cards */
        .deco-cards {
            display: flex;
            gap: 12px;
            margin-bottom: 40px;
        }

        .deco-card {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px;
            padding: 16px 20px;
            backdrop-filter: blur(8px);
            flex: 1;
        }

        .deco-card-value {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
        }

        .deco-card-label {
            font-size: 11px;
            color: rgba(255,255,255,.45);
            margin-top: 3px;
        }

        .deco-card-change {
            font-size: 11px;
            color: #4ADE80;
            margin-top: 6px;
        }
    </style>
</head>
<body>

{{-- ══════════════════════════════════
     LEFT PANEL
     ══════════════════════════════════ --}}
<div class="left-panel">
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>
    <div class="bg-circle bg-circle-3"></div>
    <div class="grid-overlay"></div>

    {{-- Brand --}}
    <div class="left-content">
        <div class="brand-logo">
            <div class="brand-icon">⛽</div>
            <div>
                <div class="brand-text-main">
                    {{ \App\Models\SystemSetting::get('app_name', 'GOIL Budget') }}
                </div>
                <div class="brand-text-sub">
                    {{ \App\Models\SystemSetting::get('company_name', 'Ghana Oil Company Limited') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Hero --}}
    <div class="left-content hero-text">
        <div class="hero-eyebrow">Financial Management System</div>
        <h1 class="hero-headline">
            Budget.<br>
            Track. <span>Approve.</span>
        </h1>
        <p class="hero-desc">
            A unified platform for planning, approving, and monitoring
            GOIL's annual budget across all departments — from submission
            to board approval.
        </p>

        {{-- Decorative stat cards --}}
        <!--<div class="deco-cards">
            <div class="deco-card">
                <div class="deco-card-value">14</div>
                <div class="deco-card-label">Departments</div>
                <div class="deco-card-change">↑ All active</div>
            </div>
            <div class="deco-card">
                <div class="deco-card-value">5</div>
                <div class="deco-card-label">Approval Stages</div>
                <div class="deco-card-change">Board to Dept</div>
            </div>
            <div class="deco-card">
                <div class="deco-card-value">4</div>
                <div class="deco-card-label">Max Revisions</div>
                <div class="deco-card-change">Per period</div>
            </div>
        </div>-->

        {{-- Feature pills --}}
        <div class="feature-pills">
            <div class="pill">
                <div class="pill-dot"></div>
                Real-time approvals
            </div>
            <div class="pill">
                <div class="pill-dot"></div>
                Variance analysis
            </div>
            <div class="pill">
                <div class="pill-dot"></div>
                Virement tracking
            </div>
            <div class="pill">
                <div class="pill-dot"></div>
                Audit trail
            </div>
            <div class="pill">
                <div class="pill-dot"></div>
                Monthly actuals
            </div>
        </div>
    </div>

    {{-- Stats strip --}}
    <!--<div class="left-content">
        <div class="stats-strip">
            <div class="stat-item">
                <div class="stat-number">GHS</div>
                <div class="stat-label">Currency</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-number">10%</div>
                <div class="stat-label">Virement cap</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">Audit logged</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-number">SSL</div>
                <div class="stat-label">Encrypted</div>
            </div>
        </div>
    </div>-->
</div>

{{-- ══════════════════════════════════
     RIGHT PANEL — Login form
     ══════════════════════════════════ --}}
<div class="right-panel">
    <div class="login-box">

        <div class="login-greeting">Welcome back 👋</div>
        <div class="login-subtext">
            Sign in to your account to continue
        </div>

        {{-- Status messages --}}
        @if(session('status'))
        <div class="alert-msg alert-success">
            <span>✓</span>
            <span>{{ session('status') }}</span>
        </div>
        @endif

        @if($errors->has('email') && !$errors->has('password'))
        <div class="alert-msg">
            <span>⚠</span>
            <span>{{ $errors->first('email') }}</span>
        </div>
        @endif

        {{-- Login form --}}
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            {{-- Email --}}
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-wrap">
                    <span class="input-icon">✉</span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        placeholder="you@goil.com"
                        autofocus
                        autocomplete="email"
                    >
                </div>
                @error('email')
                <div class="invalid-msg">
                    <span>⚠</span> {{ $message }}
                </div>
                @enderror
            </div>

            {{-- Password --}}
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                    <button type="button" class="pw-toggle" onclick="togglePassword()" id="pwToggle">
                        👁
                    </button>
                </div>
                @error('password')
                <div class="invalid-msg">
                    <span>⚠</span> {{ $message }}
                </div>
                @enderror
            </div>

            {{-- Remember me --}}
            <div class="remember-row">
                <label class="remember-check">
                    <input type="checkbox" name="remember"
                           {{ old('remember') ? 'checked' : '' }}>
                    <span>Keep me signed in</span>
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-submit" id="submitBtn">
                Sign In
            </button>

        </form>

        {{-- Contact note --}}
        <div style="text-align:center;margin-top:28px;font-size:12px;color:#94A3B8">
            Having trouble signing in?
            <span style="color:#E65C00;font-weight:600">
                Contact your system administrator
            </span>
        </div>

    </div>

    {{-- Footer --}}
    <div class="login-footer">
        © {{ date('Y') }}
        {{ \App\Models\SystemSetting::get('company_name', 'Ghana Oil Company Limited') }}
     | All rights reserved
    </div>
</div>

<script>
// Password visibility toggle
let pwVisible = false;
function togglePassword() {
    const input  = document.getElementById('password');
    const toggle = document.getElementById('pwToggle');
    pwVisible    = !pwVisible;
    input.type   = pwVisible ? 'text' : 'password';
    toggle.textContent = pwVisible ? '🙈' : '👁';
}

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function () {
    const btn  = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.innerHTML = '<span class="spinner"></span> Signing in…';
});
</script>

</body>
</html>
