<?php

use App\Models\Order;
use App\Services\AffiliateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Pastikan setiap email order lunas punya record affiliate (kode referral).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('affiliates')) {
            return;
        }

        $affiliates = app(AffiliateService::class);

        Order::query()
            ->where('status', 'paid')
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($affiliates): void {
                foreach ($orders as $order) {
                    $email = strtolower(trim((string) $order->email));
                    if ($email === '') {
                        continue;
                    }
                    $affiliates->ensureForPortalUser(
                        $email,
                        $order->full_name ?: null,
                        $order->license_id,
                    );
                }
            });
    }

    public function down(): void
    {
        // Keep affiliate rows — may already have commissions.
    }
};
