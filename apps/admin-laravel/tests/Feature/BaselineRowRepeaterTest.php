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

    public function test_protection_rows_render_with_sequential_indices(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('name="snapshot[protection_policies][0][type]"', $html);
        $this->assertStringContainsString('name="snapshot[protection_policies][1][type]"', $html);
    }
}
