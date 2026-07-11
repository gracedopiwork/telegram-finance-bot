<?php

namespace App\Http\Controllers;

use App\Services\MidtransPaymentSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Midtrans Payment Notification URL.
     * Selalu balas HTTP 200 setelah menerima request agar Midtrans tidak
     * menganggap endpoint gagal (email "Difficulties sending notifications").
     * Error diproses di log; payload tidak valid tidak mengubah status order.
     */
    public function midtrans(Request $request, MidtransPaymentSyncService $sync)
    {
        if (trim((string) config('services.midtrans.server_key')) === '') {
            Log::error('Midtrans webhook: MIDTRANS_SERVER_KEY belum dikonfigurasi');

            // Tetap 200 supaya Midtrans tidak retry/email gagal; admin harus isi key.
            return response()->json(['ok' => false, 'message' => 'Midtrans key not configured']);
        }

        $payload = $request->all();

        try {
            $sync->handleWebhook($payload);
        } catch (\RuntimeException $e) {
            Log::warning('Midtrans webhook ditolak / gagal diproses', [
                'message' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
                'transaction_status' => $payload['transaction_status'] ?? null,
                'status_code' => $payload['status_code'] ?? null,
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Midtrans webhook exception', [
                'message' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Internal error',
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
