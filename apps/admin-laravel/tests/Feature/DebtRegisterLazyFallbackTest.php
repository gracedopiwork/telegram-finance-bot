<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\DebtRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Baseline lama menyimpan utang sebagai satu angka total_debt. Daripada menulis
 * ulang data produksi lewat migrasi backfill, baris placeholder-nya disintesis
 * saat dibaca — nol baris ditulis saat deploy.
 */
class DebtRegisterLazyFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 555001;

    private const EMAIL = 'portal-test@example.com';

    public function test_synthesizes_a_placeholder_row_for_a_legacy_baseline(): void
    {
        $baseline = FinancialBaseline::factory()->withLegacyTotalDebt(12_000_000)->create();

        $rows = DebtRegister::rowsFor($baseline);

        $this->assertCount(1, $rows);
        $this->assertSame(DebtRegister::PLACEHOLDER_NAME, $rows[0]['debt_name']);
        $this->assertSame('lainnya', $rows[0]['debt_type']);
        $this->assertSame(12_000_000, $rows[0]['principal_outstanding']);
        $this->assertSame('', $rows[0]['debt_id'], 'ULID baru dicetak saat user menyimpan, bukan saat dibaca.');

        // Tidak ada yang ditulis ke database.
        $this->assertNull($baseline->fresh()->debt_register);
    }

    public function test_does_not_synthesize_when_there_is_no_debt(): void
    {
        foreach ([null, 0] as $amount) {
            $baseline = FinancialBaseline::factory()->create(['total_debt' => $amount]);
            $this->assertSame([], DebtRegister::rowsFor($baseline), 'Jangan memunculkan baris hantu.');
        }

        $this->assertSame([], DebtRegister::rowsFor(null));
    }

    public function test_a_real_register_always_wins(): void
    {
        $baseline = FinancialBaseline::factory()
            ->withDebtRegister([[
                'debt_id' => 'REAL-1', 'debt_name' => 'KPR BCA', 'debt_type' => 'kpr',
                'principal_outstanding' => 450_000_000, 'last_updated' => now()->toDateString(),
            ]])
            ->create(['total_debt' => 450_000_000]);

        $rows = DebtRegister::rowsFor($baseline);

        $this->assertCount(1, $rows);
        $this->assertSame('KPR BCA', $rows[0]['debt_name']);
    }

    public function test_the_synthesized_row_reads_as_stale(): void
    {
        $baseline = FinancialBaseline::factory()->withLegacyTotalDebt(12_000_000)->create();

        $rows = DebtRegister::rowsFor($baseline);

        // Angka gelondongan lama tidak pernah diverifikasi sebagai baris register,
        // jadi harus memicu prompt "perlu update" — bukan tampil seolah pasti.
        $this->assertTrue($rows[0]['is_stale']);
        $this->assertTrue(DebtRegister::rowsHaveStale($rows));
    }

    public function test_saving_a_legacy_baseline_persists_the_row_with_a_real_id(): void
    {
        $this->seedLegacyBaseline();

        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [[
                'debt_id' => '',
                'debt_name' => DebtRegister::PLACEHOLDER_NAME,
                'debt_type' => 'lainnya',
                'principal_outstanding' => 12_000_000,
                'monthly_installment_reference' => null,
                'remaining_tenor_months' => null,
            ]],
        ]);

        $stored = FinancialBaseline::latestForUser(self::TG)->debt_register;

        $this->assertCount(1, $stored);
        $this->assertNotEmpty($stored[0]['debt_id'], 'Sekarang barisnya nyata, jadi harus punya ULID.');
        $this->assertSame(now()->toDateString(), $stored[0]['last_updated']);
        $this->assertSame(12_000_000, $stored[0]['principal_outstanding']);
    }

    public function test_opening_and_saving_a_legacy_baseline_preserves_total_debt(): void
    {
        $this->seedLegacyBaseline();

        // Form yang dirender HARUS memperlihatkan angka lama. Kalau prefill-nya
        // kembali membaca kolom mentah, baris ini kosong dan simpan berikutnya
        // menghapus total_debt diam-diam — persis bug yang dicegah di sini.
        $this->portalLogin(self::TG, self::EMAIL);
        $form = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();
        $this->assertStringContainsString('value="12000000"', $form, 'Form harus ter-prefill dengan angka utang lama.');
        $this->assertStringContainsString(DebtRegister::PLACEHOLDER_NAME, $form);

        $prefilled = DebtRegister::rowsFor(FinancialBaseline::latestForUser(self::TG));

        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [[
                'debt_id' => $prefilled[0]['debt_id'],
                'debt_name' => $prefilled[0]['debt_name'],
                'debt_type' => $prefilled[0]['debt_type'],
                'principal_outstanding' => $prefilled[0]['principal_outstanding'],
                'monthly_installment_reference' => null,
                'remaining_tenor_months' => null,
            ]],
        ]);

        // Tanpa fallback, form akan ter-prefill kosong dan simpan ini
        // meng-null-kan total_debt — yang dibaca 4 gate akses portal.
        $this->assertSame(12_000_000, FinancialBaseline::latestForUser(self::TG)->total_debt);
    }

    public function test_result_page_shows_the_legacy_figure_in_the_table_and_total(): void
    {
        $this->seedLegacyBaseline();
        $this->portalLogin(self::TG, self::EMAIL);

        $html = $this->get('/portal/baseline')->assertStatus(200)->getContent();

        $this->assertStringContainsString(DebtRegister::PLACEHOLDER_NAME, $html);
        $this->assertStringContainsString('Total sisa pokok', $html);
        $this->assertStringContainsString('12.000.000', $html);
        $this->assertStringContainsString('perlu update', $html);
    }

    private function seedLegacyBaseline(): FinancialBaseline
    {
        return FinancialBaseline::factory()->withLegacyTotalDebt(12_000_000)->create([
            'telegram_user_id' => self::TG,
            'email' => self::EMAIL,
            'assessed_at' => now(),
            'next_review_at' => now()->addMonths(3),
            'answers_json' => ['fs' => ['q1' => 'a'], 'ftsa' => []],
        ]);
    }

    private function postSnapshot(array $snapshot): TestResponse
    {
        $this->portalLogin(self::TG, self::EMAIL);

        return $this->post('/portal/baseline', [
            'snapshot' => array_merge(['job_type' => 'employee'], $snapshot),
        ]);
    }
}
