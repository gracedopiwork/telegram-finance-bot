<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Rincian aset per item. total_asset adalah turunan dari barisnya — user tidak
 * pernah mengetiknya, jadi angka total tidak bisa berbeda dari isi tabelnya.
 */
class BaselineAssetDetailsTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 555001;

    private const EMAIL = 'portal-test@example.com';

    private function seedBaseline(array $overrides = []): FinancialBaseline
    {
        return FinancialBaseline::factory()->create(array_merge([
            'telegram_user_id' => self::TG,
            'email' => self::EMAIL,
            'assessed_at' => now(),
            'next_review_at' => now()->addMonths(3),
            'answers_json' => ['fs' => ['q1' => 'a'], 'ftsa' => []],
        ], $overrides));
    }

    private function postSnapshot(array $snapshot): TestResponse
    {
        $this->portalLogin(self::TG, self::EMAIL);

        return $this->post('/portal/baseline', [
            'snapshot' => array_merge(['job_type' => 'employee'], $snapshot),
        ]);
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            'asset_name' => 'Rumah Bogor',
            'asset_type' => 'rumah',
            'asset_value' => 900_000_000,
        ], $overrides);
    }

    private function freshBaseline(): FinancialBaseline
    {
        return FinancialBaseline::query()->where('telegram_user_id', self::TG)->firstOrFail();
    }

    public function test_it_saves_rows_and_derives_total_asset(): void
    {
        $this->seedBaseline(['total_asset' => 50_000_000, 'asset_details' => null]);

        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [
                $this->row(),
                $this->row(['asset_name' => 'Avanza', 'asset_type' => 'mobil', 'asset_value' => 300_000_000]),
            ],
        ])->assertRedirect();

        $baseline = $this->freshBaseline();

        $this->assertSame(1_200_000_000, (int) $baseline->total_asset);
        $this->assertSame([
            ['asset_name' => 'Rumah Bogor', 'asset_type' => 'rumah', 'asset_value' => 900_000_000],
            ['asset_name' => 'Avanza', 'asset_type' => 'mobil', 'asset_value' => 300_000_000],
        ], $baseline->asset_details);
    }

    public function test_two_assets_of_the_same_type_stay_separate(): void
    {
        // Inilah yang tidak mungkin di bentuk lama: map jenis => nilai hanya
        // menyimpan satu rumah.
        $this->seedBaseline();

        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [
                $this->row(['asset_name' => 'Rumah Bogor', 'asset_value' => 600_000_000]),
                $this->row(['asset_name' => 'Rumah BSD', 'asset_value' => 300_000_000]),
            ],
        ])->assertRedirect();

        $baseline = $this->freshBaseline();

        $this->assertCount(2, $baseline->asset_details);
        $this->assertSame(900_000_000, (int) $baseline->total_asset);
    }

    public function test_it_drops_blank_rows(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [
                $this->row(),
                ['asset_name' => '', 'asset_type' => 'rumah', 'asset_value' => ''],
                ['asset_name' => '', 'asset_type' => 'lainnya', 'asset_value' => ''],
            ],
        ])->assertRedirect();

        $this->assertCount(1, $this->freshBaseline()->asset_details);
    }

    public function test_a_row_without_a_name_takes_its_type_label(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [['asset_name' => '', 'asset_type' => 'tanah', 'asset_value' => 120_000_000]],
        ])->assertRedirect();

        $this->assertSame('Tanah', $this->freshBaseline()->asset_details[0]['asset_name']);
    }

    public function test_an_unknown_asset_type_is_rejected_instead_of_being_stored(): void
    {
        $this->seedBaseline();

        // Validasi menolak nilai di luar enum — tabelnya tidak boleh diam-diam
        // menyimpan jenis yang tidak dikenal. Fallback ke 'lainnya' di controller
        // dan di AssetDetails::rows() adalah lapis kedua (lihat AssetDetailsShapeTest).
        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [$this->row(['asset_type' => 'kapal_pesiar'])],
        ])->assertSessionHasErrors('snapshot.asset_details.0.asset_type');
    }

    public function test_a_posted_total_asset_cannot_override_the_rows(): void
    {
        $this->seedBaseline();

        // Klien nakal / form basi yang masih mengirim snapshot[total_asset]:
        // baris tetap yang menang.
        $this->postSnapshot([
            'total_asset' => 7,
            'asset_details_submitted' => 1,
            'asset_details' => [$this->row()],
        ])->assertRedirect();

        $this->assertSame(900_000_000, (int) $this->freshBaseline()->total_asset);
    }

    public function test_clearing_every_asset_row_clears_the_total(): void
    {
        $this->seedBaseline([
            'total_asset' => 1_200_000_000,
            'asset_details' => [$this->row()],
        ]);

        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [
                ['asset_name' => '', 'asset_type' => 'rumah', 'asset_value' => ''],
                ['asset_name' => '', 'asset_type' => 'lainnya', 'asset_value' => ''],
            ],
        ])->assertRedirect();

        $baseline = $this->freshBaseline();

        $this->assertNull($baseline->asset_details);
        $this->assertNull($baseline->total_asset);
    }

    public function test_a_snapshot_without_the_asset_table_keeps_the_stored_rows(): void
    {
        $this->seedBaseline([
            'total_asset' => 900_000_000,
            'asset_details' => [$this->row()],
        ]);

        // Form lain (diagnostik) yang tidak mengirim tabel aset sama sekali
        // tidak boleh menghapus data yang sudah tersimpan.
        $this->postSnapshot(['cash_savings' => 5_000_000])->assertRedirect();

        $baseline = $this->freshBaseline();

        $this->assertSame(900_000_000, (int) $baseline->total_asset);
        $this->assertCount(1, $baseline->asset_details);
    }

    public function test_a_legacy_map_baseline_still_saves_and_converts(): void
    {
        // User lama: kolomnya masih map. Tidak ada migrasi yang menyentuhnya —
        // konversi terjadi saat dia menyimpan sendiri.
        $this->seedBaseline(['asset_details' => ['rumah' => 900_000_000], 'total_asset' => 900_000_000]);

        $this->postSnapshot([
            'asset_details_submitted' => 1,
            'asset_details' => [$this->row(['asset_name' => 'Rumah', 'asset_value' => 900_000_000])],
        ])->assertRedirect();

        $baseline = $this->freshBaseline();

        $this->assertArrayHasKey(0, $baseline->asset_details);
        $this->assertSame('Rumah', $baseline->asset_details[0]['asset_name']);
        $this->assertSame(900_000_000, (int) $baseline->total_asset);
    }
}
