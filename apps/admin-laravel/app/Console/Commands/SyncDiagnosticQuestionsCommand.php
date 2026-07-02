<?php

namespace App\Console\Commands;

use Database\Seeders\DiagnosticContentSeeder;
use Illuminate\Console\Command;

class SyncDiagnosticQuestionsCommand extends Command
{
    protected $signature = 'diagnostic:sync-questions';

    protected $description = 'Sinkronkan soal diagnostik 1–16 dari config canonical ke database';

    public function handle(): int
    {
        $this->info('Menyinkronkan soal diagnostik...');
        (new DiagnosticContentSeeder)->syncCanonicalQuestions();
        $this->info('Selesai. Soal dan pilihan jawaban sudah diperbarui.');

        return self::SUCCESS;
    }
}
