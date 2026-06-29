<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_usage', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_user_id');
            $table->char('usage_month', 7);
            $table->unsignedInteger('cost_idr')->default(0);
            $table->unsignedInteger('text_parse_count')->default(0);
            $table->unsignedInteger('vision_parse_count')->default(0);
            $table->timestamp('quota_exhausted_notified_at')->nullable();
            $table->timestamps();

            $table->primary(['telegram_user_id', 'usage_month']);
            $table->index('usage_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_usage');
    }
};
