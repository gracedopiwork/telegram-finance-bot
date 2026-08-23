<?php

namespace Tests\Feature;

use App\Models\UserDataConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotConsentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_shows_not_accepted_and_checkboxes(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $this->withToken('test-token')
            ->getJson('/api/bot/consent?telegram_user_id=42')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('accepted', false)
            ->assertJsonPath('consent_version', config('portal_privacy.version'))
            ->assertJsonCount(6, 'checkboxes');
    }

    public function test_accept_requires_all_checkboxes(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $this->withToken('test-token')
            ->postJson('/api/bot/consent', [
                'telegram_user_id' => 42,
                'method' => 'bot',
                'checkbox_ids' => ['read_understand'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_accept_stores_consent_record(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $ids = collect(config('portal_privacy.checkboxes'))->pluck('id')->all();

        $this->withToken('test-token')
            ->postJson('/api/bot/consent', [
                'telegram_user_id' => 42,
                'method' => 'bot',
                'checkbox_ids' => $ids,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('accepted', true);

        $this->assertDatabaseHas('user_data_consents', [
            'telegram_user_id' => 42,
            'consent_version' => config('portal_privacy.version'),
            'status' => UserDataConsent::STATUS_ACCEPTED,
            'method' => UserDataConsent::METHOD_BOT,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/bot/consent?telegram_user_id=42')
            ->assertOk()
            ->assertJsonPath('accepted', true);
    }
}
