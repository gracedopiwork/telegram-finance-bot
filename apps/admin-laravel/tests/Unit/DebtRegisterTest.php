<?php

namespace Tests\Unit;

use App\Support\DebtRegister;
use PHPUnit\Framework\TestCase;

/**
 * Bagian DebtRegister yang tidak butuh framework.
 *
 * rows() / isStale() / rowsFor() butuh config dan Eloquent, jadi tidak diuji di sini.
 */
class DebtRegisterTest extends TestCase
{
    /**
     * rows() memanggil typeLabel($row['debt_type'] ?? null), jadi baris tersimpan
     * yang tidak punya debt_type mengirim null ke sini. Mengindeks array dengan
     * null adalah deprecation di PHP 8.4 — harus di-guard, bukan diandalkan ??.
     */
    public function test_a_null_type_does_not_index_an_array_with_null(): void
    {
        // ?? 'Lainnya' menutupi gejalanya — nilai kembaliannya sudah benar sejak
        // awal. Yang salah adalah jalannya: self::types()[null]. Jadi deprecation
        // dinaikkan jadi exception, supaya test ini benar-benar merah kalau
        // guard null-nya dilepas, bukan cuma mencatat catatan kecil.
        set_error_handler(
            static fn (int $severity, string $message) => throw new \RuntimeException($message),
            E_DEPRECATED
        );

        try {
            $this->assertSame('Lainnya', DebtRegister::typeLabel(null));
        } finally {
            restore_error_handler();
        }
    }

    public function test_known_and_unknown_types(): void
    {
        $this->assertSame('KPR', DebtRegister::typeLabel('kpr'));
        $this->assertSame('Kartu kredit', DebtRegister::typeLabel('kartu_kredit'));
        $this->assertSame('Lainnya', DebtRegister::typeLabel('tidak_dikenal'));
        $this->assertSame('Lainnya', DebtRegister::typeLabel('lainnya'));
    }

    public function test_the_seven_types_are_the_agreed_list(): void
    {
        $this->assertSame([
            'kpr', 'kredit_kendaraan', 'kartu_kredit', 'paylater',
            'pinjol_pinjaman_tunai', 'cicilan_produktif', 'lainnya',
        ], array_keys(DebtRegister::types()));
    }

    public function test_format_date_falls_back_to_a_dash(): void
    {
        $this->assertSame('07 Sep 2026', DebtRegister::formatDate('2026-09-07'));
        $this->assertSame('—', DebtRegister::formatDate(null));
        $this->assertSame('—', DebtRegister::formatDate(''));
        $this->assertSame('—', DebtRegister::formatDate('bukan tanggal'));
    }

    public function test_rows_have_stale_reads_the_decorated_flag(): void
    {
        $this->assertTrue(DebtRegister::rowsHaveStale([['is_stale' => false], ['is_stale' => true]]));
        $this->assertFalse(DebtRegister::rowsHaveStale([['is_stale' => false]]));
        $this->assertFalse(DebtRegister::rowsHaveStale([]));
        $this->assertFalse(DebtRegister::rowsHaveStale([[]]));
    }
}
