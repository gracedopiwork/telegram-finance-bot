<?php

namespace Tests\Feature;

use App\Mail\CheckupResultMail;
use App\Models\FinancialBaseline;
use App\Services\DiagnosticConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * W3 (revisi founder): hasil FHCU — halaman dan email — hanya menawarkan konsultasi,
 * tanpa checkout bot maupun bundle.
 */
class CheckupResultCtaTest extends TestCase
{
    use RefreshDatabase;

    private function growingBaseline(): FinancialBaseline
    {
        return FinancialBaseline::factory()->create([
            'email' => 'hasil@example.com',
            'financial_stage' => 'growing',
            'stage_label' => 'Growing',
        ]);
    }

    public function test_result_page_offers_consultation_only(): void
    {
        $baseline = $this->growingBaseline();

        $this->withSession(['checkup.result_id' => $baseline->id])
            ->get(route('checkup.result'))
            ->assertOk()
            ->assertSee('Booking Konsultasi')
            ->assertSee(e(route('company.konsultasi')), false)
            ->assertSee('Rp 250.000')
            // Menu produk di header situs memuat link checkout produk lain; yang dicek CTA hasil.
            ->assertDontSee(e(route('checkout.show', 'yfd-bot-telegram')), false)
            ->assertDontSee('Checkout YFD First Aid');
    }

    public function test_result_email_offers_consultation_only(): void
    {
        $baseline = $this->growingBaseline();
        $stageDisplay = app(DiagnosticConfigService::class)->stageDisplay('growing', 25);

        $html = (new CheckupResultMail($baseline, $stageDisplay, 3))->render();

        $this->assertStringContainsString(url('/layanan/konsultasi'), $html);
        $this->assertStringContainsString('Booking Konsultasi', $html);
        $this->assertStringNotContainsString('/checkout/', $html);
    }
}
