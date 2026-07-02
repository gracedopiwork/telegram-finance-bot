<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use Carbon\Carbon;

class FtsaEvaluationService
{
    public function __construct(
        private readonly PortalFeatureService $features,
        private readonly FtsaAnswerSummaryService $ftsaSummary,
    ) {}

    /**
     * FTSA sudah diisi dan masa evaluasi masih berjalan — pengisian ulang diblokir.
     */
    public function isRetakeLocked(int $telegramUserId): bool
    {
        return $this->retakeAvailableAt($telegramUserId) !== null;
    }

    /**
     * Tanggal pengisian ulang FTSA diperbolehkan (akhir masa evaluasi saat ini).
     */
    public function retakeAvailableAt(int $telegramUserId): ?Carbon
    {
        if (! $this->features->canAccessFtsa($telegramUserId)) {
            return null;
        }

        $baseline = FinancialBaseline::latestForUser($telegramUserId);
        if ($baseline === null || ! $this->ftsaSummary->hasFtsaAnswers($baseline)) {
            return null;
        }

        $endsAt = $this->features->ftsaEntitlementStatus($telegramUserId)['ends_at'];
        if ($endsAt?->isFuture()) {
            return $endsAt;
        }

        return null;
    }

    public function canFillOrEditFtsa(int $telegramUserId): bool
    {
        if (! $this->features->canAccessFtsa($telegramUserId)) {
            return false;
        }

        return ! $this->isRetakeLocked($telegramUserId);
    }
}
