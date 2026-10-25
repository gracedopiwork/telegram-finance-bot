<?php

namespace Tests\Feature;

use App\Models\ConsultationSlot;
use App\Models\Order;
use App\Models\Setting;
use App\Services\ConsultationCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BooksConsultations;
use Tests\TestCase;

class ConsultationBookingPricingTest extends TestCase
{
    use BooksConsultations;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePivot();
    }

    private function assertCharged(int $amount): void
    {
        $this->assertSame($amount, Order::sole()->amount);
        $this->assertSame([$amount], $this->pivotPaymentAmounts());
    }

    public function test_recovery_is_150000_even_when_email_has_comfortable_fhcu(): void
    {
        $this->fhcu('klien@example.com', 'comfortable');

        $this->book($this->openSlot(), [
            'service_type' => 'recovery',
            'situation' => 'Krisis hutang / pinjol / judol',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertCharged(150_000);
        $this->assertNull(ConsultationSlot::sole()->stage_key);
    }

    public function test_recovery_without_fhcu_is_booked_at_150000(): void
    {
        $this->book($this->openSlot(), [
            'service_type' => 'recovery',
            'situation' => 'Financial burnout',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertCharged(150_000);
    }

    public function test_premarital_couple_is_500000_without_fhcu(): void
    {
        $this->book($this->openSlot(), [
            'service_type' => 'premarital',
            'session_role' => 'couple',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertCharged(500_000);
    }

    public function test_premarital_female_uses_fhcu_stage(): void
    {
        $this->fhcu('klien@example.com', 'growing');

        $this->book($this->openSlot(), [
            'service_type' => 'premarital',
            'session_role' => 'female',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertCharged(250_000);
    }

    public function test_standard_uses_fhcu_stage_not_form_stage(): void
    {
        $this->fhcu('klien@example.com', 'steady');

        $this->book($this->openSlot(), [
            'service_type' => 'standard',
            'stage' => 'surviving',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertCharged(500_000);
    }

    public function test_premarital_requires_session_role(): void
    {
        $this->fhcu('klien@example.com', 'growing');

        $this->book($this->openSlot(), ['service_type' => 'premarital'])
            ->assertSessionHasErrors('session_role');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame([], $this->pivotPaymentAmounts());
    }

    public function test_overtime_blocked_for_fixed_price_sessions(): void
    {
        $this->fhcu('klien@example.com', 'comfortable');
        $slot = $this->openSlot();
        $this->book($slot, [
            'service_type' => 'recovery',
            'situation' => 'Financial burnout',
        ])->assertRedirect('https://pivot.test/pay/1');

        $slot->refresh();
        $slot->forceFill(['status' => ConsultationSlot::STATUS_CONFIRMED])->save();

        try {
            app(ConsultationCheckoutService::class)->createOvertimeInvoice($slot);
            $this->fail('Overtime untuk sesi tarif tetap harus ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slot', $e->errors());
        }

        $this->assertSame(1, Order::query()->count());
    }

    public function test_booking_pages_show_the_charged_price(): void
    {
        $this->get('/pertemuan?type=recovery')
            ->assertOk()
            ->assertSee('Rp 150.000')
            ->assertDontSee('Estimasi mulai');

        $this->get('/pertemuan?type=premarital')
            ->assertOk()
            ->assertSee('Rp 500.000');

        $this->get('/pertemuan')
            ->assertOk()
            ->assertDontSee('name="stage"', false)
            ->assertSee('Rp 250.000');
    }

    public function test_admin_price_settings_do_not_change_charge(): void
    {
        Setting::put('pricing.stage.growing.price_min', '999000', 'text', 'page_pricing');
        Setting::put('pricing.recovery_from', '1', 'text', 'page_pricing');

        $this->fhcu('growing@example.com', 'growing');
        $this->book($this->openSlot(), ['email' => 'growing@example.com'])
            ->assertRedirect('https://pivot.test/pay/1');
        $this->book($this->openSlot(['starts_at' => now(ConsultationSlot::BOOKING_TIMEZONE)->addDays(4)->setTime(10, 0)->format('Y-m-d H:i:s')]), [
            'email' => 'recovery@example.com',
            'service_type' => 'recovery',
            'situation' => 'Financial burnout',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertSame(
            [250_000, 150_000],
            Order::query()->orderBy('id')->pluck('amount')->map(fn ($a) => (int) $a)->all(),
        );
        $this->assertSame([250_000, 150_000], $this->pivotPaymentAmounts());
    }
}
