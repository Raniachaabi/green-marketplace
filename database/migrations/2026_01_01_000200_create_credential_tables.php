<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The verification vocabulary. Seeded, not hard-coded, so a new
        // regulatory requirement is a row rather than a deploy.
        Schema::create('credential_types', function (Blueprint $table) {
            $table->string('code', 48)->primary();
            $table->json('label');                       // {ar,fr,en}
            $table->json('description')->nullable();
            $table->string('issuing_body')->nullable();
            $table->string('legal_reference')->nullable();  // e.g. "Loi 99-42"
            $table->boolean('requires_expiry')->default(true);
            $table->boolean('requires_number')->default(true);
            $table->boolean('requires_document')->default(true);
            $table->timestamps();
        });

        // FR-024 — every badge links to a disclosure page saying exactly
        // what was checked, by whom, and when.
        Schema::create('badges', function (Blueprint $table) {
            $table->string('code', 48)->primary();
            $table->json('label');
            $table->json('description')->nullable();
            $table->string('icon', 48)->nullable();
            $table->string('colour', 16)->default('leaf');
            $table->timestamps();
        });

        Schema::create('badge_credential_type', function (Blueprint $table) {
            $table->string('badge_code', 48);
            $table->string('credential_type_code', 48);

            $table->primary(['badge_code', 'credential_type_code'], 'badge_credential_type_pk');
            $table->foreign('badge_code')->references('code')->on('badges')->cascadeOnDelete();
            $table->foreign('credential_type_code')->references('code')->on('credential_types')->cascadeOnDelete();
        });

        // FR-021 / FR-022 / FR-025 — the credential itself.
        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('credential_type_code', 48);

            $table->string('number', 128)->nullable();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();

            // NFR-08 — private disk, signed URLs only.
            $table->string('document_path')->nullable();

            $table->string('status', 24)->default('pending'); // pending|approved|rejected|expired
            $table->foreignUuid('verified_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Which reminder thresholds have already fired, so we never spam.
            $table->json('reminders_sent')->nullable();

            $table->timestamps();

            $table->foreign('credential_type_code')->references('code')->on('credential_types')->cascadeOnDelete();
            $table->index(['status', 'expires_at']);
            $table->index('credential_type_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('badge_credential_type');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('credential_types');
    }
};
