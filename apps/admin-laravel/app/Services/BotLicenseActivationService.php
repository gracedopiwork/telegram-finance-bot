<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BotLicenseActivationService
{
    public function __construct(
        private readonly LicenseEntitlementService $entitlements,
        private readonly LicenseProvisioningService $provisioning,
        private readonly PortalAccessService $portalAccess,
        private readonly PortalUserMigrationService $migration,
    ) {}

    /**
     * @return array{license_key: string, license_id: int, migrated_from_synthetic: bool}
     */
    public function activate(string $licenseKey, int $telegramUserId, ?string $telegramUsername): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        if ($licenseKey === '' || $telegramUserId <= 0) {
            throw ValidationException::withMessages([
                'license_key' => 'Kode lisensi atau user Telegram tidak valid.',
            ]);
        }

        $license = License::query()->where('license_key', $licenseKey)->first();
        if ($license === null) {
            throw ValidationException::withMessages([
                'license_key' => 'Kode lisensi tidak ditemukan.',
            ]);
        }

        if ($license->status !== 'active') {
            throw ValidationException::withMessages([
                'license_key' => 'Lisensi tidak aktif.',
            ]);
        }

        if (! $this->provisioning->hasPaidBotOrderOnLicense($license)) {
            throw ValidationException::withMessages([
                'license_key' => 'Lisensi ini belum termasuk paket YFD Bot. Beli bot Telegram dulu untuk aktivasi di sini.',
            ]);
        }

        if ($license->expires_at && $license->expires_at->isPast() && ! $this->provisioning->hasPaidBotOrderOnLicense($license)) {
            throw ValidationException::withMessages([
                'license_key' => 'Lisensi sudah expired.',
            ]);
        }

        $previousUserId = (int) ($license->assigned_user_id ?? 0);
        if ($previousUserId > 0 && $previousUserId !== $telegramUserId) {
            if (! $this->portalAccess->isSyntheticPortalUserId($previousUserId)) {
                throw ValidationException::withMessages([
                    'license_key' => 'Lisensi sudah terpakai oleh akun Telegram lain.',
                ]);
            }
        }

        if ($previousUserId === $telegramUserId) {
            return [
                'license_key' => $licenseKey,
                'license_id' => (int) $license->id,
                'migrated_from_synthetic' => false,
            ];
        }

        $email = $this->primaryEmailForLicense($license);
        $migrated = false;

        DB::transaction(function () use ($license, $telegramUserId, $telegramUsername, $previousUserId, $email, &$migrated): void {
            if ($previousUserId > 0
                && $previousUserId !== $telegramUserId
                && $this->portalAccess->isSyntheticPortalUserId($previousUserId)) {
                $this->migration->migrateSyntheticUserToTelegram($previousUserId, $telegramUserId, $email);
                $migrated = true;
            }

            $license->forceFill([
                'assigned_user_id' => $telegramUserId,
                'assigned_username' => $telegramUsername,
                'activated_at' => now(),
                'expires_at' => null,
                'status' => 'active',
            ])->save();

            DB::table('license_activations')->insert([
                'license_id' => $license->id,
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $telegramUsername,
                'activated_at' => now(),
            ]);
        });

        if ($email !== '') {
            app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);
        }

        return [
            'license_key' => $licenseKey,
            'license_id' => (int) $license->id,
            'migrated_from_synthetic' => $migrated,
        ];
    }

    private function primaryEmailForLicense(License $license): string
    {
        $email = Order::query()
            ->where('license_id', $license->id)
            ->where('status', 'paid')
            ->orderByDesc('id')
            ->value('email');

        return strtolower(trim((string) $email));
    }
}
