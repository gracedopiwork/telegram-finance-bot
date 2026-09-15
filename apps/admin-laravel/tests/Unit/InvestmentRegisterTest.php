<?php

namespace Tests\Unit;

use App\Support\InvestmentRegister;
use PHPUnit\Framework\TestCase;

/**
 * Aritmetika murni Daftar Investasi — tanpa Laravel, tanpa DB.
 *
 * Yang butuh config() (isStale, staleAfterMonths) atau Eloquent (rowsFor) diuji
 * di tests/Feature; di sini hanya yang benar-benar bisa berdiri sendiri.
 */
class InvestmentRegisterTest extends TestCase
{
    /**
     * Spec menulis rumusnya "aktual / pokok x 100%" tapi mencontohkan -20% untuk
     * 80jt dari pokok 100jt. Rasio itu menghasilkan 80%, jadi yang dimaksud
     * adalah untung/rugi. Contohnya yang benar; test ini yang menguncinya.
     */
    public function test_loss_is_negative_not_a_ratio(): void
    {
        $this->assertSame(-20.0, InvestmentRegister::yieldPercent(100_000_000, 80_000_000));
    }

    public function test_gain_and_break_even(): void
    {
        $this->assertSame(25.0, InvestmentRegister::yieldPercent(80_000_000, 100_000_000));
        $this->assertSame(0.0, InvestmentRegister::yieldPercent(50_000_000, 50_000_000));
    }

    /**
     * (aktual / pokok - 1) menghasilkan -19.999999999999996 untuk kasus ini.
     * Kurangi dulu baru bagi, supaya angka mentahnya bersih di payload dashboard.
     */
    public function test_the_raw_float_is_clean_not_a_repeating_binary_fraction(): void
    {
        $this->assertSame(-20.0, InvestmentRegister::yieldPercent(100_000_000, 80_000_000));
        $this->assertSame(-13.3333, InvestmentRegister::yieldPercent(135_000_000, 117_000_000));
    }

    public function test_no_basis_to_compare_is_null_not_zero_percent(): void
    {
        // 0% berarti "balik modal" — klaim yang tidak boleh dibuat kalau
        // modalnya sendiri tidak diketahui.
        $this->assertNull(InvestmentRegister::yieldPercent(null, 80_000_000));
        $this->assertNull(InvestmentRegister::yieldPercent(0, 80_000_000));
        $this->assertNull(InvestmentRegister::yieldPercent(100_000_000, null));
    }

    public function test_format_trims_a_whole_number_but_keeps_a_real_decimal(): void
    {
        $this->assertSame('-20%', InvestmentRegister::formatYield(-20.0));
        $this->assertSame('25%', InvestmentRegister::formatYield(25.0));
        $this->assertSame('-20,4%', InvestmentRegister::formatYield(-20.4));
        $this->assertSame('—', InvestmentRegister::formatYield(null));
    }

    public function test_portfolio_yield_is_money_weighted_not_an_average_of_rows(): void
    {
        $rows = [
            // Posisi besar, rugi kecil.
            ['realized_principal' => 1_000_000_000, 'realized_actual' => 900_000_000],
            // Posisi kecil, untung besar. Rata-rata persen akan bilang +45%.
            ['realized_principal' => 1_000_000, 'realized_actual' => 2_000_000],
        ];

        $total = InvestmentRegister::rowsYieldPercent($rows);

        $this->assertNotNull($total);
        $this->assertLessThan(0, $total, 'Portofolio yang mayoritas uangnya rugi tidak boleh tampil untung.');
        $this->assertEqualsWithDelta(-9.89, $total, 0.01);
    }

    public function test_portfolio_yield_without_any_principal_is_null(): void
    {
        // Baris hasil sintesis baseline lama: ada nilai, tidak ada modal.
        $this->assertNull(InvestmentRegister::rowsYieldPercent([
            ['realized_principal' => null, 'realized_actual' => 12_000_000],
        ]));
        $this->assertNull(InvestmentRegister::rowsYieldPercent([]));
    }

    public function test_rows_have_stale_reads_the_decorated_flag(): void
    {
        $this->assertTrue(InvestmentRegister::rowsHaveStale([['is_stale' => false], ['is_stale' => true]]));
        $this->assertFalse(InvestmentRegister::rowsHaveStale([['is_stale' => false]]));
        $this->assertFalse(InvestmentRegister::rowsHaveStale([]));
        // Baris tanpa flag sama sekali tidak boleh dianggap stale.
        $this->assertFalse(InvestmentRegister::rowsHaveStale([[]]));
    }

    public function test_unknown_type_falls_back_to_lainnya(): void
    {
        $this->assertSame('Emas', InvestmentRegister::typeLabel('emas'));
        $this->assertSame('Lainnya', InvestmentRegister::typeLabel('kripto_gelap'));
    }

    /**
     * rows() memanggil typeLabel() dengan null untuk baris tanpa investment_type.
     * ?? 'Lainnya' menutupi gejalanya, jadi deprecation dinaikkan jadi exception
     * supaya test ini merah kalau guard null-nya dilepas.
     */
    public function test_a_null_type_does_not_index_an_array_with_null(): void
    {
        set_error_handler(
            static fn (int $severity, string $message) => throw new \RuntimeException($message),
            E_DEPRECATED
        );

        try {
            $this->assertSame('Lainnya', InvestmentRegister::typeLabel(null));
        } finally {
            restore_error_handler();
        }
    }

    public function test_the_nine_types_are_the_agreed_list(): void
    {
        $this->assertSame([
            'emas', 'deposito', 'obligasi', 'p2p', 'reksadana_pasar_uang',
            'reksadana_pendapatan_tetap', 'saham', 'bitcoin', 'lainnya',
        ], array_keys(InvestmentRegister::types()));
    }

    public function test_format_date_falls_back_to_a_dash(): void
    {
        $this->assertSame('07 Sep 2026', InvestmentRegister::formatDate('2026-09-07'));
        $this->assertSame('—', InvestmentRegister::formatDate(null));
        $this->assertSame('—', InvestmentRegister::formatDate(''));
        $this->assertSame('—', InvestmentRegister::formatDate('bukan tanggal'));
    }
}
