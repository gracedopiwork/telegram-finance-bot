<?php

namespace Tests\Unit;

use App\Models\BotTransaction;
use App\Services\ImpulsivityAssessmentService;
use App\Support\TransactionTaxonomy;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class ImpulsivitySocialImpulseTest extends TestCase
{
    public function test_social_impulse_dadakan_vs_terencana_for_receivable_out_and_payable_in(): void
    {
        $rows = collect([
            $this->tx(TransactionTaxonomy::TYPE_RECEIVABLE_OUT, true, 100_000),
            $this->tx(TransactionTaxonomy::TYPE_RECEIVABLE_OUT, false, 50_000),
            $this->tx(TransactionTaxonomy::TYPE_PAYABLE_IN, true, 200_000),
            $this->tx(TransactionTaxonomy::TYPE_EXPENSE, true, 999_000),
        ]);

        $result = $this->invoke('socialImpulseBreakdown', $rows);

        $this->assertTrue($result['has_data']);
        $this->assertCount(2, $result['items']);

        $piutang = collect($result['items'])->firstWhere('type', TransactionTaxonomy::TYPE_RECEIVABLE_OUT);
        $this->assertSame(2, $piutang['count']);
        $this->assertSame(1, $piutang['impulsive_count']);
        $this->assertSame(1, $piutang['planned_count']);
        $this->assertSame(50.0, $piutang['impulsive_share']);
        $this->assertSame(50.0, $piutang['planned_share']);

        $utang = collect($result['items'])->firstWhere('type', TransactionTaxonomy::TYPE_PAYABLE_IN);
        $this->assertSame(1, $utang['count']);
        $this->assertSame(100.0, $utang['impulsive_share']);
        $this->assertSame(0.0, $utang['planned_share']);
    }

    public function test_need_impulsive_matrix_skips_non_pengeluaran(): void
    {
        $rows = collect([
            $this->tx(TransactionTaxonomy::TYPE_EXPENSE, true, 10_000, 'Wants'),
            $this->tx(TransactionTaxonomy::TYPE_RECEIVABLE_OUT, true, 10_000, 'Need'),
            $this->tx(TransactionTaxonomy::TYPE_PAYABLE_IN, false, 10_000, 'Need'),
        ]);

        $matrix = collect($this->invoke('needImpulsiveMatrix', $rows))->keyBy('key');

        $this->assertSame(1, $matrix['want_impulsive']['count']);
        $this->assertSame(0, $matrix['need_impulsive']['count']);
        $this->assertSame(0, $matrix['need_planned']['count']);
        $this->assertSame(0, $matrix['want_planned']['count']);
        $this->assertSame(100.0, $matrix['want_impulsive']['share']);
    }

    public function test_taxonomy_flag_summary_lines_and_recurring(): void
    {
        $rows = collect([
            $this->tx(TransactionTaxonomy::TYPE_EXPENSE, false, 1, null, ['risk_alert'], '2026-01-05 10:00:00'),
            $this->tx(TransactionTaxonomy::TYPE_EXPENSE, false, 1, null, ['risk_alert'], '2026-02-05 10:00:00'),
            $this->tx(TransactionTaxonomy::TYPE_EXPENSE, false, 1, null, ['late_pattern'], '2026-01-10 10:00:00'),
            $this->tx(TransactionTaxonomy::TYPE_EXPENSE, false, 1, null, ['life_event'], '2026-01-15 10:00:00'),
        ]);

        $result = $this->invoke('taxonomyFlagSummary', $rows);

        $this->assertTrue($result['has_data']);
        $this->assertTrue($result['risk_alert_recurring']);
        $this->assertFalse($result['late_pattern_recurring']);
        $this->assertSame(2, $result['risk_alert_count']);
        $this->assertSame(1, $result['late_pattern_count']);
        $this->assertSame(1, $result['life_event_count']);
        $this->assertCount(3, $result['lines']);
    }

    /**
     * @param  list<string>|null  $flags
     */
    private function tx(
        string $type,
        bool $impulsive,
        int $amount,
        ?string $nature = null,
        ?array $flags = null,
        string $recordedAt = '2026-09-01 12:00:00',
    ): BotTransaction {
        return new BotTransaction([
            'type' => $type,
            'is_impulsive' => $impulsive,
            'amount' => $amount,
            'nature' => $nature,
            'category' => 'Lain-lain',
            'mood' => 'Neutral',
            'taxonomy_flags' => $flags,
            'recorded_at' => $recordedAt,
        ]);
    }

    /** @param  Collection<int, BotTransaction>  $rows */
    private function invoke(string $method, Collection $rows): array
    {
        $service = new ImpulsivityAssessmentService;
        $ref = new ReflectionMethod(ImpulsivityAssessmentService::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($service, $rows);
    }
}
