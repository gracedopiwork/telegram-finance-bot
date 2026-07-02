<?php

namespace App\Console\Commands;

use Database\Seeders\DiagnosticContentSeeder;
use Illuminate\Console\Command;

class SyncDiagnosticQuestionsCommand extends Command
{
    protected $signature = 'diagnostic:sync-questions';

    protected $description = 'Sinkronkan soal diagnostik 1–16 dan FTSA 1–32 dari config ke database';

    public function handle(): int
    {
        $this->info('Menyinkronkan soal diagnostik & FTSA...');
        $seeder = new DiagnosticContentSeeder;
        $seeder->syncCanonicalQuestions();
        $seeder->syncFtsaQuestions();
        $this->info('Selesai. Soal diagnostik dan FTSA sudah diperbarui.');

        return self::SUCCESS;
    }
}
