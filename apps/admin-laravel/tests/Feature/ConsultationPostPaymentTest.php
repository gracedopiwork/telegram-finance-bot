<?php

namespace Tests\Feature;

use App\Jobs\DeliverPaidOrderJob;
use App\Jobs\SendBookingAdminNotificationJob;
use App\Jobs\SendConsultationPaidEmailJob;
use App\Mail\BookingPaidAdminMail;
use App\Mail\ConsultationPaidMail;
use App\Models\ConsultationSlot;
use App\Models\CpAdvisor;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingPaidAdminNotifier;
use App\Services\ConsultationSlotService;
use App\Services\FonnteClient;
use App\Services\TelegramBotClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConsultationPostPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pivot.client_id' => 'test-client-id',
            'services.pivot.client_secret' => 'test-client-secret',
            'services.pivot.callback_key' => 'test-callback-key',
            'services.pivot.base_url' => 'https://pivot.test',
            'services.booking_notify.channels' => ['email', 'telegram'],
            'services.booking_notify.emails' => ['founder1@example.com', 'founder2@example.com'],
            'services.booking_notify.telegram_ids' => ['1124600109', '1025176608', '8269562905'],
            'services.booking_notify.wa' => ['6281111111111'],
            'services.telegram.bot_token' => 'test-bot-token',
            'services.fonnte.token' => '',
        ]);
    }

    private function heldBooking(string $status = 'pending'): Order
    {
        $advisor = CpAdvisor::query()->forceCreate(['name' => 'dr. Ayuti', 'is_active' => true, 'sort' => 1]);
        $startsAt = now(ConsultationSlot::BOOKING_TIMEZONE)->addDays(3)->setTime(20, 0);

        $slot = ConsultationSlot::query()->forceCreate([
            'advisor_id' => $advisor->id,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $startsAt->copy()->addHour()->format('Y-m-d H:i:s'),
            'status' => ConsultationSlot::STATUS_HELD,
            'held_until' => now()->addMinutes(30),
            'booking_code' => 'YFD-BOOK0001',
            'guest_name' => 'Catherina Fransiska',
            'guest_phone' => '6281234567890',
            'service_type' => 'standard',
        ]);

        $order = Order::query()->create([
            'order_code' => 'YFD-CS-ORDER001',
            'full_name' => 'Catherina Fransiska',
            'email' => 'klien@example.com',
            'phone' => '6281234567890',
            'plan' => 'consultation_session',
            'order_kind' => 'consultation_session',
            'product_name' => 'Financial Consultation — sesi 1 jam (Steady)',
            'amount' => 500000,
            'original_price' => 500000,
            'currency' => 'IDR',
            'status' => $status,
            'payment_gateway' => 'pivot',
            'consultation_slot_id' => $slot->id,
        ]);
        $slot->update(['order_id' => $order->id]);

        return $order;
    }

    private function paidWebhook(Order $order): void
    {
        $this->postJson('/webhooks/pivot', [
            'event' => 'PAYMENT.PAID',
            'data' => ['id' => 'pivot-session-1', 'clientReferenceId' => $order->order_code, 'status' => 'PAID'],
        ], ['X-Callback-Api-Key' => 'test-callback-key'])->assertOk()->assertJsonPath('ok', true);
    }

    public function test_paid_webhook_confirms_slot_and_queues_notifications_without_sending_inline(): void
    {
        Queue::fake();
        Mail::fake();
        Http::fake();
        $order = $this->heldBooking();

        $this->paidWebhook($order);

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame(ConsultationSlot::STATUS_CONFIRMED, $order->consultationSlot->status);
        $this->assertNotNull($order->admin_notified_at);
        $this->assertNotNull($order->purchase_delivery_queued_at);
        $this->assertNull($order->purchase_delivery_sent_at);

        Queue::assertPushed(SendBookingAdminNotificationJob::class, 5);
        Queue::assertPushed(SendBookingAdminNotificationJob::class, fn ($job) => $job->channel === 'email' && $job->connection === 'database');
        Queue::assertPushed(SendBookingAdminNotificationJob::class, fn ($job) => $job->channel === 'telegram' && $job->recipient === '8269562905');
        Queue::assertNotPushed(SendBookingAdminNotificationJob::class, fn ($job) => $job->channel === 'wa');
        Queue::assertPushed(SendConsultationPaidEmailJob::class, 1);
        Queue::assertPushed(SendConsultationPaidEmailJob::class, fn ($job) => $job->connection === 'database');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_notifications_are_queued_once_when_webhook_and_finish_page_both_deliver(): void
    {
        Queue::fake();
        $order = $this->heldBooking();

        $this->paidWebhook($order);
        DeliverPaidOrderJob::dispatchSync($order->id);
        DeliverPaidOrderJob::dispatchSync($order->id);

        Queue::assertPushed(SendBookingAdminNotificationJob::class, 5);
        Queue::assertPushed(SendConsultationPaidEmailJob::class, 1);
    }

    public function test_finish_page_sync_marks_paid_and_queues_without_sending_in_request(): void
    {
        Queue::fake();
        Mail::fake();
        $order = $this->heldBooking();
        Http::fake([
            'https://pivot.test/v1/access-token' => Http::response(['data' => ['accessToken' => 't', 'expiresIn' => 900]]),
            'https://pivot.test/*' => Http::response(['data' => [[
                'id' => 'pivot-session-1', 'clientReferenceId' => $order->order_code, 'status' => 'PAID',
            ]]]),
        ]);

        $this->get('/checkout/finish/done?order_id='.$order->order_code)->assertOk();

        $this->assertSame('paid', $order->fresh()->status);

        Queue::assertPushed(SendConsultationPaidEmailJob::class, 1);
        Queue::assertPushed(SendBookingAdminNotificationJob::class, 5);
        Mail::assertNothingSent();
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_admin_email_job_sends_plain_booking_summary(): void
    {
        Mail::fake();
        $order = $this->heldBooking('paid');

        (new SendBookingAdminNotificationJob($order->id, 'email', 'founder1@example.com'))
            ->handle(app(BookingPaidAdminNotifier::class), app(TelegramBotClient::class), app(FonnteClient::class));

        Mail::assertSent(BookingPaidAdminMail::class, function (BookingPaidAdminMail $mail) {
            return $mail->hasTo('founder1@example.com')
                && str_contains($mail->text, 'Kode order: YFD-CS-ORDER001')
                && str_contains($mail->text, 'Kode booking: YFD-BOOK0001')
                && str_contains($mail->text, 'No. WA: 6281234567890')
                && str_contains($mail->text, 'Dokter: dr. Ayuti')
                && ! str_contains($mail->text, '*');
        });
    }

    public function test_telegram_failure_throws_so_the_queue_retries(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => false], 500)]);
        $order = $this->heldBooking('paid');

        $this->expectException(\RuntimeException::class);

        dispatch_sync(new SendBookingAdminNotificationJob($order->id, 'telegram', '1124600109'));
    }

    public function test_telegram_job_without_token_is_skipped_not_retried(): void
    {
        config(['services.telegram.bot_token' => '']);
        Http::fake();
        $order = $this->heldBooking('paid');

        dispatch_sync(new SendBookingAdminNotificationJob($order->id, 'telegram', '1124600109'));

        Http::assertNothingSent();
    }

    public function test_buyer_email_job_sends_codes_schedule_and_marks_sent(): void
    {
        Mail::fake();
        $order = $this->heldBooking('paid');

        dispatch_sync(new SendConsultationPaidEmailJob($order->id));

        Mail::assertSent(ConsultationPaidMail::class, function (ConsultationPaidMail $mail) use ($order) {
            $html = $mail->render();

            return $mail->hasTo('klien@example.com')
                && str_contains($html, 'YFD-CS-ORDER001')
                && str_contains($html, 'YFD-BOOK0001')
                && str_contains($html, $order->consultationSlot->labelSchedule())
                && str_contains($html, 'Konfirmasi booking Anda dengan admin kami')
                && str_contains($html, 'Lakukan konfirmasi dengan admin via WhatsApp');
        });
        $this->assertNotNull($order->fresh()->purchase_delivery_sent_at);
    }

    public function test_buyer_email_failure_leaves_sent_at_empty(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP timeout'));
        $order = $this->heldBooking('paid');

        try {
            dispatch_sync(new SendConsultationPaidEmailJob($order->id));
            $this->fail('Job harus melempar agar queue me-retry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('SMTP timeout', $e->getMessage());
        }

        $this->assertNull($order->fresh()->purchase_delivery_sent_at);
    }

    public function test_admin_resend_queues_buyer_email_only(): void
    {
        Queue::fake();
        $order = $this->heldBooking('paid');
        $order->forceFill(['admin_notified_at' => now(), 'purchase_delivery_queued_at' => now()])->save();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.orders.resendConsultationEmail', $order))
            ->assertRedirect();

        Queue::assertPushed(SendConsultationPaidEmailJob::class, 1);
        Queue::assertNotPushed(SendBookingAdminNotificationJob::class);
    }

    public function test_paid_finish_page_has_new_copy_and_single_whatsapp_button(): void
    {
        $order = $this->heldBooking('paid');

        $response = $this->get('/checkout/finish/done?order_id='.$order->order_code)->assertOk();

        $response->assertSee('Lakukan konfirmasi dengan admin via WhatsApp')
            ->assertSee('Konfirmasi booking Anda dengan admin kami')
            ->assertSee('Kode Booking:')
            ->assertDontSee('Admin akan mengirim')
            ->assertDontSee('Buka bot di Telegram')
            ->assertDontSee('Kembali ke Beranda')
            ->assertDontSee('Buka ringkasan booking di WhatsApp');

        preg_match('~href="(https://wa\.me/[^"]+)"[^>]*>\s*<span[^>]*>chat</span> Konfirmasi booking~', $response->getContent(), $m);
        $this->assertNotEmpty($m, 'Tombol WA konfirmasi tidak ditemukan.');
        $text = rawurldecode(parse_url(html_entity_decode($m[1]), PHP_URL_QUERY));
        $this->assertStringContainsString('*Kode order:* YFD-CS-ORDER001', $text);
        $this->assertStringContainsString('*Kode booking:* YFD-BOOK0001', $text);
        $this->assertStringNotContainsStringIgnoringCase('midtrans', $text);
        $this->assertStringNotContainsStringIgnoringCase('pivot', $text);
    }

    public function test_paid_digital_order_still_shows_bot_and_home_buttons(): void
    {
        config(['services.telegram.bot_username' => 'yfd_test_bot']);
        $order = Order::query()->create([
            'order_code' => 'YFD-DIGI0001',
            'full_name' => 'Pembeli',
            'email' => 'pembeli@example.com',
            'phone' => '6281234567890',
            'plan' => 'lite',
            'product_name' => 'YFD First Aid',
            'amount' => 99000,
            'original_price' => 99000,
            'currency' => 'IDR',
            'status' => 'paid',
            'payment_gateway' => 'pivot',
        ]);

        $this->get('/checkout/finish/done?order_id='.$order->order_code)
            ->assertOk()
            ->assertSee('Kembali ke Beranda')
            ->assertDontSee('Konfirmasi booking Anda dengan admin kami');
    }

    public function test_product_name_follows_site_settings_layanan_label_everywhere(): void
    {
        Mail::fake();
        Setting::put('page.nav.svc_consult', 'Konsultasi Financial Planner YFD', 'text', 'page_nav');
        Setting::bust();
        $order = $this->heldBooking('paid');
        $order->load('consultationSlot.advisor');

        $page = $this->get('/checkout/finish/done?order_id='.$order->order_code)->assertOk();
        $page->assertSee('Konsultasi Financial Planner YFD')->assertDontSee('sesi 1 jam (Steady)');

        dispatch_sync(new SendConsultationPaidEmailJob($order->id));
        Mail::assertSent(ConsultationPaidMail::class, function (ConsultationPaidMail $mail) {
            $html = $mail->render();

            return str_contains($html, 'Konsultasi Financial Planner YFD') && ! str_contains($html, 'sesi 1 jam');
        });

        $wa = rawurldecode((string) parse_url(app(ConsultationSlotService::class)->paidGuestWhatsAppUrl($order->consultationSlot, $order), PHP_URL_QUERY));
        $this->assertStringContainsString('*Layanan:* Konsultasi Financial Planner YFD', $wa);
        $this->assertStringContainsString('Layanan: Konsultasi Financial Planner YFD', app(BookingPaidAdminNotifier::class)->message($order));

        // Tanpa setting: default sama dengan dropdown homepage.
        Setting::query()->where('key', 'page.nav.svc_consult')->delete();
        Setting::bust();
        $this->assertSame('Consultation with Financial Planner', $order->consultationSlot->fresh()->serviceLabel());
    }
}
