<?php

namespace App\Models;

use App\Support\FinancialBaselineSchema;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FinancialBaseline extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'email',
        'assessed_at',
        'next_review_at',
        'financial_stage_score',
        'financial_stage',
        'stage_label',
        'current_goal',
        'avg_monthly_income',
        'emergency_fund',
        'cash_savings',
        'total_investment',
        'total_asset',
        'asset_details',
        'total_debt',
        'has_bpjs',
        'has_health_insurance',
        'has_income_protection',
        'has_life_insurance',
        'protection_policies',
        'ftsa_chd',
        'ftsa_rvd',
        'ftsa_ssd',
        'ftsa_esd',
        'dominant_archetype',
        'dominant_archetype_label',
        'chd_level',
        'rvd_level',
        'ssd_level',
        'esd_level',
        'answers_json',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'next_review_at' => 'datetime',
            'answers_json' => 'array',
            'asset_details' => 'array',
            'protection_policies' => 'array',
            'has_bpjs' => 'boolean',
            'has_health_insurance' => 'boolean',
            'has_income_protection' => 'boolean',
            'has_life_insurance' => 'boolean',
        ];
    }

    public static function latestForEmail(string $email): ?self
    {
        if (! FinancialBaselineSchema::isReady()) {
            return null;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return self::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderByDesc('assessed_at')
            ->first();
    }

    public static function latestForUser(int $telegramUserId): ?self
    {
        if (! FinancialBaselineSchema::isReady()) {
            return null;
        }

        return self::query()
            ->where('telegram_user_id', $telegramUserId)
            ->orderByDesc('assessed_at')
            ->first();
    }

    public function isReviewDue(): bool
    {
        if ($this->next_review_at === null) {
            return false;
        }

        return $this->next_review_at->isPast();
    }

    public function formatDate(?string $format = null): string
    {
        return $this->formatTimestamp($this->assessed_at, $format ?? 'd M Y H:i');
    }

    public function formatNextReview(?string $format = null): string
    {
        return $this->formatTimestamp($this->next_review_at, $format ?? 'd M Y');
    }

    private function formatTimestamp(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (! $value instanceof Carbon) {
            try {
                $value = Carbon::parse($value);
            } catch (\Throwable) {
                return '-';
            }
        }

        try {
            return $value->format($format);
        } catch (\Throwable) {
            return '-';
        }
    }

    public static function userNeedsBaseline(int $telegramUserId): bool
    {
        return self::latestForUser($telegramUserId) === null;
    }

    public static function reviewDueForUser(int $telegramUserId): bool
    {
        $latest = self::latestForUser($telegramUserId);

        return $latest !== null && $latest->isReviewDue();
    }
}
