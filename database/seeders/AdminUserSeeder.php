<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@goil.com'],
            [
                'name'        => 'System Administrator',
                'password'    => Hash::make('Admin@1234'),
                'employee_id' => 'IT-0001',
                'is_active'   => true,
            ]
        );

        $admin->assignRole('super_admin');

        $this->command->info('Super admin created: admin@goil.com / Admin@1234');
        $this->command->warn('Remember to change this password after first login!');
    }
}
