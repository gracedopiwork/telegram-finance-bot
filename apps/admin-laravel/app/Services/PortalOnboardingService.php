<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Models\Order;
use App\Support\PortalSession;

class PortalOnboardingService
{
    /**
     * Pembeli yang langsung ambil paket bot Telegram (bukan lewat check-up landing).
     *
     * @return list<string>
     */
    public function botOnlyProductCodes(): array
    {
        $codes = array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.bot_only_product_codes', ['yfd-bot-telegram'])
        )));

        return $codes !== [] ? $codes : ['yfd-bot-telegram'];
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
        return app(LicenseEntitlementService::class)->hasPaidBotOrderForEmail($email);
    }

    /**
     * Pembeli / pengguna aktif YFD First Aid (order bot lunas, atau transaksi bot jika bukan pembeli FTSA-only).
     */
    public function hasPaidBotOrderForUser(string $email, int $telegramUserId): bool
    {
        $entitlements = app(LicenseEntitlementService::class);
        if ($entitlements->hasPaidBotOrderForEmail($email)) {
            return true;
        }

        if ($entitlements->hasPaidBotOrderForTelegramUser($telegramUserId)) {
            return true;
        }

        if ($this->isFtsaOnlyBuyer($email)) {
            return false;
        }

        if ($telegramUserId > 0 && \App\Models\BotTransaction::query()->forUser($telegramUserId)->exists()) {
            return true;
        }

        return false;
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

        if ($this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return false;
        }

        $baseline = $this->resolveBaseline($email, $telegramUserId);

        return $baseline === null;
    }

    public function userNeedsBotOnboardingBaseline(string $email, int $telegramUserId): bool
    {
        return $this->userNeedsSnapshotBaseline($email, $telegramUserId);
    }

    public function userNeedsSnapshotBaseline(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if (! $this->hasPaidBotOrderForUser($email, $telegramUserId)) {
            return false;
        }

        if ($this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return false;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $baseline = $this->resolveBaseline($email, $telegramUserId);
        if (! $this->hasFinancialDiagnostic($baseline)) {
            return false;
        }

        return ! $this->hasFinancialSnapshot($baseline);
    }

    /**
     * Pembeli FTSA-only — snapshot angka inti belum diisi.
     */
    public function userNeedsFtsaSnapshotBaseline(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if (! app(PortalAccessService::class)->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            return false;
        }

        $baseline = $this->resolveBaseline($email, $telegramUserId);

        return ! $this->hasFtsaSnapshotComplete($baseline);
    }

    public function hasFtsaSnapshotComplete(?FinancialBaseline $baseline): bool
    {
        if ($baseline === null) {
            return false;
        }

        foreach (['avg_monthly_income', 'emergency_fund', 'cash_savings', 'total_debt'] as $field) {
            if ($baseline->{$field} !== null && (int) $baseline->{$field} > 0) {
                return true;
            }
        }

        return false;
    }

    public function hasFinancialSnapshot(?FinancialBaseline $baseline): bool
    {
        if ($baseline === null) {
            return false;
        }

        if (trim((string) ($baseline->current_goal ?? '')) !== '') {
            return true;
        }

        foreach ([
            'avg_monthly_income',
            'emergency_fund',
            'cash_savings',
            'total_investment',
            'total_asset',
            'total_debt',
        ] as $field) {
            if ($baseline->{$field} !== null && (int) $baseline->{$field} > 0) {
                return true;
            }
        }

        return (bool) $baseline->has_bpjs
            || (bool) $baseline->has_health_insurance
            || (bool) $baseline->has_income_protection
            || (bool) $baseline->has_life_insurance;
    }

    /**
     * Pembeli YFD First Aid — diagnostik + snapshot selesai (FTSA opsional terpisah).
     */
    public function hasBotPortalOnboardingComplete(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if (! $this->hasPaidBotOrderForUser($email, $telegramUserId)) {
            return false;
        }

        $baseline = $this->resolveBaseline($email, $telegramUserId);

        return $this->hasFinancialDiagnostic($baseline)
            && $this->hasFinancialSnapshot($baseline);
    }

    public function resolveBaseline(string $email, int $telegramUserId): ?FinancialBaseline
    {
        if (! \App\Support\FinancialBaselineSchema::isReady() || $telegramUserId <= 0) {
            return null;
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        return FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);
    }

    /**
     * Pembeli bot setelah FTSA — diagnostik + FTSA 1–32 sudah selesai di portal FTSA.
     */
    public function hasFtsaPortalOnboardingComplete(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        $baseline = $this->resolveBaseline($email, $telegramUserId);

        if ($baseline === null) {
            return false;
        }

        $access = app(PortalAccessService::class);
        $snapshotOk = $access->isFtsaOnlyPortalUser($email, $telegramUserId)
            ? $this->hasFtsaSnapshotComplete($baseline)
            : $this->hasFinancialSnapshot($baseline);

        return $snapshotOk
            && app(FtsaAnswerSummaryService::class)->hasCompletedFtsa($baseline);
    }

    public function hasPortalAssessmentComplete(string $email, int $telegramUserId): bool
    {
        return $this->hasFtsaPortalOnboardingComplete($email, $telegramUserId);
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

        if (app(PortalAccessService::class)->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            return false;
        }

        if ($this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return false;
        }

        $baseline = $this->resolveBaseline($email, $telegramUserId);

        return ! $this->hasFinancialDiagnostic($baseline);
    }

    public function userNeedsFtsa(string $email, int $telegramUserId): bool
    {
        if (! \App\Support\FinancialBaselineSchema::isReady()) {
            return false;
        }

        if ($this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return false;
        }

        if (! app(PortalFeatureService::class)->canAccessFtsa($telegramUserId, $email)) {
            return false;
        }

        if (app(PortalAccessService::class)->isFtsaOnlyPortalUser($email, $telegramUserId)
            && $this->userNeedsFtsaSnapshotBaseline($email, $telegramUserId)) {
            return false;
        }

        $baseline = $this->resolveBaseline($email, $telegramUserId);
        if ($baseline === null) {
            return true;
        }

        return ! app(FtsaAnswerSummaryService::class)->hasCompletedFtsa($baseline);
    }

    public function portalHomeRouteName(string $email, int $telegramUserId = 0): string
    {
        $access = app(PortalAccessService::class);

        if ($access->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            if ($this->userNeedsFtsaSnapshotBaseline($email, $telegramUserId)) {
                return 'portal.baseline.create';
            }
            if ($this->userNeedsFtsa($email, $telegramUserId)) {
                return 'portal.ftsa.create';
            }

            return 'portal.emotional';
        }

        return $access->hasBotPortalAccess($email, $telegramUserId)
            ? 'portal.dashboard'
            : 'portal.emotional';
    }

    public function portalDiagnosticUrl(): string
    {
        return route('portal.diagnostic');
    }

    public function portalFtsaUrl(): string
    {
        return route('portal.ftsa.create');
    }

    public function portalBaselineUrl(string $email, int $telegramUserId): string
    {
        if (app(PortalAccessService::class)->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            if ($this->userNeedsFtsaSnapshotBaseline($email, $telegramUserId)) {
                return route('portal.baseline.create');
            }
            if ($this->userNeedsFtsa($email, $telegramUserId)) {
                return route('portal.ftsa.create');
            }

            return route('portal.baseline.create', ['section' => 'snapshot']);
        }

        if ($this->hasFtsaPortalOnboardingComplete($email, $telegramUserId)) {
            return route('portal.baseline');
        }

        return $this->firstBaselineUrl($email, $telegramUserId);
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

    public function portalDashboardSnapshotUrl(array $query = []): string
    {
        return route('portal.dashboard', $query).'#baseline-snapshot';
    }

    public function portalTransactionsUrl(array $query = []): string
    {
        return route('portal.transactions', $query).'#input-data';
    }

    public function portalSnapshotEntryUrl(string $email, int $telegramUserId, array $query = []): string
    {
        return route('portal.baseline.create', $query);
    }

    /**
     * URL pengisian baseline — selalu di dalam portal, tanpa redirect paksa.
     */
    public function firstBaselineUrl(string $email, int $telegramUserId): string
    {
        $access = app(PortalAccessService::class);
        $isFtsaOnly = $access->isFtsaOnlyPortalUser($email, $telegramUserId);

        if ($this->userNeedsSnapshotBaseline($email, $telegramUserId)) {
            return $this->portalSnapshotEntryUrl($email, $telegramUserId);
        }

        if ($this->userNeedsFinancialDiagnostic($email, $telegramUserId)) {
            return route('portal.baseline.create');
        }

        if ($isFtsaOnly) {
            if ($this->userNeedsFtsaSnapshotBaseline($email, $telegramUserId)) {
                return route('portal.baseline.create');
            }
            if ($this->userNeedsFtsa($email, $telegramUserId)) {
                return route('portal.ftsa.create');
            }

            return route('portal.emotional');
        }

        return route($this->portalHomeRouteName($email, $telegramUserId));
    }

    /**
     * Landing check-up gratis (tamu / belum login).
     */
    public function diagnosticCheckupUrl(): string
    {
        return route('checkup.show');
    }
}
