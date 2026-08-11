<?php

namespace Tests\Feature;

use App\Models\BotSocialPayable;
use App\Models\BotSocialReceivable;
use App\Models\BotTransaction;
use App\Services\SocialLiquidityService;
use App\Support\TransactionTaxonomy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLiquidityTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_piutang_keluar_tracks_name_purpose_and_due(): void
    {
        $tx = BotTransaction::query()->create([
            'telegram_user_id' => 42,
            'recorded_at' => Carbon::parse('2026-08-06 10:00:00'),
            'type' => TransactionTaxonomy::TYPE_RECEIVABLE_OUT,
            'category' => 'Lain-lain',
            'sub_category' => 'Grace',
            'amount' => 2_700_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Di pinjam Grace 2,7 jt buat kepentingan kerja. Besok di transfer kembali.',
            'source' => 'manual',
        ]);

        $row = app(SocialLiquidityService::class)->openFromOutbound($tx);

        $this->assertSame('Grace', $row->counterparty_name);
        $this->assertStringContainsString('kepentingan kerja', mb_strtolower((string) $row->purpose));
        $this->assertNotNull($row->expected_back_at);
        $this->assertTrue($row->expected_back_at->isSameDay(Carbon::parse('2026-08-07')));
        $this->assertSame(BotSocialReceivable::STATUS_ACTIVE, $row->status);
    }

    public function test_balikin_besok_sets_due_tomorrow_not_default(): void
    {
        $svc = app(SocialLiquidityService::class);
        $base = Carbon::parse('2026-08-11 10:00:00');

        $this->assertSame(1, $svc->matchRelativeDueDays('Di pinjam Grace, balikin besok'));
        $this->assertSame(1, $svc->matchRelativeDueDays('kembali besok'));
        $this->assertSame(30, $svc->matchRelativeDueDays('sebulan ke depan'));
        $this->assertSame(30, $svc->matchRelativeDueDays('bulan depam'));

        $due = $svc->resolveExpectedBackAt('Di pinjam Grace 2,7 jt buat kerja, balikin besok', 2_700_000, $base);
        $this->assertTrue($due->isSameDay(Carbon::parse('2026-08-12')));
        $this->assertFalse($due->isSameDay($base->copy()->addDays($svc->defaultDueDays(2_700_000))));
    }

    public function test_default_due_days_by_amount(): void
    {
        $svc = app(SocialLiquidityService::class);
        $this->assertSame(30, $svc->defaultDueDays(100_000));
        $this->assertSame(60, $svc->defaultDueDays(500_000));
        $this->assertSame(60, $svc->defaultDueDays(2_000_000));
        $this->assertSame(90, $svc->defaultDueDays(2_000_001));
    }

    public function test_write_off_creates_social_expense(): void
    {
        $tx = BotTransaction::query()->create([
            'telegram_user_id' => 7,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_RECEIVABLE_OUT,
            'category' => 'Lain-lain',
            'sub_category' => 'Semuel',
            'amount' => 500_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjamin Semuel 500rb buat beli obat',
            'source' => 'manual',
        ]);
        $row = app(SocialLiquidityService::class)->openFromOutbound($tx);
        app(SocialLiquidityService::class)->writeOff($row);

        $this->assertSame(BotSocialReceivable::STATUS_WRITTEN_OFF, $row->fresh()->status);
        $this->assertDatabaseHas('bot_transactions', [
            'telegram_user_id' => 7,
            'type' => TransactionTaxonomy::TYPE_EXPENSE,
            'category' => 'Sosial & Keluarga',
            'amount' => 500_000,
        ]);
    }

    public function test_tracker_rows_include_follow_up(): void
    {
        $tx = BotTransaction::query()->create([
            'telegram_user_id' => 9,
            'recorded_at' => now()->subDays(40),
            'type' => TransactionTaxonomy::TYPE_RECEIVABLE_OUT,
            'category' => 'Lain-lain',
            'sub_category' => 'Grace',
            'amount' => 100_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjamin Grace 100rb buat makan',
            'source' => 'manual',
        ]);
        $row = app(SocialLiquidityService::class)->openFromOutbound($tx);
        $row->update(['expected_back_at' => now()->subDay()]);

        $tracker = app(SocialLiquidityService::class)->trackerReceivables(9);
        $this->assertNotEmpty($tracker);
        $this->assertTrue($tracker[0]['is_overdue']);
        $this->assertStringContainsString('ditagih', mb_strtolower($tracker[0]['follow_up']));
    }

    public function test_counterparty_ignores_purpose_dokter(): void
    {
        $svc = app(SocialLiquidityService::class);
        $this->assertSame(
            'Ayuti',
            $svc->extractCounterparty('saya meminjamkan uang kepada ayuti Meminjamkan Ayuti untuk biaya ke dokter')
        );
        $this->assertSame(
            'Sargib',
            $svc->extractCounterparty('Meminjamkan Sargib untuk ke dokter')
        );
        $this->assertSame(
            'Ayuti',
            $svc->extractCounterparty(
                'Meminjamkan Ayuti untuk biaya ke dokter',
                'dokter'
            )
        );
    }

    public function test_utang_masuk_opens_payable_with_due(): void
    {
        $tx = BotTransaction::query()->create([
            'telegram_user_id' => 11,
            'recorded_at' => Carbon::parse('2026-08-01'),
            'type' => TransactionTaxonomy::TYPE_PAYABLE_IN,
            'category' => 'Lain-lain',
            'sub_category' => 'Ayuti',
            'amount' => 1_000_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjam dari Ayuti 1jt buat biaya RS, bulan depan',
            'source' => 'manual',
        ]);

        $row = app(SocialLiquidityService::class)->openFromBorrow($tx);
        $this->assertInstanceOf(BotSocialPayable::class, $row);
        $this->assertSame('Ayuti', $row->counterparty_name);
        $this->assertNotNull($row->expected_back_at);
    }

    public function test_delete_active_payable_removes_opening_transaction(): void
    {
        $tx = BotTransaction::query()->create([
            'telegram_user_id' => 15,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_PAYABLE_IN,
            'category' => 'Lain-lain',
            'sub_category' => 'Ayuti',
            'amount' => 1_000_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjam dari Ayuti 1jt buat RS, bulan depan',
            'source' => 'manual',
        ]);
        $row = app(SocialLiquidityService::class)->openFromBorrow($tx);
        app(SocialLiquidityService::class)->deletePayable($row);

        $this->assertDatabaseMissing('bot_social_payables', ['id' => $row->id]);
        $this->assertDatabaseMissing('bot_transactions', ['id' => $tx->id]);
    }

    public function test_partial_piutang_repay_keeps_remaining(): void
    {
        $out = BotTransaction::query()->create([
            'telegram_user_id' => 21,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_RECEIVABLE_OUT,
            'category' => 'Lain-lain',
            'sub_category' => 'Sargib',
            'amount' => 5_000_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjamin Sargib 5jt buat kerja',
            'source' => 'manual',
        ]);
        $row = app(SocialLiquidityService::class)->openFromOutbound($out);
        $this->assertSame(5_000_000, (int) $row->amount_remaining);

        $partial = BotTransaction::query()->create([
            'telegram_user_id' => 21,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_RECEIVABLE_IN,
            'category' => 'Lain-lain',
            'sub_category' => 'Sargib',
            'amount' => 2_500_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Sargib mengembalikan uang yang dia pinjam tapi baru 2 jt 5 ratus',
            'source' => 'manual',
        ]);
        $updated = app(SocialLiquidityService::class)->settleFromInbound($partial);

        $this->assertNotNull($updated);
        $this->assertSame(BotSocialReceivable::STATUS_ACTIVE, $updated->status);
        $this->assertSame(2_500_000, (int) $updated->amount_remaining);

        $tracker = app(SocialLiquidityService::class)->trackerReceivables(21);
        $this->assertTrue($tracker[0]['is_partial']);
        $this->assertSame(2_500_000, $tracker[0]['amount_remaining']);
    }

    public function test_partial_utang_repay_keeps_remaining(): void
    {
        $borrow = BotTransaction::query()->create([
            'telegram_user_id' => 22,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_PAYABLE_IN,
            'category' => 'Lain-lain',
            'sub_category' => 'Mama',
            'amount' => 2_000_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjam dari mama 2jt buat kerja',
            'source' => 'manual',
        ]);
        $row = app(SocialLiquidityService::class)->openFromBorrow($borrow);
        $this->assertSame(2_000_000, (int) $row->amount_remaining);

        $partial = BotTransaction::query()->create([
            'telegram_user_id' => 22,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_PAYABLE_OUT,
            'category' => 'Lain-lain',
            'sub_category' => 'Mama',
            'amount' => 500_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Bayar cicilan utang ke mama 500rb',
            'source' => 'manual',
        ]);
        $updated = app(SocialLiquidityService::class)->settleFromRepay($partial);

        $this->assertNotNull($updated);
        $this->assertSame(BotSocialPayable::STATUS_ACTIVE, $updated->status);
        $this->assertSame(1_500_000, (int) $updated->amount_remaining);
    }
}
