<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_key', 32)->unique();
            $table->string('label', 64);
            $table->string('emoji', 8)->nullable();
            $table->string('phase', 32)->nullable();
            $table->string('diagnosis', 255)->nullable();
            $table->string('risk_label', 128)->default('Risiko keuangan');
            $table->text('risk_description')->nullable();
            $table->string('panel_color', 16)->default('#7EC8C8');
            $table->string('illustration_url', 512)->nullable();
            $table->unsignedTinyInteger('score_min')->default(0);
            $table->unsignedTinyInteger('score_max')->default(39);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('diagnostic_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_key', 32)->unique();
            $table->string('section', 128);
            $table->text('text');
            $table->text('note')->nullable();
            $table->boolean('is_scored')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('diagnostic_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_question_id')->constrained()->cascadeOnDelete();
            $table->string('option_key', 64);
            $table->string('label', 512);
            $table->smallInteger('score')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['diagnostic_question_id', 'option_key']);
        });

        if (class_exists(\Database\Seeders\DiagnosticContentSeeder::class)) {
            (new \Database\Seeders\DiagnosticContentSeeder)->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_question_options');
        Schema::dropIfExists('diagnostic_questions');
        Schema::dropIfExists('diagnostic_stages');
    }
};
