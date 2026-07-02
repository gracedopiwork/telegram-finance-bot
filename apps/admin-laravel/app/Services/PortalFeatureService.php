<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;

class PortalFeatureService
{
    public function __construct(
        private readonly LicenseEntitlementService $entitlements,
    ) {}

    public function canAccessFtsa(int $telegramUserId): bool
    {
        return $this->entitlements->hasActiveFtsaEntitlement($telegramUserId);
    }

    /**
     * @return array{active: bool, ends_at: ?\Carbon\Carbon, order: ?Order}
     */
    public function ftsaEntitlementStatus(int $telegramUserId): array
    {
        $licenseIds = License::query()
            ->where('assigned_user_id', $telegramUserId)
            ->where('status', 'active')
            ->pluck('id');

        $order = null;
        if ($licenseIds->isNotEmpty()) {
            $order = Order::query()
                ->whereIn('license_id', $licenseIds->all())
                ->where('status', 'paid')
                ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->entitlements->ftsaProductCodes()))
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->first();
        }

        $endsAt = $order ? $this->entitlements->ftsaEntitlementEndsAt($order) : null;

        return [
            'active' => $this->canAccessFtsa($telegramUserId),
            'ends_at' => $endsAt,
            'order' => $order,
        ];
    }
}
