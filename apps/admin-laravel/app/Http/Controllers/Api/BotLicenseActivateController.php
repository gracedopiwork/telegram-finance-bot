<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BotLicenseActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BotLicenseActivateController extends Controller
{
    public function store(Request $request): JsonResponse
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
            'license_key' => ['required', 'string', 'max:64'],
            'telegram_user_id' => ['required', 'integer', 'min:1'],
            'telegram_username' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = app(BotLicenseActivationService::class)->activate(
                (string) $validated['license_key'],
                (int) $validated['telegram_user_id'],
                isset($validated['telegram_username']) ? (string) $validated['telegram_username'] : null,
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Aktivasi gagal.';

            return response()->json([
                'ok' => false,
                'error' => $this->errorCodeFromMessage($message),
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'license_key' => $result['license_key'],
            'license_id' => $result['license_id'],
            'migrated_from_synthetic' => $result['migrated_from_synthetic'],
        ]);
    }

    private function errorCodeFromMessage(string $message): string
    {
        return match (true) {
            str_contains($message, 'belum termasuk paket YFD Bot') => 'bot_not_purchased',
            str_contains($message, 'tidak ditemukan') => 'license_not_found',
            str_contains($message, 'tidak aktif') => 'license_not_active',
            str_contains($message, 'expired') => 'license_expired',
            str_contains($message, 'akun Telegram lain') => 'license_used_by_other_user',
            default => 'activation_failed',
        };
    }
}
