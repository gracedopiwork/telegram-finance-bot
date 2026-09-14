<?php

namespace Tests\Unit;

use App\Mail\FtsaOnlyDeliveredMail;
use App\Mail\PaidOrderDeliveredMail;
use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
use App\Services\OrderDeliveryMailer;
use App\Services\OrderDeliveryMessageBuilder;
use App\Services\PortalOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BundleOrderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_order_is_not_ftsa_only_for_delivery_context(): void
    {
        $order = $this->makePaidOrder('yfd-first-aid-ftsa');

        $ctx = app(PortalOnboardingService::class)->orderDeliveryContext($order);

        $this->assertTrue($ctx['is_bundle']);
        $this->assertTrue($ctx['is_ftsa']);
        $this->assertFalse($ctx['is_ftsa_only']);
        $this->assertFalse($ctx['is_ftsa_upgrade']);
    }

    public function test_bundle_whatsapp_includes_activate_not_ftsa_only_copy(): void
    {
        $order = $this->makePaidOrder('yfd-first-aid-ftsa', licenseKey: 'YFD-TEST-BUNDLE');

        $text = app(OrderDeliveryMessageBuilder::class)->whatsAppText($order);

        $this->assertStringContainsString('/activate YFD-TEST-BUNDLE', $text);
        $this->assertStringNotContainsString('Tidak perlu aktivasi di YFD First Aid', $text);
        $this->assertStringContainsString('FTSA 1–32', $text);
    }

    public function test_bundle_email_uses_paid_order_mail_with_ftsa_unlock(): void
    {
        Mail::fake();

        $order = $this->makePaidOrder('yfd-first-aid-ftsa', email: 'bundle@example.com');

        app(OrderDeliveryMailer::class)->send($order);

        Mail::assertSent(PaidOrderDeliveredMail::class, function (PaidOrderDeliveredMail $mail) {
            return $mail->includeFtsaUnlock === true;
        });
        Mail::assertNotSent(FtsaOnlyDeliveredMail::class);
    }

    public function test_pure_ftsa_still_uses_ftsa_only_email(): void
    {
        Mail::fake();

        $order = $this->makePaidOrder('yfd-ftsa-premium', email: 'ftsa@example.com');

        app(OrderDeliveryMailer::class)->send($order);

        Mail::assertSent(FtsaOnlyDeliveredMail::class);
        Mail::assertNotSent(PaidOrderDeliveredMail::class);
    }

    private function makePaidOrder(string $productCode, string $email = 'buyer@example.com', string $licenseKey = 'YFD-TEST-KEY'): Order
    {
        $product = CpDigitalProduct::query()->firstOrCreate(
            ['code' => $productCode],
            [
                'name' => $productCode,
                'is_active' => true,
                'is_featured' => false,
                'sort' => 1,
                'price' => 100000,
                'currency' => 'IDR',
                'billing_mode' => 'pivot',
            ]
        );

        $license = License::query()->create([
            'license_key' => $licenseKey,
            'status' => 'active',
        ]);

        return Order::query()->create([
            'order_code' => 'ORD-'.strtoupper(substr(md5($productCode.$licenseKey.uniqid('', true)), 0, 8)),
            'full_name' => 'Tester',
            'email' => $email,
            'phone' => '08123456789',
            'plan' => $productCode,
            'digital_product_id' => $product->id,
            'product_name' => $product->name,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => 'paid',
            'paid_at' => now(),
            'license_id' => $license->id,
        ])->load(['license', 'digitalProduct']);
    }
}
