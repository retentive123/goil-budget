# ============================================================
# GOIL Budget System — Delivery Package Builder
# Run this on YOUR machine (developer machine) to produce
# the zip file you hand over to GOIL IT.
# ============================================================
# Usage:
#   cd c:\xampp\htdocs\goil-budget\deploy
#   .\package.ps1
# ============================================================

param(
    [string]$Version = (Get-Date -Format "yyyy.MM.dd")
)

$ProjectRoot = Resolve-Path "$PSScriptRoot\.."
$OutputDir   = "$PSScriptRoot\dist"
$ZipName     = "goil-budget-v$Version.zip"
$ZipPath     = "$OutputDir\$ZipName"

Write-Host "`n=== GOIL Budget System — Package Builder ===" -ForegroundColor Cyan
Write-Host "Version : $Version"
Write-Host "Source  : $ProjectRoot"
Write-Host "Output  : $ZipPath`n"

# ── 1. Build frontend assets first ──────────────────────────
Write-Host "[1/5] Building frontend assets (npm run build)..." -ForegroundColor Yellow
Set-Location $ProjectRoot
npm run build
if ($LASTEXITCODE -ne 0) { Write-Error "npm build failed. Aborting."; exit 1 }

# ── 2. Optimise composer autoloader (no dev deps) ───────────
Write-Host "[2/5] Optimising Composer autoloader..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader --quiet
if ($LASTEXITCODE -ne 0) { Write-Error "composer install failed. Aborting."; exit 1 }

# ── 3. Create output directory ───────────────────────────────
Write-Host "[3/5] Preparing output directory..." -ForegroundColor Yellow
if (!(Test-Path $OutputDir)) { New-Item -ItemType Directory -Path $OutputDir | Out-Null }
if (Test-Path $ZipPath)      { Remove-Item $ZipPath -Force }

# ── 4. Build exclusion list ──────────────────────────────────
$Excludes = @(
    '.git',
    '.env',                    # never ship secrets
    'node_modules',
    'storage\logs\*.log',
    'storage\framework\cache\data\*',
    'storage\framework\sessions\*',
    'storage\framework\views\*',
    'tests',
    'deploy\dist',             # don't include the dist folder itself
    '*.ps1',                   # don't ship dev scripts
    'phpunit.xml',
    '.phpunit.cache'
)

Write-Host "[4/5] Creating zip archive (excluding dev files)..." -ForegroundColor Yellow

# Compress-Archive doesn't support exclusions natively — use a temp copy approach
$TempDir = "$OutputDir\temp_pkg"
if (Test-Path $TempDir) { Remove-Item $TempDir -Recurse -Force }
New-Item -ItemType Directory -Path $TempDir | Out-Null

# Robocopy everything except excluded dirs
$ExcludeDirs  = 'node_modules', '.git', 'tests', 'dist'
$ExcludeFiles = '.env', '*.log', '*.ps1', 'phpunit.xml'

robocopy $ProjectRoot $TempDir /E /XD $ExcludeDirs /XF $ExcludeFiles /NFL /NDL /NJH /NJS /nc /ns | Out-Null

# Remove sessions/views/cache inside storage (keep folder structure)
Remove-Item "$TempDir\storage\logs\*"            -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item "$TempDir\storage\framework\cache\data\*" -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item "$TempDir\storage\framework\sessions\*"   -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item "$TempDir\storage\framework\views\*"      -Recurse -Force -ErrorAction SilentlyContinue

# Copy the deploy scripts into the package root
Copy-Item "$PSScriptRoot\install.ps1"          "$TempDir\install.ps1"
Copy-Item "$PSScriptRoot\env.production.txt"   "$TempDir\env.production.txt"
Copy-Item "$PSScriptRoot\web.config"           "$TempDir\public\web.config"
Copy-Item "$PSScriptRoot\INSTALL.md"           "$TempDir\INSTALL.md"

# Zip it
Compress-Archive -Path "$TempDir\*" -DestinationPath $ZipPath -Force

# Cleanup temp
Remove-Item $TempDir -Recurse -Force

# ── 5. Done ─────────────────────────────────────────────────
Write-Host "[5/5] Done!" -ForegroundColor Green
$size = [math]::Round((Get-Item $ZipPath).Length / 1MB, 1)
Write-Host "`n Package ready : $ZipPath ($size MB)" -ForegroundColor Cyan
Write-Host " Hand this zip + the password to the DB/mail to GOIL IT.`n"

# Restore dev composer deps for your own machine
Write-Host "Restoring dev Composer deps..." -ForegroundColor Gray
composer install --quiet
