<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryBucketMapping extends Model
{
    public const BUCKETS = [
        'Income',
        'Essential Living',
        'Future Building',
        'Flexible + Social',
        'Protection',
        'Transfer (Excluded)',
    ];

    public const TRANSACTION_TYPES = [
        'income' => 'Income',
        'expense' => 'Expense',
        'saving' => 'Saving/Investment',
        'tax' => 'Kewajiban Pajak',
        'receivable_out' => 'Piutang Keluar',
        'receivable_in' => 'Piutang Masuk',
        'payable_in' => 'Hutang Masuk',
        'payable_out' => 'Hutang Keluar',
        'transfer' => 'Transfer',
    ];

    public const NATURES = [
        '',
        'Need',
        'Wants',
    ];

    protected $fillable = [
        'category',
        'sub_category',
        'bucket',
        'transaction_type',
        'nature',
        'match_keywords',
        'reason',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return list<string>
     */
    public function keywordsList(): array
    {
        $raw = trim((string) $this->match_keywords);
        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);

            return is_array($decoded)
                ? array_values(array_filter(array_map('strval', $decoded)))
                : [];
        }

        return array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            preg_split('/[\n,;]+/', $raw) ?: []
        )));
    }
}
