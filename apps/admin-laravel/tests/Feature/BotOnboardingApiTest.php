<?php

namespace Tests\Feature;

use App\Models\UserOnboardingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotOnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_defaults_to_welcome(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $this->withToken('test-token')
            ->getJson('/api/bot/onboarding?telegram_user_id=77')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('completed', false)
            ->assertJsonPath('step', 'welcome')
            ->assertJsonPath('guide_url', url('/panduan'));
    }

    public function test_set_step_to_done(): void
    {
        config(['services.bot.internal_api_token' => 'test-token']);

        $this->withToken('test-token')
            ->postJson('/api/bot/onboarding', [
                'telegram_user_id' => 77,
                'step' => 'done',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('completed', true);

        $this->assertDatabaseHas('user_onboarding_states', [
            'telegram_user_id' => 77,
            'step' => UserOnboardingState::STEP_DONE,
        ]);
    }
}
