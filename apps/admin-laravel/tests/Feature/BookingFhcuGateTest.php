<?php

namespace Tests\Feature;

use App\Models\ConsultationSlot;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BooksConsultations;
use Tests\TestCase;

/**
 * W3: booking yang butuh FHCU tanpa FHCU valid diparkir di session, diarahkan ke /check-up,
 * lalu dilanjutkan dari halaman hasil.
 */
class BookingFhcuGateTest extends TestCase
{
    use BooksConsultations;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePivot();
    }

    private function secondSlot(): ConsultationSlot
    {
        return $this->openSlot([
            'starts_at' => now(ConsultationSlot::BOOKING_TIMEZONE)->addDays(4)->setTime(10, 0)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_standard_booking_without_fhcu_redirects_to_fhcu_intro(): void
    {
        $slot = $this->openSlot();

        $this->book($slot, ['email' => 'baru@example.com'])->assertRedirect(route('checkup.show'));

        $this->assertSame('baru@example.com', session('booking.pending.email'));
        $this->assertSame('standard', session('booking.pending.service_type'));
        $this->assertArrayNotHasKey('slot_id', session('booking.pending'));
        $this->assertSame(0, Order::query()->count());
        $this->assertSame([], $this->pivotPaymentAmounts());
        $this->assertTrue(ConsultationSlot::query()->bookable()->whereKey($slot->id)->exists());
    }

    public function test_premarital_female_with_expired_fhcu_redirects(): void
    {
        $this->fhcu('klien@example.com', 'growing', now()->subMonths(4));
        $slot = $this->openSlot();

        $this->book($slot, [
            'service_type' => 'premarital',
            'session_role' => 'female',
        ])->assertRedirect(route('checkup.show'));

        $this->assertSame('female', session('booking.pending.session_role'));
        $this->assertSame((int) $slot->advisor_id, (int) session('booking.pending.advisor_id'));
    }

    public function test_recovery_without_fhcu_redirects_but_couple_does_not(): void
    {
        $this->book($this->openSlot(), [
            'email' => 'baru@example.com',
            'service_type' => 'recovery',
            'situation' => 'Financial burnout',
        ])->assertRedirect(route('checkup.show'));

        $this->assertSame('recovery', session('booking.pending.service_type'));
        $this->assertSame('Financial burnout', session('booking.pending.situation'));

        session()->forget('booking.pending');

        $this->book($this->secondSlot(), [
            'email' => 'baru@example.com',
            'service_type' => 'premarital',
            'session_role' => 'couple',
        ])->assertRedirect('https://pivot.test/pay/1');

        $this->assertNull(session('booking.pending'));
    }

    public function test_intro_and_form_show_booking_banner_and_prefilled_email(): void
    {
        $pending = ['booking.pending' => ['email' => 'w3@example.com', 'service_type' => 'standard']];

        $this->withSession($pending)->get('/check-up')
            ->assertOk()
            ->assertSee('Sebelum booking konsultasi')
            ->assertSee('w3@example.com');

        $this->withSession($pending)->get('/check-up/isi')
            ->assertOk()
            ->assertSee('Sebelum booking konsultasi')
            ->assertSee('value="w3@example.com"', false);

        session()->forget('booking.pending');

        $this->get('/check-up')
            ->assertOk()
            ->assertDontSee('Sebelum booking konsultasi');
    }

    public function test_result_offers_continue_booking_with_stage(): void
    {
        $baseline = $this->fhcu('w3@example.com', 'growing');

        $this->withSession([
            'booking.pending' => ['email' => 'W3@example.com', 'service_type' => 'standard'],
            'checkup.result_id' => $baseline->id,
        ])->get(route('checkup.result'))
            ->assertOk()
            ->assertSee('Konsultasi dengan Financial Planner')
            ->assertSee(e(route('company.pertemuan', ['stage' => 'growing', 'lanjut' => 1])), false);

        $this->withSession([
            'booking.pending' => ['email' => 'lain@example.com', 'service_type' => 'standard'],
            'checkup.result_id' => $baseline->id,
        ])->get(route('checkup.result'))
            ->assertOk()
            ->assertSee('Konsultasi dengan Financial Planner')
            ->assertDontSee('lanjut=1', false)
            ->assertSee(e(route('company.konsultasi')), false);
    }

    public function test_continue_link_restores_booking_form(): void
    {
        $pending = ['booking.pending' => [
            'name' => 'Klien Lanjut',
            'email' => 'lanjut@example.com',
            'phone' => '0811',
            'service_type' => 'recovery',
            'situation' => 'Financial burnout',
            'session_role' => null,
            'advisor_id' => null,
            'age' => null,
            'condition' => null,
        ]];

        $this->withSession($pending)->get('/pertemuan?lanjut=1')
            ->assertRedirect(route('company.pertemuan', ['type' => 'recovery']));

        $this->withSession($pending)->followingRedirects()->get('/pertemuan?lanjut=1')
            ->assertOk()
            ->assertSee('value="Klien Lanjut"', false)
            ->assertSee('value="lanjut@example.com"', false);

        session()->forget('booking.pending');

        $this->get('/pertemuan?lanjut=1')->assertOk();
    }

    public function test_successful_booking_clears_pending(): void
    {
        $this->book($this->openSlot(), ['email' => 'baru@example.com'])
            ->assertRedirect(route('checkup.show'));
        $this->assertNotNull(session('booking.pending'));

        $this->fhcu('baru@example.com', 'growing');

        $this->book($this->secondSlot(), ['email' => 'baru@example.com'])
            ->assertRedirect('https://pivot.test/pay/1');
        $this->assertNull(session('booking.pending'));
    }
}
