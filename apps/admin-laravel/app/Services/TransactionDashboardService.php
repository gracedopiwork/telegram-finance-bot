<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TransactionDashboardService
{
    /** @var array<string, list<string>> */
    private const BUCKET_NATURES = [
        'Essential Living' => ['Need'],
        'Protection' => ['Need'],
        'Future Building' => ['Saving/Investement'],
        'Flexible + Social' => ['Wants', 'Donation'],
    ];

    public function __construct(
        private readonly BucketPrescriptionService $prescription,
    ) {}

    public function monthKey(?string $month = null): string
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        return Carbon::now()->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(int $telegramUserId, ?string $month = null): array
    {
        $month = $this->monthKey($month);
        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->inMonth($month)
            ->orderByDesc('recorded_at')
            ->get();

        $income = (int) $rows->where('type', 'Pemasukan')->sum('amount');
        $expense = (int) $rows->where('type', 'Pengeluaran')->sum('amount');
        $cashflow = $income - $expense;
        $savingRate = $income > 0 ? round(($cashflow / $income) * 100, 1) : 0.0;
        $transactionCount = $rows->count();

        $byCategory = $this->spendingByCategory($rows);
        $topExpenses = $byCategory->sortByDesc('amount')->take(10)->values()->all();
        $idealShares = $this->prescription->idealsForUser($telegramUserId);
        $buckets = $this->budgetBuckets($rows, $expense, $idealShares);
        $trend = $this->cashflowTrend($telegramUserId, $month);
        $pulse = $this->financialPulse($income, $expense, $savingRate, $buckets);
        $baseline = FinancialBaseline::latestForUser($telegramUserId);

        return [
            'month' => $month,
            'month_label' => $this->monthLabel($month),
            'income' => $income,
            'expense' => $expense,
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
            'baseline_review_due' => $baseline?->isReviewDue() ?? false,
            'trend' => $trend,
            'pulse' => $pulse,
            'doctors_note' => $this->doctorsNoteFinancial($cashflow, $savingRate, $pulse['score']),
            'transactions' => $rows->take(50)->map(fn (BotTransaction $t) => $this->serializeTransaction($t))->all(),
        ];
    }

    /**
     * @return Collection<int, array{category: string, amount: int, share: float}>
     */
    private function spendingByCategory(Collection $rows): Collection
    {
        $expenses = $rows->where('type', 'Pengeluaran');
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
     * @return list<array{bucket: string, amount: int, share: float, ideal: float, progress: float}>
     */
    private function budgetBuckets(Collection $rows, int $totalExpense, array $idealShares): array
    {
        if ($totalExpense === 0) {
            return collect($idealShares)
                ->map(fn (float $ideal, string $bucket) => [
                    'bucket' => $bucket,
                    'amount' => 0,
                    'share' => 0.0,
                    'ideal' => $ideal,
                    'progress' => 0.0,
                ])
                ->values()
                ->all();
        }

        $expenses = $rows->where('type', 'Pengeluaran');
        $bucketTotals = [];

        foreach ($idealShares as $bucket => $ideal) {
            $bucketTotals[$bucket] = 0;
        }

        foreach ($expenses as $row) {
            $bucket = $this->resolveBucket($row);
            $bucketTotals[$bucket] += (int) $row->amount;
        }

        $result = [];
        foreach ($idealShares as $bucket => $ideal) {
            $amount = $bucketTotals[$bucket] ?? 0;
            $share = round(($amount / $totalExpense) * 100, 1);
            $progress = $ideal > 0 ? round(min(150, ($share / $ideal) * 100), 1) : 0.0;
            $result[] = [
                'bucket' => $bucket,
                'amount' => $amount,
                'share' => $share,
                'ideal' => $ideal,
                'progress' => $progress,
            ];
        }

        return $result;
    }

    private function resolveBucket(BotTransaction $row): string
    {
        $nature = $row->nature;
        if ($nature === 'Saving/Investement') {
            return 'Future Building';
        }
        if (in_array($nature, ['Wants', 'Donation'], true) || $row->category === 'Social') {
            return 'Flexible + Social';
        }
        if (in_array($row->category, ['Listrik', 'Air'], true)) {
            return 'Protection';
        }

        return 'Essential Living';
    }

    /**
     * @return list<array{month: string, label: string, income: int, expense: int, cashflow: int}>
     */
    private function cashflowTrend(int $telegramUserId, string $currentMonth): array
    {
        $end = Carbon::createFromFormat('Y-m', $currentMonth)->endOfMonth();
        $start = $end->copy()->subMonths(5)->startOfMonth();

        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();

        $points = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $start->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $monthRows = $rows->filter(fn (BotTransaction $t) => $t->recorded_at->format('Y-m') === $key);
            $income = (int) $monthRows->where('type', 'Pemasukan')->sum('amount');
            $expense = (int) $monthRows->where('type', 'Pengeluaran')->sum('amount');
            $points[] = [
                'month' => $key,
                'label' => $m->translatedFormat('M'),
                'income' => $income,
                'expense' => $expense,
                'cashflow' => $income - $expense,
            ];
        }

        return $points;
    }

    /**
     * @param  list<array{bucket: string, amount: int, share: float, ideal: float, progress: float}>  $buckets
     * @return array{score: int, label: string}
     */
    private function financialPulse(int $income, int $expense, float $savingRate, array $buckets): array
    {
        $score = 50;
        if ($income > 0) {
            $score += (int) min(25, max(-25, $savingRate / 2));
            $score += $expense <= $income ? 10 : -15;
        }
        foreach ($buckets as $bucket) {
            $delta = abs($bucket['share'] - $bucket['ideal']);
            $score -= (int) min(8, $delta / 5);
        }
        $score = max(0, min(100, $score));

        $label = match (true) {
            $score >= 80 => 'Excellent',
            $score >= 65 => 'Good',
            $score >= 45 => 'Fair',
            default => 'Needs Attention',
        };

        return ['score' => $score, 'label' => $label];
    }

    private function doctorsNoteFinancial(int $cashflow, float $savingRate, int $pulseScore): string
    {
        if ($cashflow > 0 && $savingRate >= 20) {
            return 'Cashflow positif dan saving rate sehat. Pertahankan konsistensi pencatatan.';
        }
        if ($cashflow < 0) {
            return 'Pengeluaran melebihi pendapatan bulan ini. Tinjau kembali kategori terbesar dan kebiasaan impulsif.';
        }
        if ($savingRate < 10) {
            return 'Saving rate masih rendah. Prioritaskan alokasi Future Building sebelum pengeluaran fleksibel.';
        }
        if ($pulseScore < 50) {
            return 'Distribusi bucket belum ideal. Sesuaikan proporsi Essential, Protection, dan Future Building.';
        }

        return 'Kondisi cukup stabil. Lanjutkan catat transaksi harian agar pola lebih jelas.';
    }

    private function monthLabel(string $month): string
    {
        return Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransaction(BotTransaction $t): array
    {
        return [
            'id' => $t->id,
            'recorded_at' => $t->recorded_at->format('d-m-Y H:i'),
            'type' => $t->type,
            'category' => $t->category,
            'sub_category' => $t->sub_category,
            'amount' => $t->amount,
            'nature' => $t->nature,
            'mood' => $t->mood,
            'is_impulsive' => $t->is_impulsive,
            'notes' => $t->notes,
            'source' => $t->source,
        ];
    }
}
