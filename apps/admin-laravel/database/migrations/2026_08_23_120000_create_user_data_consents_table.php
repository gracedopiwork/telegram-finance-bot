<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lapis 2 Informed Consent — record terikat user_id (revisi 15 Agustus 2026, Bagian 17).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_data_consents')) {
            return;
        }

        Schema::create('user_data_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->index();
            $table->string('consent_version', 32);
            $table->string('status', 16)->default('accepted'); // accepted | withdrawn
            $table->string('method', 16); // bot | web
            $table->string('consent_text_version', 64);
            $table->json('checkbox_ids')->nullable();
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['telegram_user_id', 'consent_version'], 'user_data_consents_user_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_data_consents');
    }
};
