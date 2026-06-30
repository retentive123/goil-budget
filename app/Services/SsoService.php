<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SsoService
{
    /**
     * Attempt SSO authentication
     */
    public function attempt(string $email, string $password): ?User
    {
        $ssoEnabled = SystemSetting::get('sso_enabled', false);

        if ($ssoEnabled !== true) {
            return null; // SSO disabled — use local auth
        }

        try {
            return $this->authenticateWithAD($email, $password);
        } catch (\Exception $e) {
            Log::warning('AD authentication failed: ' . $e->getMessage());

            // Fallback to local login if configured
            $fallbackEnabled = SystemSetting::get('sso_fallback_to_local', true);

            if ($fallbackEnabled === true) {
                return null; // Let LoginController handle with local auth
            }

            throw $e;
        }
    }

    /**
     * Authenticate user with Active Directory
     */
    private function authenticateWithAD(string $email, string $password): ?User
    {
        // ✅ Read all settings from database (your seeder values)
        $domain     = SystemSetting::get('sso_ad_domain', 'goil.com');
        $adServer   = SystemSetting::get('sso_ad_server', 'ldap.goil.com');
        $basedn     = SystemSetting::get('sso_ad_basedn', 'dc=goil,dc=com');
        $port       = (int) SystemSetting::get('sso_ad_port', 389);
        $useSsl     = (bool) SystemSetting::get('sso_ad_ssl', false);
        $useTls     = (bool) SystemSetting::get('sso_ad_tls', false);

        // Build LDAP URI
        $protocol = $useSsl ? 'ldaps://' : 'ldap://';
        $uri = $protocol . $adServer . ':' . $port;

        // Attempt LDAP connection
        $ldapConn = @ldap_connect($uri);

        if (!$ldapConn) {
            throw new \Exception('Cannot connect to AD server: ' . $uri);
        }

        // Set LDAP options
        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

        // Use TLS if enabled
        if ($useTls) {
            if (!@ldap_start_tls($ldapConn)) {
                throw new \Exception('TLS negotiation failed.');
            }
        }

        // Format username with domain if not already
        $username = strpos($email, '@') !== false
            ? $email
            : $email . '@' . $domain;

        // Try to bind with user credentials
        $bound = @ldap_bind($ldapConn, $username, $password);

        if (!$bound) {
            ldap_close($ldapConn);
            return null; // Wrong password
        }

        // Search for the user in AD
        $filter   = "(userPrincipalName={$username})";
        $attrs    = ['cn', 'mail', 'displayname', 'department', 'employeeid'];

        $search   = ldap_search($ldapConn, $basedn, $filter, $attrs);

        if (!$search) {
            ldap_close($ldapConn);
            throw new \Exception('LDAP search failed.');
        }

        $entries  = ldap_get_entries($ldapConn, $search);
        ldap_close($ldapConn);

        if ($entries['count'] === 0) {
            return null;
        }

        $adUser = $entries[0];
        return $this->syncUser($adUser, $email);
    }

    /**
     * Sync LDAP user with local database
     */
    private function syncUser(array $adUser, string $email): ?User
    {
        $name        = $adUser['displayname'][0] ?? $adUser['cn'][0] ?? $email;
        $employeeId  = $adUser['employeeid'][0]  ?? null;
        $deptName    = $adUser['department'][0]   ?? null;

        // Find existing user
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Auto-create if setting allows
            if (!SystemSetting::get('sso_auto_create_users', false)) {
                Log::info("SSO: User {$email} authenticated by AD but not in system.");
                return null;
            }

            // Find department by name
            $department = $deptName
                ? \App\Models\Department::where('name', 'like', "%{$deptName}%")->first()
                : null;

            $user = User::create([
                'name'          => $name,
                'email'         => $email,
                'password'      => Hash::make(str()->random(32)), // random — SSO only
                'employee_id'   => $employeeId,
                'department_id' => $department?->id,
                'is_active'     => true,
            ]);

            // Assign default role
            try {
                $user->assignRole('department_user');
            } catch (\Exception $e) {
                Log::warning('Could not assign role: ' . $e->getMessage());
            }

            Log::info("SSO: Auto-created user {$email} from AD.");
        } else {
            // Sync name and employee ID from AD
            $user->update([
                'name'        => $name,
                'employee_id' => $employeeId ?? $user->employee_id,
            ]);
        }

        return $user;
    }

    /**
     * Test LDAP connection
     */
    public function testConnection(): array
    {
        $adServer   = SystemSetting::get('sso_ad_server', 'ldap.goil.com');
        $port       = (int) SystemSetting::get('sso_ad_port', 389);
        $useSsl     = (bool) SystemSetting::get('sso_ad_ssl', false);
        $useTls     = (bool) SystemSetting::get('sso_ad_tls', false);

        $protocol = $useSsl ? 'ldaps://' : 'ldap://';
        $uri = $protocol . $adServer . ':' . $port;

        $ldapConn = @ldap_connect($uri);

        if (!$ldapConn) {
            return ['success' => false, 'message' => "Cannot connect to {$uri}"];
        }

        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

        if ($useTls) {
            @ldap_start_tls($ldapConn);
        }

        $bound = @ldap_bind($ldapConn);

        if ($bound) {
            ldap_close($ldapConn);
            return ['success' => true, 'message' => "Connected to {$uri}"];
        }

        ldap_close($ldapConn);
        return ['success' => false, 'message' => "Connection failed to {$uri}"];
    }

    /**
     * Search for a user in AD
     */
    public function searchUser(string $email): ?array
    {
        $adServer   = SystemSetting::get('sso_ad_server', 'ldap.goil.com');
        $port       = (int) SystemSetting::get('sso_ad_port', 389);
        $basedn     = SystemSetting::get('sso_ad_basedn', 'dc=goil,dc=com');
        $useSsl     = (bool) SystemSetting::get('sso_ad_ssl', false);

        $protocol = $useSsl ? 'ldaps://' : 'ldap://';
        $uri = $protocol . $adServer . ':' . $port;

        $ldapConn = @ldap_connect($uri);

        if (!$ldapConn) {
            return null;
        }

        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

        // Try anonymous bind first
        if (!@ldap_bind($ldapConn)) {
            // Try with service account if configured
            $serviceUser = SystemSetting::get('sso_service_account', '');
            $servicePass = SystemSetting::get('sso_service_password', '');

            if ($serviceUser && $servicePass) {
                if (!@ldap_bind($ldapConn, $serviceUser, $servicePass)) {
                    ldap_close($ldapConn);
                    return null;
                }
            } else {
                ldap_close($ldapConn);
                return null;
            }
        }

        $filter = "(mail={$email})";
        $attrs = ['cn', 'mail', 'displayname', 'department', 'employeeid', 'samaccountname'];

        $search = ldap_search($ldapConn, $basedn, $filter, $attrs);

        if (!$search) {
            ldap_close($ldapConn);
            return null;
        }

        $entries = ldap_get_entries($ldapConn, $search);
        ldap_close($ldapConn);

        if ($entries['count'] === 0) {
            return null;
        }

        $user = $entries[0];
        return [
            'cn' => $user['cn'][0] ?? null,
            'name' => $user['displayname'][0] ?? $user['cn'][0] ?? null,
            'email' => $user['mail'][0] ?? null,
            'samaccountname' => $user['samaccountname'][0] ?? null,
            'department' => $user['department'][0] ?? null,
            'employeeid' => $user['employeeid'][0] ?? null,
        ];
    }
}
