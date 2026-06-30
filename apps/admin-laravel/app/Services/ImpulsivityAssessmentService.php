<?php

namespace App\Services;

use App\Models\BotTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ImpulsivityAssessmentService
{
    /** @var list<string> */
    private const NEGATIVE_MOODS = ['Sad', 'Stressed', 'Angry', 'Tired'];

    /** @var list<string> */
    private const MOOD_ORDER = ['Happy', 'Neutral', 'Sad', 'Stressed', 'Angry', 'Tired'];

    public function assess(int $telegramUserId, ?string $month = null): array
    {
        $month = app(TransactionDashboardService::class)->monthKey($month);
        $rows = BotTransaction::query()
            ->forUser($telegramUserId)
            ->inMonth($month)
            ->get();

        $expenses = $rows->where('type', 'Pengeluaran');
        $expenseCount = $expenses->count();
        $impulsiveRows = $expenses->where('is_impulsive', true);
        $impulsiveCount = $impulsiveRows->count();
        $totalExpense = (int) $expenses->sum('amount');
        $impulsiveAmount = (int) $impulsiveRows->sum('amount');

        $impulsiveRate = $expenseCount > 0
            ? round(($impulsiveCount / $expenseCount) * 100, 1)
            : 0.0;

        $impulsiveAmountShare = $totalExpense > 0
            ? round(($impulsiveAmount / $totalExpense) * 100, 1)
            : 0.0;

        $score = $this->impulsivityScore($impulsiveRate, $impulsiveAmountShare, $impulsiveRows);
        $matrix = $this->needImpulsiveMatrix($expenses);
        $byMood = $this->moodBreakdown($expenses);
        $moodVsImpulsive = $this->moodVsImpulsive($expenses);
        $highestLeakage = $this->highestLeakage($impulsiveRows);
        $dominantPattern = $this->dominantPattern($matrix);
        $dominantMood = $this->dominantMood($expenses);
        $moodCalendar = $this->moodCalendar($rows, $month);

        return [
            'month' => $month,
            'month_label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
            'impulsive_rate' => $impulsiveRate,
            'impulsive_count' => $impulsiveCount,
            'expense_count' => $expenseCount,
            'impulsive_amount' => $impulsiveAmount,
            'impulsive_amount_share' => $impulsiveAmountShare,
            'score' => $score,
            'grade' => $this->grade($score),
            'risk_label' => $this->riskLabel($impulsiveRate),
            'matrix' => $matrix,
            'by_mood' => $byMood,
            'mood_vs_impulsive' => $moodVsImpulsive,
            'mood_calendar' => $moodCalendar,
            'need_vs_want' => $this->needVsWant($expenses),
            'dominant_mood' => $dominantMood,
            'dominant_pattern' => $dominantPattern,
            'highest_leakage' => $highestLeakage,
            'emotional_balance' => $this->emotionalBalanceScore($expenses),
            'doctors_note' => $this->doctorsNote(
                $impulsiveRate,
                $dominantMood,
                $dominantPattern,
                $highestLeakage,
            ),
        ];
    }

    private function impulsivityScore(float $impulsiveRate, float $impulsiveAmountShare, Collection $impulsiveRows): int
    {
        $base = 100 - (int) round($impulsiveRate * 0.7 + $impulsiveAmountShare * 0.3);

        $negativeMoodImpulsive = $impulsiveRows
            ->filter(fn (BotTransaction $t) => in_array($t->mood, self::NEGATIVE_MOODS, true))
            ->count();

        $penalty = min(20, $negativeMoodImpulsive * 3);

        return max(0, min(100, $base - $penalty));
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Sangat Terkendali',
            $score >= 60 => 'Cukup Terkendali',
            $score >= 40 => 'Perlu Perhatian',
            default => 'Berisiko Tinggi',
        };
    }

    private function riskLabel(float $impulsiveRate): string
    {
        return match (true) {
            $impulsiveRate >= 40 => 'Tinggi',
            $impulsiveRate >= 20 => 'Sedang',
            default => 'Rendah',
        };
    }

    /**
     * @return list<array{key: string, label: string, count: int, share: float}>
     */
    private function needImpulsiveMatrix(Collection $expenses): array
    {
        $cells = [
            'need_planned' => ['key' => 'need_planned', 'label' => 'Need + Terencana', 'count' => 0],
            'need_impulsive' => ['key' => 'need_impulsive', 'label' => 'Need + Impulsif', 'count' => 0],
            'want_planned' => ['key' => 'want_planned', 'label' => 'Want + Terencana', 'count' => 0],
            'want_impulsive' => ['key' => 'want_impulsive', 'label' => 'Want + Impulsif', 'count' => 0],
        ];

        foreach ($expenses as $row) {
            $isWant = in_array($row->nature, ['Wants', 'Donation'], true) || $row->category === 'Jajan';
            $isImpulsive = (bool) $row->is_impulsive;
            if ($isWant) {
                $cells[$isImpulsive ? 'want_impulsive' : 'want_planned']['count']++;
            } else {
                $cells[$isImpulsive ? 'need_impulsive' : 'need_planned']['count']++;
            }
        }

        $total = max(1, $expenses->count());
        foreach ($cells as &$cell) {
            $cell['share'] = round(($cell['count'] / $total) * 100, 1);
        }

        return array_values($cells);
    }

    /**
     * @return list<array{mood: string, count: int, amount: int, share: float}>
     */
    private function moodBreakdown(Collection $expenses): array
    {
        $total = max(1, $expenses->count());

        return collect(self::MOOD_ORDER)
            ->map(function (string $mood) use ($expenses, $total) {
                $items = $expenses->where('mood', $mood);
                $count = $items->count();

                return [
                    'mood' => $mood,
                    'count' => $count,
                    'amount' => (int) $items->sum('amount'),
                    'share' => round(($count / $total) * 100, 1),
                ];
            })
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<array{mood: string, impulsive_rate: float, impulsive_amount: int}>
     */
    private function moodVsImpulsive(Collection $expenses): array
    {
        return collect(self::MOOD_ORDER)
            ->map(function (string $mood) use ($expenses) {
                $items = $expenses->where('mood', $mood);
                $count = $items->count();
                if ($count === 0) {
                    return null;
                }
                $impulsive = $items->where('is_impulsive', true);
                $impulsiveCount = $impulsive->count();

                return [
                    'mood' => $mood,
                    'impulsive_rate' => round(($impulsiveCount / $count) * 100, 1),
                    'impulsive_amount' => (int) $impulsive->sum('amount'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{category: string, amount: int}|null
     */
    private function highestLeakage(Collection $impulsiveRows): ?array
    {
        if ($impulsiveRows->isEmpty()) {
            return null;
        }

        $top = $impulsiveRows
            ->groupBy('category')
            ->map(fn (Collection $items) => (int) $items->sum('amount'))
            ->sortDesc()
            ->take(1);

        $category = $top->keys()->first();
        $amount = $top->first();

        return $category ? ['category' => (string) $category, 'amount' => (int) $amount] : null;
    }

    /**
     * @param  list<array{key: string, label: string, count: int, share: float}>  $matrix
     */
    private function dominantPattern(array $matrix): string
    {
        $top = collect($matrix)->sortByDesc('count')->first();

        return $top['label'] ?? 'Belum ada data';
    }

    private function dominantMood(Collection $expenses): string
    {
        if ($expenses->isEmpty()) {
            return 'Belum ada data';
        }

        return (string) $expenses->groupBy('mood')->sortByDesc(fn (Collection $g) => $g->count())->keys()->first();
    }

    /**
     * @return list<array{day: int, mood: string|null, emoji: string}>
     */
    private function moodCalendar(Collection $rows, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $daysInMonth = $start->daysInMonth;
        $calendar = [];

        $moodEmoji = [
            'Happy' => '😊',
            'Neutral' => '😐',
            'Sad' => '😢',
            'Stressed' => '😨',
            'Angry' => '😡',
            'Tired' => '😴',
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayRows = $rows->filter(fn (BotTransaction $t) => (int) $t->recorded_at->format('d') === $day);
            $mood = $dayRows->isNotEmpty()
                ? (string) $dayRows->groupBy('mood')->sortByDesc(fn (Collection $g) => $g->count())->keys()->first()
                : null;

            $calendar[] = [
                'day' => $day,
                'mood' => $mood,
                'emoji' => $mood ? ($moodEmoji[$mood] ?? '•') : '',
            ];
        }

        return $calendar;
    }

    /**
     * @return array{need: array{count: int, share: float}, want: array{count: int, share: float}}
     */
    private function needVsWant(Collection $expenses): array
    {
        $total = max(1, $expenses->count());
        $wantCount = $expenses->filter(
            fn (BotTransaction $t) => in_array($t->nature, ['Wants', 'Donation'], true) || $t->category === 'Jajan'
        )->count();
        $needCount = $expenses->count() - $wantCount;

        return [
            'need' => [
                'count' => $needCount,
                'share' => round(($needCount / $total) * 100, 1),
            ],
            'want' => [
                'count' => $wantCount,
                'share' => round(($wantCount / $total) * 100, 1),
            ],
        ];
    }

    /**
     * @return array{score: int, label: string}
     */
    private function emotionalBalanceScore(Collection $expenses): array
    {
        if ($expenses->isEmpty()) {
            return ['score' => 0, 'label' => 'Belum ada data'];
        }

        $positive = $expenses->whereIn('mood', ['Happy', 'Neutral'])->count();
        $negative = $expenses->whereIn('mood', self::NEGATIVE_MOODS)->count();
        $total = max(1, $positive + $negative);
        $score = (int) round(($positive / $total) * 100);

        $label = match (true) {
            $score >= 70 => 'Seimbang',
            $score >= 45 => 'Cukup',
            default => 'Perlu perhatian',
        };

        return ['score' => $score, 'label' => $label];
    }

    private function doctorsNote(
        float $impulsiveRate,
        string $dominantMood,
        string $dominantPattern,
        ?array $highestLeakage,
    ): string {
        if ($impulsiveRate >= 40) {
            $leak = $highestLeakage['category'] ?? 'beberapa kategori';
            return "Tingkat impulsifitas tinggi ({$impulsiveRate}%). Kebocoran terbesar di {$leak}. Coba jeda 10 menit sebelum transaksi non-kebutuhan.";
        }
        if (in_array($dominantMood, self::NEGATIVE_MOODS, true) && str_contains($dominantPattern, 'Impulsif')) {
            return "Pola dominan {$dominantPattern} saat mood {$dominantMood}. Pertimbangkan coping non-finansial saat emosi negatif.";
        }
        if ($impulsiveRate <= 15) {
            return 'Kebiasaan belanja cukup terkendali. Pertahankan konfirmasi preview sebelum simpan di bot.';
        }

        return "Pola dominan {$dominantPattern} dengan mood {$dominantMood}. Pantau kategori impulsif secara mingguan.";
    }
}
