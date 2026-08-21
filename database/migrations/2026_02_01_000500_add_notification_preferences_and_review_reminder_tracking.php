<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 §1 — notification preferences. Transactional notifications
        // (order/shipment/credential/moderation) stay always-on: a buyer
        // cannot silently miss "your order shipped". What is genuinely
        // discretionary — a followed seller's activity, restock alerts, and
        // platform announcements — is what these two flags actually gate.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_social')->default(true)->after('status');
            $table->boolean('notify_announcements')->default(true)->after('notify_social');
        });

        // One-shot tracking so a delivered order line is only ever reminded
        // once, the same pattern as credentials.reminders_sent.
        Schema::table('order_lines', function (Blueprint $table) {
            $table->timestamp('review_reminder_sent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn('review_reminder_sent_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_social', 'notify_announcements']);
        });
    }
};
