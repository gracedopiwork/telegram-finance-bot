<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 32)->unique();
            $table->string('full_name', 120);
            $table->string('email', 190);
            $table->string('telegram_username', 120)->nullable();
            $table->string('plan', 32);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 8)->default('IDR');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('payment_gateway', 32)->default('midtrans');
            $table->string('payment_reference', 128)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
