<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error — GOIL Budget Tool</title>
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
            background: #991B1B;
            color: #fff;
            text-align: center;
            padding: 32px 24px;
        }
        .card-header .icon { font-size: 56px; margin-bottom: 10px; }
        .card-header h3 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .card-header p { font-size: 13px; color: rgba(255,255,255,.7); }
        .card-body { padding: 28px 32px; text-align: center; }
        .error-code {
            font-size: 80px;
            font-weight: 700;
            color: #991B1B;
            opacity: .1;
            line-height: 1;
            margin-top: -16px;
        }
        .error-msg {
            font-size: 15px;
            font-weight: 600;
            color: #1B2A4A;
            margin-top: -16px;
            margin-bottom: 8px;
        }
        .error-sub { font-size: 13px; color: #64748B; line-height: 1.6; margin-bottom: 20px; }
        .btn {
            display: inline-block;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            margin: 4px;
        }
        .btn-back { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
        .btn-home { background: #E65C00; color: #fff; }
        .tips {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #E2E8F0;
            text-align: left;
        }
        .tips-title { font-size: 12px; font-weight: 700; color: #1B2A4A; margin-bottom: 6px; }
        .tips-title i { color: #C9A84C; margin-right: 4px; }
        .tips ul { list-style: none; padding: 0; }
        .tips li { font-size: 12px; color: #64748B; margin-bottom: 3px; }
        .tips li::before { content: '✓'; color: #10B981; margin-right: 6px; font-size: 10px; }
        .footer { text-align: center; margin-top: 16px; font-size: 11px; color: #94A3B8; }
        .footer span { color: #E65C00; }
    </style>
</head>
<body>
    <div>
        <div class="card">
            <div class="card-header">
                <div class="icon">⚠️</div>
                <h3>Something Went Wrong</h3>
                <p>An unexpected error occurred on the server</p>
            </div>
            <div class="card-body">
                <div class="error-code">500</div>
                <div class="error-msg">Internal Server Error</div>
                <p class="error-sub">
                    Our team has been notified. Please try again in a few moments.
                    If the problem persists, contact your system administrator.
                </p>

                <a href="javascript:history.back()" class="btn btn-back">← Go Back</a>
                <a href="/" class="btn btn-home">⌂ Home</a>

                <div class="tips">
                    <div class="tips-title"><i>💡</i> What you can do</div>
                    <ul>
                        <li>Refresh the page and try again</li>
                        <li>Clear your browser cache and cookies</li>
                        <li>Contact your administrator with the time this occurred</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer">
            <span>⚙</span> GOIL Budget Management System
        </div>
    </div>
</body>
</html>
