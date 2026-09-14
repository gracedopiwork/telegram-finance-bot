<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\InvestmentRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BaselineInvestmentRegisterTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 555001;

    private const EMAIL = 'portal-test@example.com';

    /**
     * Baseline yang sudah punya jawaban diagnostik → simpan berikutnya masuk
     * jalur snapshot-only, jadi test cukup mengirim bagian snapshot.
     */
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
            'investment_id' => '',
            'investment_type' => 'emas',
            'purpose' => 'Dana Pensiun',
            'start_year' => 2025,
            'target_years' => 21,
            'target_amount' => 5_000_000_000,
            'realized_principal' => 100_000_000,
            'realized_actual' => 80_000_000,
        ], $overrides);
    }

    public function test_it_saves_rows_and_derives_total_investment(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [
                $this->row(),
                $this->row([
                    'investment_type' => 'saham',
                    'purpose' => 'Dana Pendidikan',
                    'realized_principal' => 50_000_000,
                    'realized_actual' => 70_000_000,
                ]),
            ],
        ])->assertRedirect();

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $rows = $baseline->investment_register;

        $this->assertCount(2, $rows);
        $this->assertSame('emas', $rows[0]['investment_type']);
        $this->assertSame('Dana Pensiun', $rows[0]['purpose']);
        $this->assertSame(2025, $rows[0]['start_year']);
        $this->assertSame(21, $rows[0]['target_years']);
        $this->assertSame(5_000_000_000, $rows[0]['target_amount']);

        // total_investment adalah jumlah realisasi aktual, bukan pokok.
        $this->assertSame(150_000_000, $baseline->total_investment);
        $this->assertSame(150_000_000, InvestmentRegister::totalActual($rows));
        $this->assertSame(150_000_000, InvestmentRegister::totalActual($rows));
    }

    public function test_it_drops_blank_rows(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [
                $this->row(),
                // Jenis selalu terkirim karena select punya nilai default —
                // baris ini tetap harus dianggap kosong.
                ['investment_id' => '', 'investment_type' => 'emas', 'purpose' => '',
                    'start_year' => null, 'target_years' => null, 'target_amount' => null,
                    'realized_principal' => null, 'realized_actual' => null],
            ],
        ])->assertRedirect();

        $this->assertCount(1, FinancialBaseline::latestForUser(self::TG)->investment_register);
    }

    public function test_investment_id_survives_a_second_save(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [$this->row()],
        ])->assertRedirect();

        $id = FinancialBaseline::latestForUser(self::TG)->investment_register[0]['investment_id'];
        $this->assertNotSame('', $id);

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [$this->row([
                'investment_id' => $id,
                'purpose' => 'Dana Pensiun Dini',
            ])],
        ])->assertRedirect();

        $after = FinancialBaseline::latestForUser(self::TG)->investment_register[0];
        $this->assertSame($id, $after['investment_id']);
        $this->assertSame('Dana Pensiun Dini', $after['purpose']);
    }

    public function test_last_updated_holds_when_only_the_purpose_changed(): void
    {
        $stale = now()->subMonths(8)->toDateString();
        $this->seedBaseline()->update(['investment_register' => [[
            'investment_id' => 'INV-KEEP-ME',
            'investment_type' => 'emas',
            'purpose' => 'Dana Pensiun',
            'start_year' => 2025,
            'target_years' => 21,
            'target_amount' => 5_000_000_000,
            'realized_principal' => 100_000_000,
            'realized_actual' => 80_000_000,
            'last_updated' => $stale,
        ]]]);

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [$this->row([
                'investment_id' => 'INV-KEEP-ME',
                'purpose' => 'Pensiun',
            ])],
        ])->assertRedirect();

        $after = FinancialBaseline::latestForUser(self::TG)->investment_register[0];
        $this->assertSame('Pensiun', $after['purpose']);
        $this->assertSame($stale, $after['last_updated'], 'Ganti tujuan bukan verifikasi ulang angka.');
    }

    public function test_last_updated_advances_when_realized_actual_changes(): void
    {
        $stale = now()->subMonths(8)->toDateString();
        $this->seedBaseline()->update(['investment_register' => [[
            'investment_id' => 'INV-KEEP-ME',
            'investment_type' => 'emas',
            'purpose' => 'Dana Pensiun',
            'realized_principal' => 100_000_000,
            'realized_actual' => 80_000_000,
            'last_updated' => $stale,
        ]]]);

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [$this->row([
                'investment_id' => 'INV-KEEP-ME',
                'realized_actual' => 95_000_000,
            ])],
        ])->assertRedirect();

        $after = FinancialBaseline::latestForUser(self::TG)->investment_register[0];
        $this->assertSame(95_000_000, $after['realized_actual']);
        $this->assertSame(now()->toDateString(), $after['last_updated']);
    }

    public function test_user_can_clear_every_investment_row(): void
    {
        $this->seedBaseline([
            'investment_register' => [[
                'investment_id' => 'INV-OLD',
                'investment_type' => 'emas',
                'purpose' => 'Dana Pensiun',
                'realized_principal' => 100_000_000,
                'realized_actual' => 80_000_000,
                'last_updated' => now()->toDateString(),
            ]],
            'total_investment' => 80_000_000,
        ]);

        // Semua baris dikosongkan: tabel tetap terkirim, isinya blank.
        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [
                ['investment_id' => '', 'investment_type' => 'emas', 'purpose' => '',
                    'start_year' => null, 'target_years' => null, 'target_amount' => null,
                    'realized_principal' => null, 'realized_actual' => null],
            ],
        ])->assertRedirect();

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $this->assertNull($baseline->investment_register);
        $this->assertNull($baseline->total_investment);
    }

    public function test_a_submit_without_the_table_keeps_existing_rows(): void
    {
        $this->seedBaseline([
            'investment_register' => [[
                'investment_id' => 'INV-OLD',
                'investment_type' => 'emas',
                'purpose' => 'Dana Pensiun',
                'realized_principal' => 100_000_000,
                'realized_actual' => 80_000_000,
                'last_updated' => now()->toDateString(),
            ]],
            'total_investment' => 80_000_000,
        ]);

        $this->postSnapshot(['emergency_fund' => 9_000_000])->assertRedirect();

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $this->assertCount(1, $baseline->investment_register);
        $this->assertSame(80_000_000, $baseline->total_investment);
    }

    public function test_an_unknown_investment_type_falls_back_instead_of_being_stored(): void
    {
        $this->seedBaseline();

        // Validasi menolak nilai di luar enum — tabelnya tidak boleh diam-diam
        // menyimpan jenis yang tidak dikenal.
        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [$this->row(['investment_type' => 'kripto_gelap'])],
        ])->assertSessionHasErrors('snapshot.investment_register.0.investment_type');
    }

    public function test_result_page_shows_the_totals_and_yield(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'investment_register_submitted' => 1,
            'investment_register' => [$this->row()],
        ])->assertRedirect();

        $this->portalLogin(self::TG, self::EMAIL);
        $html = $this->get('/portal/baseline')->assertStatus(200)->getContent();

        $this->assertStringContainsString('Daftar investasi', $html);
        $this->assertStringContainsString('Dana Pensiun', $html);
        $this->assertStringContainsString('100.000.000', $html);
        $this->assertStringContainsString('80.000.000', $html);
        $this->assertStringContainsString('-20%', $html);

        $this->assertSame(80_000_000, FinancialBaseline::latestForUser(self::TG)->total_investment);
    }

    public function test_the_form_no_longer_offers_a_single_total_investment_input(): void
    {
        $this->seedBaseline();
        $this->portalLogin(self::TG, self::EMAIL);

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('name="snapshot[total_investment]"', $html);
        $this->assertStringContainsString('name="snapshot[investment_register][0][realized_actual]"', $html);
    }
}
