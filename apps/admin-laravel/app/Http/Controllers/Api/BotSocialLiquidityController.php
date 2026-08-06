<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialLiquidityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotSocialLiquidityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->authorizationError($request)) {
            return $error;
        }

        $validated = $request->validate([
            'telegram_user_id' => ['required', 'integer', 'min:1'],
            'kind' => ['nullable', 'in:piutang,utang,all'],
        ]);

        $userId = (int) $validated['telegram_user_id'];
        $kind = (string) ($validated['kind'] ?? 'all');
        $social = app(SocialLiquidityService::class);

        $receivables = $kind === 'utang' ? [] : $social->trackerReceivables($userId, 30);
        $payables = $kind === 'piutang' ? [] : $social->trackerPayables($userId, 30);

        $activeReceivables = array_values(array_filter(
            $receivables,
            static fn (array $row): bool => ($row['status'] ?? '') === 'active'
        ));
        $activePayables = array_values(array_filter(
            $payables,
            static fn (array $row): bool => ($row['status'] ?? '') === 'active'
        ));

        return response()->json([
            'ok' => true,
            'piutang' => [
                'active' => $activeReceivables,
                'all' => $receivables,
                'active_total' => array_sum(array_column($activeReceivables, 'amount')),
                'overdue_total' => array_sum(array_map(
                    static fn (array $row): int => ! empty($row['is_overdue']) ? (int) $row['amount'] : 0,
                    $activeReceivables
                )),
            ],
            'utang' => [
                'active' => $activePayables,
                'all' => $payables,
                'active_total' => array_sum(array_column($activePayables, 'amount')),
                'overdue_total' => array_sum(array_map(
                    static fn (array $row): int => ! empty($row['is_overdue']) ? (int) $row['amount'] : 0,
                    $activePayables
                )),
            ],
            'notify' => [
                'enabled' => trim((string) config('services.telegram.bot_token', '')) !== '',
                'schedule' => 'daily 09:00 Asia/Jakarta',
            ],
        ]);
    }

    private function authorizationError(Request $request): ?JsonResponse
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
