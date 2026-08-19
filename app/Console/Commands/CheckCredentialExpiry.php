<?php

namespace App\Console\Commands;

use App\Models\Credential;
use App\Services\CredentialManager;
use Illuminate\Console\Command;

/**
 * FR-025 / FR-026 — runs daily.
 *
 * Two jobs: warn sellers before a credential lapses, and enforce the moment
 * it does. The enforcement half is the one that matters; the warnings exist
 * so enforcement rarely has to fire.
 */
class CheckCredentialExpiry extends Command
{
    protected $signature = 'credentials:check-expiry {--dry-run : Report without changing anything}';

    protected $description = 'Send credential expiry reminders and suspend listings whose credentials have lapsed';

    public function handle(CredentialManager $manager): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 1. Enforce — anything already past its date.
        $expired = $manager->findNewlyExpired();
        $suspendedTotal = 0;

        foreach ($expired as $credential) {
            $holder = $credential->holder()?->displayName() ?? '—';

            if ($dryRun) {
                $this->warn("Would expire: {$credential->credential_type_code} ({$holder})");

                continue;
            }

            $manager->markExpired($credential);
            $this->line("Expired: {$credential->credential_type_code} ({$holder})");
        }

        // 2. Warn — anything approaching its date.
        $horizon = max(config('marketplace.credential_reminder_days'));
        $reminded = 0;

        Credential::expiringWithin($horizon)
            ->with(['user', 'organization', 'credentialType'])
            ->chunkById(200, function ($chunk) use ($manager, $dryRun, &$reminded) {
                foreach ($chunk as $credential) {
                    foreach ($manager->dueReminderThresholds($credential) as $threshold) {
                        if ($dryRun) {
                            $this->line("Would remind at T-{$threshold}: {$credential->credential_type_code}");

                            continue;
                        }

                        // Wire a real notification here (SMS / WhatsApp).
                        // Recording it first means a failed send is visible
                        // rather than silently retried forever.
                        $manager->recordReminderSent($credential, $threshold);
                        $reminded++;
                    }
                }
            });

        $this->newLine();
        $this->info(sprintf(
            '%s%d credential(s) expired, %d reminder(s) sent.',
            $dryRun ? '[dry run] ' : '',
            $expired->count(),
            $reminded,
        ));

        return self::SUCCESS;
    }
}
