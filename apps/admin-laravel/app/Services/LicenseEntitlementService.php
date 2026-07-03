<?php

namespace App\Services;

use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
use Carbon\Carbon;

class LicenseEntitlementService
{
    /**
     * Bot = selamanya (null). FTSA-only = evaluasi 12 bulan (atau config).
     */
    public function expiresAtForNewLicense(Order $order): ?Carbon
    {
        $code = $this->productCode($order);

        if ($this->isBotProductCode($code)) {
            return null;
        }

        if ($this->isFtsaProductCode($code)) {
            return now()->addMonths($this->ftsaEvaluationMonths());
        }

        return null;
    }

    public function ftsaEvaluationMonths(): int
    {
        return max(1, (int) config('portal.ftsa.evaluation_months', 12));
    }

    /**
     * FTSA aktif jika ada order FTSA paid dalam periode evaluasi (12 bulan).
     */
    public function hasActiveFtsaEntitlement(int $telegramUserId): bool
    {
        if (! (bool) config('portal.ftsa.requires_upgrade', true)) {
            return true;
        }

        $codes = $this->ftsaProductCodes();
        if ($codes === []) {
            return false;
        }

        $licenseIds = License::query()
            ->where('assigned_user_id', $telegramUserId)
            ->where('status', 'active')
            ->pluck('id');

        if ($licenseIds->isEmpty()) {
            return false;
        }

        $order = Order::query()
            ->whereIn('license_id', $licenseIds->all())
            ->where('status', 'paid')
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $codes))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if ($order === null) {
            return false;
        }

        return $this->ftsaEntitlementEndsAt($order)?->isFuture() ?? false;
    }

    public function hasActiveFtsaEntitlementForEmail(string $email): bool
    {
        if (! (bool) config('portal.ftsa.requires_upgrade', true)) {
            return true;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        $codes = $this->ftsaProductCodes();
        if ($codes === []) {
            return false;
        }

        $order = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $codes))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if ($order === null) {
            return false;
        }

        return $this->ftsaEntitlementEndsAt($order)?->isFuture() ?? false;
    }

    public function ftsaEntitlementEndsAt(Order $order): ?Carbon
    {
        if (! $this->isFtsaProductCode($this->productCode($order))) {
            return null;
        }

        $start = $order->paid_at ?? $order->created_at;
        if ($start === null) {
            return null;
        }

        if (! $start instanceof Carbon) {
            $start = Carbon::parse($start);
        }

        return $start->copy()->addMonths($this->ftsaEvaluationMonths());
    }

    public function isBotProductCode(string $code): bool
    {
        return in_array($code, $this->botProductCodes(), true);
    }

    public function isFtsaProductCode(string $code): bool
    {
        return in_array($code, $this->ftsaProductCodes(), true);
    }

    /**
     * @return list<string>
     */
    public function botProductCodes(): array
    {
        $codes = array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.bot_only_product_codes', ['yfd-bot-telegram'])
        )));

        if ($codes === []) {
            $codes = ['yfd-bot-telegram'];
        }

        $aliases = ['yfd-first-aid', 'yfd-bot', 'yfd-telegram-bot'];
        $featured = CpDigitalProduct::query()
            ->active()
            ->featured()
            ->where('billing_mode', 'midtrans')
            ->pluck('code')
            ->all();

        return array_values(array_unique(array_merge($codes, $aliases, $featured)));
    }

    /**
     * @return list<string>
     */
    public function ftsaProductCodes(): array
    {
        return array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.ftsa.unlock_product_codes', ['yfd-ftsa-premium'])
        )));
    }

    public function hasPaidBotOrderOnLicense(License $license): bool
    {
        $codes = $this->botProductCodes();

        return Order::query()
            ->where('status', 'paid')
            ->where('license_id', $license->id)
            ->where(function ($q) use ($codes) {
                $q->whereIn('plan', $codes)
                    ->orWhereHas('digitalProduct', function ($dq) use ($codes) {
                        $dq->where(function ($productQ) use ($codes) {
                            $productQ->whereIn('code', $codes)
                                ->orWhere(function ($flagshipQ) {
                                    $flagshipQ->where('billing_mode', 'midtrans')
                                        ->where('is_featured', true)
                                        ->where('is_active', true);
                                });
                        });
                    });
            })
            ->exists();
    }

    public function hasPaidFtsaOrderOnLicense(License $license): bool
    {
        $codes = $this->ftsaProductCodes();
        if ($codes === []) {
            return false;
        }

        return Order::query()
            ->where('status', 'paid')
            ->where('license_id', $license->id)
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $codes))
            ->exists();
    }

    /**
     * Label hak akses dari order lunas (bukan kolom plan yang bisa tertinggal dari order pertama).
     */
    public function licenseEntitlementLabel(License $license): string
    {
        $hasBot = $this->hasPaidBotOrderOnLicense($license);
        $hasFtsa = $this->hasPaidFtsaOrderOnLicense($license);

        if ($hasBot && $hasFtsa) {
            return 'FTSA Premium + YFD First Aid';
        }

        if ($hasBot) {
            return 'YFD First Aid (selamanya)';
        }

        if ($hasFtsa) {
            return 'FTSA Premium';
        }

        return (string) ($license->plan ?: '—');
    }

    /**
     * @return list<string>
     */
    public function licenseEntitlementCodes(License $license): array
    {
        $codes = [];

        if ($this->hasPaidBotOrderOnLicense($license)) {
            $codes = array_merge($codes, $this->botProductCodes());
        }

        if ($this->hasPaidFtsaOrderOnLicense($license)) {
            $codes = array_merge($codes, $this->ftsaProductCodes());
        }

        return array_values(array_unique($codes));
    }

    /**
     * Nilai singkat untuk kolom licenses.plan (varchar 32) — bukan daftar kode produk penuh.
     */
    public function resolveStoredPlanForLicense(License $license): string
    {
        $hasBot = $this->hasPaidBotOrderOnLicense($license);
        $hasFtsa = $this->hasPaidFtsaOrderOnLicense($license);

        if ($hasBot && $hasFtsa) {
            return 'yfd-ftsa+bot';
        }

        if ($hasBot) {
            return $this->botProductCodes()[0] ?? 'yfd-bot-telegram';
        }

        if ($hasFtsa) {
            return $this->ftsaProductCodes()[0] ?? 'yfd-ftsa-premium';
        }

        return (string) ($license->plan ?: 'manual');
    }

    private function productCode(Order $order): string
    {
        return (string) ($order->digitalProduct?->code ?? $order->plan ?? '');
    }
}
