<?php

use Illuminate\Support\Facades\Schedule;

// FR-025 / FR-026 — credential expiry reminders and automatic listing suspension.
Schedule::command('credentials:check-expiry')->dailyAt('02:00');

// FR-069 — participant medical data is purged on a fixed schedule.
Schedule::command('participants:purge')->dailyAt('03:00');

// Phase 2 §1 — nudge a buyer to review a delivery a few days after the fact.
Schedule::command('reviews:send-reminders')->dailyAt('09:00');
