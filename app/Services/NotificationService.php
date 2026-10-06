<?php

namespace App\Services;

use App\Models\ApprovalStage;
use App\Models\BudgetNotification;
use App\Models\BudgetVersion;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class NotificationService
{
    /**
     * Called once when a budget is submitted.
     *
     * In-app  → department members + every approver across all active stages.
     * Email   → first-stage approvers only (the rest receive email when it reaches their stage).
     */
    public function notifySubmission(BudgetVersion $version, ApprovalStage $firstStage): void
    {
        if (!\App\Models\SystemSetting::get('notify_on_submission', true)) {
            return;
        }

        $ownerName = $version->ownerName();

        // ── In-app: department/subsidiary members ─────────────────────────────
        $deptMembers = $this->entityMembers($version);

        foreach ($deptMembers as $member) {
            BudgetNotification::create([
                'user_id'         => $member->id,
                'type'            => 'budget_submitted',
                'subject'         => "Budget submitted for approval — {$version->period->name}",
                'message'         => "Version {$version->version_number} of your budget for {$version->period->name} has been submitted and is now pending approval.",
                'notifiable_id'   => $version->id,
                'notifiable_type' => BudgetVersion::class,
            ]);
        }

        // ── In-app: all approvers across every active stage ───────────────────
        $allStages = ApprovalStage::where('is_active', true)->orderBy('order')->get();

        foreach ($allStages as $stage) {
            foreach ($this->stageApprovers($stage, $version) as $approver) {
                BudgetNotification::create([
                    'user_id'         => $approver->id,
                    'type'            => 'budget_pending_approval',
                    'subject'         => "Budget submitted — {$ownerName}",
                    'message'         => "{$ownerName} has submitted their budget (v{$version->version_number}) for {$version->period->name} and it is pending approval.",
                    'notifiable_id'   => $version->id,
                    'notifiable_type' => BudgetVersion::class,
                ]);
            }
        }

        // ── Email: first stage only ───────────────────────────────────────────
        foreach ($this->stageApprovers($firstStage, $version) as $approver) {
            $this->sendEmail(
                to:       $approver->email,
                subject:  "Budget pending your approval — {$ownerName}",
                body:     "Dear {$approver->name},\n\n{$ownerName} has submitted their budget (Version {$version->version_number}) for {$version->period->name}.\n\nPlease log in to the GOIL Budget Tool to review and approve or reject it.",
                eventKey: 'budget_submitted',
                vars:     [
                    'approver_name' => $approver->name,
                    'dept_name'     => $ownerName,
                    'period_name'   => $version->period->name,
                    'version'       => $version->version_number,
                ]
            );
        }

        $this->fireWebhooks('budget_submitted', [
            'version_id'  => $version->id,
            'dept'        => $ownerName,
            'period'      => $version->period->name,
            'version_no'  => $version->version_number,
        ]);
    }

    /**
     * Called when an approval stage passes and the next stage is activated.
     *
     * In-app + Email → next-stage approvers (action required at their stage).
     */
    public function notifyApprovers(BudgetVersion $version, ApprovalStage $stage): void
    {
        if (!\App\Models\SystemSetting::get('notify_on_approval', true)) {
            return;
        }

        $ownerName = $version->ownerName();

        foreach ($this->stageApprovers($stage, $version) as $approver) {
            BudgetNotification::create([
                'user_id'         => $approver->id,
                'type'            => 'budget_pending_approval',
                'subject'         => "Action required: budget awaiting your approval — {$ownerName}",
                'message'         => "{$ownerName}'s budget (v{$version->version_number}) for {$version->period->name} has reached your approval stage. Please review and take action.",
                'notifiable_id'   => $version->id,
                'notifiable_type' => BudgetVersion::class,
            ]);

            $this->sendEmail(
                to:       $approver->email,
                subject:  "Action required: budget awaiting your approval — {$ownerName}",
                body:     "Dear {$approver->name},\n\n{$ownerName}'s budget (Version {$version->version_number}) for {$version->period->name} has reached your approval stage.\n\nPlease log in to the GOIL Budget Tool to review and take action.",
                eventKey: 'budget_stage_reached',
                vars:     [
                    'approver_name' => $approver->name,
                    'dept_name'     => $ownerName,
                    'period_name'   => $version->period->name,
                    'version'       => $version->version_number,
                ]
            );
        }

        $this->fireWebhooks('budget_stage_reached', [
            'version_id' => $version->id,
            'dept'       => $ownerName,
            'period'     => $version->period->name,
            'stage'      => $stage->name,
        ]);
    }

    /**
     * Called when a budget is fully approved or rejected.
     *
     * In-app + Email → department/subsidiary members only.
     */
    public function notifyDepartment(BudgetVersion $version, string $decision, string $comments = ''): void
    {
        $key = $decision === 'approved' ? 'notify_on_approval' : 'notify_on_rejection';

        if (!\App\Models\SystemSetting::get($key, true)) {
            return;
        }

        $isApproved = $decision === 'approved';
        $subject    = $isApproved
            ? "Your budget has been approved — {$version->period->name}"
            : "Your budget requires revision — {$version->period->name}";

        $message = $isApproved
            ? "Your department's budget (v{$version->version_number}) for {$version->period->name} has been approved."
            : "Your department's budget (v{$version->version_number}) for {$version->period->name} has been rejected and requires revision.\n\nComments: {$comments}";

        foreach ($this->entityMembers($version) as $member) {
            BudgetNotification::create([
                'user_id'         => $member->id,
                'type'            => "budget_{$decision}",
                'subject'         => $subject,
                'message'         => $message,
                'notifiable_id'   => $version->id,
                'notifiable_type' => BudgetVersion::class,
            ]);

            $this->sendEmail(
                to:       $member->email,
                subject:  $subject,
                body:     "Dear {$member->name},\n\n{$message}\n\nPlease log in to the GOIL Budget Tool for details.",
                eventKey: "budget_{$decision}",
                vars:     [
                    'dept_name'   => $version->ownerName(),
                    'period_name' => $version->period->name,
                    'version'     => $version->version_number,
                    'comments'    => $comments,
                    'member_name' => $member->name,
                ]
            );
        }

        $this->fireWebhooks("budget_{$decision}", [
            'version_id' => $version->id,
            'dept'       => $version->ownerName(),
            'period'     => $version->period->name,
            'decision'   => $decision,
            'comments'   => $comments,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Active users who belong to the entity (dept or subsidiary) that owns the version.
     */
    private function entityMembers(BudgetVersion $version)
    {
        if ($version->subsidiary_id) {
            return User::where('subsidiary_id', $version->subsidiary_id)
                       ->where('is_active', true)
                       ->get();
        }
        return User::where('department_id', $version->department_id)
                   ->where('is_active', true)
                   ->get();
    }

    /**
     * Active approvers for a given stage, scoped to the submitting entity:
     *   - Users with a department/subsidiary → only for their own entity (e.g. department_head)
     *   - Users with no department/subsidiary → org-wide roles (finance, gceo, board)
     */
    private function stageApprovers(ApprovalStage $stage, BudgetVersion $version)
    {
        return User::role($stage->role_name)
                   ->where('is_active', true)
                   ->where(function ($q) use ($version) {
                       if ($version->subsidiary_id) {
                           $q->where('subsidiary_id', $version->subsidiary_id)
                             ->orWhere(fn($q2) => $q2->whereNull('department_id')->whereNull('subsidiary_id'));
                       } else {
                           $q->where('department_id', $version->department_id)
                             ->orWhere(fn($q2) => $q2->whereNull('department_id')->whereNull('subsidiary_id'));
                       }
                   })
                   ->get();
    }

    /**
     * Send an email, using a DB template if one exists for the given event key.
     * Falls back to the provided subject/body if no template is found.
     *
     * @param string      $to
     * @param string      $subject   Fallback subject
     * @param string      $body      Fallback body
     * @param string|null $eventKey  Looks up EmailTemplate::forEvent($eventKey) if provided
     * @param array       $vars      Template variable substitutions e.g. ['approver_name' => 'Kwame']
     */
    private function sendEmail(
        string $to,
        string $subject,
        string $body,
        ?string $eventKey = null,
        array $vars = []
    ): void {
        if (!\App\Models\SystemSetting::get('email_notifications_enabled', true)) {
            return;
        }

        // Use custom DB template if one is configured for this event
        if ($eventKey) {
            $template = EmailTemplate::forEvent($eventKey);
            if ($template) {
                $rendered = $template->render($vars);
                $subject  = $rendered['subject'];
                $body     = $rendered['body'];
            }
        }

        $signature = \App\Models\SystemSetting::get('email_signature', "Regards,\nGOIL Budget System");
        $fullBody  = $body . "\n\n" . $signature;

        try {
            Mail::raw($fullBody, function (Message $message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Exception $e) {
            \Log::error("Budget notification email failed: {$e->getMessage()}");
        }
    }

    /** Fire webhooks for a budget event. */
    private function fireWebhooks(string $event, array $payload): void
    {
        try {
            (new WebhookService())->fire($event, $payload);
        } catch (\Exception $e) {
            \Log::error("Webhook fire failed: {$e->getMessage()}");
        }
    }
}
