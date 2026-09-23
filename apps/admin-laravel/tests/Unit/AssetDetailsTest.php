<?php

namespace Tests\Unit;

use App\Support\AssetDetails;
use PHPUnit\Framework\TestCase;

/**
 * Aturan murni Rincian aset — tanpa Laravel, tanpa DB.
 *
 * Fokusnya satu hal yang paling mahal kalau salah: kolom asset_details memuat
 * DUA bentuk sekaligus (map lama dan list baris), karena sengaja tidak ada
 * migrasi data. rows() harus benar untuk keduanya.
 */
class AssetDetailsTest extends TestCase
{
    public function test_a_legacy_map_becomes_named_rows(): void
    {
        // Nama aset diambil dari label jenisnya — inilah "backfill" yang
        // dijanjikan ke user, dikerjakan saat baca, bukan saat migrasi.
        $rows = AssetDetails::rows(['rumah' => 900_000_000, 'mobil' => 300_000_000]);

        $this->assertCount(2, $rows);
        $this->assertSame(
            [['Rumah', 'rumah', 900_000_000], ['Mobil', 'mobil', 300_000_000]],
            array_map(fn ($r) => [$r['asset_name'], $r['asset_type'], $r['asset_value']], $rows)
        );
    }

    public function test_a_legacy_map_keeps_a_stated_zero_but_drops_unfilled_keys(): void
    {
        $rows = AssetDetails::rows(['rumah' => 0, 'mobil' => null, 'tanah' => '']);

        $this->assertCount(1, $rows);
        $this->assertSame(0, $rows[0]['asset_value']);
    }

    public function test_row_lists_pass_through_with_a_derived_type_label(): void
    {
        $rows = AssetDetails::rows([
            ['asset_name' => 'Rumah Bogor', 'asset_type' => 'rumah', 'asset_value' => 900_000_000],
        ]);

        $this->assertSame('Rumah Bogor', $rows[0]['asset_name']);
        $this->assertSame('Rumah', $rows[0]['asset_type_label']);
    }

    public function test_two_rows_of_the_same_type_stay_separate(): void
    {
        // Justru inilah yang tidak mungkin di bentuk map: satu kunci = satu rumah.
        $rows = AssetDetails::rows([
            ['asset_name' => 'Rumah Bogor', 'asset_type' => 'rumah', 'asset_value' => 600_000_000],
            ['asset_name' => 'Rumah BSD', 'asset_type' => 'rumah', 'asset_value' => 300_000_000],
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame(900_000_000, AssetDetails::total($rows));
    }

    public function test_an_unknown_type_falls_back_to_lainnya(): void
    {
        $rows = AssetDetails::rows([
            ['asset_name' => 'Kapal', 'asset_type' => 'kapal_pesiar', 'asset_value' => 5_000_000],
        ]);

        $this->assertSame('lainnya', $rows[0]['asset_type']);
        $this->assertSame('Lainnya', $rows[0]['asset_type_label']);
    }

    /**
     * typeLabel(null) pernah jadi bug nyata di DebtRegister (commit caba695).
     * Dikunci di sini supaya tidak terulang untuk aset.
     */
    public function test_type_label_survives_null_and_unknown(): void
    {
        $this->assertSame('Lainnya', AssetDetails::typeLabel(null));
        $this->assertSame('Lainnya', AssetDetails::typeLabel('kapal_pesiar'));
        $this->assertSame('Rumah', AssetDetails::typeLabel('rumah'));
    }

    public function test_total_is_identical_for_both_shapes(): void
    {
        $map = ['rumah' => 900_000_000, 'mobil' => 300_000_000];
        $list = [
            ['asset_name' => 'Rumah', 'asset_type' => 'rumah', 'asset_value' => 900_000_000],
            ['asset_name' => 'Mobil', 'asset_type' => 'mobil', 'asset_value' => 300_000_000],
        ];

        $this->assertSame(1_200_000_000, AssetDetails::total($map));
        $this->assertSame(AssetDetails::total($map), AssetDetails::total($list));
    }

    /**
     * null = belum diisi, 0 = user menyatakan asetnya nol. Dibedakan karena
     * total_asset dipakai gate onboarding: 0 lolos sebagai "sudah menjawab".
     */
    public function test_total_separates_unfilled_from_a_stated_zero(): void
    {
        $this->assertNull(AssetDetails::total([]));
        $this->assertNull(AssetDetails::total(null));
        $this->assertSame(0, AssetDetails::total([
            ['asset_name' => 'Kosong', 'asset_type' => 'lainnya', 'asset_value' => 0],
        ]));
    }

    public function test_malformed_input_is_survivable(): void
    {
        foreach ([null, [], '', 0, 'rumah', ['rumah' => null]] as $garbage) {
            $this->assertSame([], AssetDetails::rows($garbage));
            $this->assertNull(AssetDetails::total($garbage));
        }

        // Baris yang bukan array dilewati, bukan bikin fatal.
        $this->assertSame([], AssetDetails::rows(['bukan array', 42]));
    }

    public function test_the_type_enum_is_the_one_the_form_and_validator_share(): void
    {
        $this->assertSame(
            ['rumah', 'tanah', 'apartemen', 'mobil', 'lainnya'],
            array_keys(AssetDetails::types())
        );
        $this->assertSame(12, AssetDetails::MAX_ROWS);
    }
}
