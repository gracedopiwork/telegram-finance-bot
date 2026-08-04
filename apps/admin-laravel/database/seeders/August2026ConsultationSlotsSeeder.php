<?php

namespace Database\Seeders;

use App\Models\CpAdvisor;
use App\Services\ConsultationSlotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal available Agustus 2026 dari Tim YFD (WA).
 * Sesi ±1 jam, slot ditampilkan berjarak 2 jam dalam window availability.
 * Pastikan advisor Ayuti & Catherine ada (buat otomatis jika belum).
 */
class August2026ConsultationSlotsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('consultation_slots') || ! Schema::hasTable('cp_advisors')) {
            $this->command?->warn('Tabel consultation_slots / cp_advisors belum ada. Jalankan migrate dulu.');

            return;
        }

        $ayuti = $this->ensureAdvisor([
            'name' => 'dr. Ayuti Bulaan',
            'role_label' => 'Founder & Financial Doctor',
            'badges' => ['Dokter Umum', 'QWP', 'Founder'],
            'years_exp' => '2018',
            'spec_short' => 'Founder YFD',
            'spec_icon' => 'favorite',
            'spec_long' => 'Dokter umum dengan ketertarikan mendalam pada dunia finansial sejak 2018.',
            'tag' => 'Founder',
            'sort' => 1,
            'is_active' => true,
        ], ['%Ayuti%', '%Bulaan%']);

        $catherine = $this->ensureAdvisor([
            'name' => 'dr. Catherine',
            'role_label' => 'Co-Founder & Financial Doctor',
            'badges' => ['Dokter Umum', 'QWP', 'Co-Founder'],
            'years_exp' => 'YFD',
            'spec_short' => 'Co-Founder YFD',
            'spec_icon' => 'healing',
            'spec_long' => 'Bersama dr. Ayuti membangun YFD sebagai pusat kesehatan finansial pertama di Indonesia.',
            'tag' => 'Co-Founder',
            'sort' => 2,
            'is_active' => true,
        ], ['%Catherine%', '%Cathrina%']);

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

        $this->command?->info("Catherine ({$catherine->name} #{$catherine->id}): {$nCatherine} slot baru.");
        $this->command?->info("Ayuti ({$ayuti->name} #{$ayuti->id}): {$nAyuti} slot baru.");
        $this->command?->info('Overlap / duplikat otomatis dilewati.');
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  list<string>  $likePatterns
     */
    private function ensureAdvisor(array $defaults, array $likePatterns): CpAdvisor
    {
        $query = CpAdvisor::query();
        $query->where(function ($q) use ($likePatterns, $defaults) {
            $q->where('name', $defaults['name']);
            foreach ($likePatterns as $pattern) {
                $q->orWhere('name', 'like', $pattern);
            }
        });

        $existing = $query->orderBy('id')->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return $existing->fresh();
        }

        $advisor = CpAdvisor::create($defaults);
        $this->command?->warn("Advisor dibuat otomatis: {$advisor->name} (#{$advisor->id}).");

        return $advisor;
    }
}
