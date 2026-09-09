<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Aturan Debt Register di satu tempat, supaya form, dashboard, dan prompt AI
 * tidak pernah berbeda pendapat soal "apa itu stale" atau "berapa total sisa pokok".
 *
 * Prinsip yang dikunci di spec (Section 3.3 & 5):
 * principal_outstanding HANYA berubah lewat update manual user. Cicilan bulanan
 * mengandung bunga yang rasionya berubah tiap bulan, jadi mengurangi sisa pokok
 * sebesar nominal transaksi akan makin salah tiap bulan.
 *
 * @see docs/Baseline_Data_IT_Guidance_v1.0.pdf Section 3
 */
class DebtRegister
{
    /**
     * Kategori 14 taxonomy. Taxonomy di kode ini flat (17 kategori, tanpa
     * sub-kategori), jadi granularitas "Cicilan konsumtif / KPR" dibawa oleh
     * debt_type, bukan oleh kategori.
     */
    public const LINKED_EXPENSE_CATEGORY = 'Cicilan & Hutang';

    public const MAX_ROWS = 12;

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'kpr' => 'KPR',
            'kredit_kendaraan' => 'Kredit kendaraan',
            'kartu_kredit' => 'Kartu kredit',
            'paylater' => 'Paylater',
            'pinjol_pinjaman_tunai' => 'Pinjol / pinjaman tunai',
            'cicilan_produktif' => 'Cicilan produktif',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        return self::types()[$type] ?? 'Lainnya';
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

            $out[] = [
                'debt_id' => (string) ($row['debt_id'] ?? ''),
                'debt_name' => (string) ($row['debt_name'] ?? 'Utang'),
                'debt_type' => (string) ($row['debt_type'] ?? 'lainnya'),
                'debt_type_label' => self::typeLabel($row['debt_type'] ?? null),
                'principal_outstanding' => self::nullableInt($row['principal_outstanding'] ?? null),
                'monthly_installment_reference' => self::nullableInt($row['monthly_installment_reference'] ?? null),
                'remaining_tenor_months' => self::nullableInt($row['remaining_tenor_months'] ?? null),
                'linked_expense_category' => (string) ($row['linked_expense_category'] ?? self::LINKED_EXPENSE_CATEGORY),
                'last_updated' => $lastUpdated,
                'last_updated_label' => self::formatDate($lastUpdated),
                'is_stale' => self::isStale($lastUpdated),
            ];
        }

        return $out;
    }

    /**
     * Total sisa pokok — inilah yang mengisi kolom total_debt supaya semua
     * konsumen lama (gate onboarding, dashboard, prompt FTSA) tetap jalan.
     */
    public static function totalPrincipal(mixed $raw): ?int
    {
        $rows = self::rows($raw);
        if ($rows === []) {
            return null;
        }

        $total = 0;
        foreach ($rows as $row) {
            $total += (int) ($row['principal_outstanding'] ?? 0);
        }

        return $total;
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

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
