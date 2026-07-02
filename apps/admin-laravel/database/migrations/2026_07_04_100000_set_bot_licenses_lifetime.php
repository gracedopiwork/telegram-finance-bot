<?php

use App\Models\License;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('licenses') || ! Schema::hasTable('orders')) {
            return;
        }

        $botCodes = array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            explode(',', (string) env('PORTAL_BOT_ONLY_PRODUCT_CODES', 'yfd-bot-telegram'))
        )));

        if ($botCodes === []) {
            return;
        }

        $licenseIds = Order::query()
            ->where('status', 'paid')
            ->whereNotNull('license_id')
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $botCodes))
            ->pluck('license_id')
            ->unique()
            ->filter()
            ->all();

        if ($licenseIds !== []) {
            License::query()->whereIn('id', $licenseIds)->update(['expires_at' => null]);
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan tanggal expired lama.
    }
};
