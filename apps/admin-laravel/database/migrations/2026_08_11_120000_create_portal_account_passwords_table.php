<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portal_account_passwords')) {
            return;
        }

        Schema::create('portal_account_passwords', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->timestamp('password_set_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_account_passwords');
    }
};
