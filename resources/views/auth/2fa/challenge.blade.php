<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — GOIL Budget</title>
    @vite(['resources/sass/app.scss','resources/js/app.js'])
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, sans-serif;
        }
        .challenge-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 24px 64px rgba(0,0,0,.3);
        }
        .shield {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #1B2A4A, #E65C00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin: 0 auto 20px;
        }
        .code-input {
            font-size: 28px;
            letter-spacing: 12px;
            text-align: center;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            padding: 16px;
            width: 100%;
            outline: none;
            font-family: monospace;
            transition: border-color .2s;
        }
        .code-input:focus { border-color: #E65C00; }
        .btn-verify {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1B2A4A, #E65C00);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="challenge-card">
    <div class="shield">🛡</div>
    <h5 class="text-center fw-bold mb-1" style="color:#1B2A4A">
        Two-Factor Authentication
    </h5>
    <p class="text-center text-muted small mb-4">
        Enter the 6-digit code from your authenticator app
    </p>

    @if($errors->any())
    <div class="alert alert-danger small mb-3">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <input type="text"
               name="code"
               class="code-input"
               placeholder="_ _ _ _ _ _"
               maxlength="6"
               inputmode="numeric"
               autocomplete="one-time-code"
               autofocus>

        <button type="submit" class="btn-verify">Verify</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault();document.getElementById('logoutForm').submit()"
           style="font-size:12px;color:#94A3B8">
            ← Back to login
        </a>
        <form id="logoutForm" method="POST" action="{{ route('logout') }}">@csrf</form>
    </div>
</div>
</body>
</html>
