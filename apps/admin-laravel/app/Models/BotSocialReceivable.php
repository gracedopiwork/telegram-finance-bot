<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSocialReceivable extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_WRITTEN_OFF = 'written_off';

    public const STATUS_DISPUTED = 'disputed';

    protected $table = 'bot_social_receivables';

    protected $fillable = [
        'telegram_user_id',
        'outbound_transaction_id',
        'settled_transaction_id',
        'counterparty_name',
        'amount',
        'expected_back_at',
        'status',
        'mood_at_lend',
    ];

    protected function casts(): array
    {
        return [
            'expected_back_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function outboundTransaction(): BelongsTo
    {
        return $this->belongsTo(BotTransaction::class, 'outbound_transaction_id');
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
