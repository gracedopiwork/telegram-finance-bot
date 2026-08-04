<?php

namespace App\Services;

use App\Models\ConsultationSlot;
use App\Models\CpAdvisor;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationSlotService
{
    public function releaseExpiredHolds(): int
    {
        return ConsultationSlot::query()
            ->where('status', ConsultationSlot::STATUS_HELD)
            ->whereNotNull('held_until')
            ->where('held_until', '<', now())
            ->update([
                'status' => ConsultationSlot::STATUS_OPEN,
                'held_until' => null,
                'booking_code' => null,
                'service_type' => null,
                'guest_name' => null,
                'guest_phone' => null,
                'guest_age' => null,
                'financial_stage' => null,
                'situation' => null,
                'notes' => null,
                'confirmed_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Soft-hold a slot for the guest, then return WA redirect URL.
     *
     * @param  array{
     *   name: string,
     *   phone?: ?string,
     *   age?: ?int,
     *   stage?: ?string,
     *   situation?: ?string,
     *   condition?: ?string,
     *   service_type?: string
     * }  $guest
     */
    public function holdAndBuildWaUrl(ConsultationSlot $slot, array $guest): string
    {
        $this->releaseExpiredHolds();

        $held = DB::transaction(function () use ($slot, $guest) {
            /** @var ConsultationSlot|null $locked */
            $locked = ConsultationSlot::query()
                ->whereKey($slot->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isOpen() || $locked->starts_at->isPast()) {
                throw ValidationException::withMessages([
                    'slot_id' => 'Slot ini sudah tidak tersedia. Silakan pilih jadwal lain.',
                ]);
            }

            $locked->fill([
                'status' => ConsultationSlot::STATUS_HELD,
                'held_until' => now()->addMinutes(ConsultationSlot::HOLD_MINUTES),
                'booking_code' => ConsultationSlot::generateBookingCode(),
                'service_type' => $guest['service_type'] ?? 'standard',
                'guest_name' => $guest['name'],
                'guest_phone' => $guest['phone'] ?? null,
                'guest_age' => $guest['age'] ?? null,
                'financial_stage' => $guest['stage'] ?? null,
                'situation' => $guest['situation'] ?? null,
                'notes' => $guest['condition'] ?? null,
            ]);
            $locked->save();

            return $locked->fresh(['advisor']);
        });

        return $this->whatsAppUrl($held);
    }

    public function confirmPayment(ConsultationSlot $slot): void
    {
        $this->releaseExpiredHolds();

        if ($slot->fresh()?->status === ConsultationSlot::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'slot' => 'Hold sudah kedaluwarsa; slot kembali tersedia.',
            ]);
        }

        if (! in_array($slot->status, [ConsultationSlot::STATUS_HELD, ConsultationSlot::STATUS_CONFIRMED], true)) {
            throw ValidationException::withMessages([
                'slot' => 'Status slot tidak bisa dikonfirmasi.',
            ]);
        }

        $slot->update([
            'status' => ConsultationSlot::STATUS_CONFIRMED,
            'held_until' => null,
            'confirmed_at' => now(),
        ]);
    }

    public function releaseHold(ConsultationSlot $slot): void
    {
        if (! in_array($slot->status, [ConsultationSlot::STATUS_HELD, ConsultationSlot::STATUS_CONFIRMED], true)) {
            return;
        }

        $slot->update([
            'status' => ConsultationSlot::STATUS_OPEN,
            'held_until' => null,
            'booking_code' => null,
            'service_type' => null,
            'guest_name' => null,
            'guest_phone' => null,
            'guest_age' => null,
            'financial_stage' => null,
            'situation' => null,
            'notes' => null,
            'confirmed_at' => null,
        ]);
    }

    public function cancelSlot(ConsultationSlot $slot): void
    {
        $slot->update([
            'status' => ConsultationSlot::STATUS_CANCELLED,
            'held_until' => null,
        ]);
    }

    /**
     * @param  list<string>  $times  e.g. ["09:00","10:00"]
     * @return int number created
     */
    public function createSlotsForDate(CpAdvisor $advisor, string $date, array $times, int $durationMinutes = 60): int
    {
        $created = 0;
        foreach ($times as $time) {
            $time = trim((string) $time);
            if ($time === '') {
                continue;
            }
            $starts = \Carbon\Carbon::parse($date.' '.$time);
            $ends = $starts->copy()->addMinutes($durationMinutes);

            $overlap = ConsultationSlot::query()
                ->where('advisor_id', $advisor->id)
                ->where('status', '!=', ConsultationSlot::STATUS_CANCELLED)
                ->where('starts_at', '<', $ends)
                ->where('ends_at', '>', $starts)
                ->exists();

            if ($overlap) {
                continue;
            }

            ConsultationSlot::create([
                'advisor_id' => $advisor->id,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'status' => ConsultationSlot::STATUS_OPEN,
            ]);
            $created++;
        }

        return $created;
    }

    private function whatsAppUrl(ConsultationSlot $slot): string
    {
        $wa = Setting::val('contact.wa_number', '6285111228911') ?: '6285111228911';
        $advisorName = $slot->advisor?->name ?? 'Dokter YFD';
        $service = ($slot->service_type === 'recovery')
            ? 'Financial Recovery Program'
            : 'Financial Consultation';

        $lines = [
            'Halo Tim YFD, saya ingin booking konsultasi.',
            '',
            '*Kode booking:* '.$slot->booking_code,
            '*Nama:* '.($slot->guest_name ?? '-'),
        ];
        if ($slot->guest_age) {
            $lines[] = '*Usia:* '.$slot->guest_age;
        }
        if ($slot->guest_phone) {
            $lines[] = '*No. WA:* '.$slot->guest_phone;
        }
        $lines[] = '*Layanan:* '.$service;
        $lines[] = '*Dokter:* '.$advisorName;
        $lines[] = '*Jadwal:* '.$slot->labelDate().' · '.$slot->labelTimeRange().' WIB';
        if ($slot->financial_stage) {
            $lines[] = '*Tahap finansial:* '.$slot->financial_stage;
        }
        if ($slot->situation) {
            $lines[] = '*Kondisi:* '.$slot->situation;
        }
        if ($slot->notes) {
            $lines[] = '';
            $lines[] = '*Cerita / keluhan:*';
            $lines[] = $slot->notes;
        }
        $lines[] = '';
        $lines[] = 'Slot sudah di-hold '.ConsultationSlot::HOLD_MINUTES.' menit. Mohon info pembayaran untuk mengunci jadwal. Terima kasih.';

        return 'https://wa.me/'.$wa.'?text='.rawurlencode(implode("\n", $lines));
    }
}
