<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\AssetDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard untuk bug reindex baris berulang (Tabel proteksi / Debt Register).
 *
 * Nama input berbentuk snapshot[<field>][N][kolom]. Regex reindex WAJIB menyertakan
 * "]" sebelum indeks. Kalau tidak, regex tidak pernah cocok, baris gagal dinomori ulang,
 * indeks bentrok setelah hapus+tambah, dan PHP membuang salah satu baris (last-wins).
 */
class BaselineRowRepeaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_protection_row_reindex_regex_includes_the_closing_bracket(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString(
            'protection_policies]\[\d+]',
            $html,
            'Regex reindex Tabel proteksi harus cocok dengan snapshot[protection_policies][N][...].'
        );

        $this->assertStringNotContainsString(
            "replace(/protection_policies\[\\d+]/",
            $html,
            'Regex reindex lama (tanpa "]" sebelum indeks) tidak pernah cocok — jangan dipakai lagi.'
        );
    }

    public function test_debt_total_row_is_not_an_input(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('data-debt-total', $html);

        // Baris total harus tetap <td> polos. Begitu jadi input dengan name=,
        // user bisa mengirim total yang tidak cocok dengan jumlah barisnya.
        preg_match('/<td[^>]*data-debt-total[^>]*>/', $html, $cell);
        $this->assertNotEmpty($cell, 'Sel total tidak ditemukan.');
        $this->assertStringNotContainsString('name=', $cell[0]);
        $this->assertStringNotContainsString('<input', $cell[0]);
    }

    public function test_investment_row_reindex_regex_includes_the_closing_bracket(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString(
            'investment_register]\[\d+]',
            $html,
            'Regex reindex Daftar investasi harus cocok dengan snapshot[investment_register][N][...].'
        );

        $this->assertStringNotContainsString(
            "replace(/investment_register\[\\d+]/",
            $html,
            'Regex reindex tanpa "]" sebelum indeks tidak pernah cocok — jangan dipakai.'
        );
    }

    public function test_investment_total_and_yield_cells_are_not_inputs(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        // Total dan yield diturunkan dari baris. Begitu jadi input dengan name=,
        // user bisa mengirim angka yang tidak cocok dengan barisnya sendiri.
        foreach ([
            'data-investment-total-principal',
            'data-investment-total-actual',
            'data-investment-total-yield',
            'data-investment-yield',
        ] as $marker) {
            $this->assertStringContainsString($marker, $html);

            preg_match('/<td[^>]*'.preg_quote($marker, '/').'[^>]*>/', $html, $cell);
            $this->assertNotEmpty($cell, "Sel {$marker} tidak ditemukan.");
            $this->assertStringNotContainsString('name=', $cell[0]);
            $this->assertStringNotContainsString('<input', $cell[0]);
        }
    }

    public function test_protection_rows_render_with_sequential_indices(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('name="snapshot[protection_policies][0][type]"', $html);
        $this->assertStringContainsString('name="snapshot[protection_policies][1][type]"', $html);
    }

    public function test_asset_total_row_is_not_an_input(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('data-asset-total', $html);

        // Total aset diturunkan dari Rincian aset. Begitu jadi input dengan name=,
        // user bisa mengirim total yang tidak cocok dengan rinciannya.
        preg_match('/<td[^>]*data-asset-total[^>]*>/', $html, $cell);
        $this->assertNotEmpty($cell, 'Sel total aset tidak ditemukan.');
        $this->assertStringNotContainsString('name=', $cell[0]);
        $this->assertStringNotContainsString('<input', $cell[0]);

        // Input "Total aset" lama harus benar-benar hilang, bukan sekadar disembunyikan.
        $this->assertStringNotContainsString('name="snapshot[total_asset]"', $html);
    }

    public function test_asset_rows_render_with_sequential_indices(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        foreach ([0, 1] as $i) {
            foreach (['asset_name', 'asset_type', 'asset_value'] as $field) {
                $this->assertStringContainsString(
                    'name="snapshot[asset_details]['.$i.']['.$field.']"',
                    $html
                );
            }
        }

        // Dropdown jenis aset harus menawarkan seluruh enum yang divalidasi server.
        foreach (AssetDetails::types() as $value => $label) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
        }

        $this->assertStringContainsString('name="snapshot[asset_details_submitted]"', $html);
    }

    public function test_asset_row_reindex_regex_includes_the_closing_bracket(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString(
            'asset_details]\[\d+]',
            $html,
            'Regex reindex Rincian aset harus cocok dengan snapshot[asset_details][N][...].'
        );

        $this->assertStringNotContainsString(
            "replace(/asset_details\[\\d+]/",
            $html,
            'Regex reindex tanpa "]" sebelum indeks tidak pernah cocok — jangan dipakai.'
        );
    }

    public function test_a_legacy_asset_map_renders_as_named_rows(): void
    {
        // User lama yang kolomnya masih map {"rumah": ...} harus tetap melihat
        // angkanya di form, tanpa migrasi data apa pun.
        FinancialBaseline::factory()
            ->withLegacyAssetDetails(['rumah' => 900_000_000, 'mobil' => 300_000_000])
            ->create([
                'telegram_user_id' => 555001,
                'email' => 'portal-test@example.com',
                'assessed_at' => now(),
                'next_review_at' => now()->addMonths(3),
                'answers_json' => ['fs' => ['q1' => 'a'], 'ftsa' => []],
            ]);

        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('value="Rumah"', $html);
        $this->assertStringContainsString('value="Mobil"', $html);
        $this->assertStringContainsString('value="900000000"', $html);
        $this->assertStringContainsString('value="300000000"', $html);
        $this->assertStringContainsString('1.200.000.000', $html);
    }

    public function test_snapshot_tables_render_in_balance_sheet_order(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $positions = [];
        foreach (['Rincian aset', 'Daftar utang', 'Tabel proteksi', 'Daftar investasi'] as $heading) {
            $pos = strpos($html, $heading);
            $this->assertNotFalse($pos, "Judul \"{$heading}\" tidak ditemukan di form.");
            $positions[$heading] = $pos;
        }

        $sorted = $positions;
        asort($sorted);
        $this->assertSame(array_keys($positions), array_keys($sorted), 'Urutan tabel snapshot berubah.');
    }

    public function test_debt_total_row_is_labelled_total(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('colspan="2">Total</td>', $html);
        $this->assertStringNotContainsString('Total sisa pokok', $html);
    }
}
