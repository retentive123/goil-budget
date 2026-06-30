<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Maintenance — GOIL Budget Tool</title>
    <style>
        body {
            margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
            background: linear-gradient(135deg, #1a0a00, #2d1200, #E65C00);
            font-family: system-ui, sans-serif; color:#fff; text-align:center;
        }
        .box { max-width: 480px; padding: 40px; }
        .icon { font-size: 56px; margin-bottom: 20px; }
        h1 { font-size: 24px; margin-bottom: 12px; }
        p { color: rgba(255,255,255,.7); line-height:1.6; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🛠️</div>
        <h1>We'll be right back</h1>
        <p>
            {{ $exception->getMessage() ?: 'The GOIL Budget Tool is undergoing scheduled maintenance. We expect to be back online shortly. Thank you for your patience.' }}
        </p>
    </div>
</body>
</html>
