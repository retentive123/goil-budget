<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // General
            [
                'key'         => 'app_name',
                'value'       => 'GOIL Budget Tool',
                'type'        => 'string',
                'label'       => 'Application Name',
                'description' => 'Name displayed in the header and emails.',
                'group'       => 'general',
            ],
            [
                'key'         => 'company_name',
                'value'       => 'Ghana Oil Company Limited',
                'type'        => 'string',
                'label'       => 'Company Name',
                'description' => 'Full company name used in reports and PDF headers.',
                'group'       => 'general',
            ],
            [
                'key'         => 'currency_symbol',
                'value'       => 'GHS',
                'type'        => 'string',
                'label'       => 'Currency Symbol',
                'description' => 'Currency symbol used throughout the system.',
                'group'       => 'general',
            ],
            [
                'key'         => 'fiscal_year_start',
                'value'       => '01',
                'type'        => 'integer',
                'label'       => 'Fiscal Year Start Month',
                'description' => 'Month number the fiscal year begins (1 = January).',
                'group'       => 'general',
            ],

            // Budget
            [
                'key'         => 'max_budget_versions',
                'value'       => '4',
                'type'        => 'integer',
                'label'       => 'Max Budget Versions Per Period',
                'description' => 'Maximum revision cycles allowed per department per period.',
                'group'       => 'budget',
            ],
            [
                'key'         => 'virement_limit_pct',
                'value'       => '10',
                'type'        => 'integer',
                'label'       => 'Virement Limit (%)',
                'description' => 'Maximum % of an account code budget that can be vired out.',
                'group'       => 'budget',
            ],
            [
                'key'         => 'allow_virement_after_approval',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Allow Virements After Approval',
                'description' => 'Whether departments can request virements on an approved budget.',
                'group'       => 'budget',
            ],
            [
                'key'         => 'require_justification',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Require Line Item Justification',
                'description' => 'Force departments to enter a note for each budget line item.',
                'group'       => 'budget',
            ],
            [
                'key'         => 'budget_entry_deadline_days',
                'value'       => '30',
                'type'        => 'integer',
                'label'       => 'Budget Entry Deadline (days)',
                'description' => 'Number of days after a period opens that departments must submit.',
                'group'       => 'budget',
            ],

            // Notifications
            [
                'key'         => 'email_notifications_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Enable Email Notifications',
                'description' => 'Send email notifications for submissions, approvals and rejections.',
                'group'       => 'notifications',
            ],
            [
                'key'         => 'notify_on_submission',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Notify on Budget Submission',
                'description' => 'Notify approvers when a department submits a budget.',
                'group'       => 'notifications',
            ],
            [
                'key'         => 'notify_on_approval',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Notify on Approval',
                'description' => 'Notify department when their budget is approved at any stage.',
                'group'       => 'notifications',
            ],
            [
                'key'         => 'notify_on_rejection',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Notify on Rejection',
                'description' => 'Notify department when their budget is rejected.',
                'group'       => 'notifications',
            ],
            [
                'key'         => 'notify_on_virement',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Notify on Virement Decision',
                'description' => 'Notify department when a virement is approved or rejected.',
                'group'       => 'notifications',
            ],
            [
                'key'         => 'notify_finance_on_virement',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Notify Finance on Virement Request',
                'description' => 'Alert finance reviewers when a new virement is submitted.',
                'group'       => 'notifications',
            ],

            // Security
            [
                'key'         => 'session_timeout_minutes',
                'value'       => '10',
                'type'        => 'integer',
                'label'       => 'Session Timeout (minutes)',
                'description' => 'Minutes of inactivity before a session is automatically logged out.',
                'group'       => 'security',
            ],
            [
                'key'         => 'max_login_attempts',
                'value'       => '5',
                'type'        => 'integer',
                'label'       => 'Max Login Attempts',
                'description' => 'Failed login attempts before the account is temporarily locked.',
                'group'       => 'security',
            ],
            [
                'key'         => 'login_lockout_minutes',
                'value'       => '15',
                'type'        => 'integer',
                'label'       => 'Lockout Duration (minutes)',
                'description' => 'How long an account stays locked after too many failed attempts.',
                'group'       => 'security',
            ],
            [
                'key'         => 'force_password_change_days',
                'value'       => '90',
                'type'        => 'integer',
                'label'       => 'Password Expiry (days)',
                'description' => 'Force users to change password after this many days. Set 0 to disable.',
                'group'       => 'security',
            ],
            [
                'key'         => 'two_factor_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Two-Factor Authentication',
                'description' => 'Require 2FA for all users. Requires email to be configured.',
                'group'       => 'security',
            ],

            // Backup settings group
            [
                'key'         => 'backup_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Enable Scheduled Backups',
                'description' => 'Run automatic database backups on schedule.',
                'group'       => 'backup',
            ],
            [
                'key'         => 'backup_frequency',
                'value'       => 'daily',
                'type'        => 'string',
                'label'       => 'Backup Frequency',
                'description' => 'daily, weekly, or monthly.',
                'group'       => 'backup',
            ],
            [
                'key'         => 'backup_keep_count',
                'value'       => '10',
                'type'        => 'integer',
                'label'       => 'Backups to Keep',
                'description' => 'Number of recent backups to retain. Older ones are deleted automatically.',
                'group'       => 'backup',
            ],
            [
                'key'         => 'backup_notify_email',
                'value'       => '',
                'type'        => 'string',
                'label'       => 'Backup Notification Email',
                'description' => 'Email address to notify after each backup (leave blank to disable).',
                'group'       => 'backup',
            ],

            [
                'key'         => 'sso_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Enable SSO / Active Directory Login',
                'description' => 'Allow users to log in with their GOIL network credentials.',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_fallback_to_local',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Fallback to Local Login if SSO Fails',
                'description' => 'If AD is unreachable, allow users to log in with local passwords.',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_ad_domain',
                'value'       => 'goil.com',
                'type'        => 'string',
                'label'       => 'Active Directory Domain',
                'description' => 'The domain suffix for AD authentication (e.g. goil.com).',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_auto_create_users',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Auto-Create Users from AD',
                'description' => 'Automatically create a system user when an AD user logs in for the first time.',
                'group'       => 'security',
            ],

            [
                'key'         => 'single_session_only',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Single Session Only',
                'description' => 'If a user logs in on a new device, their previous session is automatically logged out.',
                'group'       => 'security',
            ],


            [
                'key'         => 'sso_ad_server',
                'value'       => 'ldap.goil.com',
                'type'        => 'string',
                'label'       => 'Active Directory Server',
                'description' => 'The hostname or IP of your AD server.',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_ad_port',
                'value'       => '389',
                'type'        => 'integer',
                'label'       => 'AD Port',
                'description' => 'LDAP port (389 for standard, 636 for LDAPS).',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_ad_basedn',
                'value'       => 'dc=goil,dc=com',
                'type'        => 'string',
                'label'       => 'Base DN',
                'description' => 'The base distinguished name for LDAP searches.',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_ad_ssl',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Use LDAPS (SSL)',
                'description' => 'Enable SSL for secure LDAP connection (port 636).',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_ad_tls',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Use TLS',
                'description' => 'Enable TLS for secure LDAP connection.',
                'group'       => 'security',
            ],

            [
                'key'         => 'sso_service_account',
                'value'       => '',
                'type'        => 'string',
                'label'       => 'LDAP Service Account',
                'description' => 'Username for service account used for LDAP searches (e.g. cn=admin,dc=goil,dc=com)',
                'group'       => 'security',
            ],
            [
                'key'         => 'sso_service_password',
                'value'       => '',
                'type'        => 'string',
                'label'       => 'LDAP Service Password',
                'description' => 'Password for the LDAP service account.',
                'group'       => 'security',
            ],

            [
                'key'         => 'enforce_segregation_of_duties',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Enforce Segregation of Duties',
                'description' => 'Prevent the same user who submitted/recorded from approving, confirming or authorising the same record.',
                'group'       => 'security',
            ],

        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded — ' . count($settings) . ' settings created.');
    }
}
