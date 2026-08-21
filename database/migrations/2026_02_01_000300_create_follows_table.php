<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 §3 — a seller storefront in this app is always a User
        // (/boutique/{user:slug} — organizations have no storefront of their
        // own today), so a follow is buyer-user -> seller-user.
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('follower_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_user_id', 'seller_user_id']);
            $table->index('seller_user_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE follows ADD CONSTRAINT follows_not_self_chk CHECK (follower_user_id <> seller_user_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
