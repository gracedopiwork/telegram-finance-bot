<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;

class PortalFeatureService
{
    public function canAccessFtsa(int $telegramUserId): bool
    {
        $requiresUpgrade = (bool) config('portal.ftsa.requires_upgrade', true);
        if (! $requiresUpgrade) {
            return true;
        }

        $codes = (array) config('portal.ftsa.unlock_product_codes', []);
        if (empty($codes)) {
            return false;
        }

        $licenseIds = License::query()
            ->where('assigned_user_id', $telegramUserId)
            ->where('status', 'active')
            ->pluck('id');

        if ($licenseIds->isEmpty()) {
            return false;
        }

        return Order::query()
            ->whereIn('license_id', $licenseIds->all())
            ->where('status', 'paid')
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $codes))
            ->exists();
    }
}
