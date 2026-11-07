<?php

namespace Tests\Feature;

use App\Models\ConsultationSlot;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BooksConsultations;
use Tests\TestCase;

class BookingWhatsAppRequiredTest extends TestCase
{
    use BooksConsultations;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePivot();
    }

    public function test_booking_without_whatsapp_is_rejected_for_every_service_type(): void
    {
        $this->fhcu('klien@example.com', 'growing');

        foreach ([
            ['service_type' => 'standard'],
            ['service_type' => 'recovery'],
            ['service_type' => 'premarital', 'session_role' => 'couple'],
        ] as $overrides) {
            $slot = $this->openSlot();

            $this->from(route('company.pertemuan'))
                ->book($slot, array_merge($overrides, ['phone' => '']))
                ->assertRedirect(route('company.pertemuan'))
                ->assertSessionHasErrors('phone');

            $this->assertSame(ConsultationSlot::STATUS_OPEN, $slot->fresh()->status, $overrides['service_type']);
        }

        $this->assertSame(0, Order::count());
    }

    public function test_invalid_whatsapp_is_rejected(): void
    {
        $this->fhcu('klien@example.com', 'growing');
        $slot = $this->openSlot();

        $this->from(route('company.pertemuan'))
            ->book($slot, ['phone' => '12345'])
            ->assertSessionHasErrors('phone');

        $this->assertSame(0, Order::count());
    }

    public function test_whatsapp_is_stored_normalised_on_order_and_slot(): void
    {
        $this->fhcu('klien@example.com', 'growing');
        $slot = $this->openSlot();

        $this->book($slot, ['phone' => '0812-3456-7890'])->assertRedirect('https://pivot.test/pay/1');

        $this->assertSame('6281234567890', Order::sole()->phone);
        $this->assertSame('6281234567890', $slot->fresh()->guest_phone);
    }

    public function test_parked_booking_keeps_normalised_whatsapp(): void
    {
        $slot = $this->openSlot();

        $this->book($slot, ['phone' => '+62 812 3456 7890'])->assertRedirect(route('checkup.show'));

        $this->assertSame('6281234567890', session('booking.pending.phone'));
    }

    public function test_form_marks_whatsapp_required(): void
    {
        $this->get('/pertemuan')
            ->assertOk()
            ->assertSee('No. WhatsApp *')
            ->assertDontSee('No. WhatsApp (opsional)');
    }
}
