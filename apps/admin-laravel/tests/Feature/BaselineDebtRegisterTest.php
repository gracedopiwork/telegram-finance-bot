<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use App\Support\DebtRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BaselineDebtRegisterTest extends TestCase
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
            'debt_id' => '',
            'debt_name' => 'KPR BCA',
            'debt_type' => 'kpr',
            'principal_outstanding' => 450_000_000,
            'monthly_installment_reference' => 4_200_000,
            'remaining_tenor_months' => 96,
        ], $overrides);
    }

    public function test_it_saves_rows_and_derives_total_debt(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [
                $this->row(),
                $this->row([
                    'debt_name' => 'Kartu Kredit Mandiri',
                    'debt_type' => 'kartu_kredit',
                    'principal_outstanding' => 8_000_000,
                    'monthly_installment_reference' => 1_500_000,
                    'remaining_tenor_months' => null,
                ]),
            ],
        ]);

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $register = $baseline->debt_register;

        $this->assertCount(2, $register);
        $this->assertSame('KPR BCA', $register[0]['debt_name']);
        $this->assertSame(DebtRegister::LINKED_EXPENSE_CATEGORY, $register[0]['linked_expense_category']);
        $this->assertNull($register[1]['remaining_tenor_months']);
        $this->assertNotEmpty($register[0]['debt_id']);
        $this->assertSame(458_000_000, $baseline->total_debt);
    }

    public function test_it_drops_blank_rows(): void
    {
        $this->seedBaseline();

        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [
                $this->row(),
                ['debt_id' => '', 'debt_name' => '', 'debt_type' => 'lainnya',
                    'principal_outstanding' => null, 'monthly_installment_reference' => null,
                    'remaining_tenor_months' => null],
            ],
        ]);

        $this->assertCount(1, FinancialBaseline::latestForUser(self::TG)->debt_register);
    }

    public function test_debt_id_survives_a_second_save(): void
    {
        $this->seedBaseline();
        $this->postSnapshot(['debt_register_submitted' => 1, 'debt_register' => [$this->row()]]);

        $first = FinancialBaseline::latestForUser(self::TG)->debt_register[0]['debt_id'];

        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [$this->row(['debt_id' => $first, 'debt_name' => 'KPR BCA (renamed)'])],
        ]);

        $after = FinancialBaseline::latestForUser(self::TG)->debt_register[0];
        $this->assertSame($first, $after['debt_id'], 'debt_id harus immutable — transaksi bot menunjuk ke sini.');
        $this->assertSame('KPR BCA (renamed)', $after['debt_name']);
    }

    public function test_last_updated_holds_when_nothing_material_changed(): void
    {
        $stale = '2026-01-15';
        $this->seedBaseline(['debt_register' => [$this->row([
            'debt_id' => 'DEBT-KEEP-1',
            'last_updated' => $stale,
            'linked_expense_category' => DebtRegister::LINKED_EXPENSE_CATEGORY,
        ])]]);

        // nama berubah, sisa pokok & cicilan tidak
        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [$this->row(['debt_id' => 'DEBT-KEEP-1', 'debt_name' => 'KPR BCA baru'])],
        ]);

        $this->assertSame(
            $stale,
            FinancialBaseline::latestForUser(self::TG)->debt_register[0]['last_updated'],
            'Menyimpan ulang tanpa mengubah angka tidak boleh me-reset jam staleness.'
        );
    }

    public function test_last_updated_advances_when_principal_changes(): void
    {
        $this->seedBaseline(['debt_register' => [$this->row([
            'debt_id' => 'DEBT-MOVE-1',
            'last_updated' => '2026-01-15',
            'linked_expense_category' => DebtRegister::LINKED_EXPENSE_CATEGORY,
        ])]]);

        $this->postSnapshot([
            'debt_register_submitted' => 1,
            'debt_register' => [$this->row(['debt_id' => 'DEBT-MOVE-1', 'principal_outstanding' => 430_000_000])],
        ]);

        $this->assertSame(
            now()->toDateString(),
            FinancialBaseline::latestForUser(self::TG)->debt_register[0]['last_updated']
        );
    }

    public function test_user_can_clear_every_debt_row(): void
    {
        $this->seedBaseline(['debt_register' => [$this->row(['debt_id' => 'DEBT-GONE-1'])], 'total_debt' => 450_000_000]);

        $this->postSnapshot(['debt_register_submitted' => 1, 'debt_register' => []]);

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $this->assertNull($baseline->debt_register, 'Utang lunas harus bisa dikosongkan, bukan dikembalikan oleh merge.');
        $this->assertNull($baseline->total_debt);
    }

    public function test_a_submit_without_the_table_keeps_existing_rows(): void
    {
        $this->seedBaseline(['debt_register' => [$this->row(['debt_id' => 'DEBT-SAFE-1'])]]);

        $this->postSnapshot([]);   // form tanpa bagian debt register sama sekali

        $this->assertCount(1, FinancialBaseline::latestForUser(self::TG)->debt_register);
    }
}
