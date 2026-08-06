<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Utang sosial aktif: dibuka saat Utang Masuk, ditutup saat Utang Keluar.
 */
class BotSocialPayable extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_DISPUTED = 'disputed';

    protected $table = 'bot_social_payables';

    protected $fillable = [
        'telegram_user_id',
        'inbound_transaction_id',
        'settled_transaction_id',
        'counterparty_name',
        'purpose',
        'amount',
        'expected_back_at',
        'due_notified_at',
        'status',
        'mood_at_borrow',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expected_back_at' => 'datetime',
            'due_notified_at' => 'datetime',
        ];
    }

    public function inboundTransaction(): BelongsTo
    {
        return $this->belongsTo(BotTransaction::class, 'inbound_transaction_id');
    }

    public function settledTransaction(): BelongsTo
    {
        return $this->belongsTo(BotTransaction::class, 'settled_transaction_id');
    }

    public function scopeForUser($query, int $telegramUserId)
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
