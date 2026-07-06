<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_guidance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('guidance_type', 40);
            $table->string('period_key', 20);
            $table->string('ai_provider', 20)->default('claude');
            $table->json('payload');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['telegram_user_id', 'guidance_type', 'period_key'], 'portal_guidance_user_type_period');
            $table->index(['guidance_type', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_guidance_snapshots');
    }
};
