<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Models\Order;
use App\Support\FinancialBaselineSchema;

class PortalOnboardingService
{
    /**
     * Pembeli yang langsung ambil paket bot Telegram (bukan lewat check-up landing).
     *
     * @return list<string>
     */
    public function botOnlyProductCodes(): array
    {
        return array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.bot_only_product_codes', ['yfd-bot-telegram'])
        )));
    }

    public function isBotOnlyBuyer(string $email, int $telegramUserId): bool
    {
        $email = strtolower(trim($email));
        $codes = $this->botOnlyProductCodes();
        if ($email === '' || $codes === []) {
            return false;
        }

        return Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $codes))
            ->exists();
    }

    public function userNeedsBaseline(int $telegramUserId): bool
    {
        if (! FinancialBaselineSchema::isReady()) {
            return false;
        }

        return FinancialBaseline::userNeedsBaseline($telegramUserId);
    }

    /**
     * URL pengisian baseline pertama kali sesuai jalur pembelian.
     */
    public function firstBaselineUrl(string $email, int $telegramUserId): string
    {
        if ($this->isBotOnlyBuyer($email, $telegramUserId)) {
            return route('portal.baseline.create');
        }

        return route('checkup.show');
    }
}
