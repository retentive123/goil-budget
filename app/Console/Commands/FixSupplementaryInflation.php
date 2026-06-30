<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplementaryBudget;
use App\Models\BudgetLineItem;

class FixSupplementaryInflation extends Command
{
    protected $signature   = 'fix:supplementary-inflation {--dry-run}';
    protected $description = 'Reverse the q4_amount inflation caused by the old supplementary approval bug';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        $approved = SupplementaryBudget::where('status', 'approved')->get();

        $this->info("Found {$approved->count()} approved supplementary record(s) to check.");

        foreach ($approved as $supp) {
            $item = BudgetLineItem::find($supp->budget_line_item_id);
            if (!$item) continue;

            // original_amount was captured at request time — this is the true original total
            $expectedOriginalTotal = $supp->original_amount;
            $currentTotal          = $item->total_amount;

            // If current total already includes this supplementary amount, it was inflated
            if ($currentTotal > $expectedOriginalTotal) {
                $diff = $currentTotal - $expectedOriginalTotal;

                $this->line(
                    "Line {$item->id} ({$item->accountCode->code}): " .
                    "current={$currentTotal}, original={$expectedOriginalTotal}, " .
                    "will subtract={$diff} from q4_amount"
                );

                if (!$dryRun) {
                    $item->update([
                        'q4_amount' => max(0, $item->q4_amount - $diff),
                    ]);
                }
            }
        }

        $this->info($dryRun
            ? 'Dry run complete — no changes made. Remove --dry-run to apply.'
            : 'Correction applied. q1-q4_amount now reflect original approved budget only.'
        );
    }
}
