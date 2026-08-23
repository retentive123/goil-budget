<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired — GOIL Budget</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F4F6FA;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #1E293B;
        }
        .card {
            width: 100%;
            max-width: 480px;
            margin: 24px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,.12);
        }
        .card-header {
            background: #92400E;
            color: #fff;
            text-align: center;
            padding: 32px 24px;
        }
        .card-header .icon { font-size: 56px; margin-bottom: 8px; }
        .card-header h3 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .card-header p  { font-size: 13px; opacity: .75; }
        .card-body {
            padding: 28px 28px 24px;
            text-align: center;
        }
        .error-code {
            font-size: 80px;
            font-weight: 800;
            color: #92400E;
            opacity: .1;
            line-height: 1;
        }
        .main-msg {
            font-size: 16px;
            font-weight: 600;
            color: #1B2A4A;
            margin: -12px 0 6px;
        }
        .sub-msg { font-size: 13px; color: #64748B; }
        .btn-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .btn-secondary { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
        .btn-primary   { background: #E65C00; color: #fff; }
        .tips {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #E2E8F0;
            text-align: left;
        }
        .tips-title {
            font-size: 12px;
            font-weight: 600;
            color: #1B2A4A;
            margin-bottom: 8px;
        }
        .tips ul { list-style: none; }
        .tips li {
            font-size: 12px;
            color: #64748B;
            margin-bottom: 4px;
        }
        .tips li::before {
            content: '✓';
            color: #10B981;
            margin-right: 6px;
            font-size: 11px;
        }
        .footer {
            text-align: center;
            margin-top: 16px;
            font-size: 11px;
            color: #94A3B8;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="icon">⏱</div>
        <h3>Session Expired</h3>
        <p>Your session has timed out for security</p>
    </div>

    <div class="card-body">
        <div class="error-code">419</div>

        <div class="main-msg">Your page session has expired</div>

        <p class="sub-msg">
            This happens automatically after a period of inactivity to keep your account secure.
            Please go back and log in again.
        </p>

        <div class="btn-row">
            <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
            <a href="{{ route('login') }}" class="btn btn-primary">🔑 Log In Again</a>
        </div>

        <div class="tips">
            <div class="tips-title">💡 Why does this happen?</div>
            <ul>
                <li>Pages expire after {{ config('session.lifetime', 120) }} minutes of inactivity</li>
                <li>This protects your account from unauthorised access</li>
                <li>Simply log in again to continue where you left off</li>
            </ul>
        </div>
    </div>
</div>

<div class="footer">🛡 GOIL Budget Management System</div>

</body>
</html>
