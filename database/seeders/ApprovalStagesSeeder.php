<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalStage;

class ApprovalStagesSeeder extends Seeder
{
    public function run(): void
    {
        // route_key: null = always runs, 'gceo' / 'coo' = branch-specific
        $stages = [
            ['name' => 'Department Head',    'order' => 1, 'role_name' => 'department_head',  'route_key' => null,   'is_active' => true],
            ['name' => 'Finance Review',     'order' => 2, 'role_name' => 'finance_reviewer', 'route_key' => null,   'is_active' => true],
            ['name' => 'GCEO & MD Approval', 'order' => 3, 'role_name' => 'gceo',             'route_key' => 'gceo', 'is_active' => true],
            ['name' => 'COO Approval',       'order' => 4, 'role_name' => 'coo',              'route_key' => 'coo',  'is_active' => true],
            ['name' => 'Board Approval',     'order' => 5, 'role_name' => 'board',            'route_key' => null,   'is_active' => true],
        ];

        foreach ($stages as $stage) {
            ApprovalStage::updateOrCreate(
                ['role_name' => $stage['role_name']],
                $stage
            );
        }
    }
}
