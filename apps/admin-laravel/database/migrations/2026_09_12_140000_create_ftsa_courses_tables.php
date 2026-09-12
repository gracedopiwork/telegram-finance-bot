<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ftsa_courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('company_name', 160);
            $table->string('registration_code', 64)->unique();
            $table->date('workshop_at');
            $table->unsignedSmallInteger('access_days')->default(30);
            $table->unsignedInteger('max_registrants')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ftsa_course_registrants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ftsa_course_id')->constrained('ftsa_courses')->cascadeOnDelete();
            $table->string('full_name', 120);
            $table->string('email', 190);
            $table->string('phone', 32)->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('access_ends_at');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->timestamps();

            $table->unique(['ftsa_course_id', 'email']);
            $table->index('email');
            $table->index('access_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftsa_course_registrants');
        Schema::dropIfExists('ftsa_courses');
    }
};
