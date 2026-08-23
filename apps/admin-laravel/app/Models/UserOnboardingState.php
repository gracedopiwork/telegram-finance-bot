<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOnboardingState extends Model
{
    public const STEP_WELCOME = 'welcome';

    public const STEP_DONE = 'done';

    protected $fillable = [
        'telegram_user_id',
        'step',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function isComplete(): bool
    {
        return $this->step === self::STEP_DONE && $this->completed_at !== null;
    }
}
