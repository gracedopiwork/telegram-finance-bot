<?php

namespace Tests\Feature;

use App\Support\InvestmentRegister;
use Tests\TestCase;

/**
 * Bagian InvestmentRegister yang butuh framework: rows() mendekorasi tiap baris
 * dan isStale() membaca config('baseline_assessment.review_months').
 *
 * Aritmetika murninya ada di tests/Unit/InvestmentRegisterTest.php.
 */
class InvestmentRegisterRowsTest extends TestCase
{
    public function test_rows_decorates_each_row_with_its_own_yield(): void
    {
        $rows = InvestmentRegister::rows([
            ['investment_type' => 'emas', 'purpose' => 'Dana Pensiun',
                'realized_principal' => 100_000_000, 'realized_actual' => 80_000_000],
            ['investment_type' => 'saham', 'realized_principal' => null, 'realized_actual' => 5_000_000],
        ]);

        $this->assertSame('Emas', $rows[0]['investment_type_label']);
        $this->assertSame(-20.0, $rows[0]['yield_percent']);
        $this->assertSame('-20%', $rows[0]['yield_label']);

        // Tanpa pokok tidak ada dasar pembanding — "—", bukan 0%.
        $this->assertNull($rows[1]['yield_percent']);
        $this->assertSame('—', $rows[1]['yield_label']);
    }

    public function test_rows_ignores_junk_entries(): void
    {
        $this->assertSame([], InvestmentRegister::rows(null));
        $this->assertSame([], InvestmentRegister::rows('bukan array'));
        $this->assertCount(1, InvestmentRegister::rows([['purpose' => 'Dana Pensiun'], 'sampah']));
    }

    public function test_total_yield_over_raw_rows_is_money_weighted(): void
    {
        $raw = [
            ['realized_principal' => 1_000_000_000, 'realized_actual' => 900_000_000],
            ['realized_principal' => 1_000_000, 'realized_actual' => 2_000_000],
        ];

        $this->assertSame(1_001_000_000, InvestmentRegister::totalPrincipal($raw));
        $this->assertSame(902_000_000, InvestmentRegister::totalActual($raw));
        $this->assertEqualsWithDelta(-9.89, InvestmentRegister::totalYieldPercent($raw), 0.01);
    }

    public function test_a_row_without_a_date_counts_as_stale(): void
    {
        // Tanpa tanggal kita tidak bisa menjamin datanya masih berlaku.
        $this->assertTrue(InvestmentRegister::isStale(null));
        $this->assertTrue(InvestmentRegister::isStale(''));
        $this->assertFalse(InvestmentRegister::isStale(now()->toDateString()));
        $this->assertTrue(InvestmentRegister::isStale(
            now()->subMonths(InvestmentRegister::staleAfterMonths() + 1)->toDateString()
        ));
    }

    public function test_stale_window_is_two_baseline_cycles(): void
    {
        $this->assertSame(
            max(1, (int) config('baseline_assessment.review_months', 3)) * 2,
            InvestmentRegister::staleAfterMonths()
        );
    }
}
