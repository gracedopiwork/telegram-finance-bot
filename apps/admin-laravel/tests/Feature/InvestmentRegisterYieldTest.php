<?php

namespace Tests\Feature;

use App\Support\InvestmentRegister;
use Tests\TestCase;

/**
 * Yield adalah untung/rugi terhadap modal, bukan rasio nilai terhadap modal.
 * Spec awal menulis "aktual/pokok x 100%" tapi mencontohkan -20% untuk 80jt/100jt —
 * contohnya yang benar, rumusnya yang keliru. Test ini yang mengunci itu.
 */
class InvestmentRegisterYieldTest extends TestCase
{
    public function test_loss_is_negative_not_a_ratio(): void
    {
        $this->assertSame(-20.0, InvestmentRegister::yieldPercent(100_000_000, 80_000_000));
        $this->assertSame('-20%', InvestmentRegister::formatYield(-20.0));
    }

    public function test_gain_is_positive(): void
    {
        $this->assertSame(25.0, InvestmentRegister::yieldPercent(80_000_000, 100_000_000));
        $this->assertSame('25%', InvestmentRegister::formatYield(25.0));
    }

    public function test_break_even_is_zero(): void
    {
        $this->assertSame(0.0, InvestmentRegister::yieldPercent(50_000_000, 50_000_000));
        $this->assertSame('0%', InvestmentRegister::formatYield(0.0));
    }

    public function test_fractional_yield_keeps_one_decimal_with_indonesian_separator(): void
    {
        $this->assertSame('-20,4%', InvestmentRegister::formatYield(-20.4));
    }

    public function test_no_basis_to_compare_is_null_not_zero_percent(): void
    {
        // Nol persen berarti "balik modal" — klaim yang tidak boleh dibuat
        // kalau modalnya sendiri tidak diketahui.
        $this->assertNull(InvestmentRegister::yieldPercent(null, 80_000_000));
        $this->assertNull(InvestmentRegister::yieldPercent(0, 80_000_000));
        $this->assertNull(InvestmentRegister::yieldPercent(100_000_000, null));
        $this->assertSame('—', InvestmentRegister::formatYield(null));
    }

    public function test_total_yield_is_money_weighted_not_an_average_of_row_percentages(): void
    {
        $rows = [
            // Posisi besar, rugi kecil.
            ['realized_principal' => 1_000_000_000, 'realized_actual' => 900_000_000],
            // Posisi kecil, untung besar. Rata-rata persen akan bilang +45%.
            ['realized_principal' => 1_000_000, 'realized_actual' => 2_000_000],
        ];

        $this->assertSame(1_001_000_000, InvestmentRegister::totalPrincipal($rows));
        $this->assertSame(902_000_000, InvestmentRegister::totalActual($rows));

        $total = InvestmentRegister::totalYieldPercent($rows);
        $this->assertNotNull($total);
        $this->assertLessThan(0, $total, 'Portofolio yang mayoritas uangnya rugi tidak boleh tampil untung.');
        $this->assertEqualsWithDelta(-9.89, $total, 0.01);
    }

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
        $this->assertNull($rows[1]['yield_percent']);
        $this->assertSame('—', $rows[1]['yield_label']);
    }

    public function test_unknown_type_falls_back_to_lainnya(): void
    {
        $this->assertSame('Lainnya', InvestmentRegister::typeLabel('kripto_gelap'));
        $this->assertSame('Lainnya', InvestmentRegister::typeLabel(null));
        $this->assertArrayHasKey('lainnya', InvestmentRegister::types());
    }

    public function test_a_row_without_a_date_counts_as_stale(): void
    {
        $this->assertTrue(InvestmentRegister::isStale(null));
        $this->assertTrue(InvestmentRegister::isStale(''));
        $this->assertFalse(InvestmentRegister::isStale(now()->toDateString()));
        $this->assertTrue(InvestmentRegister::isStale(
            now()->subMonths(InvestmentRegister::staleAfterMonths() + 1)->toDateString()
        ));
    }
}
