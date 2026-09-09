<?php

namespace Tests\Feature;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotDebtRegisterApiTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 777001;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.bot.internal_api_token' => 'test-token']);
    }

    private function seedRegister(int $tg = self::TG): FinancialBaseline
    {
        return FinancialBaseline::factory()->withDebtRegister([
            [
                'debt_id' => 'DEBT-KPR-1', 'debt_name' => 'KPR BCA', 'debt_type' => 'kpr',
                'principal_outstanding' => 450_000_000, 'monthly_installment_reference' => 4_200_000,
                'remaining_tenor_months' => 96, 'linked_expense_category' => 'Cicilan & Hutang',
                'last_updated' => now()->toDateString(),
            ],
        ])->create(['telegram_user_id' => $tg]);
    }

    public function test_it_requires_a_valid_token(): void
    {
        $this->seedRegister();

        $this->getJson('/api/bot/debt-register?telegram_user_id='.self::TG)->assertStatus(401);
        $this->getJson('/api/bot/debt-register?telegram_user_id='.self::TG, [
            'Authorization' => 'Bearer wrong',
        ])->assertStatus(401);
    }

    public function test_it_returns_only_matching_fields_never_the_principal(): void
    {
        $this->seedRegister();

        $response = $this->getJson('/api/bot/debt-register?telegram_user_id='.self::TG, [
            'Authorization' => 'Bearer test-token',
        ])->assertStatus(200);

        $debt = $response->json('data.debts.0');

        $this->assertSame(['debt_id', 'debt_name', 'debt_type'], array_keys($debt));
        $this->assertSame('KPR BCA', $debt['debt_name']);
        // Bot tidak boleh bisa menarasikan sisa pokok — caranya: tidak pernah dikirim.
        $response->assertJsonMissing(['principal_outstanding' => 450_000_000]);
    }

    public function test_it_is_scoped_to_the_requested_user(): void
    {
        $this->seedRegister();

        $this->getJson('/api/bot/debt-register?telegram_user_id=888002', [
            'Authorization' => 'Bearer test-token',
        ])->assertStatus(200)->assertJsonPath('data.debts', []);
    }

    public function test_transaction_accepts_a_debt_id_the_user_owns(): void
    {
        $this->seedRegister();

        $this->postJson('/api/bot/transactions', $this->txn(['debt_id' => 'DEBT-KPR-1']), [
            'Authorization' => 'Bearer test-token',
        ])->assertStatus(200);

        $this->assertSame('DEBT-KPR-1', BotTransaction::query()->latest('id')->first()->debt_id);
    }

    public function test_a_foreign_debt_id_is_dropped_but_the_transaction_still_saves(): void
    {
        $this->seedRegister();
        FinancialBaseline::factory()->withDebtRegister([
            ['debt_id' => 'DEBT-OTHER-9', 'debt_name' => 'KPR Orang Lain', 'debt_type' => 'kpr',
                'last_updated' => now()->toDateString()],
        ])->create(['telegram_user_id' => 888002]);

        $this->postJson('/api/bot/transactions', $this->txn(['debt_id' => 'DEBT-OTHER-9']), [
            'Authorization' => 'Bearer test-token',
        ])->assertStatus(200);

        $saved = BotTransaction::query()->latest('id')->first();
        $this->assertNull($saved->debt_id, 'debt_id milik user lain tidak boleh menempel.');
        $this->assertSame(1_500_000, $saved->amount, 'Transaksinya sendiri tetap harus tersimpan.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function txn(array $overrides = []): array
    {
        return array_merge([
            'telegram_user_id' => self::TG,
            'type' => 'Pengeluaran',
            'category' => 'Cicilan & Hutang',
            'sub_category' => '-',
            'nature' => 'Need',
            'notes' => 'byr kk mandiri',
            'amount' => 1_500_000,
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'source' => 'manual',
        ], $overrides);
    }
}
