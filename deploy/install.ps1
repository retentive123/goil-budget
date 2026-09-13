# ============================================================
# GOIL Budget System — Server Installer
# Run this on the GOIL server (Windows Server) as Administrator
# after unzipping the delivery package.
# ============================================================
# Usage (run as Administrator in PowerShell):
#   cd C:\inetpub\wwwroot\goil-budget
#   Set-ExecutionPolicy Bypass -Scope Process -Force
#   .\install.ps1
# ============================================================

#Requires -RunAsAdministrator

$AppRoot  = $PSScriptRoot
$PhpExe   = "php"          # adjust if PHP is not in PATH, e.g. "C:\php\php.exe"
$MysqlExe = "mysql"        # adjust if MySQL is not in PATH

Write-Host "`n╔══════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   GOIL Budget System — Installation Script  ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════╝`n" -ForegroundColor Cyan

Set-Location $AppRoot

# ── STEP 1: Check prerequisites ─────────────────────────────
Write-Host "► Checking prerequisites..." -ForegroundColor Yellow

function Require-Command ($cmd, $label) {
    if (!(Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-Error "$label not found in PATH. Install it and add to PATH before re-running."
        exit 1
    }
    $ver = & $cmd --version 2>&1 | Select-Object -First 1
    Write-Host "  ✓ $label : $ver"
}

Require-Command "php"      "PHP 8.2+"
Require-Command "composer" "Composer"
Require-Command "mysql"    "MySQL"

# PHP version check
$phpVer = (& php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
if ([double]$phpVer -lt 8.2) {
    Write-Error "PHP 8.2 or higher is required. Found: $phpVer"
    exit 1
}

# Required PHP extensions
$required = @('pdo_mysql','mbstring','openssl','tokenizer','xml','fileinfo','gd','zip','bcmath','curl','intl')
$loaded   = (& php -r "echo implode(',', get_loaded_extensions());").Split(',')
$missing  = $required | Where-Object { $_ -notin $loaded }
if ($missing) {
    Write-Error "Missing PHP extensions: $($missing -join ', ')`nEnable them in php.ini and restart PHP."
    exit 1
}
Write-Host "  ✓ All required PHP extensions present"

# ── STEP 2: Configure .env ───────────────────────────────────
Write-Host "`n► Configuring environment..." -ForegroundColor Yellow

if (!(Test-Path ".env")) {
    if (Test-Path "env.production.txt") {
        Copy-Item "env.production.txt" ".env"
        Write-Host "  ✓ Copied env.production.txt → .env"
        Write-Host "  ⚠  Edit .env now to fill in your DB credentials, mail server, and APP_URL." -ForegroundColor Magenta
        Write-Host "  Press ENTER when done..." -ForegroundColor Magenta
        Read-Host
    } else {
        Copy-Item ".env.example" ".env"
        Write-Host "  ✓ Copied .env.example → .env"
        Write-Host "  ⚠  You MUST edit .env before continuing." -ForegroundColor Red
        Write-Host "  Press ENTER when done..." -ForegroundColor Red
        Read-Host
    }
} else {
    Write-Host "  ✓ .env already exists — using it."
}

# ── STEP 3: Generate app key ─────────────────────────────────
Write-Host "`n► Generating application key..." -ForegroundColor Yellow
& $PhpExe artisan key:generate --force
if ($LASTEXITCODE -ne 0) { Write-Error "key:generate failed."; exit 1 }

# ── STEP 4: Install Composer dependencies ───────────────────
Write-Host "`n► Installing PHP dependencies (Composer)..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -ne 0) { Write-Error "Composer install failed."; exit 1 }

# ── STEP 5: Create database ──────────────────────────────────
Write-Host "`n► Creating database..." -ForegroundColor Yellow
$dbName = (& $PhpExe -r "
    \$env = file_get_contents('.env');
    preg_match('/^DB_DATABASE=(.+)$/m', \$env, \$m);
    echo trim(\$m[1] ?? 'goil_budget');
")
$dbUser = (& $PhpExe -r "
    \$env = file_get_contents('.env');
    preg_match('/^DB_USERNAME=(.+)$/m', \$env, \$m);
    echo trim(\$m[1] ?? 'root');
")
$dbPass = (& $PhpExe -r "
    \$env = file_get_contents('.env');
    preg_match('/^DB_PASSWORD=(.+)$/m', \$env, \$m);
    echo trim(\$m[1] ?? '');
")

Write-Host "  Creating database '$dbName' if it doesn't exist..."
$createDb = "CREATE DATABASE IF NOT EXISTS \`$dbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($dbPass) {
    echo $createDb | & $MysqlExe -u $dbUser -p$dbPass 2>&1
} else {
    echo $createDb | & $MysqlExe -u $dbUser 2>&1
}
Write-Host "  ✓ Database ready"

# ── STEP 6: Run migrations and seeders ──────────────────────
Write-Host "`n► Running database migrations..." -ForegroundColor Yellow
& $PhpExe artisan migrate --force
if ($LASTEXITCODE -ne 0) { Write-Error "Migrations failed."; exit 1 }

Write-Host "`n► Seeding initial data (roles, permissions, admin user)..." -ForegroundColor Yellow
& $PhpExe artisan db:seed --force
if ($LASTEXITCODE -ne 0) { Write-Error "Seeding failed."; exit 1 }

# ── STEP 7: Storage setup ───────────────────────────────────
Write-Host "`n► Setting up storage..." -ForegroundColor Yellow
& $PhpExe artisan storage:link --force
if ($LASTEXITCODE -ne 0) { Write-Warning "storage:link failed — may need to be done manually." }

# Create storage directories if missing
@(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
) | ForEach-Object {
    if (!(Test-Path $_)) { New-Item -ItemType Directory -Path $_ -Force | Out-Null }
}

# ── STEP 8: Set folder permissions ──────────────────────────
Write-Host "`n► Setting folder permissions for IIS app pool..." -ForegroundColor Yellow
$AppPoolUser = "IIS AppPool\DefaultAppPool"  # adjust to your app pool name
$Folders     = @("storage", "bootstrap\cache")

foreach ($folder in $Folders) {
    $acl = Get-Acl $folder
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $AppPoolUser, "Modify", "ContainerInherit,ObjectInherit", "None", "Allow"
    )
    $acl.SetAccessRule($rule)
    Set-Acl $folder $acl
    Write-Host "  ✓ Write permissions granted on $folder"
}

# ── STEP 9: Optimise for production ─────────────────────────
Write-Host "`n► Optimising for production..." -ForegroundColor Yellow
& $PhpExe artisan config:cache
& $PhpExe artisan route:cache
& $PhpExe artisan view:cache
& $PhpExe artisan event:cache

# ── STEP 10: Windows Task Scheduler ─────────────────────────
Write-Host "`n► Setting up Windows Task Scheduler..." -ForegroundColor Yellow

$PhpPath   = (Get-Command php).Source
$ArtisanPath = "$AppRoot\artisan"

# Scheduler — runs every minute, triggers Laravel's scheduler
$schedAction  = New-ScheduledTaskAction -Execute $PhpPath -Argument "$ArtisanPath schedule:run" -WorkingDirectory $AppRoot
$schedTrigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date)
$schedSettings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 5)
Register-ScheduledTask -TaskName "GOIL Budget - Laravel Scheduler" `
    -Action $schedAction -Trigger $schedTrigger -Settings $schedSettings `
    -RunLevel Highest -Force | Out-Null

# Queue worker — runs every 5 minutes (restarts if stopped)
$queueAction  = New-ScheduledTaskAction -Execute $PhpPath `
    -Argument "$ArtisanPath queue:work --sleep=3 --tries=3 --timeout=60 --max-time=300" `
    -WorkingDirectory $AppRoot
$queueTrigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 5) -Once -At (Get-Date)
Register-ScheduledTask -TaskName "GOIL Budget - Queue Worker" `
    -Action $queueAction -Trigger $queueTrigger `
    -RunLevel Highest -Force | Out-Null

Write-Host "  ✓ 'GOIL Budget - Laravel Scheduler' task registered (every 1 min)"
Write-Host "  ✓ 'GOIL Budget - Queue Worker' task registered (every 5 min)"

# ── DONE ────────────────────────────────────────────────────
Write-Host "`n╔══════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║   Installation Complete!                     ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host @"

Next steps:
  1. Point your IIS site root to: $AppRoot\public
  2. Import the web.config from the public\ folder into IIS
  3. Ensure the IIS App Pool runs as .NET CLR version = No Managed Code
  4. Open the app in a browser and log in with:
       Email    : admin@goil.com
       Password : Admin@1234
  5. IMMEDIATELY change the admin password (Settings → Users)
  6. Configure System Settings (company name, mail, backup frequency)
  7. Create budget period and start adding departments

"@ -ForegroundColor White
