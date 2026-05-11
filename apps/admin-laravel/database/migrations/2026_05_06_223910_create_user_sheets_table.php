<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_sheets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('spreadsheet_id', 128)->unique();
            $table->string('spreadsheet_url', 512)->nullable();
            $table->string('dashboard_version', 32)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sheets');
    }
};
