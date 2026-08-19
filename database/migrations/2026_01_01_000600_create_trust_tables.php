<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-100 — no review without a fulfilled transaction.
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('order_line_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('target_type', 24);            // listing|seller
            $table->uuid('target_id');
            $table->unsignedTinyInteger('rating');
            $table->json('dimensions')->nullable();
            $table->text('body')->nullable();
            $table->json('media_paths')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->unique(['author_user_id', 'order_line_id'], 'reviews_one_per_line');
        });

        // FR-104 — reportable by any buyer of that listing, with lot capture.
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('order_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot_number', 64)->nullable();
            $table->string('type', 32);                   // quality|safety|counterfeit|non_conformity
            $table->string('severity', 16)->default('low');
            $table->string('status', 24)->default('open');
            $table->text('body')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index('lot_number');
        });

        // FR-105 — the recall console. Identify a lot, freeze it, notify
        // everyone who bought it, log every step.
        Schema::create('recalls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number', 64);
            $table->text('reason');
            $table->foreignUuid('initiated_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('open'); // open|notified|closed
            $table->timestamps();

            $table->index(['listing_id', 'lot_number']);
        });

        Schema::create('recall_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recall_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_line_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16)->default('sms');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['recall_id', 'order_line_id'], 'recall_notif_unique');
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 64);
            $table->string('status', 24)->default('open');
            $table->text('body')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // FR-103 — versioned agreements with timestamped acceptance.
        Schema::create('agreement_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 24);                   // seller|buyer|consent
            $table->string('version', 16);
            $table->json('title');
            $table->string('body_path')->nullable();
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['type', 'version']);
        });

        Schema::create('agreement_acceptances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agreement_version_id')->constrained()->cascadeOnDelete();
            $table->timestamp('accepted_at')->useCurrent();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'agreement_version_id'], 'agreement_acceptance_unique');
        });

        // FR-108 / NFR-12 — append-only. If you ever need to demonstrate
        // diligence, this table is the argument.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 64);
            $table->uuid('entity_id')->nullable();
            $table->string('action', 64);
            $table->json('context')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('agreement_acceptances');
        Schema::dropIfExists('agreement_versions');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('recall_notifications');
        Schema::dropIfExists('recalls');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('reviews');
    }
};
