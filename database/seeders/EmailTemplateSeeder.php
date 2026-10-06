<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'event_key' => 'budget_submitted',
                'label'     => 'Budget Submitted (to approver)',
                'subject'   => 'Budget pending your approval — {dept_name}',
                'body'      => "Dear {approver_name},\n\n{dept_name} has submitted their budget (Version {version}) for {period_name} and it is now pending your review.\n\nPlease log in to the GOIL Budget System to review and take action.",
            ],
            [
                'event_key' => 'budget_stage_reached',
                'label'     => 'Budget Reached Your Approval Stage',
                'subject'   => 'Action required: budget awaiting your approval — {dept_name}',
                'body'      => "Dear {approver_name},\n\n{dept_name}'s budget (Version {version}) for {period_name} has reached your approval stage.\n\nPlease log in to the GOIL Budget System to review and take action.",
            ],
            [
                'event_key' => 'budget_approved',
                'label'     => 'Budget Approved (to department)',
                'subject'   => 'Your budget has been approved — {period_name}',
                'body'      => "Dear {member_name},\n\nYour department's budget (Version {version}) for {period_name} has been approved.\n\nPlease log in to the GOIL Budget System to view the details.",
            ],
            [
                'event_key' => 'budget_rejected',
                'label'     => 'Budget Rejected (to department)',
                'subject'   => 'Your budget requires revision — {period_name}',
                'body'      => "Dear {member_name},\n\nYour department's budget (Version {version}) for {period_name} has been rejected and requires revision.\n\nComments: {comments}\n\nPlease log in to the GOIL Budget System to revise and resubmit.",
            ],
            [
                'event_key' => 'deadline_reminder',
                'label'     => 'Budget Deadline Reminder',
                'subject'   => 'Budget deadline reminder — {period_name}',
                'body'      => "Dear {member_name},\n\nThis is a reminder that the budget submission deadline for {period_name} is on {deadline}.\n\nYour department has not yet submitted a budget for this period. Please log in and submit as soon as possible.",
            ],
            [
                'event_key' => 'virement_submitted',
                'label'     => 'Virement Request Submitted',
                'subject'   => 'New virement request — {dept_name}',
                'body'      => "Dear {approver_name},\n\n{dept_name} has submitted a virement request for {period_name}.\n\nPlease log in to the GOIL Budget System to review and approve or reject it.",
            ],
            [
                'event_key' => 'virement_approved',
                'label'     => 'Virement Approved',
                'subject'   => 'Your virement request has been approved — {period_name}',
                'body'      => "Dear {member_name},\n\nYour virement request for {period_name} has been approved.\n\nPlease log in to the GOIL Budget System for details.",
            ],
            [
                'event_key' => 'virement_rejected',
                'label'     => 'Virement Rejected',
                'subject'   => 'Your virement request has been rejected — {period_name}',
                'body'      => "Dear {member_name},\n\nYour virement request for {period_name} has been rejected.\n\nComments: {comments}\n\nPlease contact your finance reviewer for further guidance.",
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(['event_key' => $t['event_key']], $t + ['is_active' => true]);
        }

        $this->command->info('Email templates seeded — ' . count($templates) . ' templates.');
    }
}
