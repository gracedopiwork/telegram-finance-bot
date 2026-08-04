<?php

namespace App\Console\Commands;

use App\Services\ConsultationSlotService;
use Illuminate\Console\Command;

class ReleaseExpiredConsultationHoldsCommand extends Command
{
    protected $signature = 'consultation:release-expired-holds';

    protected $description = 'Lepas soft-hold jadwal konsultasi yang sudah lewat masa tunggu pembayaran';

    public function handle(ConsultationSlotService $slots): int
    {
        $n = $slots->releaseExpiredHolds();
        $this->info("Released {$n} expired hold(s).");

        return self::SUCCESS;
    }
}
