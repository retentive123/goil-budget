<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome to {{ config('app.name') }}</title>
<style>
  body { margin:0; padding:0; background:#F1F5F9; font-family:Arial,Helvetica,sans-serif; color:#1B2A4A; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); }
  .header { background:#E65C00; padding:32px 40px; text-align:center; }
  .header h1 { margin:0; color:#fff; font-size:22px; font-weight:700; }
  .header p  { margin:6px 0 0; color:rgba(255,255,255,.85); font-size:14px; }
  .body   { padding:36px 40px; }
  .greeting { font-size:18px; font-weight:700; margin-bottom:12px; }
  .intro    { font-size:14px; color:#475569; line-height:1.7; margin-bottom:24px; }
  .creds    { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:20px 24px; margin-bottom:24px; }
  .creds table { width:100%; border-collapse:collapse; font-size:14px; }
  .creds td { padding:6px 0; vertical-align:top; }
  .creds td:first-child { color:#94A3B8; width:120px; }
  .creds td:last-child  { font-weight:600; color:#1B2A4A; word-break:break-all; }
  .btn-wrap { text-align:center; margin:28px 0 24px; }
  .btn { display:inline-block; background:#E65C00; color:#fff; text-decoration:none;
         padding:13px 36px; border-radius:8px; font-size:15px; font-weight:700; }
  .note { background:#FFF7ED; border-left:4px solid #E65C00; border-radius:0 8px 8px 0;
          padding:12px 16px; font-size:13px; color:#92400E; margin-bottom:24px; line-height:1.6; }
  .footer { background:#F8FAFC; border-top:1px solid #E2E8F0; padding:20px 40px;
            text-align:center; font-size:12px; color:#94A3B8; }
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <h1>{{ config('app.name') }}</h1>
    <p>Budget Management System</p>
  </div>

  <div class="body">
    <div class="greeting">Welcome, {{ $user->name }}!</div>
    <p class="intro">
      Your account has been created on the GOIL Budget Management System.
      Use the credentials below to log in for the first time.
    </p>

    <div class="creds">
      <table>
        <tr>
          <td>Login URL</td>
          <td><a href="{{ $loginUrl }}" style="color:#E65C00">{{ $loginUrl }}</a></td>
        </tr>
        <tr>
          <td>Email</td>
          <td>{{ $user->email }}</td>
        </tr>
        @if($user->employee_id)
        <tr>
          <td>Employee ID</td>
          <td>{{ $user->employee_id }}</td>
        </tr>
        @endif
        <tr>
          <td>Password</td>
          <td style="font-family:monospace;letter-spacing:1px">{{ $plainPassword }}</td>
        </tr>
        <tr>
          <td>Role</td>
          <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
        </tr>
      </table>
    </div>

    <div class="note">
      <strong>You will be asked to change your password on first login.</strong>
      Please choose a strong password and do not share your credentials with anyone.
    </div>

    <div class="btn-wrap">
      <a href="{{ $loginUrl }}" class="btn">Log In Now</a>
    </div>

    <p style="font-size:13px;color:#94A3B8;text-align:center">
      If you did not expect this email or believe it was sent in error,
      please contact your system administrator.
    </p>
  </div>

  <div class="footer">
    &copy; {{ date('Y') }} Ghana Oil Company Limited &middot; GOIL Budget System<br>
    This is an automated message — please do not reply.
  </div>

</div>
</body>
</html>
