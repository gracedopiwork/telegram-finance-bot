<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotTransaction extends Model
{
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
        return $query->where('type', 'Pengeluaran');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'Pemasukan');
    }
}
