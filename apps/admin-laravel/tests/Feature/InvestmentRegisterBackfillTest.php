<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\InvestmentRegister;
use App\Support\InvestmentRegisterBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentRegisterBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_legacy_total_investment_into_one_row(): void
    {
        $baseline = FinancialBaseline::factory()->withLegacyTotalInvestment(12_000_000)->create([
            'investment_register' => null,
        ]);

        $this->assertSame(1, (new InvestmentRegisterBackfill)());

        $rows = $baseline->fresh()->investment_register;
        $this->assertCount(1, $rows);
        $this->assertSame(InvestmentRegisterBackfill::PLACEHOLDER_PURPOSE, $rows[0]['purpose']);
        $this->assertSame('lainnya', $rows[0]['investment_type']);
        $this->assertSame(12_000_000, $rows[0]['realized_actual']);
        $this->assertNotSame('', $rows[0]['investment_id']);
    }

    public function test_principal_stays_null_so_yield_is_not_invented(): void
    {
        FinancialBaseline::factory()->withLegacyTotalInvestment(12_000_000)->create([
            'investment_register' => null,
        ]);

        (new InvestmentRegisterBackfill)();

        $rows = InvestmentRegister::rows(FinancialBaseline::query()->first()->investment_register);

        // Angka lama adalah nilai yang dipegang, bukan modal yang disetor —
        // 0% akan berarti "balik modal", klaim yang tidak pernah diverifikasi.
        $this->assertNull($rows[0]['realized_principal']);
        $this->assertNull($rows[0]['yield_percent']);
        $this->assertSame('—', $rows[0]['yield_label']);
    }

    public function test_last_updated_is_the_migration_date_not_the_original_fill_date(): void
    {
        FinancialBaseline::factory()->withLegacyTotalInvestment(12_000_000)->create([
            'investment_register' => null,
            'assessed_at' => now()->subYears(2),
        ]);

        (new InvestmentRegisterBackfill)();

        $rows = FinancialBaseline::query()->first()->investment_register;
        $this->assertSame(now()->toDateString(), $rows[0]['last_updated']);
    }

    public function test_it_derives_the_same_total_it_replaced(): void
    {
        FinancialBaseline::factory()->withLegacyTotalInvestment(12_000_000)->create([
            'investment_register' => null,
        ]);

        (new InvestmentRegisterBackfill)();

        $baseline = FinancialBaseline::query()->first();
        $this->assertSame(
            $baseline->total_investment,
            InvestmentRegister::totalActual($baseline->investment_register),
            'Backfill tidak boleh mengubah angka yang sudah dibaca konsumen lama.'
        );
    }

    public function test_it_skips_baselines_that_already_have_a_register(): void
    {
        $existing = [[
            'investment_id' => 'INV-KEEP',
            'investment_type' => 'saham',
            'purpose' => 'Dana Pendidikan',
            'realized_principal' => 5_000_000,
            'realized_actual' => 6_000_000,
            'last_updated' => now()->toDateString(),
        ]];

        $baseline = FinancialBaseline::factory()
            ->withLegacyTotalInvestment(12_000_000)
            ->withInvestmentRegister($existing)
            ->create();

        $this->assertSame(0, (new InvestmentRegisterBackfill)());
        $this->assertSame('INV-KEEP', $baseline->fresh()->investment_register[0]['investment_id']);
    }

    public function test_it_ignores_null_and_zero_totals(): void
    {
        FinancialBaseline::factory()->create(['total_investment' => null, 'investment_register' => null]);
        FinancialBaseline::factory()->create(['total_investment' => 0, 'investment_register' => null]);

        $this->assertSame(0, (new InvestmentRegisterBackfill)());
        $this->assertSame(0, FinancialBaseline::query()->whereNotNull('investment_register')->count());
    }
}
