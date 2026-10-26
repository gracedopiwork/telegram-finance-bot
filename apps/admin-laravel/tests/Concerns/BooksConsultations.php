<?php

namespace Tests\Concerns;

use App\Models\ConsultationSlot;
use App\Models\CpAdvisor;
use App\Models\FinancialBaseline;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/**
 * Helper booking konsultasi untuk feature test: slot open, FHCU per email, Pivot palsu.
 * Dipakai ConsultationBookingPricingTest (W1) dan gate FHCU (W3).
 */
trait BooksConsultations
{
    protected function fakePivot(): void
    {
        config([
            'services.pivot.client_id' => 'test-client-id',
            'services.pivot.client_secret' => 'test-client-secret',
            'services.pivot.base_url' => 'https://pivot.test',
        ]);

        Http::fake([
            'https://pivot.test/v1/access-token' => Http::response([
                'data' => ['accessToken' => 'test-token', 'expiresIn' => 900],
            ]),
            'https://pivot.test/v2/payments' => Http::response([
                'data' => ['id' => 'pivot-pay-1', 'paymentUrl' => 'https://pivot.test/pay/1'],
            ]),
        ]);
    }

    /**
     * Nominal (rupiah) yang dikirim ke Pivot /v2/payments, urut sesuai request.
     *
     * @return list<int>
     */
    protected function pivotPaymentAmounts(): array
    {
        return Http::recorded(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/v2/payments'))
            ->map(fn (array $pair) => (int) $pair[0]['amount']['value'])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function openSlot(array $attributes = []): ConsultationSlot
    {
        $advisorId = $attributes['advisor_id'] ?? CpAdvisor::query()->forceCreate([
            'name' => 'dr. Test Advisor',
            'is_active' => true,
            'sort' => 1,
        ])->id;

        $startsAt = now(ConsultationSlot::BOOKING_TIMEZONE)->addDays(3)->setTime(10, 0);

        return ConsultationSlot::query()->forceCreate(array_merge([
            'advisor_id' => $advisorId,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $startsAt->copy()->addHour()->format('Y-m-d H:i:s'),
            'status' => ConsultationSlot::STATUS_OPEN,
        ], $attributes));
    }

    /**
     * POST /pertemuan/book dengan data valid; $overrides menimpa field form.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function book(ConsultationSlot $slot, array $overrides = []): TestResponse
    {
        return $this->post(route('company.pertemuan.book'), array_merge([
            'slot_id' => $slot->id,
            'advisor_id' => $slot->advisor_id,
            'name' => 'Klien Test',
            'email' => 'klien@example.com',
            'phone' => '081234567890',
            'service_type' => 'standard',
        ], $overrides));
    }

    protected function fhcu(string $email, string $stage, ?\DateTimeInterface $assessedAt = null): FinancialBaseline
    {
        $assessedAt ??= now();

        return FinancialBaseline::factory()->create([
            'email' => $email,
            'financial_stage' => $stage,
            'stage_label' => ucfirst($stage),
            'assessed_at' => $assessedAt,
            'next_review_at' => Carbon::instance($assessedAt)->addMonths(3),
        ]);
    }
}
