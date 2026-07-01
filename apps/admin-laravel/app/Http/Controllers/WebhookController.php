<?php

namespace App\Http\Controllers;

use App\Services\MidtransPaymentSyncService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtrans(Request $request, MidtransPaymentSyncService $sync)
    {
        if (trim((string) config('services.midtrans.server_key')) === '') {
            return response()->json(['message' => 'Midtrans key not configured'], 500);
        }

        $payload = $request->all();

        try {
            $sync->handleWebhook($payload);
        } catch (\RuntimeException $e) {
            $code = match ($e->getMessage()) {
                'Invalid signature' => 401,
                'Order not found' => 404,
                default => 422,
            };

            return response()->json(['message' => $e->getMessage()], $code);
        }

        return response()->json(['ok' => true]);
    }
}
