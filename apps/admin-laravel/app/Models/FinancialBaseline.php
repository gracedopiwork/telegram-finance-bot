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
        'job_type',
        'tax_scheme',
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

    public const JOB_EMPLOYEE = 'employee';

    public const JOB_SELF_EMPLOYED = 'self_employed';

    public static function jobTypeOptions(): array
    {
        return [
            self::JOB_EMPLOYEE => 'Karyawan murni (gaji, PPh 21/TER sudah dipotong pemberi kerja)',
            self::JOB_SELF_EMPLOYED => 'Ada pekerjaan bebas/usaha (praktik, freelancer, pemilik usaha, kombinasi dengan gaji)',
        ];
    }

    public function jobTypeLabel(): string
    {
        return self::jobTypeOptions()[$this->job_type] ?? 'Belum diisi';
    }

    public function taxSchemeLabel(): string
    {
        return match ($this->tax_scheme) {
            'inactive' => 'Pajak First Aid tidak aktif (default karyawan)',
            'norma' => 'Norma penghitungan (default pekerjaan bebas/usaha)',
            'pembukuan' => 'Pembukuan',
            'umkm_final' => 'PPh Final UMKM 0,5%',
            default => 'Belum diatur',
        };
    }

    public function isReviewDue(): bool
    {
        $months = max(1, (int) config('baseline_assessment.review_months', 3));

        // Prefer assessed_at + config so changing review_months unlocks earlier assessments.
        if ($this->assessed_at !== null) {
            return $this->assessed_at->copy()->addMonths($months)->isPast();
        }

        if ($this->next_review_at === null) {
            return false;
        }

        return $this->next_review_at->isPast();
    }

    public function reviewAvailableAt(): ?Carbon
    {
        $months = max(1, (int) config('baseline_assessment.review_months', 3));

        if ($this->assessed_at !== null) {
            return $this->assessed_at->copy()->addMonths($months);
        }

        return $this->next_review_at;
    }

    public function formatDate(?string $format = null): string
    {
        return $this->formatTimestamp($this->assessed_at, $format ?? 'd M Y H:i');
    }

    public function formatNextReview(?string $format = null): string
    {
        return $this->formatTimestamp($this->reviewAvailableAt() ?? $this->next_review_at, $format ?? 'd M Y');
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
