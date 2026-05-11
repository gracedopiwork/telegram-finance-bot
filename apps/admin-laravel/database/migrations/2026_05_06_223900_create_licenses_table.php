<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->string('license_key', 64)->unique();
            $table->string('plan', 32)->default('basic');
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');
            $table->dateTime('expires_at')->nullable();
            $table->integer('max_accounts')->default(1);
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('assigned_username')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
