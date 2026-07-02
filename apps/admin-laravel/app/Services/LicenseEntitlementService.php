<?php

namespace App\Services;

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
        return array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.bot_only_product_codes', ['yfd-bot-telegram'])
        )));
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

    private function productCode(Order $order): string
    {
        return (string) ($order->digitalProduct?->code ?? $order->plan ?? '');
    }
}
