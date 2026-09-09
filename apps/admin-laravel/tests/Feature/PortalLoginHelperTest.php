<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalLoginHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_login_helper_reaches_the_baseline_form(): void
    {
        $this->portalLogin();

        $response = $this->get('/portal/baseline/baru');

        $response->assertStatus(200);
        $response->assertSee('Tabel proteksi', false);
    }

    public function test_financial_baseline_factory_persists(): void
    {
        $baseline = FinancialBaseline::factory()->withLegacyTotalDebt(12_000_000)->create();

        $this->assertDatabaseHas('financial_baselines', [
            'id' => $baseline->id,
            'total_debt' => 12_000_000,
        ]);
    }
}
