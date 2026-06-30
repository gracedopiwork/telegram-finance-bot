<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->timestamp('recorded_at');
            $table->enum('type', ['Pemasukan', 'Pengeluaran']);
            $table->string('category', 64);
            $table->string('sub_category', 128);
            $table->unsignedBigInteger('amount');
            $table->string('nature', 32);
            $table->string('mood', 32);
            $table->boolean('is_impulsive')->default(false);
            $table->text('notes');
            $table->enum('source', ['manual', 'receipt_photo'])->default('manual');
            $table->timestamps();

            $table->index(['telegram_user_id', 'recorded_at']);
            $table->index(['telegram_user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_transactions');
    }
};
