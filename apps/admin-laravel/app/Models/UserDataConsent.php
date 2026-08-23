<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDataConsent extends Model
{
    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const METHOD_BOT = 'bot';

    public const METHOD_WEB = 'web';

    protected $fillable = [
        'telegram_user_id',
        'consent_version',
        'status',
        'method',
        'consent_text_version',
        'checkbox_ids',
        'consented_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'checkbox_ids' => 'array',
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
}
