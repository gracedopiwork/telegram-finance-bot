<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\GoogleSheetPrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotSheetController extends Controller
{
    public function ensureSheetAccess(Request $request, string $orderCode): JsonResponse
    {
        $expected = (string) config('services.bot.internal_api_token', '');
        if ($expected === '') {
            return response()->json(['ok' => false, 'error' => 'BOT_INTERNAL_API_TOKEN belum di-set di Laravel .env'], 503);
        }

        $token = $request->bearerToken() ?? (string) $request->header('X-Bot-Token', '');
        if ($token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $order = Order::where('order_code', $orderCode)->first();
        if (! $order) {
            return response()->json(['ok' => false, 'error' => 'order_not_found'], 404);
        }

        if ($order->status !== 'paid' || ! $order->spreadsheet_id) {
            return response()->json([
                'ok' => false,
                'error' => 'order_not_ready',
                'message' => 'Order belum lunas atau belum punya spreadsheet_id.',
            ], 422);
        }

        $privacy = app(GoogleSheetPrivacyService::class);

        try {
            $diag = $privacy->ensureOrderAccessible($order);
            $saCheck = $privacy->verifyServiceAccountOpensSpreadsheet((string) $order->spreadsheet_id);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => 'ensure_failed',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => $saCheck['ok'],
            'spreadsheet_id' => $order->spreadsheet_id,
            'customer_access' => $diag['ok'],
            'sa_email' => $saCheck['email'],
            'sa_opens_sheet' => $saCheck['ok'],
            'sa_error' => $saCheck['error'],
            'message' => $diag['message'],
        ]);
    }
}
