<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PivotWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_pivot_webhook_get_is_healthy(): void
    {
        $this->getJson('/webhooks/pivot')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_pivot_webhook_marks_consultation_order_paid(): void
    {
        config([
            'services.pivot.client_id' => 'test-client-id',
            'services.pivot.client_secret' => 'test-client-secret',
            'services.pivot.callback_key' => 'test-callback-key',
        ]);

        $order = Order::query()->create([
            'order_code' => 'YFD-CS-TEST01',
            'full_name' => 'Tester',
            'email' => 'tester@example.com',
            'phone' => '081234567890',
            'plan' => 'consultation_session',
            'order_kind' => 'consultation_session',
            'product_name' => 'Consultation',
            'amount' => 150000,
            'original_price' => 150000,
            'currency' => 'IDR',
            'status' => 'pending',
            'payment_gateway' => 'pivot',
        ]);

        $this->postJson('/webhooks/pivot', [
            'event' => 'PAYMENT.PAID',
            'data' => [
                'id' => 'pivot-session-1',
                'clientReferenceId' => $order->order_code,
                'status' => 'PAID',
            ],
        ], [
            'X-Callback-Api-Key' => 'test-callback-key',
        ])->assertOk()->assertJsonPath('ok', true);

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('pivot-session-1', $order->payment_reference);
        $this->assertNotNull($order->paid_at);
    }

    public function test_pivot_webhook_rejects_invalid_callback_key(): void
    {
        config([
            'services.pivot.client_id' => 'test-client-id',
            'services.pivot.client_secret' => 'test-client-secret',
            'services.pivot.callback_key' => 'expected-key',
        ]);

        $this->postJson('/webhooks/pivot', [
            'event' => 'PAYMENT.PAID',
            'data' => ['clientReferenceId' => 'MISSING'],
        ], [
            'X-Callback-Api-Key' => 'wrong-key',
        ])->assertOk()->assertJsonPath('ok', false);
    }
}
