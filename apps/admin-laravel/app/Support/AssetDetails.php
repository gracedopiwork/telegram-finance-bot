<?php

namespace App\Support;

/**
 * Rincian aset di satu tempat, supaya form baseline, controller, dan panel
 * data dashboard tidak pernah berbeda pendapat soal "jenis aset apa saja"
 * atau "berapa total aset".
 *
 * Polanya disalin dari DebtRegister / InvestmentRegister — tabel per item di
 * satu kolom json, bukan satu angka gelondongan.
 *
 * total_asset adalah turunan: user mengisi barisnya, angka totalnya dihitung.
 *
 * Bentuk lama (sebelum tabel per item) adalah map {"rumah": 900000000}.
 * rows() sengaja masih menerimanya dan mengubahnya jadi baris saat dibaca —
 * tidak ada migrasi data yang menulis ulang kolom user. Baris lama baru
 * tersimpan dalam bentuk baru saat user menyimpan formnya sendiri.
 */
class AssetDetails
{
    public const MAX_ROWS = 12;

    /**
     * Jenis aset untuk dropdown "Jenis aset". Kuncinya juga dipakai sebagai
     * kunci map bentuk lama, jadi konversi bentuk lama cukup baca label di sini.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'rumah' => 'Rumah',
            'tanah' => 'Tanah',
            'apartemen' => 'Apartemen',
            'mobil' => 'Mobil',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        // $type di-guard dulu: rows() memanggil ini dengan null untuk baris
        // tersimpan yang tidak punya asset_type, dan mengindeks array dengan null
        // adalah deprecation di PHP 8.4. Pola yang sama dengan DebtRegister.
        return $type === null ? 'Lainnya' : (self::types()[$type] ?? 'Lainnya');
    }

    /**
     * Baris ter-normalisasi untuk view / total.
     *
     * Menerima dua bentuk:
     * 1. list baris [{asset_name, asset_type, asset_value}, ...] — bentuk sekarang;
     * 2. map {"rumah": 900000000, ...} — bentuk lama, dikonversi jadi baris dengan
     *    nama aset diambil dari label jenisnya ("Rumah"), sesuai permintaan backfill.
     *
     * @return list<array<string, mixed>>
     */
    public static function rows(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        return array_is_list($raw)
            ? self::rowsFromList($raw)
            : self::rowsFromLegacyMap($raw);
    }

    /**
     * Total aset — inilah yang mengisi kolom total_asset supaya semua konsumen
     * lama (dashboard, halaman hasil, prompt FTSA) tetap membaca satu angka.
     *
     * null kalau tidak ada baris, bukan 0: 0 berarti "user bilang tidak punya
     * aset", null berarti "belum diisi".
     */
    public static function total(mixed $raw): ?int
    {
        $rows = self::rows($raw);
        if ($rows === []) {
            return null;
        }

        $total = 0;
        foreach ($rows as $row) {
            $total += (int) ($row['asset_value'] ?? 0);
        }

        return $total;
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<array<string, mixed>>
     */
    private static function rowsFromList(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = (string) ($row['asset_type'] ?? 'lainnya');
            if (! array_key_exists($type, self::types())) {
                $type = 'lainnya';
            }

            $out[] = [
                'asset_name' => trim((string) ($row['asset_name'] ?? '')),
                'asset_type' => $type,
                'asset_type_label' => self::typeLabel($type),
                'asset_value' => self::nullableInt($row['asset_value'] ?? null),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<array<string, mixed>>
     */
    private static function rowsFromLegacyMap(array $raw): array
    {
        $out = [];
        foreach (array_keys(self::types()) as $type) {
            $value = self::nullableInt($raw[$type] ?? null);
            if ($value === null) {
                continue;
            }

            $out[] = [
                'asset_name' => self::typeLabel($type),
                'asset_type' => $type,
                'asset_type_label' => self::typeLabel($type),
                'asset_value' => $value,
            ];
        }

        return $out;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        return (int) $value;
    }
}
