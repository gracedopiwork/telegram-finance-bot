<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;

class PortalFeatureService
{
    public function __construct(
        private readonly LicenseEntitlementService $entitlements,
    ) {}

    public function canAccessFtsa(int $telegramUserId, ?string $email = null): bool
    {
        $email = strtolower(trim((string) $email));
        if ($email !== '' && $this->entitlements->hasActiveFtsaEntitlementForEmail($email)) {
            return true;
        }

        return $this->entitlements->hasActiveFtsaEntitlement($telegramUserId);
    }

    /**
     * @return array{active: bool, ends_at: ?\Carbon\Carbon, order: ?Order}
     */
    public function ftsaEntitlementStatus(int $telegramUserId, ?string $email = null): array
    {
        $email = strtolower(trim((string) $email));

        $order = null;
        if ($email !== '') {
            $order = Order::query()
                ->where('status', 'paid')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->entitlements->ftsaProductCodes()))
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->first();
        }

        if ($order === null) {
            $licenseIds = License::query()
                ->where('assigned_user_id', $telegramUserId)
                ->where('status', 'active')
                ->pluck('id');

            if ($licenseIds->isNotEmpty()) {
                $order = Order::query()
                    ->whereIn('license_id', $licenseIds->all())
                    ->where('status', 'paid')
                    ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $this->entitlements->ftsaProductCodes()))
                    ->orderByDesc('paid_at')
                    ->orderByDesc('id')
                    ->first();
            }
        }

        $endsAt = $order ? $this->entitlements->ftsaEntitlementEndsAt($order) : null;

        return [
            'active' => $this->canAccessFtsa($telegramUserId, $email),
            'ends_at' => $endsAt,
            'order' => $order,
        ];
    }
}
