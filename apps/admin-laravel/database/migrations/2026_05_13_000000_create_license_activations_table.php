<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('license_activations')) {
            return;
        }

        Schema::create('license_activations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('telegram_username', 255)->nullable();
            $table->dateTime('activated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};
