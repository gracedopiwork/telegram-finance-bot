<?php

namespace Tests\Feature;

use App\Support\ConsultationPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_26_120000_point_consultation_service_to_detail_page.php';

    public function test_detail_page_shows_founder_sections_and_charged_prices(): void
    {
        $response = $this->get('/layanan/konsultasi');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Consultation with Financial Planner 1-on-1',
            'Cakupan',
            'Persiapan konsultasi',
            'Kontrol dan evaluasi',
            'Tarif',
        ]);

        $stages = ConsultationPricing::stages();
        $this->assertNotEmpty($stages);
        foreach ($stages as $tier) {
            $response->assertSee(ConsultationPricing::formatRupiah((int) $tier['session_price']).'/jam');
        }

        $response->assertSee('href="'.route('checkup.show').'"', false);
        $response->assertSee('href="'.route('company.pertemuan').'"', false);
    }

    public function test_paket_redirects_permanently(): void
    {
        $this->get('/paket')->assertStatus(301)->assertRedirect('/layanan/konsultasi');
    }

    public function test_layout_links_point_to_detail_page(): void
    {
        $html = $this->get('/layanan/konsultasi')->assertOk()->getContent();
        $detail = 'href="'.url('/layanan/konsultasi').'"';

        // Mega-menu (desktop) + footer.
        $this->assertGreaterThanOrEqual(2, substr_count($html, $detail));
        $this->assertStringNotContainsString('/paket"', $html);
    }

    public function test_service_card_migration_up_and_down(): void
    {
        $consult = DB::table('cp_services')->insertGetId([
            'title' => 'Consultation with Financial Planner 1-on-1',
            'cta_label' => 'Lihat Paket Konsultasi',
            'cta_route' => 'company.pertemuan',
            'sort' => 3,
        ]);
        $recovery = DB::table('cp_services')->insertGetId([
            'title' => 'Financial Recovery Program',
            'cta_label' => 'Lihat Program Recovery',
            'cta_route' => 'company.bundle.recovery',
            'sort' => 6,
        ]);
        $route = fn (int $id) => DB::table('cp_services')->where('id', $id)->value('cta_route');

        $migration = require base_path(self::MIGRATION);

        $migration->up();
        $this->assertSame('company.konsultasi', $route($consult));
        $this->assertSame('company.bundle.recovery', $route($recovery));

        $migration->down();
        $this->assertSame('company.pertemuan', $route($consult));
        $this->assertSame('company.bundle.recovery', $route($recovery));
    }
}
