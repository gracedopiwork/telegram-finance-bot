<?php

namespace Tests\Feature;

use App\Models\FinancialBaseline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Last check up" = assessed_at. Simpan snapshot tidak boleh memajukannya
 * ke tanggal hari ini (YFD-2).
 */
class BaselineCheckupDateTest extends TestCase
{
    use RefreshDatabase;

    private const TG = 555101;

    private const EMAIL = 'checkup-date@example.com';

    public function test_snapshot_save_keeps_last_and_next_checkup_dates(): void
    {
        $assessedAt = now()->subMonth()->startOfSecond();
        $nextReviewAt = $assessedAt->copy()->addMonths(3);

        FinancialBaseline::factory()->create([
            'telegram_user_id' => self::TG,
            'email' => self::EMAIL,
            'assessed_at' => $assessedAt,
            'next_review_at' => $nextReviewAt,
            'answers_json' => ['fs' => ['q1' => 'a'], 'ftsa' => []],
        ]);

        $this->portalLogin(self::TG, self::EMAIL);
        $this->post('/portal/baseline', [
            'snapshot' => ['job_type' => 'employee', 'avg_monthly_income' => 12_000_000],
        ])->assertRedirect();

        $baseline = FinancialBaseline::latestForUser(self::TG);
        $this->assertSame(12_000_000, $baseline->avg_monthly_income, 'Snapshot harus tetap tersimpan.');
        $this->assertTrue($assessedAt->equalTo($baseline->assessed_at), 'assessed_at bergeser ke '.$baseline->assessed_at);
        $this->assertTrue($nextReviewAt->equalTo($baseline->next_review_at), 'next_review_at bergeser ke '.$baseline->next_review_at);
    }
}
