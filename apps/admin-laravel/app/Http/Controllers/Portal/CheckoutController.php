<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransPaymentSyncService;
use App\Services\PortalCheckoutService;
use App\Services\PortalFeatureService;
use App\Support\PortalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function ftsaSnap(Request $request, PortalCheckoutService $checkout): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $email = (string) (PortalSession::email($request) ?? '');
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $fullName = (string) $request->session()->get(PortalSession::DISPLAY_NAME, 'Pengguna');

        if ($email === '' || $telegramUserId <= 0) {
            return response()->json(['message' => 'Sesi tidak valid. Silakan login ulang.'], 401);
        }

        try {
            $result = $checkout->createFtsaSnapCheckout(
                $email,
                $telegramUserId,
                $fullName,
                $validated['phone'],
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'snap_token' => $result['snap_token'],
            'order_code' => $result['order']->order_code,
            'status_url' => route('portal.checkout.ftsa.status', ['order' => $result['order']->order_code]),
        ]);
    }

    public function ftsaStatus(
        Request $request,
        string $order,
        MidtransPaymentSyncService $paymentSync,
        PortalCheckoutService $checkout,
        PortalFeatureService $features,
    ): JsonResponse {
        $email = (string) (PortalSession::email($request) ?? '');
        $telegramUserId = (int) PortalSession::telegramUserId($request);

        if ($email === '' || $telegramUserId <= 0) {
            return response()->json(['message' => 'Sesi tidak valid.'], 401);
        }

        $orderModel = Order::query()->where('order_code', $order)->firstOrFail();

        if (! $checkout->orderBelongsToSession($orderModel, $email)) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($orderModel->status === 'pending') {
            $paymentSync->syncOrderFromApi($orderModel);
            $orderModel->refresh();
        }

        return response()->json([
            'status' => $orderModel->status,
            'ftsa_unlocked' => $features->canAccessFtsa($telegramUserId, $email),
        ]);
    }
}
