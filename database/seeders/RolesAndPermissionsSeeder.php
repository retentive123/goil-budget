<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- PERMISSIONS ---

        $permissions = [

            // Budget period management (Super Admin only)
            'manage budget periods',

            // Department & account code config (Admin/Finance)
            'manage departments',
            'manage account codes',
            'manage department mappings',

            // User management
            'manage users',

            // Budget entry (Department User)
            'create budget',
            'edit budget',
            'submit budget',
            'view own department budget',

            // Budget review (Finance / Approvers)
            'view all budgets',
            'approve budget',
            'reject budget',

            // Virement
            'request virement',
            'approve virement',

            // Supplementary budget
            'request supplementary budget',
            'approve supplementary budget',

            // Deadline overrides
            'grant deadline override',

            // Account categories
            'manage categories',

            // Reports
            'view reports',
            'export reports',

            // Audit log
            'view audit log',

            // System settings
            'manage system settings',

            // Two-factor authentication
            'disable two factor',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // --- ROLES ---

        // 1. Department User — enters and submits their dept budget
        $deptUser = Role::firstOrCreate(['name' => 'department_user']);
        $deptUser->syncPermissions([
            'create budget',
            'edit budget',
            'submit budget',
            'view own department budget',
            'request virement',
            'request supplementary budget',
            'view reports',
        ]);

        // 2. Department Head — reviews and forwards dept budget
        $deptHead = Role::firstOrCreate(['name' => 'department_head']);
        $deptHead->syncPermissions([
            'create budget',
            'edit budget',
            'submit budget',
            'view own department budget',
            'approve budget',
            'reject budget',
            'request virement',
            'request supplementary budget',
            'view reports',
            'export reports',
        ]);

        // 3. Finance Reviewer — reviews all budgets, manages structure
        $finance = Role::firstOrCreate(['name' => 'finance_reviewer']);
        $finance->syncPermissions([
            'view all budgets',
            'view own department budget',
            'approve budget',
            'reject budget',
            'manage account codes',
            'manage categories',
            'manage department mappings',
            'approve virement',
            'approve supplementary budget',
            'grant deadline override',
            'view reports',
            'export reports',
        ]);

        // 4. GCEO / MD — management approval
        $gceo = Role::firstOrCreate(['name' => 'gceo']);
        $gceo->syncPermissions([
            'view all budgets',
            'approve budget',
            'reject budget',
            'approve virement',
            'view reports',
            'export reports',
        ]);

        // 5. Board — final approval
        $board = Role::firstOrCreate(['name' => 'board']);
        $board->syncPermissions([
            'view all budgets',
            'approve budget',
            'reject budget',
            'view reports',
            'export reports',
        ]);

        // 6. BDU Admin — configures the system, manages mappings
        $bduAdmin = Role::firstOrCreate(['name' => 'bdu_admin']);
        $bduAdmin->syncPermissions([
            'view all budgets',
            'manage departments',
            'manage account codes',
            'manage categories',
            'manage department mappings',
            'manage users',
            'approve virement',
            'approve supplementary budget',
            'grant deadline override',
            'view reports',
            'export reports',
            'view audit log',
            'disable two factor',
        ]);

        // 7. Super Admin — unrestricted access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());
    }
}
