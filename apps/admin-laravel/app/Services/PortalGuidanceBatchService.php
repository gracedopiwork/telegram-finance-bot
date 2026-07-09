<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use App\Models\PortalGuidanceSnapshot;
use Carbon\Carbon;

class PortalGuidanceBatchService
{
    public function __construct(
        private readonly TransactionDashboardService $dashboard,
        private readonly ImpulsivityAssessmentService $behavioral,
        private readonly PortalAiGuidanceService $aiGuidance,
        private readonly PortalGuidanceSnapshotService $snapshots,
    ) {}

    /**
     * @return array{processed: int, stored: int, skipped: int, empty: int}
     */
    public function generateWeeklyClinicalSummaries(?Carbon $weekAnchor = null, bool $force = false): array
    {
        $week = $this->snapshots->monthCumulativeWeekRange($weekAnchor);
        $periodLabel = $week['label'];

        $userIds = BotTransaction::query()
            ->whereBetween('recorded_at', [$week['start'], $week['end']])
            ->distinct()
            ->pluck('telegram_user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $stats = ['processed' => 0, 'stored' => 0, 'skipped' => 0, 'empty' => 0];

        foreach ($userIds as $telegramUserId) {
            $stats['processed']++;

            if (! $force && PortalGuidanceSnapshot::findForUser(
                $telegramUserId,
                PortalGuidanceSnapshot::TYPE_CLINICAL_SUMMARY_WEEKLY,
                $week['key'],
            ) !== null) {
                $stats['skipped']++;

                continue;
            }

            $context = $this->dashboard->financialGuidanceContext(
                $telegramUserId,
                $week['start'],
                $week['end'],
                $periodLabel,
                1,
            );

            if ($context['transaction_count'] === 0) {
                $stats['empty']++;

                continue;
            }

            $baseline = FinancialBaseline::latestForUser($telegramUserId);
            $stored = $this->aiGuidance->generateAndStoreWeeklyClinicalSummary(
                $telegramUserId,
                $week['key'],
                $context['metrics'],
                $baseline,
                $context['fallback_clinical'],
            );

            if ($stored) {
                $stats['stored']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{processed: int, stored: int, skipped: int, empty: int}
     */
    public function generateMonthlyDoctorsNotes(?string $monthKey = null, bool $force = false): array
    {
        $anchor = $monthKey !== null
            ? Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth()
            : now();
        $month = $this->snapshots->monthRange($anchor);
        $periodLabel = $anchor->translatedFormat('F Y');

        $userIds = BotTransaction::query()
            ->whereBetween('recorded_at', [$month['start'], $month['end']])
            ->distinct()
            ->pluck('telegram_user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $stats = ['processed' => 0, 'stored' => 0, 'skipped' => 0, 'empty' => 0];

        foreach ($userIds as $telegramUserId) {
            $stats['processed']++;

            if (! $force && PortalGuidanceSnapshot::findForUser(
                $telegramUserId,
                PortalGuidanceSnapshot::TYPE_DOCTORS_NOTE_MONTHLY,
                $month['key'],
            ) !== null) {
                $stats['skipped']++;

                continue;
            }

            $context = $this->dashboard->financialGuidanceContext(
                $telegramUserId,
                $month['start'],
                $month['end'],
                $periodLabel,
                1,
            );

            if ($context['transaction_count'] === 0) {
                $stats['empty']++;

                continue;
            }

            $baseline = FinancialBaseline::latestForUser($telegramUserId);
            $stored = $this->aiGuidance->generateAndStoreMonthlyDoctorsNote(
                $telegramUserId,
                $month['key'],
                $context['metrics'],
                $baseline,
                $context['fallback_doctors_note'],
            );

            if ($stored) {
                $stats['stored']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{processed: int, stored: int, skipped: int, empty: int}
     */
    public function generateMonthlyBehavioralGuidance(?string $monthKey = null, bool $force = false): array
    {
        $anchor = $monthKey !== null
            ? Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth()
            : now();
        $month = $this->snapshots->monthRange($anchor);

        $userIds = BotTransaction::query()
            ->where('type', 'Pengeluaran')
            ->whereBetween('recorded_at', [$month['start'], $month['end']])
            ->distinct()
            ->pluck('telegram_user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $stats = ['processed' => 0, 'stored' => 0, 'skipped' => 0, 'empty' => 0];

        foreach ($userIds as $telegramUserId) {
            $stats['processed']++;

            if (! $force && PortalGuidanceSnapshot::findForUser(
                $telegramUserId,
                PortalGuidanceSnapshot::TYPE_BEHAVIORAL_MONTHLY,
                $month['key'],
            ) !== null) {
                $stats['skipped']++;

                continue;
            }

            $context = $this->behavioral->monthlyGuidanceContext($telegramUserId, $month['key']);

            if ($context['expense_count'] === 0) {
                $stats['empty']++;

                continue;
            }

            $stored = $this->aiGuidance->generateAndStoreMonthlyBehavioralGuidance(
                $telegramUserId,
                $month['key'],
                $context['metrics'],
                $context['baseline'],
                $context['fallback'],
            );

            if ($stored) {
                $stats['stored']++;
            }
        }

        return $stats;
    }
}
