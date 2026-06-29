<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotAiDailyStat extends Model
{
    protected $table = 'bot_ai_daily_stats';

    protected $primaryKey = 'stat_date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'stat_date',
        'success_count',
        'rate_limit_count',
        'fallback_count',
        'error_count',
        'last_rate_limit_at',
        'last_detail',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'last_rate_limit_at' => 'datetime',
        ];
    }
}
