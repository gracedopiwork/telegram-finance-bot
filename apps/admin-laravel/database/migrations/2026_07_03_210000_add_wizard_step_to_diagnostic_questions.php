<?php

use Database\Seeders\DiagnosticContentSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('diagnostic_questions')) {
            return;
        }

        if (! Schema::hasColumn('diagnostic_questions', 'wizard_step')) {
            Schema::table('diagnostic_questions', function (Blueprint $table) {
                $table->unsignedTinyInteger('wizard_step')->default(1)->after('question_key');
                $table->index('wizard_step');
            });
        }

        (new DiagnosticContentSeeder)->syncCanonicalQuestions();
    }

    public function down(): void
    {
        if (Schema::hasTable('diagnostic_questions') && Schema::hasColumn('diagnostic_questions', 'wizard_step')) {
            Schema::table('diagnostic_questions', function (Blueprint $table) {
                $table->dropIndex(['wizard_step']);
                $table->dropColumn('wizard_step');
            });
        }
    }
};
