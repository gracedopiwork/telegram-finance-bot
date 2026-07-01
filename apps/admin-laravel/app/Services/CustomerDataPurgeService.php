<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use App\Models\License;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerDataPurgeService
{
    /**
     * Hapus transaksi bot + baseline untuk semua Telegram user yang pernah terhubung ke email ini.
     */
    public function purgeFinanceDataForEmail(string $email): int
    {
        $userIds = $this->collectTelegramUserIdsForEmail($email);

        return $this->purgeFinanceDataForTelegramUserIds($userIds);
    }

    /**
     * @return list<int>
     */
    public function collectTelegramUserIdsForEmail(string $email): array
    {
        $licenseIds = Order::query()
            ->whereRaw('LOWER(email) = ?', [$this->normalizeEmail($email)])
            ->whereNotNull('license_id')
            ->pluck('license_id');

        return $this->collectTelegramUserIdsForLicenseIds($licenseIds);
    }

    /**
     * @return list<int>
     */
    public function collectTelegramUserIdsForOrder(Order $order): array
    {
        $order->loadMissing('license');
        $licenseIds = collect([$order->license_id])->filter();

        return $this->collectTelegramUserIdsForLicenseIds($licenseIds);
    }

    /**
     * @param  Collection<int, int|string|null>|array<int, int|string|null>  $licenseIds
     * @return list<int>
     */
    public function collectTelegramUserIdsForLicenseIds(Collection|array $licenseIds): array
    {
        $ids = collect($licenseIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $fromLicenses = License::query()
            ->whereIn('id', $ids)
            ->whereNotNull('assigned_user_id')
            ->pluck('assigned_user_id');

        $fromActivations = DB::table('license_activations')
            ->whereIn('license_id', $ids->all())
            ->pluck('telegram_user_id');

        return $fromLicenses
            ->merge($fromActivations)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $telegramUserIds
     */
    public function purgeFinanceDataForTelegramUserIds(array $telegramUserIds): int
    {
        $telegramUserIds = collect($telegramUserIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($telegramUserIds === []) {
            return 0;
        }

        $deleted = 0;
        $deleted += BotTransaction::query()->whereIn('telegram_user_id', $telegramUserIds)->delete();
        $deleted += FinancialBaseline::query()->whereIn('telegram_user_id', $telegramUserIds)->delete();

        return $deleted;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
