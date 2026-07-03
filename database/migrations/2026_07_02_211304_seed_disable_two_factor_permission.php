<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'disable two factor', 'guard_name' => 'web']);

        foreach (['bdu_admin', 'super_admin'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            if ($role && !$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'disable two factor')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
