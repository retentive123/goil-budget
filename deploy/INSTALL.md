# GOIL Budget System — Installation Guide

**Version:** See zip filename  
**Prepared by:** [Your company name]  
**Support contact:** [Your contact]

---

## Server Requirements

Before starting, confirm the server has:

| Requirement | Minimum | Recommended |
|---|---|---|
| **OS** | Windows Server 2019 | Windows Server 2022 |
| **Web server** | IIS 10 | IIS 10 with URL Rewrite module |
| **PHP** | 8.2 | 8.2 or 8.3 |
| **MySQL** | 8.0 | 8.0 |
| **Composer** | 2.x | latest |
| **RAM** | 4 GB | 8 GB |
| **Disk** | 10 GB free | 50 GB free (for backups) |
| **HTTPS** | Required | SSL certificate on the domain |

### Required PHP Extensions
All must be enabled in `php.ini`:
`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `fileinfo`, `gd`, `zip`, `bcmath`, `curl`, `intl`

### Required IIS Features
- URL Rewrite 2.x (download from Microsoft)
- CGI / FastCGI (for PHP-CGI handler)
- .NET CLR version on the App Pool = **No Managed Code**

---

## Installation Steps

### 1. Unzip the package

Unzip `goil-budget-vYYYY.MM.DD.zip` to your desired location, e.g.:

```
C:\inetpub\wwwroot\goil-budget\
```

### 2. Configure the environment file

Open `env.production.txt` in Notepad and fill in all values marked `<<<`:

```
APP_URL=https://budget.goil.com    ← your actual domain
DB_PASSWORD=                        ← your database password
MAIL_HOST=mail.goil.com            ← GOIL mail server
MAIL_USERNAME=budget-system@goil.com
MAIL_PASSWORD=                      ← mail account password
```

Save the file, then rename it to `.env`.

### 3. Run the installer

Open **PowerShell as Administrator**, navigate to the app folder, and run:

```powershell
cd C:\inetpub\wwwroot\goil-budget
Set-ExecutionPolicy Bypass -Scope Process -Force
.\install.ps1
```

The script will:
- Check all prerequisites
- Generate the application key
- Install PHP dependencies
- Create the database
- Run all migrations
- Seed roles, permissions, and the initial admin account
- Set folder permissions for IIS
- Register two Windows Task Scheduler tasks (scheduler + queue worker)

### 4. Configure IIS Site

1. Open **IIS Manager**
2. Create a new site (or use an existing one)
3. Set **Physical Path** to: `C:\inetpub\wwwroot\goil-budget\public`
4. Set **Port** to 443 and bind your SSL certificate
5. Set the **Application Pool** to:
   - .NET CLR version: **No Managed Code**
   - Identity: ApplicationPoolIdentity (or a service account)
6. Add a PHP handler if not already present:
   - Request path: `*.php`
   - Executable: `C:\php\php-cgi.exe`
   - Name: `PHP_via_FastCGI`

The `web.config` in the `public\` folder handles all URL rewriting automatically.

### 5. First login

Open a browser and go to `https://budget.goil.com`

```
Email    : admin@goil.com
Password : Admin@1234
```

**Change this password immediately** — go to your profile → Change Password.

---

## Post-Installation Configuration

Log in as `admin@goil.com` and configure the following in **Settings**:

| Setting | Where |
|---|---|
| Company name | Settings → System Settings |
| Currency symbol | Settings → System Settings |
| Session timeout | Settings → System Settings |
| Budget period (year) | Setup → Budget Periods |
| Approval stages | Setup → Approval Stages |
| Account categories & codes | Setup → Categories / Account Codes |
| Departments | Setup → Departments |
| Subsidiaries | Setup → Subsidiaries |
| Users | Admin → Users (or use Bulk Import CSV) |

---

## Windows Task Scheduler (Verify)

The installer creates two scheduled tasks. Verify they are running:

1. Open **Task Scheduler**
2. Look for:
   - `GOIL Budget - Laravel Scheduler` (runs every 1 minute)
   - `GOIL Budget - Queue Worker` (runs every 5 minutes)
3. Right-click each → **Run** → check Last Run Result is `0x0` (success)

If the tasks need to be recreated manually, run these from the app folder:

```powershell
# Scheduler (runs every minute)
php artisan schedule:run

# Queue worker (keep running in background)
php artisan queue:work --sleep=3 --tries=3 --timeout=60
```

---

## Updating the Software

When a new version is delivered:

1. Back up the database: `php artisan backup:run`
2. Unzip the new package **over** the existing folder (do not delete `.env`)
3. Open PowerShell as Administrator and run:
   ```powershell
   cd C:\inetpub\wwwroot\goil-budget
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. Test in the browser

---

## Troubleshooting

| Problem | Solution |
|---|---|
| 500 error on first load | Check `storage\logs\laravel.log` for the actual error |
| Blank white page | Enable `APP_DEBUG=true` temporarily, reload, then set back to `false` |
| Emails not sending | Check MAIL_* settings in `.env`; run `php artisan tinker` → `Mail::raw('test',fn($m)=>$m->to('you@goil.com')->subject('test'))` |
| Queue jobs stuck | Open Task Scheduler, ensure "GOIL Budget - Queue Worker" is running |
| Cannot write to storage | Re-run `icacls storage /grant "IIS AppPool\DefaultAppPool":(OI)(CI)M` |
| Session expired immediately | Check `SESSION_DOMAIN` in `.env` matches your actual domain |

---

## Support

For technical support, contact: **[Your company name]**  
Email: **[support@yourcompany.com]**  
Phone: **[Your phone]**
