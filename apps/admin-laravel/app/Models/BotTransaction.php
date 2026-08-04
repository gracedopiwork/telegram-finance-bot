<?php

namespace App\Models;

use App\Support\TransactionTaxonomy;
use Illuminate\Database\Eloquent\Model;

class BotTransaction extends Model
{
    public const TYPE_INCOME = TransactionTaxonomy::TYPE_INCOME;

    public const TYPE_EXPENSE = TransactionTaxonomy::TYPE_EXPENSE;

    public const TYPE_SAVING = TransactionTaxonomy::TYPE_SAVING;

    public const TYPE_TAX = TransactionTaxonomy::TYPE_TAX;

    public const TYPE_RECEIVABLE_OUT = TransactionTaxonomy::TYPE_RECEIVABLE_OUT;

    public const TYPE_RECEIVABLE_IN = TransactionTaxonomy::TYPE_RECEIVABLE_IN;

    protected $fillable = [
        'telegram_user_id',
        'recorded_at',
        'type',
        'category',
        'sub_category',
        'amount',
        'nature',
        'mood',
        'is_impulsive',
        'notes',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'amount' => 'integer',
            'is_impulsive' => 'boolean',
        ];
    }

    public function scopeForUser($query, int $telegramUserId)
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    public function scopeInMonth($query, string $yearMonth)
    {
        [$year, $month] = array_pad(explode('-', $yearMonth, 2), 2, null);

        return $query
            ->whereYear('recorded_at', (int) $year)
            ->whereMonth('recorded_at', (int) $month);
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeSavingInvestment($query)
    {
        return $query->where('type', self::TYPE_SAVING);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }
}
