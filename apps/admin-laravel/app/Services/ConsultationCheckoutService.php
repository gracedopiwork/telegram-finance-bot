<?php

namespace App\Services;

use App\Jobs\SendConsultationPaidEmailJob;
use App\Models\ConsultationSlot;
use App\Models\Order;
use App\Services\ConsultationPricing\ConsultationPriceRules;
use App\Services\ConsultationPricing\FhcuRequiredException;
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
        private PivotService $pivot,
        private ConsultationPriceRules $prices,
        private BookingPaidAdminNotifier $adminNotifier,
    ) {}

    /**
     * Hold slot + create Pivot redirect payment for consultation session.
     * Tarif dari ConsultationPriceRules (service_type + session_role), dihitung sebelum slot di-hold.
     *
     * @param  array{
     *   name: string,
     *   email: string,
     *   phone?: ?string,
     *   age?: ?int,
     *   situation?: ?string,
     *   condition?: ?string,
     *   service_type?: string,
     *   session_role?: ?string
     * }  $guest
     * @return array{order: Order, slot: ConsultationSlot, payment_url: string}
     *
     * @throws FhcuRequiredException layanan bertarif FHCU tanpa FHCU valid (missing/expired)
     */
    public function holdAndCreateSessionPayment(ConsultationSlot $slot, array $guest): array
    {
        if (! $this->pivot->isReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Payment gateway belum siap. Hubungi admin YFD.',
            ]);
        }

        $email = strtolower(trim((string) $guest['email']));
        $serviceType = $guest['service_type'] ?? 'standard';
        $quote = $this->prices->for($serviceType, $guest['session_role'] ?? null)->quote($email);
        $amount = $quote->amount;

        $held = $this->slots->holdSlot($slot, [
            'name' => $guest['name'],
            'phone' => $guest['phone'] ?? null,
            'age' => $guest['age'] ?? null,
            'stage' => $quote->stageLabel !== null
                ? $quote->stageLabel.' — '.ConsultationPricing::formatRupiah($amount)
                : null,
            'stage_key' => $quote->stageKey,
            'amount_due' => $amount,
            'situation' => $guest['situation'] ?? null,
            'condition' => $guest['condition'] ?? null,
            'service_type' => $serviceType,
        ]);

        $order = DB::transaction(function () use ($held, $guest, $email, $quote) {
            $orderCode = 'YFD-CS-'.Str::upper(Str::random(8));
            $order = Order::query()->create([
                'order_code' => $orderCode,
                'full_name' => trim((string) $guest['name']),
                'email' => $email,
                'phone' => isset($guest['phone']) ? trim((string) $guest['phone']) : null,
                'plan' => self::KIND_SESSION,
                'order_kind' => self::KIND_SESSION,
                'product_name' => $quote->label,
                'amount' => $quote->amount,
                'original_price' => $quote->amount,
                'discount_amount' => 0,
                'currency' => 'IDR',
                'status' => 'pending',
                'payment_gateway' => 'pivot',
                'consultation_slot_id' => $held->id,
                'admin_note' => 'Booking '.$held->booking_code.' · '
                    .($quote->stageKey !== null ? 'tahap '.$quote->stageKey : 'tarif tetap'),
            ]);

            $held->order_id = $order->id;
            $held->save();

            return $order;
        });

        $payment = $this->pivot->createRedirectPayment([
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

        $order->payment_token = $payment['id'] ?? null;
        $order->payment_url = $payment['payment_url'] ?? null;
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
     * Admin generates overtime Pivot invoice after session.
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

        if (! $this->pivot->isReady()) {
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
            'payment_gateway' => 'pivot',
            'consultation_slot_id' => $slot->id,
            'admin_note' => 'Overtime untuk booking '.($slot->booking_code ?? '#'.$slot->id),
        ]);

        $payment = $this->pivot->createRedirectPayment([
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

        $order->payment_token = $payment['id'] ?? null;
        $order->payment_url = $payment['payment_url'] ?? null;
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

    /**
     * Kunci slot (sinkron, supaya halaman sukses langsung menampilkan jadwal terkunci) lalu
     * antrekan notif admin + email pembeli. Aman dipanggil berkali-kali (webhook + sync
     * halaman sukses bisa sama-sama melihat order pending): klaim atomik menjamin sekali kirim.
     */
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

            if ($this->claim($order, 'admin_notified_at')) {
                $this->adminNotifier->dispatchFor($order);
            }

            if ($this->claim($order, 'purchase_delivery_queued_at')) {
                SendConsultationPaidEmailJob::dispatch($order->id);
            }

            return;
        }

        // Overtime / tanpa slot: tidak ada email booking; tandai selesai seperti sebelumnya.
        Order::whereKey($order->id)->whereNull('purchase_delivery_sent_at')
            ->update(['purchase_delivery_sent_at' => now()]);
    }

    /** UPDATE … WHERE col IS NULL: hanya satu pemanggil yang menang. */
    private function claim(Order $order, string $column): bool
    {
        return Order::whereKey($order->id)->whereNull($column)->update([$column => now()]) === 1;
    }
}
