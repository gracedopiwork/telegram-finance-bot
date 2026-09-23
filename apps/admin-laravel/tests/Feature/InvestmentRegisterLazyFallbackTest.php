<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\InvestmentRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Baseline lama menyimpan investasi sebagai satu angka total_investment tanpa
 * investment_register. Barisnya disintesis saat dibaca, bukan ditulis lewat
 * migrasi backfill — tidak ada staging untuk melatih rollback-nya.
 *
 * Yang dijaga di sini terutama satu hal: user lama yang membuka form lalu
 * menyimpan tidak boleh kehilangan angka investasinya.
 */
class InvestmentRegisterLazyFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 555001;

    private const EMAIL = 'portal-test@example.com';

    private function legacyBaseline(int $amount = 12_000_000): FinancialBaseline
    {
        return FinancialBaseline::factory()->create([
            'telegram_user_id' => self::TG,
            'email' => self::EMAIL,
            'assessed_at' => now(),
            'next_review_at' => now()->addMonths(3),
            'answers_json' => ['fs' => ['q1' => 'a'], 'ftsa' => []],
            'total_investment' => $amount,
            'investment_register' => null,
        ]);
    }

    public function test_a_legacy_total_is_synthesized_into_one_row_at_read_time(): void
    {
        $rows = InvestmentRegister::rowsFor($this->legacyBaseline());

        $this->assertCount(1, $rows);
        $this->assertSame(InvestmentRegister::PLACEHOLDER_PURPOSE, $rows[0]['purpose']);
        $this->assertSame('lainnya', $rows[0]['investment_type']);
        $this->assertSame(12_000_000, $rows[0]['realized_actual']);
    }

    public function test_nothing_is_written_to_the_database_by_reading(): void
    {
        $baseline = $this->legacyBaseline();

        InvestmentRegister::rowsFor($baseline);

        $this->assertNull($baseline->fresh()->investment_register,
            'Sintesis saat baca tidak boleh menulis apa pun ke DB.');
    }

    public function test_the_synthesized_row_has_no_id_and_no_date(): void
    {
        $rows = InvestmentRegister::rowsFor($this->legacyBaseline());

        // ULID dicetak saat user pertama kali menyimpan — saat itulah barisnya nyata.
        $this->assertSame('', $rows[0]['investment_id']);
        // Angka gelondongan tidak pernah diverifikasi sebagai baris register.
        $this->assertNull($rows[0]['last_updated']);
        $this->assertTrue($rows[0]['is_stale']);
        // Tanpa pokok, yield tidak boleh mengarang angka.
        $this->assertNull($rows[0]['realized_principal']);
        $this->assertNull($rows[0]['yield_percent']);
    }

    public function test_a_real_register_is_never_overridden_by_the_legacy_total(): void
    {
        $baseline = FinancialBaseline::factory()->create([
            'total_investment' => 12_000_000,
            'investment_register' => [[
                'investment_id' => 'INV-REAL',
                'investment_type' => 'saham',
                'purpose' => 'Dana Pendidikan',
                'realized_principal' => 5_000_000,
                'realized_actual' => 6_000_000,
                'last_updated' => now()->toDateString(),
            ]],
        ]);

        $rows = InvestmentRegister::rowsFor($baseline);

        $this->assertCount(1, $rows);
        $this->assertSame('Dana Pendidikan', $rows[0]['purpose']);
    }

    public function test_null_and_zero_totals_synthesize_nothing(): void
    {
        $this->assertSame([], InvestmentRegister::rowsFor(
            FinancialBaseline::factory()->create(['total_investment' => null, 'investment_register' => null])
        ));
        $this->assertSame([], InvestmentRegister::rowsFor(
            FinancialBaseline::factory()->create(['total_investment' => 0, 'investment_register' => null])
        ));
        $this->assertSame([], InvestmentRegister::rowsFor(null));
    }

    public function test_the_form_prefills_the_legacy_total_instead_of_blank_rows(): void
    {
        $this->legacyBaseline();
        $this->portalLogin(self::TG, self::EMAIL);

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringContainsString('value="12000000"', $html,
            'Form harus terisi dari angka lama, bukan menampilkan baris kosong.');
        $this->assertStringContainsString(InvestmentRegister::PLACEHOLDER_PURPOSE, $html);
    }

    /**
     * Ini inti dari perubahan: tanpa prefill, user lama membuka form, melihat
     * baris kosong, menekan Simpan, dan total_investment-nya terhapus diam-diam.
     */
    public function test_saving_the_prefilled_form_keeps_the_legacy_total(): void
    {
        $this->legacyBaseline();
        $this->portalLogin(self::TG, self::EMAIL);

        // Persis yang dikirim browser setelah form ter-prefill dan user menekan
        // Simpan tanpa mengubah apa pun.
        $rows = InvestmentRegister::rowsFor(FinancialBaseline::latestForUser(self::TG));
        $this->post('/portal/baseline', [
            'snapshot' => [
                'job_type' => 'employee',
                'investment_register_submitted' => 1,
                'investment_register' => [[
                    'investment_id' => $rows[0]['investment_id'],
                    'investment_type' => $rows[0]['investment_type'],
                    'purpose' => $rows[0]['purpose'],
                    'start_year' => null,
                    'target_years' => null,
                    'target_amount' => null,
                    'realized_principal' => $rows[0]['realized_principal'],
                    'realized_actual' => $rows[0]['realized_actual'],
                ]],
            ],
        ])->assertRedirect();

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $this->assertSame(12_000_000, $baseline->total_investment,
            'Menyimpan form yang ter-prefill tidak boleh menghapus angka investasi lama.');
        $this->assertCount(1, $baseline->investment_register);
        // Baris jadi nyata: ULID dicetak saat simpan pertama.
        $this->assertNotSame('', $baseline->investment_register[0]['investment_id']);
    }
}
