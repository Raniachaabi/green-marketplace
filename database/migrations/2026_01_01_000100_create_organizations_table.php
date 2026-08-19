<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-004 — associations, GDA, SMSA, companies and institutions (schools).
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 24);              // association|gda|smsa|company|institution
            $table->string('legal_name');
            $table->string('slug')->unique();
            $table->string('matricule_fiscal', 32)->nullable();
            $table->string('rne_number', 64)->nullable();
            $table->string('statutes_doc_path')->nullable();
            $table->text('story')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('governorate', 32)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('governorate');
        });

        // FR-005 — payouts are split between members by configured share.
        Schema::create('organization_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 24)->default('member');   // owner|admin|member
            $table->decimal('revenue_share_pct', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->string('governorate', 32);
            $table->string('delegation', 64)->nullable();
            $table->string('locality')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 8)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('governorate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};
