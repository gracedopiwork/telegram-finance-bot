<?php

namespace Tests\Feature;

use App\Support\AssetDetails;
use Tests\TestCase;

/**
 * Kontrak dua bentuk asset_details. Inilah yang melindungi data user lama:
 * tidak ada migrasi yang menulis ulang kolomnya, jadi map lama HARUS tetap
 * terbaca sampai user menyimpan formnya sendiri.
 */
class AssetDetailsShapeTest extends TestCase
{
    public function test_a_legacy_map_is_read_as_named_rows(): void
    {
        $rows = AssetDetails::rows(['rumah' => 900_000_000, 'mobil' => 300_000_000]);

        $this->assertCount(2, $rows);
        $this->assertSame('Rumah', $rows[0]['asset_name']);
        $this->assertSame('rumah', $rows[0]['asset_type']);
        $this->assertSame('Rumah', $rows[0]['asset_type_label']);
        $this->assertSame(900_000_000, $rows[0]['asset_value']);
        $this->assertSame('Mobil', $rows[1]['asset_name']);
        $this->assertSame(300_000_000, $rows[1]['asset_value']);
    }

    public function test_total_is_the_same_for_both_shapes(): void
    {
        $map = ['rumah' => 900_000_000, 'mobil' => 300_000_000];
        $list = [
            ['asset_name' => 'Rumah', 'asset_type' => 'rumah', 'asset_value' => 900_000_000],
            ['asset_name' => 'Mobil', 'asset_type' => 'mobil', 'asset_value' => 300_000_000],
        ];

        $this->assertSame(1_200_000_000, AssetDetails::total($map));
        $this->assertSame(1_200_000_000, AssetDetails::total($list));
    }

    public function test_a_row_list_round_trips_unchanged(): void
    {
        $rows = AssetDetails::rows([
            ['asset_name' => 'Rumah Bogor', 'asset_type' => 'rumah', 'asset_value' => 900_000_000],
        ]);

        $this->assertSame('Rumah Bogor', $rows[0]['asset_name']);
        $this->assertSame('rumah', $rows[0]['asset_type']);
        $this->assertSame(900_000_000, $rows[0]['asset_value']);
    }

    public function test_an_unknown_type_falls_back_to_lainnya(): void
    {
        $rows = AssetDetails::rows([
            ['asset_name' => 'Kapal', 'asset_type' => 'kapal_pesiar', 'asset_value' => 5_000_000],
        ]);

        $this->assertSame('lainnya', $rows[0]['asset_type']);
        $this->assertSame('Lainnya', $rows[0]['asset_type_label']);
    }

    public function test_empty_and_malformed_input_is_survivable(): void
    {
        foreach ([null, [], '', 0, 'rumah', ['rumah' => null]] as $garbage) {
            $this->assertSame([], AssetDetails::rows($garbage));
            $this->assertNull(AssetDetails::total($garbage));
        }

        // Baris yang bukan array dilewati, bukan bikin fatal.
        $this->assertSame([], AssetDetails::rows(['bukan array', 42]));
    }

    public function test_total_is_null_when_there_are_no_rows_but_zero_when_stated(): void
    {
        // null = belum diisi; 0 = user bilang asetnya nol.
        $this->assertNull(AssetDetails::total([]));
        $this->assertSame(0, AssetDetails::total([
            ['asset_name' => 'Kosong', 'asset_type' => 'lainnya', 'asset_value' => 0],
        ]));
    }
}
