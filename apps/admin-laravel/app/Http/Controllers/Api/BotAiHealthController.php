<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotAiHealthController extends Controller
{
    public function record(Request $request): JsonResponse
    {
        $expected = (string) config('services.bot.internal_api_token', '');
        if ($expected === '') {
            return response()->json(['ok' => false, 'error' => 'BOT_INTERNAL_API_TOKEN belum di-set'], 503);
        }

        $token = $request->bearerToken() ?? (string) $request->header('X-Bot-Token', '');
        if ($token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:success,rate_limit,fallback,error'],
            'detail' => ['nullable', 'string', 'max:500'],
        ]);

        app(AiHealthService::class)->record(
            $validated['event'],
            $validated['detail'] ?? null,
        );

        return response()->json(['ok' => true]);
    }
}
