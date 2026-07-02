<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BotCategoryRulesService;
use App\Services\CategoryBucketMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotCategoryRulesController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $expected = (string) config('services.bot.internal_api_token', '');
        if ($expected === '') {
            return response()->json(['ok' => false, 'error' => 'BOT_INTERNAL_API_TOKEN belum di-set'], 503);
        }

        $token = $request->bearerToken() ?? (string) $request->header('X-Bot-Token', '');
        if ($token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $payload = app(BotCategoryRulesService::class)->export();

        return response()->json([
            'ok' => true,
            'data' => $payload,
        ]);
    }
}
