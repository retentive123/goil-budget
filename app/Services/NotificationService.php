<?php

namespace App\Services;

use App\Models\BudgetVersion;
use App\Models\ApprovalStage;
use App\Models\BudgetNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class NotificationService
{
    // Notify all users with the given approval stage role
    public function notifyApprovers(BudgetVersion $version, ApprovalStage $stage): void
    {

    if (!\App\Models\SystemSetting::get('notify_on_submission', true)) {
        return;
    }
        $approvers = User::role($stage->role_name)
                         ->where('is_active', true)
                         ->get();

        foreach ($approvers as $approver) {
            // Store in-app notification
            BudgetNotification::create([
                'user_id'         => $approver->id,
                'type'            => 'budget_pending_approval',
                'subject'         => "Budget awaiting your approval — {$version->department->name}",
                'message'         => "{$version->department->name} has submitted their budget (v{$version->version_number}) for {$version->period->name}. Please review and action it.",
                'notifiable_id'   => $version->id,
                'notifiable_type' => BudgetVersion::class,
            ]);

            // Send email notification
            $this->sendEmail(
                to:      $approver->email,
                subject: "Budget pending approval — {$version->department->name}",
                body:    "Dear {$approver->name},\n\n{$version->department->name} has submitted their budget (Version {$version->version_number}) for {$version->period->name}.\n\nPlease log in to the GOIL Budget Tool to review and approve or reject it.\n\nThis is an automated notification."
            );
        }
    }

    // Notify the department that their budget was approved or rejected
    public function notifyDepartment(BudgetVersion $version, string $decision, string $comments = ''): void
    {

    $key = $decision === 'approved' ? 'notify_on_approval' : 'notify_on_rejection';

    if (!\App\Models\SystemSetting::get($key, true)) {
        return;
    }
        $members = User::where('department_id', $version->department_id)
                       ->where('is_active', true)
                       ->get();

        $isApproved = $decision === 'approved';
        $subject    = $isApproved
            ? "Your budget has been approved — {$version->period->name}"
            : "Your budget requires revision — {$version->period->name}";

        $message = $isApproved
            ? "Your department's budget (v{$version->version_number}) for {$version->period->name} has been approved."
            : "Your department's budget (v{$version->version_number}) for {$version->period->name} has been rejected and requires revision.\n\nComments: {$comments}";

        foreach ($members as $member) {
            BudgetNotification::create([
                'user_id'         => $member->id,
                'type'            => "budget_{$decision}",
                'subject'         => $subject,
                'message'         => $message,
                'notifiable_id'   => $version->id,
                'notifiable_type' => BudgetVersion::class,
            ]);

            $this->sendEmail(
                to:      $member->email,
                subject: $subject,
                body:    "Dear {$member->name},\n\n{$message}\n\nPlease log in to the GOIL Budget Tool for details.\n\nThis is an automated notification."
            );
        }
    }

   private function sendEmail(string $to, string $subject, string $body): void
{
    // Respect the global email toggle
    if (!\App\Models\SystemSetting::get('email_notifications_enabled', true)) {
        return;
    }

    try {
        Mail::raw($body, function (Message $message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    } catch (\Exception $e) {
        \Log::error("Budget notification email failed: {$e->getMessage()}");
    }
}
}
