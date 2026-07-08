<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotTransaction;
use App\Services\CategoryAutoRegisterService;
use App\Support\PortalTimezone;
use App\Support\TransactionTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotTransactionController extends Controller
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
            'telegram_user_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:'.implode(',', TransactionTaxonomy::TYPES)],
            'category' => ['required', 'string', 'max:64'],
            'sub_category' => ['nullable', 'string', 'max:128'],
            'amount' => ['required', 'integer', 'min:1'],
            'nature' => ['required', 'in:'.implode(',', TransactionTaxonomy::NATURES)],
            'mood' => ['required', 'string', 'max:32'],
            'is_impulsive' => ['required', 'boolean'],
            'notes' => ['required', 'string', 'max:2000'],
            'source' => ['nullable', 'in:manual,receipt_photo'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $category = app(CategoryAutoRegisterService::class)->resolveOrRegister(
            (string) $validated['category'],
            (string) $validated['type'],
            (string) $validated['nature'],
            (string) $validated['notes'],
        );

        $transaction = BotTransaction::query()->create([
            'telegram_user_id' => (int) $validated['telegram_user_id'],
            'recorded_at' => isset($validated['recorded_at'])
                ? PortalTimezone::parseRecordedAt((string) $validated['recorded_at'], (int) $validated['telegram_user_id'])
                : PortalTimezone::nowUtc(),
            'type' => $validated['type'],
            'category' => $category,
            'sub_category' => trim((string) ($validated['sub_category'] ?? '')) ?: '-',
            'amount' => (int) $validated['amount'],
            'nature' => $validated['nature'],
            'mood' => $validated['mood'],
            'is_impulsive' => (bool) $validated['is_impulsive'],
            'notes' => $validated['notes'],
            'source' => $validated['source'] ?? 'manual',
        ]);

        return response()->json([
            'ok' => true,
            'id' => $transaction->id,
        ]);
    }
}
