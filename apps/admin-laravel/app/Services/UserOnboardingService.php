<?php

namespace App\Services;

use App\Models\UserOnboardingState;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UserOnboardingService
{
    /** @var list<string> */
    public const STEPS = [
        'welcome',
        'fh',
        'buckets',
        'purpose',
        'features',
        'home',
        'done',
    ];

    public function statusPayload(int $telegramUserId): array
    {
        $row = $this->findOrNull($telegramUserId);

        return [
            'ok' => true,
            'telegram_user_id' => $telegramUserId,
            'step' => $row?->step ?? UserOnboardingState::STEP_WELCOME,
            'completed' => $row?->isComplete() ?? false,
            'completed_at' => optional($row?->completed_at)->toIso8601String(),
            'guide_url' => url('/panduan'),
            'support_wa' => (string) config('portal_guide.support_wa', '+62 851-1122-8911'),
            'support_wa_url' => (string) config('portal_guide.support_wa_url', 'https://wa.me/6285111228911'),
            'topics' => array_values((array) config('portal_guide.topics', [])),
            'bot_summaries' => (array) config('portal_guide.bot_summaries', []),
            'faq' => array_values((array) config('portal_guide.faq', [])),
        ];
    }

    public function setStep(int $telegramUserId, string $step): UserOnboardingState
    {
        if ($telegramUserId < 1) {
            throw ValidationException::withMessages([
                'telegram_user_id' => 'telegram_user_id tidak valid.',
            ]);
        }

        $step = strtolower(trim($step));
        if (! in_array($step, self::STEPS, true)) {
            throw ValidationException::withMessages([
                'step' => 'Step onboarding tidak dikenal.',
            ]);
        }

        $completedAt = $step === UserOnboardingState::STEP_DONE ? Carbon::now() : null;

        /** @var UserOnboardingState $row */
        $row = UserOnboardingState::query()->updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            [
                'step' => $step,
                'completed_at' => $completedAt,
            ],
        );

        return $row;
    }

    public function isComplete(int $telegramUserId): bool
    {
        $row = $this->findOrNull($telegramUserId);

        return $row?->isComplete() ?? false;
    }

    private function findOrNull(int $telegramUserId): ?UserOnboardingState
    {
        if ($telegramUserId < 1) {
            return null;
        }

        return UserOnboardingState::query()
            ->where('telegram_user_id', $telegramUserId)
            ->first();
    }
}
