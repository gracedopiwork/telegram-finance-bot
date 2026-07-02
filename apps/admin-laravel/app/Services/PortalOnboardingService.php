<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Models\Order;

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

    /**
     * @return list<string>
     */
    public function ftsaUnlockProductCodes(): array
    {
        return array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.ftsa.unlock_product_codes', ['yfd-ftsa-premium'])
        )));
    }

    public function isFtsaUnlockOrder(Order $order): bool
    {
        $code = (string) ($order->digitalProduct?->code ?? $order->plan ?? '');

        return in_array($code, $this->ftsaUnlockProductCodes(), true);
    }

    /**
     * Upgrade FTSA: pembeli sudah punya order bot paid pada lisensi yang sama.
     */
    public function isFtsaUpgradeOrder(Order $order): bool
    {
        if (! $this->isFtsaUnlockOrder($order) || ! $order->license_id) {
            return false;
        }

        return Order::query()
            ->where('status', 'paid')
            ->where('license_id', $order->license_id)
            ->where('id', '<', $order->id)
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->botOnlyProductCodes()))
            ->exists();
    }

    /**
     * Beli FTSA saja (belum pernah beli bot) — portal terbatas ke dashboard FTSA.
     */
    public function isFtsaOnlyBuyer(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        $hasFtsa = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->ftsaUnlockProductCodes()))
            ->exists();

        if (! $hasFtsa) {
            return false;
        }

        return ! $this->hasPaidBotOrder($email);
    }

    public function hasPaidBotOrder(string $email): bool
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

    public function isBotOnlyBuyer(string $email, int $telegramUserId): bool
    {
        return $this->hasPaidBotOrder($email);
    }

    public function isBotAfterFtsaBuyer(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! $this->hasPaidBotOrder($email)) {
            return false;
        }

        return Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->ftsaUnlockProductCodes()))
            ->exists();
    }

    /**
     * Upgrade bot setelah pembeli FTSA-only pada lisensi yang sama.
     */
    public function isBotAfterFtsaOrder(Order $order): bool
    {
        $code = (string) ($order->digitalProduct?->code ?? $order->plan ?? '');
        if (! in_array($code, $this->botOnlyProductCodes(), true) || ! $order->license_id) {
            return false;
        }

        return Order::query()
            ->where('status', 'paid')
            ->where('license_id', $order->license_id)
            ->where('id', '<', $order->id)
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->ftsaUnlockProductCodes()))
            ->exists();
    }

    /**
     * @return array{is_ftsa: bool, is_ftsa_upgrade: bool, is_ftsa_only: bool, is_bot_after_ftsa: bool}
     */
    public function orderDeliveryContext(Order $order): array
    {
        $isFtsa = $this->isFtsaUnlockOrder($order);
        $isUpgrade = $isFtsa && $this->isFtsaUpgradeOrder($order);
        $isBotAfterFtsa = $this->isBotAfterFtsaOrder($order);

        return [
            'is_ftsa' => $isFtsa,
            'is_ftsa_upgrade' => $isUpgrade,
            'is_ftsa_only' => $isFtsa && ! $isUpgrade,
            'is_bot_after_ftsa' => $isBotAfterFtsa,
        ];
    }

    public function userNeedsBaseline(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        return FinancialBaseline::userNeedsBaseline($telegramUserId);
    }

    public function userNeedsBotOnboardingBaseline(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if (! $this->hasPaidBotOrder($email)) {
            return false;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        if ($this->isBotAfterFtsaBuyer($email)) {
            return ! $this->hasFtsaPortalOnboardingComplete($email, $telegramUserId);
        }

        $baseline = FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);

        return ! $this->hasFinancialDiagnostic($baseline);
    }

    /**
     * Pembeli bot setelah FTSA — diagnostik + FTSA 1–32 sudah selesai di portal FTSA.
     */
    public function hasFtsaPortalOnboardingComplete(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $baseline = FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);

        if ($baseline === null) {
            return false;
        }

        return $this->hasFinancialDiagnostic($baseline)
            && app(FtsaAnswerSummaryService::class)->hasCompletedFtsa($baseline);
    }

    public function hasFinancialDiagnostic(?FinancialBaseline $baseline): bool
    {
        if ($baseline === null) {
            return false;
        }

        $fs = $baseline->answers_json['fs'] ?? [];

        return is_array($fs) && $fs !== [];
    }

    public function userNeedsFinancialDiagnostic(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if ($this->isBotAfterFtsaBuyer($email)
            && $this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return false;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $baseline = FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);

        return ! $this->hasFinancialDiagnostic($baseline);
    }

    public function userNeedsFtsa(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if ($this->isBotAfterFtsaBuyer($email)
            && $this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return false;
        }

        if (! app(PortalFeatureService::class)->canAccessFtsa($telegramUserId, $email)) {
            return false;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $baseline = FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);
        if ($baseline === null) {
            return true;
        }

        return ! app(FtsaAnswerSummaryService::class)->hasCompletedFtsa($baseline);
    }

    public function portalHomeRouteName(string $email): string
    {
        return $this->isFtsaOnlyBuyer($email) ? 'portal.emotional' : 'portal.dashboard';
    }

    public function portalDiagnosticUrl(): string
    {
        return route('portal.diagnostic');
    }

    public function portalFtsaUrl(): string
    {
        return route('portal.baseline.create');
    }

    public function portalBaselineUrl(): string
    {
        return route('portal.baseline.create');
    }

    /**
     * @deprecated Gunakan portalDiagnosticUrl() untuk user login portal.
     */
    public function nextFtsaOnlyOnboardingUrl(string $email, int $telegramUserId): string
    {
        return $this->portalHomeRouteName($email) === 'portal.emotional'
            ? route('portal.emotional')
            : route('portal.dashboard');
    }

    /**
     * URL pengisian baseline — selalu di dalam portal, tanpa redirect paksa.
     */
    public function firstBaselineUrl(string $email, int $telegramUserId): string
    {
        if ($this->userNeedsFinancialDiagnostic($email, $telegramUserId)) {
            return $this->portalDiagnosticUrl();
        }

        if ($this->userNeedsFtsa($email, $telegramUserId)) {
            return $this->portalFtsaUrl();
        }

        return route($this->portalHomeRouteName($email));
    }

    /**
     * Landing check-up gratis (tamu / belum login).
     */
    public function diagnosticCheckupUrl(): string
    {
        return route('checkup.show');
    }
}
