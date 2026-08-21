<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('seller_response')->nullable()->after('body');
            $table->timestamp('seller_response_at')->nullable()->after('seller_response');
        });

        // Phase 2 §16 — review reporting. Any signed-in user can flag a
        // review once; the review itself is never hidden automatically —
        // an admin decides (ReviewResource already has the moderation tools).
        Schema::create('review_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('review_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 32);
            $table->timestamps();

            $table->unique(['review_id', 'reporter_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['seller_response', 'seller_response_at']);
        });
    }
};
