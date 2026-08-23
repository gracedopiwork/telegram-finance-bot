<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BotOnboardingController extends Controller
{
    public function show(Request $request, UserOnboardingService $onboarding): JsonResponse
    {
        if ($unauthorized = $this->unauthorized($request)) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'telegram_user_id' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(
            $onboarding->statusPayload((int) $validated['telegram_user_id'])
        );
    }

    public function store(Request $request, UserOnboardingService $onboarding): JsonResponse
    {
        if ($unauthorized = $this->unauthorized($request)) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'telegram_user_id' => ['required', 'integer', 'min:1'],
            'step' => ['required', 'string', 'max:32'],
        ]);

        try {
            $row = $onboarding->setStep(
                (int) $validated['telegram_user_id'],
                (string) $validated['step'],
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Onboarding gagal disimpan.';

            return response()->json([
                'ok' => false,
                'error' => 'onboarding_invalid',
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'step' => $row->step,
            'completed' => $row->isComplete(),
            'completed_at' => optional($row->completed_at)->toIso8601String(),
        ]);
    }

    private function unauthorized(Request $request): ?JsonResponse
    {
        $expected = (string) config('services.bot.internal_api_token', '');
        if ($expected === '') {
            return response()->json(['ok' => false, 'error' => 'BOT_INTERNAL_API_TOKEN belum di-set'], 503);
        }

        $token = $request->bearerToken() ?? (string) $request->header('X-Bot-Token', '');
        if ($token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        return null;
    }
}
