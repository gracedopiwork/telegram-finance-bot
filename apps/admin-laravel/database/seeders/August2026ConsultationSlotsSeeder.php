<?php

namespace Database\Seeders;

use App\Models\CpAdvisor;
use App\Services\ConsultationSlotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal available Agustus 2026 dari Tim YFD (WA).
 * Sesi ±1 jam, slot ditampilkan berjarak 2 jam dalam window availability.
 */
class August2026ConsultationSlotsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('consultation_slots') || ! Schema::hasTable('cp_advisors')) {
            $this->command?->warn('Tabel consultation_slots / cp_advisors belum ada.');

            return;
        }

        $catherine = CpAdvisor::query()
            ->where('name', 'like', '%Catherine%')
            ->orderBy('id')
            ->first();
        $ayuti = CpAdvisor::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%Ayuti%')
                    ->orWhere('name', 'like', '%Bulaan%');
            })
            ->orderBy('id')
            ->first();

        if (! $catherine || ! $ayuti) {
            $this->command?->error('Advisor Catherine / Ayuti tidak ditemukan. Seed CompanyProfile dulu.');

            return;
        }

        $service = app(ConsultationSlotService::class);

        $catherineWindows = [
            ['date' => '2026-08-04', 'start' => '11:00', 'end' => '18:00'],
            ['date' => '2026-08-05', 'start' => '09:00', 'end' => '14:00'],
            ['date' => '2026-08-06', 'start' => '09:00', 'end' => '14:00'],
            ['date' => '2026-08-07', 'start' => '09:00', 'end' => '14:00'],
            ['date' => '2026-08-10', 'start' => '11:00', 'end' => '17:00'],
            ['date' => '2026-08-11', 'start' => '09:00', 'end' => '13:00'],
            ['date' => '2026-08-13', 'start' => '09:00', 'end' => '14:00'],
            ['date' => '2026-08-14', 'start' => '12:00', 'end' => '20:00'],
            ['date' => '2026-08-16', 'start' => '13:00', 'end' => '14:00'],
            ['date' => '2026-08-25', 'start' => '11:00', 'end' => '17:00'],
            ['date' => '2026-08-28', 'start' => '09:00', 'end' => '14:00'],
            ['date' => '2026-08-30', 'start' => '09:00', 'end' => '14:00'],
        ];

        $ayutiWindows = [
            ['date' => '2026-08-04', 'start' => '18:00', 'end' => '21:00'],
            ['date' => '2026-08-08', 'start' => '20:00', 'end' => '22:00'],
            ['date' => '2026-08-09', 'start' => '14:00', 'end' => '22:00'],
            ['date' => '2026-08-11', 'start' => '13:00', 'end' => '22:00'],
            ['date' => '2026-08-12', 'start' => '13:00', 'end' => '22:00'],
            ['date' => '2026-08-14', 'start' => '13:00', 'end' => '22:00'],
            ['date' => '2026-08-18', 'start' => '13:00', 'end' => '22:00'],
            ['date' => '2026-08-22', 'start' => '13:00', 'end' => '18:00'],
            ['date' => '2026-08-23', 'start' => '13:00', 'end' => '22:00'],
            ['date' => '2026-08-30', 'start' => '13:00', 'end' => '22:00'],
        ];

        $nCatherine = $service->createSlotsFromWindows($catherine, $catherineWindows);
        $nAyuti = $service->createSlotsFromWindows($ayuti, $ayutiWindows);

        $this->command?->info("Catherine ({$catherine->name}): {$nCatherine} slot baru.");
        $this->command?->info("Ayuti ({$ayuti->name}): {$nAyuti} slot baru.");
        $this->command?->info('Overlap / duplikat otomatis dilewati.');
    }
}
