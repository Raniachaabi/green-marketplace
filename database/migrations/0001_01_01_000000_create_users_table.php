<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FR-001 — phone is the primary identifier in this market, not email.
            $table->string('phone', 32)->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('full_name');
            $table->string('password')->nullable();

            // FR-020 — base identity tier (CIN check) unlocks the "verified seller" badge.
            $table->timestamp('cin_verified_at')->nullable();

            $table->string('preferred_locale', 5)->default('ar');
            $table->string('status', 24)->default('active');
            $table->boolean('is_admin')->default(false);

            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->string('slug')->nullable()->unique();

            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
        });

        // FR-002 — one account, several roles. A farmer buys AND sells.
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 24);
            $table->timestamp('granted_at')->useCurrent();

            $table->unique(['user_id', 'role']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
    }
};
