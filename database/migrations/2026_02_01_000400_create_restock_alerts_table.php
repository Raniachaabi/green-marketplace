<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 §5 — "notify me when available". A row here is fulfilled
        // (notified_at set) rather than deleted once the notification goes
        // out, so a buyer's subscription history is preserved and a fresh
        // subscription is still possible the next time the item runs out.
        Schema::create('restock_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'notified_at']);
        });

        // A plain unique(user_id, listing_id) would block a second, later
        // subscription after the first one was already fulfilled — this
        // only blocks a *duplicate pending* subscription.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX restock_alerts_pending_unique ON restock_alerts (user_id, listing_id) WHERE notified_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_alerts');
    }
};
