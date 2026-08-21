<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 §15 — real, deterministic personalization needs real
        // browsing history. One row per (user, listing); a repeat view
        // just bumps viewed_at rather than growing the table forever.
        Schema::create('recently_viewed_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');

            $table->unique(['user_id', 'listing_id']);
            $table->index(['user_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_viewed_listings');
    }
};
