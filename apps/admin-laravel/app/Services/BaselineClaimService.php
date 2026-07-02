<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Models\Order;
use App\Support\FinancialBaselineSchema;
use Illuminate\Support\Collection;

class BaselineClaimService
{
    public function __construct(
        private readonly PortalAccessService $portalAccess,
        private readonly FtsaAnswerSummaryService $ftsaSummary,
    ) {}

    /**
     * Hubungkan baseline guest / FTSA sintetis ke akun Telegram portal saat ini.
     */
    public function claimForUser(string $email, int $telegramUserId): ?FinancialBaseline
    {
        if (! FinancialBaselineSchema::isReady() || $telegramUserId <= 0) {
            return null;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $existing = FinancialBaseline::latestForUser($telegramUserId);
        if ($existing !== null && $this->baselineIsFullyComplete($existing) && $this->baselineHasValidScores($existing)) {
            return $existing;
        }

        $this->reassignClaimableBaselines($email, $telegramUserId);

        $merged = $this->mergeSiblingBaselines($email, $telegramUserId);
        $baseline = $merged ?? FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);

        if ($baseline !== null) {
            $this->syncComputedScores($baseline);
            $baseline = $baseline->fresh();
        }

        return $baseline;
    }

    private function reassignClaimableBaselines(string $email, int $telegramUserId): void
    {
        $candidates = FinancialBaseline::query()
            ->where(function ($query) use ($email, $telegramUserId): void {
                $query->whereRaw('LOWER(email) = ?', [$email])
                    ->orWhere('telegram_user_id', $telegramUserId);
            })
            ->get();

        foreach ($candidates as $baseline) {
            $oldUserId = (int) ($baseline->telegram_user_id ?? 0);
            if ($oldUserId === $telegramUserId) {
                continue;
            }
            if ($oldUserId > 0 && ! $this->portalAccess->isSyntheticPortalUserId($oldUserId)) {
                continue;
            }

            $baseline->update([
                'telegram_user_id' => $telegramUserId,
                'email' => $email,
            ]);
        }

        $licenseIds = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('license_id')
            ->pluck('license_id');

        foreach ($licenseIds as $licenseId) {
            $syntheticId = $this->portalAccess->syntheticPortalUserId((int) $licenseId);
            FinancialBaseline::query()
                ->where('telegram_user_id', $syntheticId)
                ->update([
                    'telegram_user_id' => $telegramUserId,
                    'email' => $email,
                ]);
        }
    }

    private function mergeSiblingBaselines(string $email, int $telegramUserId): ?FinancialBaseline
    {
        $rows = $this->collectMergeableBaselines($email, $telegramUserId);
        if ($rows->isEmpty()) {
            return null;
        }

        /** @var FinancialBaseline $primary */
        $primary = $rows->first();
        $mergedAnswers = is_array($primary->answers_json) ? $primary->answers_json : [];

        foreach ($rows->skip(1) as $other) {
            $otherAnswers = is_array($other->answers_json) ? $other->answers_json : [];
            if (empty($mergedAnswers['fs']) && ! empty($otherAnswers['fs'])) {
                $mergedAnswers['fs'] = $otherAnswers['fs'];
            }
            if (empty($mergedAnswers['ftsa']) && ! empty($otherAnswers['ftsa'])) {
                $mergedAnswers['ftsa'] = $otherAnswers['ftsa'];
            }
            $this->copyMissingScores($primary, $other);
        }

        $primary->update([
            'telegram_user_id' => $telegramUserId,
            'email' => $email,
            'answers_json' => $mergedAnswers,
        ]);

        $duplicateIds = $rows->skip(1)->pluck('id')->all();
        if ($duplicateIds !== []) {
            FinancialBaseline::query()->whereIn('id', $duplicateIds)->delete();
        }

        return $primary->fresh();
    }

    /**
     * @return Collection<int, FinancialBaseline>
     */
    private function collectMergeableBaselines(string $email, int $telegramUserId): Collection
    {
        return FinancialBaseline::query()
            ->where(function ($query) use ($email, $telegramUserId): void {
                $query->whereRaw('LOWER(email) = ?', [$email])
                    ->orWhere('telegram_user_id', $telegramUserId);
            })
            ->orderByDesc('assessed_at')
            ->get()
            ->filter(function (FinancialBaseline $row) use ($telegramUserId): bool {
                $uid = (int) ($row->telegram_user_id ?? 0);

                return $uid === 0
                    || $uid === $telegramUserId
                    || $this->portalAccess->isSyntheticPortalUserId($uid);
            })
            ->values();
    }

    private function syncComputedScores(FinancialBaseline $baseline): void
    {
        $answers = is_array($baseline->answers_json) ? $baseline->answers_json : [];
        $hasFs = is_array($answers['fs'] ?? null) && $answers['fs'] !== [];
        $hasFtsa = $this->ftsaSummary->hasFtsaAnswers($baseline);

        if (! $hasFs && ! $hasFtsa) {
            return;
        }

        $staleArchetype = in_array((string) ($baseline->dominant_archetype ?? ''), ['guest', 'locked', ''], true);
        $zeroFtsaScores = $hasFtsa
            && (int) $baseline->ftsa_chd === 0
            && (int) $baseline->ftsa_rvd === 0
            && (int) $baseline->ftsa_ssd === 0
            && (int) $baseline->ftsa_esd === 0;

        if (! $staleArchetype && ! $zeroFtsaScores) {
            return;
        }

        $payload = [
            'fs' => $answers['fs'] ?? [],
            'ftsa' => $answers['ftsa'] ?? [],
        ];

        $result = app(BaselineAssessmentService::class)->assess($payload, $hasFtsa);

        $baseline->update([
            'financial_stage_score' => $result['financial_stage_score'],
            'financial_stage' => $result['financial_stage'],
            'stage_label' => $result['stage_label'],
            'ftsa_chd' => $result['ftsa_chd'],
            'ftsa_rvd' => $result['ftsa_rvd'],
            'ftsa_ssd' => $result['ftsa_ssd'],
            'ftsa_esd' => $result['ftsa_esd'],
            'dominant_archetype' => $result['dominant_archetype'],
            'dominant_archetype_label' => $result['dominant_archetype_label'],
            'chd_level' => $result['chd_level'],
            'rvd_level' => $result['rvd_level'],
            'ssd_level' => $result['ssd_level'],
            'esd_level' => $result['esd_level'],
            'answers_json' => $result['answers'],
        ]);
    }

    private function copyMissingScores(FinancialBaseline $primary, FinancialBaseline $other): void
    {
        $fields = [
            'financial_stage_score', 'financial_stage', 'stage_label',
            'ftsa_chd', 'ftsa_rvd', 'ftsa_ssd', 'ftsa_esd',
            'dominant_archetype', 'dominant_archetype_label',
            'chd_level', 'rvd_level', 'ssd_level', 'esd_level',
        ];

        $updates = [];
        foreach ($fields as $field) {
            $current = $primary->{$field};
            $incoming = $other->{$field};
            if (($current === null || $current === '' || $current === 0) && $incoming !== null && $incoming !== '' && $incoming !== 0) {
                $updates[$field] = $incoming;
            }
        }

        if ($updates !== []) {
            $primary->fill($updates)->save();
        }
    }

    private function baselineIsFullyComplete(FinancialBaseline $baseline): bool
    {
        $hasFs = is_array($baseline->answers_json['fs'] ?? null)
            && $baseline->answers_json['fs'] !== [];

        return $hasFs && $this->ftsaSummary->hasCompletedFtsa($baseline);
    }

    private function baselineHasValidScores(FinancialBaseline $baseline): bool
    {
        if ($this->ftsaSummary->hasCompletedFtsa($baseline)) {
            if (in_array((string) ($baseline->dominant_archetype ?? ''), ['guest', 'locked', ''], true)) {
                return false;
            }

            return ((int) $baseline->ftsa_chd + (int) $baseline->ftsa_rvd + (int) $baseline->ftsa_ssd + (int) $baseline->ftsa_esd) > 0;
        }

        $hasFs = is_array($baseline->answers_json['fs'] ?? null) && $baseline->answers_json['fs'] !== [];
        if ($hasFs) {
            return (int) $baseline->financial_stage_score > 0;
        }

        return true;
    }
}
