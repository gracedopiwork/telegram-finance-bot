<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('portal_user_preferences')) {
            return;
        }

        Schema::create('portal_user_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('timezone_source', 16)->default('default'); // default | auto | manual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_user_preferences');
    }
};
