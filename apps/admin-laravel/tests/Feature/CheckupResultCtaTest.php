<?php

namespace Tests\Feature;

use App\Mail\CheckupResultMail;
use App\Models\DiagnosticStage;
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
            ->assertSee('Konsultasi dengan Financial Planner')
            ->assertSee(e(route('company.konsultasi')), false)
            ->assertSee('Rp 250.000')
            // Menu produk di header situs memuat link checkout produk lain; yang dicek CTA hasil.
            ->assertDontSee(e(route('checkout.show', 'yfd-bot-telegram')), false)
            ->assertDontSee('Checkout YFD First Aid');
    }

    public function test_result_panel_uses_fhcu_form_color_not_stage_color(): void
    {
        DiagnosticStage::query()->create([
            'stage_key' => 'comfortable',
            'label' => 'Comfortable',
            'panel_color' => '#8BB8E8',
            'score_min' => 34,
            'score_max' => 39,
            'sort_order' => 4,
        ]);
        $baseline = FinancialBaseline::factory()->create([
            'email' => 'hasil@example.com',
            'financial_stage' => 'comfortable',
            'stage_label' => 'Comfortable',
        ]);

        $this->withSession(['checkup.result_id' => $baseline->id])
            ->get(route('checkup.result'))
            ->assertOk()
            ->assertSee('linear-gradient('.config('yfd_brand.mint_light').'33', false)
            ->assertDontSee('#8BB8E8', false);
    }

    public function test_result_email_offers_consultation_only(): void
    {
        $baseline = $this->growingBaseline();
        $stageDisplay = app(DiagnosticConfigService::class)->stageDisplay('growing', 25);

        $html = (new CheckupResultMail($baseline, $stageDisplay, 3))->render();

        $this->assertStringContainsString(url('/layanan/konsultasi'), $html);
        $this->assertStringContainsString('Konsultasi dengan Financial Planner', $html);
        $this->assertStringNotContainsString('/checkout/', $html);
    }
}
