<?php

namespace App\Support;

use App\Models\FinancialBaseline;
use Carbon\CarbonImmutable;

/**
 * Aturan Daftar Investasi di satu tempat, supaya form, halaman hasil, dan prompt AI
 * tidak pernah berbeda pendapat soal "apa itu stale", "berapa total realisasi",
 * atau "berapa yield-nya".
 *
 * Polanya disalin dari DebtRegister — tabel per item di satu kolom json, bukan
 * tabel baru yang perlu dipelajari user dari nol.
 *
 * Prinsip yang dikunci:
 * realisasi_aktual adalah stock hasil input manual per siklus Baseline 3 bulan.
 * Nilainya TIDAK PERNAH diturunkan dari akumulasi transaksi harian — harga pasar
 * emas/saham/reksadana bergerak sendiri, terlepas dari berapa yang user setor.
 *
 * @see docs/Baseline_Data_IT_Guidance_v1.0.pdf Section 2 (breakdown per item
 *      mengikuti pola Debt Register) & Section 3.3-3.4 (manual + staleness)
 */
class InvestmentRegister
{
    public const MAX_ROWS = 12;

    /**
     * Tujuan baris yang disintesis untuk baseline lama yang masih memakai
     * "Total investasi" tunggal.
     */
    public const PLACEHOLDER_PURPOSE = 'Investasi (belum dirinci)';

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'emas' => 'Emas',
            'deposito' => 'Deposito',
            'obligasi' => 'Obligasi',
            'p2p' => 'P2P',
            'reksadana_pasar_uang' => 'Reksadana Pasar Uang',
            'reksadana_pendapatan_tetap' => 'Reksadana Pendapatan Tetap',
            'saham' => 'Saham',
            'bitcoin' => 'Bitcoin',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        // $type di-guard dulu: mengindeks array dengan null memicu deprecation
        // di PHP 8.4, dan baris lama memang bisa tidak punya jenis sama sekali.
        return $type === null ? 'Lainnya' : (self::types()[$type] ?? 'Lainnya');
    }

    /**
     * Berapa bulan sampai data dianggap usang: 2 siklus Baseline terlewat.
     * Diturunkan dari config supaya ikut kalau siklus review berubah.
     */
    public static function staleAfterMonths(): int
    {
        return max(1, (int) config('baseline_assessment.review_months', 3)) * 2;
    }

    public static function isStale(?string $lastUpdated): bool
    {
        if (! is_string($lastUpdated) || trim($lastUpdated) === '') {
            // Tanpa tanggal kita tidak bisa menjamin datanya masih berlaku —
            // perlakukan sebagai stale daripada menampilkannya seolah pasti.
            return true;
        }

        try {
            $date = CarbonImmutable::parse($lastUpdated);
        } catch (\Throwable) {
            return true;
        }

        return $date->addMonths(self::staleAfterMonths())->isPast();
    }

    /**
     * Yield = untung/rugi terhadap modal, bukan rasio nilai terhadap modal.
     * Realisasi pokok 100jt dengan aktual 80jt berarti -20%, bukan 80%.
     *
     * null (bukan 0%) kalau tidak ada dasar pembanding: tanpa pokok, atau pokok 0,
     * angka persen apa pun cuma akan menyesatkan.
     */
    public static function yieldPercent(?int $principal, ?int $actual): ?float
    {
        if ($principal === null || $principal === 0 || $actual === null) {
            return null;
        }

        // Selisih dulu baru dibagi, bukan (aktual/pokok - 1): bentuk yang kedua
        // menghasilkan -19.999999999999996 untuk 80jt dari 100jt. Dibulatkan
        // supaya nilai mentahnya juga rapi di payload dashboard, bukan cuma di layar.
        return round(($actual - $principal) / $principal * 100, 4);
    }

    public static function formatYield(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $text = number_format($value, 1, ',', '.');

        // "-20,0%" dibaca lebih berat dari yang dimaksud; angka bulat tampil bulat.
        if (str_ends_with($text, ',0')) {
            $text = substr($text, 0, -2);
        }

        return $text.'%';
    }

    /**
     * Baris ter-normalisasi + flag turunan untuk dipakai view / prompt.
     *
     * @return list<array<string, mixed>>
     */
    public static function rows(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lastUpdated = isset($row['last_updated']) && is_string($row['last_updated'])
                ? $row['last_updated']
                : null;

            $principal = self::nullableInt($row['realized_principal'] ?? null);
            $actual = self::nullableInt($row['realized_actual'] ?? null);
            $yield = self::yieldPercent($principal, $actual);

            $out[] = [
                'investment_id' => (string) ($row['investment_id'] ?? ''),
                'investment_type' => (string) ($row['investment_type'] ?? 'lainnya'),
                'investment_type_label' => self::typeLabel($row['investment_type'] ?? null),
                'purpose' => (string) ($row['purpose'] ?? ''),
                'start_year' => self::nullableInt($row['start_year'] ?? null),
                'target_years' => self::nullableInt($row['target_years'] ?? null),
                'target_amount' => self::nullableInt($row['target_amount'] ?? null),
                'realized_principal' => $principal,
                'realized_actual' => $actual,
                'yield_percent' => $yield,
                'yield_label' => self::formatYield($yield),
                'last_updated' => $lastUpdated,
                'last_updated_label' => self::formatDate($lastUpdated),
                'is_stale' => self::isStale($lastUpdated),
            ];
        }

        return $out;
    }

    /**
     * Baris efektif untuk sebuah baseline.
     *
     * Baseline lama menyimpan investasi sebagai satu angka `total_investment`
     * tanpa investment_register. Daripada menulis ulang data produksi lewat
     * migrasi backfill — yang sulit dibatalkan tanpa staging — baris
     * placeholder-nya disintesis saat dibaca. Tidak ada baris yang ditulis saat
     * deploy, dan data pindah sendiri begitu user menyimpan Baseline berikutnya.
     *
     * Polanya sama dengan DebtRegister::rowsFor(): hitung pengganti saat baca,
     * jangan simpan apa-apa.
     *
     * @return list<array<string, mixed>>
     */
    public static function rowsFor(?FinancialBaseline $baseline): array
    {
        if ($baseline === null) {
            return [];
        }

        $rows = self::rows($baseline->investment_register);
        if ($rows !== []) {
            return $rows;
        }

        $legacy = (int) ($baseline->total_investment ?? 0);
        if ($legacy <= 0) {
            return [];
        }

        return self::rows([[
            // Kosong: normalizeInvestmentRegister() yang mencetak ULID asli saat
            // user pertama kali menyimpan — saat itulah barisnya jadi nyata.
            'investment_id' => '',
            'investment_type' => 'lainnya',
            'purpose' => self::PLACEHOLDER_PURPOSE,
            'start_year' => null,
            'target_years' => null,
            'target_amount' => null,
            // Angka lama adalah nilai yang dipegang sekarang, bukan modal yang
            // disetor. Pokok dibiarkan null supaya yield tampil "—" daripada
            // mengarang 0% dari modal yang tidak pernah diketahui.
            'realized_principal' => null,
            'realized_actual' => $legacy,
            // Null disengaja: angka gelondongan ini tidak pernah diverifikasi
            // sebagai baris register, jadi isStale() menandainya "perlu update".
            'last_updated' => null,
        ]]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function rowsHaveStale(array $rows): bool
    {
        foreach ($rows as $row) {
            if ($row['is_stale'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Yield portofolio ditimbang uang, bukan rata-rata persen antar baris —
     * rata-rata persen membuat posisi 1 juta punya bobot sama dengan 1 miliar.
     *
     * Satu-satunya implementasi aturan itu; totalYieldPercent() mendelegasi ke sini.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public static function rowsYieldPercent(array $rows): ?float
    {
        $principal = (int) array_sum(array_column($rows, 'realized_principal'));

        return self::yieldPercent(
            $principal ?: null,
            (int) array_sum(array_column($rows, 'realized_actual')),
        );
    }

    /**
     * Total realisasi aktual — inilah yang mengisi kolom total_investment supaya
     * semua konsumen lama (gate onboarding, dashboard, prompt FTSA) tetap jalan.
     */
    public static function totalActual(mixed $raw): ?int
    {
        return self::sum($raw, 'realized_actual');
    }

    public static function totalPrincipal(mixed $raw): ?int
    {
        return self::sum($raw, 'realized_principal');
    }

    public static function totalYieldPercent(mixed $raw): ?float
    {
        return self::rowsYieldPercent(self::rows($raw));
    }

    public static function hasStaleRow(mixed $raw): bool
    {
        foreach (self::rows($raw) as $row) {
            if ($row['is_stale']) {
                return true;
            }
        }

        return false;
    }

    public static function formatDate(?string $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '—';
        }

        try {
            return CarbonImmutable::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    private static function sum(mixed $raw, string $field): ?int
    {
        $rows = self::rows($raw);
        if ($rows === []) {
            return null;
        }

        $total = 0;
        foreach ($rows as $row) {
            $total += (int) ($row[$field] ?? 0);
        }

        return $total;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
