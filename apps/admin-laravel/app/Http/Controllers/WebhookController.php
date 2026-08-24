<?php

namespace App\Http\Controllers;

use App\Services\MidtransPaymentSyncService;
use App\Services\PivotPaymentSyncService;
use App\Services\PivotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Midtrans Payment Notification URL (legacy — retained for old pending orders).
     */
    public function midtrans(Request $request, MidtransPaymentSyncService $sync)
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'ok' => true,
                'message' => 'Endpoint Midtrans webhook aktif (legacy). Pembayaran baru memakai /webhooks/pivot.',
            ]);
        }

        if (trim((string) config('services.midtrans.server_key')) === '') {
            Log::error('Midtrans webhook: MIDTRANS_SERVER_KEY belum dikonfigurasi');

            return response()->json(['ok' => false, 'message' => 'Midtrans key not configured']);
        }

        $payload = $request->all();

        try {
            $sync->handleWebhook($payload);
        } catch (\RuntimeException $e) {
            Log::warning('Midtrans webhook ditolak / gagal diproses', [
                'message' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Midtrans webhook exception', [
                'message' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['ok' => false, 'message' => 'Internal error']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Pivot Payment Notify callback.
     * Selalu balas HTTP 200 agar Pivot tidak retry berlebihan pada error bisnis.
     */
    public function pivot(Request $request, PivotService $pivot, PivotPaymentSyncService $sync)
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'ok' => true,
                'message' => 'Endpoint Pivot webhook aktif. Notifikasi pembayaran dikirim via POST.',
            ]);
        }

        if (! $pivot->isReady()) {
            Log::error('Pivot webhook: credentials belum dikonfigurasi');

            return response()->json(['ok' => false, 'message' => 'Pivot not configured']);
        }

        if (! $pivot->verifyCallbackRequest($request)) {
            Log::warning('Pivot webhook: callback key tidak valid');

            return response()->json(['ok' => false, 'message' => 'Invalid callback key']);
        }

        $payload = $request->all();

        try {
            $sync->handleWebhook($payload);
        } catch (\RuntimeException $e) {
            Log::warning('Pivot webhook ditolak / gagal diproses', [
                'message' => $e->getMessage(),
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Pivot webhook exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'Internal error']);
        }

        return response()->json(['ok' => true]);
    }
}
