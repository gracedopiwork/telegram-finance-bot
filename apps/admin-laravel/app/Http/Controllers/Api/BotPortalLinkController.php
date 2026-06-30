<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PortalAutoLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotPortalLinkController extends Controller
{
    public function create(Request $request): JsonResponse
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
            'telegram_user_id' => ['required', 'integer', 'min:1'],
        ]);

        $url = app(PortalAutoLoginService::class)->createSignedLoginUrl(
            (int) $validated['telegram_user_id'],
        );

        if ($url === null) {
            return response()->json([
                'ok' => false,
                'error' => 'license_not_ready',
                'message' => 'Lisensi belum aktif atau order tidak ditemukan.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'url' => $url,
            'expires_minutes' => 30,
        ]);
    }
}
