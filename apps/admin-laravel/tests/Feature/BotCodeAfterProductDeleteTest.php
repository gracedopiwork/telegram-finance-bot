<?php

namespace Tests\Feature;

use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
use App\Support\PortalSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prod: PORTAL_BOT_ONLY_PRODUCT_CODES=yfd-first-aid. Pembeli lama punya order plan=yfd-bot-telegram;
 * kode itu dulu hanya dikenali lewat baris produknya (aktif + featured). Setelah produk itu dihapus,
 * digital_product_id jadi NULL dan dashboard transaksi menolak mereka.
 */
class BotCodeAfterProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 777_001;

    private const EMAIL = 'lama@example.com';

    public function test_first_aid_buyer_keeps_dashboard_after_their_product_row_is_deleted(): void
    {
        config(['portal.bot_only_product_codes' => ['yfd-first-aid']]);
        CpDigitalProduct::query()->where('code', 'yfd-bot-telegram')->delete();

        $license = License::query()->create([
            'license_key' => 'TFB-OLD1-OLD1-OLD1',
            'plan' => 'yfd-bot-telegram',
            'status' => 'active',
            'expires_at' => now()->addMonths(6),
            'max_accounts' => 1,
            'assigned_user_id' => self::TG,
        ]);
        Order::query()->create([
            'order_code' => 'YFD-OLD-0001',
            'full_name' => 'Pembeli Lama',
            'email' => self::EMAIL,
            'plan' => 'yfd-bot-telegram',
            'digital_product_id' => null,
            'product_name' => 'YFD First Aid',
            'amount' => 299000,
            'currency' => 'IDR',
            'status' => 'paid',
            'payment_gateway' => 'pivot',
            'paid_at' => now()->subMonths(4),
            'license_id' => $license->id,
        ]);

        $this->portalLogin(self::TG, self::EMAIL);
        $this->withSession([PortalSession::LICENSE_ID => $license->id]);

        // Sebelum fix: 302 ke /portal/emotional ("Dashboard transaksi tersedia setelah membeli YFD First Aid").
        $this->get('/portal/dashboard')->assertOk();
    }
}
