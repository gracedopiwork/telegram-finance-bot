<?php

use App\Models\Order;
use App\Services\AffiliateService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(AffiliateService::class);

        Order::query()
            ->where('status', 'paid')
            ->whereNotNull('affiliate_id')
            ->with(['digitalProduct', 'affiliate'])
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($service) {
                foreach ($orders as $order) {
                    $service->creditCommissionForPaidOrder($order);
                }
            });
    }

    public function down(): void
    {
        // no-op: komisi historis tidak dihapus otomatis
    }
};
