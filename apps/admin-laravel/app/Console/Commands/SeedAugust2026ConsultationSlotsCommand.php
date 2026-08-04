<?php

namespace App\Console\Commands;

use Database\Seeders\August2026ConsultationSlotsSeeder;
use Illuminate\Console\Command;

class SeedAugust2026ConsultationSlotsCommand extends Command
{
    protected $signature = 'consultation:seed-august-2026';

    protected $description = 'Masukkan jadwal available Agustus 2026 (dr Catherine & dr Ayuti) dari WA Tim YFD';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => August2026ConsultationSlotsSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
