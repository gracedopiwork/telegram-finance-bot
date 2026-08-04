<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained('cp_advisors')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('open'); // open|held|confirmed|cancelled
            $table->timestamp('held_until')->nullable();
            $table->string('booking_code', 32)->nullable()->unique();
            $table->string('service_type', 40)->nullable(); // standard|recovery
            $table->string('guest_name', 200)->nullable();
            $table->string('guest_phone', 40)->nullable();
            $table->unsignedTinyInteger('guest_age')->nullable();
            $table->string('financial_stage', 40)->nullable();
            $table->string('situation', 200)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['advisor_id', 'starts_at']);
            $table->index(['status', 'held_until']);
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_slots');
    }
};
