<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\Order;
use App\Services\LicenseEntitlementService;
use App\Services\PortalAccessService;
use Illuminate\Console\Command;

class PortalAccessDebugCommand extends Command
{
    protected $signature = 'portal:debug-access {email} {license_key}';

    protected $description = 'Cek hak akses portal untuk email + kode lisensi (FTSA-only vs bot)';

    public function handle(
        LicenseEntitlementService $entitlements,
        PortalAccessService $access,
    ): int {
        $email = strtolower(trim((string) $this->argument('email')));
        $licenseKey = strtoupper(trim((string) $this->argument('license_key')));

        $license = License::query()->where('license_key', $licenseKey)->first();
        if ($license === null) {
            $this->error('Lisensi tidak ditemukan.');

            return self::FAILURE;
        }

        $orders = Order::query()
            ->where('license_id', $license->id)
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->with('digitalProduct')
            ->orderByDesc('id')
            ->get();

        $this->info("License #{$license->id} ({$license->license_key})");
        $this->line('plan: '.($license->plan ?: '—'));
        $this->line('assigned_user_id: '.($license->assigned_user_id ?: '—'));
        $this->line('hasFtsaOnLicense: '.($entitlements->hasPaidFtsaOrderOnLicense($license) ? 'yes' : 'no'));
        $this->line('hasBotOnLicense: '.($entitlements->hasPaidBotOrderOnLicense($license) ? 'yes' : 'no'));
        $this->line('botProductCodes: '.json_encode($entitlements->botProductCodes()));
        $this->line('ftsaProductCodes: '.json_encode($entitlements->ftsaProductCodes()));
        $this->line('isFtsaOnlyBuyer(email): '.($access->isFtsaOnlyPortalUser($email, (int) ($license->assigned_user_id ?: 0)) ? 'yes' : 'no'));
        $this->newLine();
        $this->info('Paid orders on license for this email:');

        if ($orders->isEmpty()) {
            $this->warn('  (tidak ada)');

            return self::FAILURE;
        }

        foreach ($orders as $order) {
            $code = (string) ($order->digitalProduct?->code ?? $order->plan ?? '—');
            $this->line("  #{$order->id} {$code} @ {$order->paid_at}");
        }

        $portalMode = $entitlements->hasPaidFtsaOrderOnLicense($license)
            && ! $entitlements->hasPaidBotOrderOnLicense($license)
            ? 'FTSA_ONLY'
            : ($entitlements->hasPaidBotOrderOnLicense($license) ? 'BOT' : 'UNKNOWN');

        $this->newLine();
        $this->info("Expected portal mode: {$portalMode}");

        return self::SUCCESS;
    }
}
