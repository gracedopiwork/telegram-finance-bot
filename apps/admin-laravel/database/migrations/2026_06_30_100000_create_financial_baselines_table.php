<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_baselines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->timestamp('assessed_at');
            $table->timestamp('next_review_at');
            $table->unsignedTinyInteger('financial_stage_score')->default(0);
            $table->string('financial_stage', 32);
            $table->string('stage_label', 64);
            $table->unsignedTinyInteger('ftsa_chd')->default(0);
            $table->unsignedTinyInteger('ftsa_rvd')->default(0);
            $table->unsignedTinyInteger('ftsa_ssd')->default(0);
            $table->unsignedTinyInteger('ftsa_esd')->default(0);
            $table->string('dominant_archetype', 16);
            $table->string('dominant_archetype_label', 64);
            $table->string('chd_level', 32)->nullable();
            $table->string('rvd_level', 32)->nullable();
            $table->string('ssd_level', 32)->nullable();
            $table->string('esd_level', 32)->nullable();
            $table->json('answers_json');
            $table->timestamps();

            $table->index(['telegram_user_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_baselines');
    }
};
