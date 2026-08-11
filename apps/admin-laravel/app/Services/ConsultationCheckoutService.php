<?php

namespace App\Services;

use App\Models\ConsultationSlot;
use App\Models\Order;
use App\Models\Setting;
use App\Support\ConsultationPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConsultationCheckoutService
{
    public const KIND_SESSION = 'consultation_session';

    public const KIND_OVERTIME = 'consultation_overtime';

    public function __construct(
        private ConsultationSlotService $slots,
        private MidtransService $midtrans,
    ) {}

    /**
     * Hold slot + create Midtrans Snap payment for consultation session.
     *
     * @param  array{
     *   name: string,
     *   email: string,
     *   phone?: ?string,
     *   age?: ?int,
     *   stage_key: string,
     *   situation?: ?string,
     *   condition?: ?string,
     *   service_type?: string
     * }  $guest
     * @return array{order: Order, slot: ConsultationSlot, payment_url: string}
     */
    public function holdAndCreateSessionPayment(ConsultationSlot $slot, array $guest): array
    {
        if (! $this->midtrans->isSnapReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Payment gateway belum siap. Hubungi admin YFD.',
            ]);
        }

        $email = strtolower(trim((string) $guest['email']));
        $fhcu = ConsultationPricing::fhcuStatusForEmail($email);
        if (! $fhcu['valid']) {
            throw ValidationException::withMessages([
                'email' => $fhcu['message'],
            ]);
        }

        $stageKey = $fhcu['stage_key']
            ?? ConsultationPricing::normalizeStageKey($guest['stage_key'] ?? null);
        $amount = ConsultationPricing::sessionAmount($stageKey);
        if ($stageKey === null || $amount < 1) {
            throw ValidationException::withMessages([
                'stage' => 'Tahap finansial / tarif sesi tidak valid.',
            ]);
        }

        $tier = ConsultationPricing::forStage($stageKey);
        $serviceType = $guest['service_type'] ?? 'standard';

        $held = $this->slots->holdSlot($slot, [
            'name' => $guest['name'],
            'phone' => $guest['phone'] ?? null,
            'age' => $guest['age'] ?? null,
            'stage' => ($tier['label'] ?? $stageKey).' — '.ConsultationPricing::formatRupiah($amount),
            'stage_key' => $stageKey,
            'amount_due' => $amount,
            'situation' => $guest['situation'] ?? null,
            'condition' => $guest['condition'] ?? null,
            'service_type' => $serviceType,
        ]);

        $order = DB::transaction(function () use ($held, $guest, $email, $stageKey, $amount, $serviceType, $tier) {
            $orderCode = 'YFD-CS-'.Str::upper(Str::random(8));
            $productName = match ($serviceType) {
                'recovery' => 'Financial Recovery — sesi konsultasi',
                'premarital' => 'Premarital Check Up — sesi konsultasi',
                default => 'Financial Consultation — sesi 1 jam',
            };

            $order = Order::query()->create([
                'order_code' => $orderCode,
                'full_name' => trim((string) $guest['name']),
                'email' => $email,
                'phone' => isset($guest['phone']) ? trim((string) $guest['phone']) : null,
                'plan' => self::KIND_SESSION,
                'order_kind' => self::KIND_SESSION,
                'product_name' => $productName.' ('.$tier['label'].')',
                'amount' => $amount,
                'original_price' => $amount,
                'discount_amount' => 0,
                'currency' => 'IDR',
                'status' => 'pending',
                'payment_gateway' => 'midtrans',
                'consultation_slot_id' => $held->id,
                'admin_note' => 'Booking '.$held->booking_code.' · tahap '.$stageKey,
            ]);

            $held->order_id = $order->id;
            $held->save();

            return $order;
        });

        $snap = $this->midtrans->createSnapTransaction([
            'order_id' => $order->order_code,
            'gross_amount' => $amount,
            'full_name' => $order->full_name,
            'email' => $order->email,
            'phone' => $order->phone ?? '',
            'item_details' => [[
                'id' => self::KIND_SESSION,
                'price' => $amount,
                'quantity' => 1,
                'name' => Str::limit($order->product_name, 50, ''),
            ]],
        ]);

        $order->payment_token = $snap['token'] ?? null;
        $order->payment_url = $snap['redirect_url'] ?? null;
        $order->save();

        if (! filled($order->payment_url)) {
            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat link pembayaran. Coba lagi atau hubungi admin.',
            ]);
        }

        return [
            'order' => $order->fresh(),
            'slot' => $held->fresh(['advisor']),
            'payment_url' => (string) $order->payment_url,
        ];
    }

    /**
     * Admin generates overtime Midtrans invoice after session.
     *
     * @return array{order: Order, payment_url: string}
     */
    public function createOvertimeInvoice(ConsultationSlot $slot, int $overtimeHours = 1): array
    {
        if (! $slot->isConfirmed()) {
            throw ValidationException::withMessages([
                'slot' => 'Overtime hanya untuk booking yang sudah lunas/confirmed.',
            ]);
        }

        $stageKey = ConsultationPricing::normalizeStageKey($slot->stage_key ?: $slot->financial_stage);
        $unit = ConsultationPricing::overtimeAmount($stageKey);
        $hours = max(1, min(1, $overtimeHours)); // max +1 jam per brief
        $amount = $unit * $hours;

        if ($stageKey === null || $amount < 1) {
            throw ValidationException::withMessages([
                'slot' => 'Tarif overtime tidak tersedia untuk tahap ini.',
            ]);
        }

        if (! $this->midtrans->isSnapReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Payment gateway belum siap.',
            ]);
        }

        $email = '';
        if ($slot->order_id) {
            $email = strtolower(trim((string) Order::query()->whereKey($slot->order_id)->value('email')));
        }

        $order = Order::query()->create([
            'order_code' => 'YFD-OT-'.Str::upper(Str::random(8)),
            'full_name' => $slot->guest_name ?: 'Guest',
            'email' => $email !== '' ? $email : 'admin.findoc@yourfinancialdoctor.id',
            'phone' => $slot->guest_phone,
            'plan' => self::KIND_OVERTIME,
            'order_kind' => self::KIND_OVERTIME,
            'product_name' => 'Overtime konsultasi '.$hours.' jam ('.$stageKey.')',
            'amount' => $amount,
            'original_price' => $amount,
            'discount_amount' => 0,
            'currency' => 'IDR',
            'status' => 'pending',
            'payment_gateway' => 'midtrans',
            'consultation_slot_id' => $slot->id,
            'admin_note' => 'Overtime untuk booking '.($slot->booking_code ?? '#'.$slot->id),
        ]);

        $snap = $this->midtrans->createSnapTransaction([
            'order_id' => $order->order_code,
            'gross_amount' => $amount,
            'full_name' => $order->full_name,
            'email' => $order->email,
            'phone' => $order->phone ?? '',
            'item_details' => [[
                'id' => self::KIND_OVERTIME,
                'price' => $amount,
                'quantity' => 1,
                'name' => Str::limit($order->product_name, 50, ''),
            ]],
        ]);

        $order->payment_token = $snap['token'] ?? null;
        $order->payment_url = $snap['redirect_url'] ?? null;
        $order->save();

        if (! filled($order->payment_url)) {
            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat invoice overtime.',
            ]);
        }

        return [
            'order' => $order->fresh(),
            'payment_url' => (string) $order->payment_url,
        ];
    }

    public function isConsultationOrder(Order $order): bool
    {
        $kind = (string) ($order->order_kind ?: $order->plan);

        return in_array($kind, [self::KIND_SESSION, self::KIND_OVERTIME], true)
            || filled($order->consultation_slot_id);
    }

    public function markConsultationPaid(Order $order): void
    {
        if (! $this->isConsultationOrder($order)) {
            return;
        }

        $slot = null;
        if ($order->consultation_slot_id) {
            $slot = ConsultationSlot::query()->find($order->consultation_slot_id);
        }

        if ($slot && (string) ($order->order_kind ?: $order->plan) === self::KIND_SESSION) {
            $this->slots->confirmPayment($slot);
            $this->notifyAdminPaid($slot, $order);
        }
    }

    public function notifyAdminPaid(ConsultationSlot $slot, Order $order): void
    {
        $wa = Setting::val('contact.wa_number', '6285111228911') ?: '6285111228911';
        $service = match ($slot->service_type) {
            'recovery' => 'Financial Recovery Program',
            'premarital' => 'Premarital Financial Health Check Up',
            default => 'Financial Consultation',
        };
        $advisorName = $slot->advisor?->name ?? 'Dokter YFD';

        $lines = [
            '✅ *BOOKING LUNAS* — siap dijadwalkan',
            '',
            '*Kode booking:* '.($slot->booking_code ?? '—'),
            '*Order:* '.$order->order_code,
            '*Status bayar:* LUNAS via Midtrans',
            '*Nominal:* '.$order->amountLabel(),
            '',
            '*Nama:* '.($slot->guest_name ?? $order->full_name),
            '*Email:* '.$order->email,
        ];
        if ($slot->guest_phone || $order->phone) {
            $lines[] = '*No. WA:* '.($slot->guest_phone ?: $order->phone);
        }
        $lines[] = '*Layanan:* '.$service;
        $lines[] = '*Jadwal:* '.$slot->labelDate().' · '.$slot->starts_at->format('H:i').' WIB';
        $lines[] = '*Dokter:* '.$advisorName;
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
        $lines[] = 'Pembayaran sudah diverifikasi sistem. Tidak perlu konfirmasi transfer manual.';
        $lines[] = '';
        $lines[] = 'Tindak lanjut admin: kirim 2 link form screening ke klien. Klien wajib isi paling lambat '.ConsultationSlot::INTAKE_FORM_HOURS.' jam sebelum sesi.';

        $url = 'https://wa.me/'.$wa.'?text='.rawurlencode(implode("\n", $lines));

        // Prefer Fonnte if available; otherwise leave admin_note with WA draft link.
        try {
            $fonnte = app(FonnteClient::class);
            if (method_exists($fonnte, 'sendText') && filled($wa)) {
                $fonnte->sendText($wa, implode("\n", $lines));

                return;
            }
        } catch (\Throwable) {
            // fall through
        }

        $order->admin_note = trim((string) $order->admin_note."\nWA admin draft: ".$url);
        $order->save();
    }
}
