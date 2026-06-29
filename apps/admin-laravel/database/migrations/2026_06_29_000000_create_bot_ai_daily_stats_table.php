<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_ai_daily_stats', function (Blueprint $table) {
            $table->date('stat_date')->primary();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('rate_limit_count')->default(0);
            $table->unsignedInteger('fallback_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('last_rate_limit_at')->nullable();
            $table->string('last_detail', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_ai_daily_stats');
    }
};
