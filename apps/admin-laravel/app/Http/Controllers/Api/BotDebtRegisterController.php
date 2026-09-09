<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Support\DebtRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar utang milik satu user, untuk fuzzy-match nama transaksi cicilan ke debt_id.
 *
 * Sengaja HANYA mengirim id, nama, dan jenis. Bot tidak butuh sisa pokok, dan
 * dengan tidak mengirimkannya, bot secara struktural tidak bisa menarasikan
 * angka sisa pokok — aturan Section 5 jadi tidak mungkin dilanggar dari sisi bot.
 */
class BotDebtRegisterController extends Controller
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

        $validated = $request->validate([
            'telegram_user_id' => ['required', 'integer', 'min:1'],
        ]);

        $baseline = FinancialBaseline::latestForUser((int) $validated['telegram_user_id']);

        $debts = [];
        foreach (DebtRegister::rows($baseline?->debt_register) as $row) {
            if ($row['debt_id'] === '') {
                continue;
            }
            $debts[] = [
                'debt_id' => $row['debt_id'],
                'debt_name' => $row['debt_name'],
                'debt_type' => $row['debt_type'],
            ];
        }

        return response()->json([
            'ok' => true,
            'data' => ['debts' => $debts],
        ]);
    }
}
