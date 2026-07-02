<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ftsa_questions')) {
            return;
        }

        Schema::create('ftsa_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('question_num')->unique();
            $table->string('domain_key', 8);
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('domain_key', 'ftsa_questions_domain_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftsa_questions');
    }
};
