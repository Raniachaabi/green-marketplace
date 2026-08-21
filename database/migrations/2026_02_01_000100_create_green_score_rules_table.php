<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 — the Green Score. Every point a listing earns traces back
        // to one row here and one piece of real data (a green attribute, a
        // verified badge, a known governorate) — never a free-text claim.
        Schema::create('green_score_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->json('name');                 // {ar,fr,en}
            $table->json('description')->nullable();
            $table->string('icon', 8)->nullable();
            $table->string('category', 32);       // origin|certification|packaging|producer|craftsmanship

            // What real data this rule checks — see GreenScoreCheckType.
            $table->string('check_type', 32);
            $table->string('check_value', 64)->nullable();

            $table->unsignedInteger('points');
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('green_score_rules');
    }
};
