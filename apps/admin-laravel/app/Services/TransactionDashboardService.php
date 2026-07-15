<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use App\Support\PortalTimezone;
use App\Support\TransactionTaxonomy;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TransactionDashboardService
{
    /** @var list<int> */
    private const ALLOWED_PERIODS = [1, 3, 6, 12];

    private const TRANSACTION_TABLE_LIMIT = 500;

    public function __construct(
        private readonly BucketPrescriptionService $prescription,
        private readonly CategoryBucketService $categoryBuckets,
    ) {}

    public function monthKey(?string $month = null): string
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        return Carbon::now()->format('Y-m');
    }

    public function periodMonths(?int $period = null): int
    {
        $period = $period ?? 1;

        return in_array($period, self::ALLOWED_PERIODS, true) ? $period : 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(int $telegramUserId, ?string $month = null, ?int $period = null, ?string $email = null): array
    {
        $email = strtolower(trim((string) $email));
        if ($email !== '') {
            app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);
        }

        $month = $this->monthKey($month);
        $periodMonths = $this->periodMonths($period);
        $range = $this->periodRange($month, $periodMonths);

        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->whereBetween('recorded_at', [$range['start'], $range['end']])
            ->orderByDesc('recorded_at')
            ->get();

        $income = (int) $rows->where('type', TransactionTaxonomy::TYPE_INCOME)->sum('amount');
        $expense = (int) $rows->where('type', TransactionTaxonomy::TYPE_EXPENSE)->sum('amount');
        $savingInvestment = (int) $rows->where('type', TransactionTaxonomy::TYPE_SAVING)->sum('amount');
        $cashflow = $income - $expense - $savingInvestment;
        $savingRate = $income > 0 ? round(($savingInvestment / $income) * 100, 1) : 0.0;
        $transactionCount = $rows->count();

        $byCategory = $this->spendingByCategory($rows);
        $topExpenses = $byCategory->sortByDesc('amount')->take(10)->values()->all();
        $idealShares = $this->prescription->idealsForUser($telegramUserId);
        $buckets = $this->budgetBuckets($rows, $expense, $savingInvestment, $idealShares);
        $trend = $this->cashflowTrend($telegramUserId, $month, 6);
        $baseline = FinancialBaseline::latestForUser($telegramUserId);
        if ($baseline === null && $email !== '') {
            $baseline = FinancialBaseline::latestForEmail($email);
        }
        $incomeAnalysis = $this->incomeAnalysis($rows, $income);
        $savingAnalysis = $this->savingAnalysis($rows);
        $dailyExpenses = $this->dailyExpenseTrend($telegramUserId, $month);
        $fallbackClinical = $this->clinicalSummary($income, $expense, $cashflow, $savingRate, $buckets, $baseline, $periodMonths);
        $fallbackDoctorsNote = $this->doctorsNoteFinancial($cashflow, $savingRate, $buckets, $baseline);

        $aiGuidance = app(PortalAiGuidanceService::class)->financial(
            $telegramUserId,
            $month,
            $periodMonths,
            [
                'period_label' => $this->periodLabel($month, $periodMonths),
                'income' => $income,
                'expense' => $expense,
                'saving_investment' => $savingInvestment,
                'cashflow' => $cashflow,
                'saving_rate' => $savingRate,
                'transaction_count' => $transactionCount,
                'buckets' => $buckets,
            ],
            $baseline,
            [
                'clinical_summary' => $fallbackClinical,
                'doctors_note' => $fallbackDoctorsNote,
            ],
        );

        return [
            'month' => $month,
            'period_months' => $periodMonths,
            'period_label' => $this->periodLabel($month, $periodMonths),
            'month_label' => $this->monthLabel($month),
            'income' => $income,
            'expense' => $expense,
            'saving_investment' => $savingInvestment,
            'cashflow' => $cashflow,
            'saving_rate' => $savingRate,
            'transaction_count' => $transactionCount,
            'income_share' => $income > 0 ? 100.0 : 0.0,
            'expense_share' => $income > 0 ? round(($expense / $income) * 100, 1) : 0.0,
            'cashflow_share' => $income > 0 ? round(($cashflow / $income) * 100, 1) : 0.0,
            'by_category' => $byCategory->values()->all(),
            'top_expenses' => $topExpenses,
            'buckets' => $buckets,
            'bucket_ideals_source' => $baseline?->financial_stage ?? 'growing',
            'baseline' => $this->serializeBaseline($baseline),
            'baseline_review_due' => $baseline?->isReviewDue() ?? false,
            'trend' => $trend,
            'income_analysis' => $incomeAnalysis,
            'saving_analysis' => $savingAnalysis,
            'daily_expenses' => $dailyExpenses,
            'clinical_summary' => $aiGuidance['clinical_summary'],
            'doctors_note' => $aiGuidance['doctors_note'],
            'ai_source' => $aiGuidance['ai_source'],
            'ai_generated_at' => $aiGuidance['generated_at'],
            'clinical_pending' => $aiGuidance['clinical_pending'] ?? false,
            'doctors_pending' => $aiGuidance['doctors_pending'] ?? false,
            'clinical_generated_at' => $aiGuidance['clinical_generated_at'] ?? null,
            'doctors_generated_at' => $aiGuidance['doctors_generated_at'] ?? null,
            'transactions_total' => $rows->count(),
            'transactions_shown' => min($rows->count(), self::TRANSACTION_TABLE_LIMIT),
            'transactions' => $rows->take(self::TRANSACTION_TABLE_LIMIT)->map(fn (BotTransaction $t) => $this->serializeTransaction($t, $telegramUserId))->all(),
        ];
    }

    /**
     * Metrik + fallback untuk generate / tampilkan guidance AI (rentang tanggal bebas).
     *
     * @return array{
     *   metrics: array<string, mixed>,
     *   fallback_clinical: array{headline: string, findings: list<string>, status: string},
     *   fallback_doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string},
     *   transaction_count: int
     * }
     */
    public function financialGuidanceContext(
        int $telegramUserId,
        Carbon $start,
        Carbon $end,
        string $periodLabel,
        int $periodMonthsForClinical = 1,
    ): array {
        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->whereBetween('recorded_at', [$start, $end])
            ->orderByDesc('recorded_at')
            ->get();

        $income = (int) $rows->where('type', TransactionTaxonomy::TYPE_INCOME)->sum('amount');
        $expense = (int) $rows->where('type', TransactionTaxonomy::TYPE_EXPENSE)->sum('amount');
        $savingInvestment = (int) $rows->where('type', TransactionTaxonomy::TYPE_SAVING)->sum('amount');
        $cashflow = $income - $expense - $savingInvestment;
        $savingRate = $income > 0 ? round(($savingInvestment / $income) * 100, 1) : 0.0;
        $transactionCount = $rows->count();

        $idealShares = $this->prescription->idealsForUser($telegramUserId);
        $buckets = $this->budgetBuckets($rows, $expense, $savingInvestment, $idealShares);
        $baseline = FinancialBaseline::latestForUser($telegramUserId);

        $fallbackClinical = $this->clinicalSummary(
            $income,
            $expense,
            $cashflow,
            $savingRate,
            $buckets,
            $baseline,
            $periodMonthsForClinical,
        );
        $fallbackDoctorsNote = $this->doctorsNoteFinancial($cashflow, $savingRate, $buckets, $baseline);

        return [
            'metrics' => [
                'period_label' => $periodLabel,
                'income' => $income,
                'expense' => $expense,
                'saving_investment' => $savingInvestment,
                'cashflow' => $cashflow,
                'saving_rate' => $savingRate,
                'transaction_count' => $transactionCount,
                'buckets' => $buckets,
            ],
            'fallback_clinical' => $fallbackClinical,
            'fallback_doctors_note' => $fallbackDoctorsNote,
            'transaction_count' => $transactionCount,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function periodRange(string $anchorMonth, int $periodMonths): array
    {
        $end = Carbon::createFromFormat('Y-m', $anchorMonth)->endOfMonth();
        $start = $end->copy()->subMonths($periodMonths - 1)->startOfMonth();

        return ['start' => $start, 'end' => $end];
    }

    private function periodLabel(string $anchorMonth, int $periodMonths): string
    {
        if ($periodMonths === 1) {
            return $this->monthLabel($anchorMonth);
        }

        $range = $this->periodRange($anchorMonth, $periodMonths);

        return $range['start']->translatedFormat('M Y').' – '.$range['end']->translatedFormat('M Y');
    }

    /**
     * @return Collection<int, array{category: string, amount: int, share: float}>
     */
    private function spendingByCategory(Collection $rows): Collection
    {
        $expenses = $rows->where('type', TransactionTaxonomy::TYPE_EXPENSE);
        $total = (int) $expenses->sum('amount');
        if ($total === 0) {
            return collect();
        }

        return $expenses
            ->groupBy('category')
            ->map(function (Collection $items, string $category) use ($total) {
                $amount = (int) $items->sum('amount');

                return [
                    'category' => $category,
                    'amount' => $amount,
                    'share' => round(($amount / $total) * 100, 1),
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, float>  $idealShares
     * @return list<array{bucket: string, amount: int, share: float, ideal: float, progress: float, status: string, status_label: string}>
     */
    private function budgetBuckets(Collection $rows, int $totalExpense, int $totalSaving, array $idealShares): array
    {
        $totalAllocated = $totalExpense + $totalSaving;
        if ($totalAllocated === 0) {
            return collect($idealShares)
                ->map(fn (float $ideal, string $bucket) => [
                    'bucket' => $bucket,
                    'amount' => 0,
                    'share' => 0.0,
                    'ideal' => $ideal,
                    'progress' => 0.0,
                    'status' => 'empty',
                    'status_label' => 'Belum ada data',
                ])
                ->values()
                ->all();
        }

        $bucketTotals = array_fill_keys(array_keys($idealShares), 0);

        $allocatedRows = $rows->whereIn('type', [
            TransactionTaxonomy::TYPE_EXPENSE,
            TransactionTaxonomy::TYPE_SAVING,
        ]);
        foreach ($allocatedRows as $row) {
            $bucket = $this->categoryBuckets->resolve($row);
            if ($bucket === null || ! array_key_exists($bucket, $bucketTotals)) {
                continue;
            }
            $bucketTotals[$bucket] += (int) $row->amount;
        }

        $result = [];
        foreach ($idealShares as $bucket => $ideal) {
            $amount = $bucketTotals[$bucket] ?? 0;
            $share = round(($amount / $totalAllocated) * 100, 1);
            $progress = $ideal > 0 ? round(min(150, ($share / $ideal) * 100), 1) : 0.0;
            $status = $this->bucketStatus($bucket, $share, $ideal);

            $result[] = [
                'bucket' => $bucket,
                'amount' => $amount,
                'share' => $share,
                'ideal' => $ideal,
                'progress' => $progress,
                'status' => $status['key'],
                'status_label' => $status['label'],
            ];
        }

        return $result;
    }

    /**
     * @return array{key: string, label: string}
     */
    private function bucketStatus(string $bucket, float $share, float $ideal): array
    {
        return match ($bucket) {
            'Essential Living', 'Flexible + Social' => $this->maxBucketStatus($bucket, $share, $ideal),
            'Future Building', 'Protection' => $this->minBucketStatus($bucket, $share, $ideal),
            default => abs($share - $ideal) <= 5
              ? ['key' => 'on_target', 'label' => 'Sesuai target']
              : ($share > $ideal
                ? ['key' => 'over', 'label' => 'Di atas target']
                : ['key' => 'under', 'label' => 'Di bawah target']),
        };
    }

    /**
     * Bucket dengan target minimum — semakin tinggi % aktual, semakin sehat.
     *
     * @return array{key: string, label: string}
     */
    private function minBucketStatus(string $bucket, float $share, float $ideal): array
    {
        $underLabel = match ($bucket) {
            'Protection' => 'Di bawah target proteksi',
            default => 'Di bawah minimum ideal',
        };
        $metLabel = match ($bucket) {
            'Protection' => 'Memenuhi target proteksi',
            default => 'Memenuhi minimum',
        };
        $nearLabel = match ($bucket) {
            'Protection' => 'Mendekati target proteksi',
            default => 'Mendekati minimum',
        };

        if ($share < $ideal - 5) {
            return ['key' => 'under_min', 'label' => $underLabel];
        }

        if ($share >= $ideal) {
            return ['key' => 'met', 'label' => $metLabel];
        }

        return ['key' => 'near_min', 'label' => $nearLabel];
    }

    /**
     * Bucket dengan target maksimum — semakin rendah % aktual, semakin sehat.
     *
     * @return array{key: string, label: string}
     */
    private function maxBucketStatus(string $bucket, float $share, float $ideal): array
    {
        $overLabel = match ($bucket) {
            'Essential Living' => 'Melebihi batas esensial',
            'Protection' => 'Melebihi batas proteksi',
            default => 'Melebihi batas fleksibel',
        };
        $withinLabel = match ($bucket) {
            'Essential Living' => 'Di bawah maksimum — sehat',
            default => 'Dalam batas',
        };

        if ($share > $ideal + 5) {
            return ['key' => 'over_max', 'label' => $overLabel];
        }

        if ($share <= $ideal) {
            return ['key' => 'within', 'label' => $withinLabel];
        }

        return ['key' => 'near_max', 'label' => 'Mendekati batas maksimum'];
    }

    /**
     * @return list<array{month: string, label: string, income: int, expense: int, cashflow: int}>
     */
    private function cashflowTrend(int $telegramUserId, string $currentMonth, int $months): array
    {
        $end = Carbon::createFromFormat('Y-m', $currentMonth)->endOfMonth();
        $start = $end->copy()->subMonths($months - 1)->startOfMonth();

        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();

        $points = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $monthRows = $rows->filter(fn (BotTransaction $t) => $t->recorded_at->format('Y-m') === $key);
            $income = (int) $monthRows->where('type', TransactionTaxonomy::TYPE_INCOME)->sum('amount');
            $expense = (int) $monthRows->where('type', TransactionTaxonomy::TYPE_EXPENSE)->sum('amount');
            $saving = (int) $monthRows->where('type', TransactionTaxonomy::TYPE_SAVING)->sum('amount');
            $points[] = [
                'month' => $key,
                'label' => $m->translatedFormat('M'),
                'income' => $income,
                'expense' => $expense,
                'saving_investment' => $saving,
                'cashflow' => $income - $expense - $saving,
            ];
        }

        return $points;
    }

    /**
     * @return list<array{label: string, amount: int, share: float}>
     */
    private function savingAnalysis(Collection $rows): array
    {
        $savingRows = $rows->where('type', TransactionTaxonomy::TYPE_SAVING);
        $total = (int) $savingRows->sum('amount');
        if ($total === 0) {
            return [];
        }

        return $savingRows
            ->groupBy(fn (BotTransaction $row) => $this->savingAnalysisLabel($row))
            ->map(function (Collection $items, string $label) use ($total) {
                $amount = (int) $items->sum('amount');

                return [
                    'label' => $label,
                    'amount' => $amount,
                    'share' => round(($amount / $total) * 100, 1),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    private function savingAnalysisLabel(BotTransaction $row): string
    {
        $category = trim((string) $row->category);
        if ($category !== '' && $category !== '-') {
            $fromCategory = $this->inferSavingLabelFromNotes($category);
            if ($fromCategory !== null) {
                return $fromCategory;
            }

            return $category;
        }

        $notes = trim((string) $row->notes);
        if ($notes !== '' && $notes !== '-') {
            $inferred = $this->inferSavingLabelFromNotes($notes);
            if ($inferred !== null) {
                return $inferred;
            }

            return mb_strlen($notes) > 36 ? mb_substr($notes, 0, 33).'…' : $notes;
        }

        return 'Tabungan/Investasi';
    }

    private function inferSavingLabelFromNotes(string $notes): ?string
    {
        $lower = mb_strtolower($notes);
        foreach ([
            'reksadana' => 'Reksadana',
            'saham' => 'Saham',
            'obligasi' => 'Obligasi',
            'emas' => 'Emas',
            'deposito' => 'Deposito',
            'crypto' => 'Crypto',
            'dana darurat' => 'Dana darurat',
            'nabung' => 'Tabungan',
            'investasi' => 'Investasi',
        ] as $keyword => $label) {
            if (str_contains($lower, $keyword)) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Pengeluaran harian dalam bulan terpilih (untuk grafik).
     *
     * @return list<array{label: string, day: int, amount: int}>
     */
    private function dailyExpenseTrend(int $telegramUserId, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->where('type', TransactionTaxonomy::TYPE_EXPENSE)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();

        $points = [];
        for ($day = 1; $day <= $end->day; $day++) {
            $points[] = ['label' => (string) $day, 'day' => $day, 'amount' => 0];
        }

        foreach ($rows as $row) {
            $dayIndex = (int) $row->recorded_at->format('j') - 1;
            if (isset($points[$dayIndex])) {
                $points[$dayIndex]['amount'] += (int) $row->amount;
            }
        }

        return $points;
    }

    /**
     * @return array{total: int, by_source: list<array{label: string, amount: int, share: float}>, stability: string}
     */
    private function incomeAnalysis(Collection $rows, int $totalIncome): array
    {
        $incomeRows = $rows->where('type', 'Pemasukan');
        if ($totalIncome === 0) {
            return [
                'total' => 0,
                'by_source' => [],
                'stability' => 'Belum ada pemasukan tercatat',
            ];
        }

        $bySource = $incomeRows
            ->groupBy('category')
            ->map(function (Collection $items, string $category) use ($totalIncome) {
                $amount = (int) $items->sum('amount');

                return [
                    'label' => $category,
                    'amount' => $amount,
                    'share' => round(($amount / $totalIncome) * 100, 1),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();

        $topShare = $bySource[0]['share'] ?? 0;
        $stability = match (true) {
            count($bySource) <= 1 && $topShare >= 90 => 'Sangat terkonsentrasi — diversifikasi sumber pendapatan disarankan',
            $topShare >= 70 => 'Cukup terkonsentrasi pada satu sumber',
            default => 'Relatif terdiversifikasi',
        };

        return [
            'total' => $totalIncome,
            'by_source' => $bySource,
            'stability' => $stability,
        ];
    }

    /**
     * @param  list<array{bucket: string, amount: int, share: float, ideal: float, progress: float, status: string, status_label: string}>  $buckets
     * @return array{headline: string, findings: list<string>, status: string}
     */
    private function clinicalSummary(
        int $income,
        int $expense,
        int $cashflow,
        float $savingRate,
        array $buckets,
        ?FinancialBaseline $baseline,
        int $periodMonths,
    ): array {
        $findings = [];
        $periodText = $periodMonths === 1 ? 'bulan ini' : "{$periodMonths} bulan terakhir";

        if ($income === 0 && $expense === 0) {
            return [
                'headline' => 'Belum ada data transaksi',
                'findings' => ['Mulai catat pemasukan & pengeluaran via YFD First Aid.'],
                'status' => 'no_data',
            ];
        }

        if ($cashflow < 0) {
            $findings[] = "Cashflow negatif {$periodText} — pengeluaran melebihi pendapatan.";
        } elseif ($savingRate >= 20) {
            $findings[] = "Saving rate {$savingRate}% — di atas ambang sehat (≥20%).";
        } else {
            $findings[] = "Saving rate {$savingRate}% — masih di bawah ideal 20%.";
        }

        foreach ($buckets as $bucket) {
            if (in_array($bucket['status'], ['under_min', 'over_max', 'over'], true)) {
                $findings[] = "{$bucket['bucket']}: {$bucket['status_label']} ({$bucket['share']}% vs ideal {$bucket['ideal']}%).";
            }
        }

        if ($baseline?->emergency_fund && $baseline->avg_monthly_income) {
            $monthsCovered = round($baseline->emergency_fund / max(1, $baseline->avg_monthly_income), 1);
            $findings[] = "Dana darurat baseline ≈ {$monthsCovered} bulan pengeluaran (dari snapshot).";
        }

        $headline = match (true) {
            $cashflow < 0 => 'Defisit — perlu restrukturisasi pengeluaran',
            $savingRate >= 20 => 'Kesehatan arus kas baik',
            $savingRate >= 10 => 'Stabil — masih ada ruang optimasi',
            default => 'Perlu perhatian — saving rate rendah',
        };

        $status = match (true) {
            $cashflow < 0 => 'critical',
            $savingRate >= 20 => 'healthy',
            $savingRate >= 10 => 'fair',
            default => 'attention',
        };

        return [
            'headline' => $headline,
            'findings' => array_slice($findings, 0, 5),
            'status' => $status,
        ];
    }

    /**
     * @param  list<array{bucket: string, amount: int, share: float, ideal: float, progress: float, status: string, status_label: string}>  $buckets
     * @return array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}
     */
    private function doctorsNoteFinancial(
        int $cashflow,
        float $savingRate,
        array $buckets,
        ?FinancialBaseline $baseline,
    ): array {
        $recommendations = [];

        if ($cashflow > 0 && $savingRate < 30) {
            $recommendations[] = 'Alokasikan cashflow positif ke Future Building dengan menaikkan saving rate minimal >30%.';
        } elseif ($cashflow > 0) {
            $recommendations[] = 'Pertahankan saving rate di atas 30% dan arahkan surplus ke instrumen jangka panjang.';
        }

        if ($cashflow < 0) {
            $recommendations[] = 'Kurangi pengeluaran Flexible + Social hingga cashflow kembali positif sebelum menambah investasi.';
        }

        $hasBucketIssue = false;
        foreach ($buckets as $bucket) {
            $name = (string) ($bucket['bucket'] ?? '');
            $share = (float) ($bucket['share'] ?? 0);
            $ideal = (float) ($bucket['ideal'] ?? 0);
            $status = (string) ($bucket['status'] ?? '');

            if ($name === 'Essential Living') {
                if (in_array($status, ['over_max', 'over'], true)) {
                    $recommendations[] = 'Kurangi pengeluaran Essential Living agar tidak melebihi batas prescription tahap finansial Anda.';
                    $hasBucketIssue = true;
                }

                continue;
            }

            if ($name === 'Flexible + Social' && in_array($status, ['over_max', 'over'], true)) {
                $recommendations[] = 'Kontrol pengeluaran Flexible + Social agar tidak melebihi 10% dari pendapatan.';
                $hasBucketIssue = true;
            }

            if ($name === 'Future Building' && in_array($status, ['under_min', 'under'], true)) {
                $recommendations[] = 'Naikkan alokasi Future Building agar mendekati target minimal 30% dari pendapatan.';
                $hasBucketIssue = true;
            }

            if ($name === 'Protection' && in_array($status, ['under_min', 'under', 'near_min'], true) && $share < $ideal) {
                $recommendations[] = 'Evaluasi dan optimalkan alokasi proteksi keuangan (asuransi/dana darurat) — saat ini masih di bawah target prescription.';
                $hasBucketIssue = true;
            }

            if ($name === 'Future Building' && $share > 0 && $savingRate >= 20) {
                $recommendations[] = 'Lakukan diversifikasi ke instrumen investasi selain saham untuk menyeimbangkan risiko.';
            }
        }

        if ($hasBucketIssue) {
            $recommendations[] = 'Prioritaskan penyesuaian bucket yang menyimpang (Flexible + Social, Future Building, Protection) — Essential Living yang rendah justru sehat.';
        }

        if ($baseline !== null && ! $baseline->has_health_insurance && ! $baseline->has_life_insurance) {
            $recommendations[] = 'Evaluasi proteksi keuangan Anda — pertimbangkan konsultasi dengan tim YFD via WhatsApp untuk penyesuaian asuransi.';
        }

        $recommendations = array_values(array_unique(array_slice($recommendations, 0, 5)));

        if ($recommendations === []) {
            $recommendations[] = 'Pertahankan konsistensi pencatatan transaksi dan tinjau bucket prescription setiap minggu.';
        }

        return [
            'summary' => 'Rekomendasi untuk periode ini',
            'findings' => $recommendations,
            'interpretation' => '',
            'priority' => $recommendations[0],
            'education' => '',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeBaseline(?FinancialBaseline $baseline): ?array
    {
        if ($baseline === null) {
            return null;
        }

        return [
            'stage_label' => $baseline->stage_label,
            'financial_stage' => $baseline->financial_stage,
            'current_goal' => $baseline->current_goal,
            'avg_monthly_income' => $baseline->avg_monthly_income,
            'emergency_fund' => $baseline->emergency_fund,
            'cash_savings' => $baseline->cash_savings,
            'total_investment' => $baseline->total_investment,
            'total_asset' => $baseline->total_asset,
            'total_debt' => $baseline->total_debt,
            'protection' => [
                'bpjs' => $baseline->has_bpjs,
                'health' => $baseline->has_health_insurance,
                'income' => $baseline->has_income_protection,
                'life' => $baseline->has_life_insurance,
            ],
            'dominant_archetype_label' => $baseline->dominant_archetype_label,
            'assessed_at' => $baseline->assessed_at->format('d M Y'),
            'has_financial_snapshot' => $this->baselineHasFinancialSnapshot($baseline),
        ];
    }

    private function baselineHasFinancialSnapshot(FinancialBaseline $baseline): bool
    {
        if ($baseline->current_goal) {
            return true;
        }

        foreach (['avg_monthly_income', 'emergency_fund', 'cash_savings', 'total_investment', 'total_asset', 'total_debt'] as $field) {
            if ($baseline->{$field} !== null && (int) $baseline->{$field} > 0) {
                return true;
            }
        }

        return $baseline->has_bpjs
          || $baseline->has_health_insurance
          || $baseline->has_income_protection
          || $baseline->has_life_insurance;
    }

    private function monthLabel(string $month): string
    {
        return Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransaction(BotTransaction $t, int $telegramUserId): array
    {
        return [
            'id' => $t->id,
            'recorded_at' => PortalTimezone::formatRecordedAt($t->recorded_at, $telegramUserId),
            'type' => $t->type,
            'category' => $t->category,
            'sub_category' => $t->sub_category,
            'amount' => $t->amount,
            'nature' => $t->nature,
            'mood' => $t->mood,
            'is_impulsive' => $t->is_impulsive,
            'notes' => $t->notes,
            'source' => $t->source,
            'bucket' => $this->categoryBuckets->resolve($t) ?? '—',
        ];
    }
}
