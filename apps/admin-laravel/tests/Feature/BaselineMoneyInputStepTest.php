<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard untuk bug "harus bisa input 3 digit ke belakang" (YFD-1).
 *
 * Input rupiah di form baseline dulu memakai step="1000", jadi browser menolak
 * angka seperti 14266332 ("nearest valid values are 14266000 and 14267000")
 * dan user terpaksa membulatkan ke ribuan. Server dan kolom DB selalu menerima
 * rupiah utuh; hanya atribut HTML yang memblokir.
 */
class BaselineMoneyInputStepTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 555001;

    private const EMAIL = 'portal-test@example.com';

    private function seedBaseline(): FinancialBaseline
    {
        return FinancialBaseline::factory()->create([
            'telegram_user_id' => self::TG,
            'email' => self::EMAIL,
            'assessed_at' => now(),
            'next_review_at' => now()->addMonths(3),
            'answers_json' => ['fs' => ['q1' => 'a'], 'ftsa' => []],
        ]);
    }

    public function test_money_inputs_accept_any_whole_rupiah(): void
    {
        $this->portalLogin();

        $html = $this->get('/portal/baseline/baru')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('step="1000"', $html);

        foreach ([
            'snapshot[avg_monthly_income]',
            'snapshot[emergency_fund]',
            'snapshot[cash_savings]',
            'snapshot[asset_details][0][asset_value]',
            'snapshot[debt_register][0][principal_outstanding]',
            'snapshot[debt_register][0][monthly_installment_reference]',
            'snapshot[protection_policies][0][annual_premium]',
            'snapshot[investment_register][0][target_amount]',
            'snapshot[investment_register][0][realized_principal]',
            'snapshot[investment_register][0][realized_actual]',
        ] as $name) {
            preg_match('/<input[^>]*name="'.preg_quote($name, '/').'"[^>]*>/', $html, $input);
            $this->assertNotEmpty($input, "Input {$name} tidak ditemukan.");
            $this->assertStringContainsString('step="1"', $input[0], "Input {$name} harus step=\"1\".");
        }
    }

    public function test_it_saves_values_that_are_not_multiples_of_a_thousand(): void
    {
        $this->seedBaseline();
        $this->portalLogin(self::TG, self::EMAIL);

        $this->post('/portal/baseline', [
            'snapshot' => [
                'job_type' => 'employee',
                'avg_monthly_income' => 14_266_332,
                'emergency_fund' => 1_234_567,
                'debt_register_submitted' => 1,
                'debt_register' => [[
                    'debt_id' => '',
                    'debt_name' => 'KPR BCA',
                    'debt_type' => 'kpr',
                    'principal_outstanding' => 450_123_456,
                    'monthly_installment_reference' => 4_200_789,
                    'remaining_tenor_months' => 96,
                ]],
            ],
        ])->assertSessionHasNoErrors();

        $baseline = FinancialBaseline::latestForUser(self::TG);

        $this->assertSame(14_266_332, (int) $baseline->avg_monthly_income);
        $this->assertSame(1_234_567, (int) $baseline->emergency_fund);
        $this->assertSame(450_123_456, $baseline->total_debt);
        $this->assertSame(4_200_789, (int) $baseline->debt_register[0]['monthly_installment_reference']);
    }

    public function test_it_still_rejects_negative_and_fractional_rupiah(): void
    {
        $this->seedBaseline();
        $this->portalLogin(self::TG, self::EMAIL);

        $this->post('/portal/baseline', [
            'snapshot' => ['job_type' => 'employee', 'avg_monthly_income' => -1],
        ])->assertSessionHasErrors('snapshot.avg_monthly_income');

        $this->post('/portal/baseline', [
            'snapshot' => ['job_type' => 'employee', 'emergency_fund' => '1234.5'],
        ])->assertSessionHasErrors('snapshot.emergency_fund');
    }
}
