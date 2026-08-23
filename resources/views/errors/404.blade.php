<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — GOIL Budget</title>
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
            background: #1B2A4A;
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
            color: #1B2A4A;
            opacity: .1;
            line-height: 1;
        }
        .main-msg {
            font-size: 15px;
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
            margin-bottom: 4px;
        }
        .tips li a { color: #E65C00; text-decoration: none; }
        .tips li a:hover { text-decoration: underline; }
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
        <div class="icon">🗺</div>
        <h3>Page Not Found</h3>
        <p>The page you're looking for doesn't exist</p>
    </div>

    <div class="card-body">
        <div class="error-code">404</div>

        <div class="main-msg">
            {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : "We couldn't find the page you were looking for." }}
        </div>

        <p class="sub-msg">
            The link may be broken, the page may have been moved, or you may have mistyped the address.
        </p>

        <div class="btn-row">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">← Go Back</a>
            @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">🏠 Dashboard</a>
            @else
            <a href="{{ route('login') }}" class="btn btn-primary">🔑 Log In</a>
            @endauth
        </div>

        <div class="tips">
            <div class="tips-title">💡 Common pages</div>
            <ul>
                @auth
                <li>→ <a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li>→ <a href="{{ route('reports.index') }}">Reports</a></li>
                @else
                <li>→ <a href="{{ route('login') }}">Login</a></li>
                @endauth
            </ul>
        </div>
    </div>
</div>

<div class="footer">🛡 GOIL Budget Management System</div>

</body>
</html>
