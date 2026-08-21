<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\OrderLine;
use App\Notifications\ReviewReminder;
use Illuminate\Console\Command;

/**
 * Phase 2 §1 — runs daily. A buyer is reminded once, a fixed number of days
 * after delivery, only for an order line that is still unreviewed and only
 * ever once (review_reminder_sent_at is a one-shot flag, same pattern as
 * credentials.reminders_sent).
 */
class SendReviewReminders extends Command
{
    protected $signature = 'reviews:send-reminders {--dry-run : Report without sending anything}';

    protected $description = 'Remind buyers to review order lines that were delivered a while ago and never reviewed';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = (int) config('marketplace.review_reminder_days');

        // Orders have no separate "delivered_at" column — updated_at is a
        // true record of when the status last changed, which for a
        // Delivered order is the delivery moment itself.
        $due = OrderLine::query()
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Delivered)
                ->where('updated_at', '<=', now()->subDays($days)))
            ->whereNull('review_reminder_sent_at')
            ->whereDoesntHave('review')
            ->with('order.buyer')
            ->get();

        $sent = 0;

        foreach ($due as $line) {
            $buyer = $line->order?->buyer;

            if (! $buyer) {
                continue;
            }

            if ($dryRun) {
                $this->line("Would remind {$buyer->full_name} to review {$line->title()}");

                continue;
            }

            $buyer->notify(new ReviewReminder($line));
            $line->forceFill(['review_reminder_sent_at' => now()])->save();
            $sent++;
        }

        $this->info(($dryRun ? '[dry run] ' : '')."{$sent} review reminder(s) sent.");

        return self::SUCCESS;
    }
}
