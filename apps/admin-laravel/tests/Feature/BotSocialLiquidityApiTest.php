<?php

namespace Tests\Feature;

use App\Models\BotSocialPayable;
use App\Models\BotSocialReceivable;
use App\Models\BotTransaction;
use App\Support\TransactionTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSocialLiquidityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_active_piutang_and_utang(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $out = BotTransaction::query()->create([
            'telegram_user_id' => 99,
            'recorded_at' => now(),
            'type' => TransactionTaxonomy::TYPE_RECEIVABLE_OUT,
            'category' => 'Lain-lain',
            'sub_category' => 'Grace',
            'amount' => 500_000,
            'nature' => 'Need',
            'mood' => 'Neutral',
            'is_impulsive' => false,
            'notes' => 'Pinjamin Grace 500rb buat obat, minggu depan',
            'source' => 'manual',
        ]);
        BotSocialReceivable::query()->create([
            'telegram_user_id' => 99,
            'outbound_transaction_id' => $out->id,
            'counterparty_name' => 'Grace',
            'purpose' => 'obat',
            'amount' => 500_000,
            'expected_back_at' => now()->addDays(7),
            'status' => BotSocialReceivable::STATUS_ACTIVE,
        ]);

        $borrow = BotTransaction::query()->create([
            'telegram_user_id' => 99,
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
        BotSocialPayable::query()->create([
            'telegram_user_id' => 99,
            'inbound_transaction_id' => $borrow->id,
            'counterparty_name' => 'Ayuti',
            'purpose' => 'RS',
            'amount' => 1_000_000,
            'expected_back_at' => now()->addDays(30),
            'status' => BotSocialPayable::STATUS_ACTIVE,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/bot/social-liquidity?telegram_user_id=99&kind=all')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('piutang.active.0.name', 'Grace')
            ->assertJsonPath('utang.active.0.name', 'Ayuti');
    }
}
