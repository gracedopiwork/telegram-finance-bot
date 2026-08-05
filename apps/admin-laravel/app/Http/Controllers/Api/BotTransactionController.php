<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotTransaction;
use App\Services\CategoryAutoRegisterService;
use App\Services\CategoryBucketService;
use App\Services\SocialLiquidityService;
use App\Support\PortalTimezone;
use App\Support\TransactionTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotTransactionController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        if ($error = $this->authorizationError($request)) {
            return $error;
        }

        $validated = $request->validate($this->classificationRules());
        $category = app(CategoryAutoRegisterService::class)->resolveFromNotes(
            (string) $validated['category'],
            (string) $validated['type'],
            (string) $validated['notes'],
        );
        $transaction = $this->previewTransaction($validated, $category);

        return response()->json([
            'ok' => true,
            'category' => $category,
            'bucket' => app(CategoryBucketService::class)->resolve($transaction),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->authorizationError($request)) {
            return $error;
        }

        $validated = $request->validate(array_merge($this->classificationRules(), [
            'telegram_user_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'integer', 'min:1'],
            'mood' => ['required', 'string', 'max:32'],
            'is_impulsive' => ['required', 'boolean'],
            'source' => ['nullable', 'in:manual,receipt_photo'],
            'recorded_at' => ['nullable', 'date'],
            'taxonomy_flags' => ['nullable', 'array'],
            'taxonomy_flags.*' => ['string', 'max:64'],
        ]));

        $category = app(CategoryAutoRegisterService::class)->resolveOrRegister(
            app(CategoryAutoRegisterService::class)->resolveFromNotes(
                (string) $validated['category'],
                (string) $validated['type'],
                (string) $validated['notes'],
            ),
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
            'taxonomy_flags' => $validated['taxonomy_flags'] ?? null,
        ]);

        if (TransactionTaxonomy::isReceivable((string) $transaction->type)) {
            app(SocialLiquidityService::class)->syncFromTransaction($transaction);
        }

        return response()->json([
            'ok' => true,
            'id' => $transaction->id,
            'category' => $transaction->category,
            'bucket' => app(CategoryBucketService::class)->resolve($transaction),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function classificationRules(): array
    {
        return [
            'type' => ['required', 'in:'.implode(',', TransactionTaxonomy::TYPES)],
            'category' => ['required', 'string', 'max:64'],
            'sub_category' => ['nullable', 'string', 'max:128'],
            'nature' => ['required', 'in:'.implode(',', TransactionTaxonomy::NATURES)],
            'notes' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function previewTransaction(array $validated, string $category): BotTransaction
    {
        return new BotTransaction([
            'type' => $validated['type'],
            'category' => $category,
            'sub_category' => trim((string) ($validated['sub_category'] ?? '')) ?: '-',
            'nature' => $validated['nature'],
            'notes' => $validated['notes'],
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
