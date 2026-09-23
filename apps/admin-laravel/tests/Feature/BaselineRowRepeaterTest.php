<?php

namespace Tests\Feature;

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
}
