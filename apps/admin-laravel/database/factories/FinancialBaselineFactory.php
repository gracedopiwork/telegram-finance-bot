<?php

namespace Database\Factories;

use App\Models\FinancialBaseline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialBaseline>
 */
class FinancialBaselineFactory extends Factory
{
    protected $model = FinancialBaseline::class;

    public function definition(): array
    {
        $assessedAt = now();

        return [
            'telegram_user_id' => $this->faker->unique()->numberBetween(100_000, 999_999),
            'email' => $this->faker->unique()->safeEmail(),
            'assessed_at' => $assessedAt,
            'next_review_at' => $assessedAt->copy()->addMonths(
                max(1, (int) config('baseline_assessment.review_months', 3))
            ),
            'financial_stage_score' => 25,
            'financial_stage' => 'steady',
            'stage_label' => 'Steady',
            'current_goal' => 'Menabung untuk dana darurat',
            'avg_monthly_income' => 10_000_000,
            'emergency_fund' => 5_000_000,
            'cash_savings' => 3_000_000,
            'total_investment' => 2_000_000,
            'total_asset' => 50_000_000,
            'total_debt' => null,
            'has_bpjs' => true,
            'has_health_insurance' => false,
            'has_income_protection' => false,
            'has_life_insurance' => false,
            'ftsa_chd' => 0,
            'ftsa_rvd' => 0,
            'ftsa_ssd' => 0,
            'ftsa_esd' => 0,
            'dominant_archetype' => 'locked',
            'dominant_archetype_label' => 'FTSA Premium Locked',
            'answers_json' => ['fs' => [], 'ftsa' => []],
        ];
    }

    /**
     * Baseline lama yang masih memakai field "Total utang" tunggal (pra Debt Register).
     */
    public function withLegacyTotalDebt(int $amount = 12_000_000): self
    {
        return $this->state(fn () => ['total_debt' => $amount]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function withDebtRegister(array $rows): self
    {
        return $this->state(fn () => ['debt_register' => $rows]);
    }

    /**
     * Baseline lama yang masih memakai field "Total investasi" tunggal
     * (pra Daftar Investasi).
     */
    public function withLegacyTotalInvestment(int $amount = 2_000_000): self
    {
        return $this->state(fn () => ['total_investment' => $amount]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function withInvestmentRegister(array $rows): self
    {
        return $this->state(fn () => ['investment_register' => $rows]);
    }
}
