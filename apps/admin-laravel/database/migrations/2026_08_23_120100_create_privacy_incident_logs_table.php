<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timestamp "pertama kali insiden diketahui" untuk hitung mundur 72 jam (Bagian 11).
 * Diisi manual / ops — tooling deteksi otomatis masih SOP.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('privacy_incident_logs')) {
            return;
        }

        Schema::create('privacy_incident_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('first_known_at');
            $table->string('summary', 500);
            $table->text('notes')->nullable();
            $table->timestamp('users_notified_at')->nullable();
            $table->timestamp('authority_reported_at')->nullable();
            $table->string('recorded_by', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_incident_logs');
    }
};
