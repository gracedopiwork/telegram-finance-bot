<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resume onboarding bot (Welcome → Kenalan / Langsung → Home).
 * Sumber: YFD First Aid Onboarding revisi 15 Agustus 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_onboarding_states')) {
            return;
        }

        Schema::create('user_onboarding_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('step', 32)->default('welcome');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_states');
    }
};
