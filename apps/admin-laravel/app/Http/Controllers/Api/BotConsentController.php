<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserDataConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BotConsentController extends Controller
{
    public function show(Request $request, UserDataConsentService $consents): JsonResponse
    {
        if ($unauthorized = $this->unauthorized($request)) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'telegram_user_id' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(
            $consents->statusPayload((int) $validated['telegram_user_id'])
        );
    }

    public function store(Request $request, UserDataConsentService $consents): JsonResponse
    {
        if ($unauthorized = $this->unauthorized($request)) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'telegram_user_id' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', 'in:bot,web'],
            'checkbox_ids' => ['required', 'array', 'min:1'],
            'checkbox_ids.*' => ['string', 'max:64'],
        ]);

        try {
            $row = $consents->accept(
                (int) $validated['telegram_user_id'],
                (string) $validated['method'],
                array_values($validated['checkbox_ids']),
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Consent gagal disimpan.';

            return response()->json([
                'ok' => false,
                'error' => 'consent_invalid',
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'accepted' => true,
            'consent_version' => $row->consent_version,
            'method' => $row->method,
            'consented_at' => optional($row->consented_at)->toIso8601String(),
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
