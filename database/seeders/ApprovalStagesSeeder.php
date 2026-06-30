<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalStage;

class ApprovalStagesSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            [
                'name'      => 'Department Head',
                'order'     => 1,
                'role_name' => 'department_head',
                'is_active' => true,
            ],
            [
                'name'      => 'Finance Review',
                'order'     => 2,
                'role_name' => 'finance_reviewer',
                'is_active' => true,
            ],
            [
                'name'      => 'GCEO & MD Approval',
                'order'     => 3,
                'role_name' => 'gceo',
                'is_active' => true,
            ],
            [
                'name'      => 'Board Approval',
                'order'     => 4,
                'role_name' => 'board',
                'is_active' => true,
            ],
        ];

        foreach ($stages as $stage) {
            ApprovalStage::updateOrCreate(
                ['order' => $stage['order']],
                $stage
            );
        }
    }
}
