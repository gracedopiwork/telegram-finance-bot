<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ImpulsivityAssessmentService
{
  /** @var list<string> */
  private const NEGATIVE_MOODS = ['Sad', 'Stressed', 'Angry', 'Tired'];

  /** @var list<string> */
  private const POSITIVE_MOODS = ['Happy'];

  /** @var list<string> */
  private const NEUTRAL_MOODS = ['Neutral'];

  /** @var list<string> */
  private const MOOD_ORDER = ['Happy', 'Neutral', 'Sad', 'Stressed', 'Angry', 'Tired'];

  public function assess(int $telegramUserId, ?string $month = null, ?int $period = null, ?string $email = null): array
  {
    $dashboard = app(TransactionDashboardService::class);
    $month = $dashboard->monthKey($month);
    $periodMonths = $dashboard->periodMonths($period);
    $range = $this->periodRange($month, $periodMonths);

    $rows = BotTransaction::query()
      ->forUser($telegramUserId)
      ->whereBetween('recorded_at', [$range['start'], $range['end']])
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
    $moodGroups = $this->moodGroups($expenses);
    $moodVsImpulsive = $this->moodVsImpulsive($expenses);
    $moodSpendingMatrix = $this->moodSpendingMatrix($expenses);
    $moodTimeline = $this->moodTimeline($rows, $month, $periodMonths);
    $highestLeakage = $this->highestLeakage($impulsiveRows);
    $impulsiveCategories = $this->topImpulsiveCategories($impulsiveRows);
    $moodTable = $this->moodTableRows($expenses);
    $dominantPattern = $this->dominantPattern($matrix);
    $dominantMood = $this->dominantMood($expenses);
    $moodCalendar = $this->moodCalendar($rows, $month);
    $baseline = FinancialBaseline::latestForUser($telegramUserId);
    if ($baseline === null && is_string($email) && trim($email) !== '') {
      $baseline = FinancialBaseline::latestForEmail($email);
    }
    $ftsaProfile = $this->ftsaProfile($baseline);
    $emotionalBalance = $this->emotionalBalanceScore($expenses);

    $core = [
      'month' => $month,
      'period_months' => $periodMonths,
      'period_label' => $periodMonths === 1
        ? Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y')
        : $range['start']->translatedFormat('M Y').' – '.$range['end']->translatedFormat('M Y'),
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
      'mood_groups' => $moodGroups,
      'mood_vs_impulsive' => $moodVsImpulsive,
      'mood_spending_matrix' => $moodSpendingMatrix,
      'mood_timeline' => $moodTimeline,
      'mood_calendar' => $moodCalendar,
      'need_vs_want' => $this->needVsWant($expenses),
      'dominant_mood' => $dominantMood,
      'dominant_pattern' => $dominantPattern,
      'highest_leakage' => $highestLeakage,
      'impulsive_categories' => $impulsiveCategories,
      'mood_table' => $moodTable,
      'emotional_balance' => $emotionalBalance,
      'ftsa_profile' => $ftsaProfile,
    ];

    $fallbackGuidance = [
      'insights' => $this->autoInsights($impulsiveRate, $dominantMood, $dominantPattern, $moodGroups, $highestLeakage, $ftsaProfile),
      'recommendations' => $this->recommendations($impulsiveRate, $dominantMood, $ftsaProfile, $moodGroups),
      'doctors_note' => $this->doctorsNote(
        $impulsiveRate,
        $dominantMood,
        $dominantPattern,
        $highestLeakage,
        $ftsaProfile,
      ),
    ];

    $aiGuidance = app(PortalAiGuidanceService::class)->behavioral(
      $telegramUserId,
      $month,
      $periodMonths,
      $core,
      $baseline,
      $fallbackGuidance,
    );

    return array_merge($core, [
      'insights' => $aiGuidance['insights'],
      'recommendations' => $aiGuidance['recommendations'],
      'doctors_note' => $aiGuidance['doctors_note'],
      'ai_source' => $aiGuidance['ai_source'],
      'ai_generated_at' => $aiGuidance['generated_at'],
    ]);
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
      $isWant = $row->nature === 'Wants' || $row->category === 'Jajan';
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
   * @return array{positive: array{count: int, share: float, amount: int}, neutral: array{count: int, share: float, amount: int}, negative: array{count: int, share: float, amount: int}}
   */
  private function moodGroups(Collection $expenses): array
  {
    $total = max(1, $expenses->count());

    $build = function (array $moods) use ($expenses, $total) {
      $items = $expenses->whereIn('mood', $moods);
      $count = $items->count();

      return [
        'count' => $count,
        'share' => round(($count / $total) * 100, 1),
        'amount' => (int) $items->sum('amount'),
      ];
    };

    return [
      'positive' => $build(self::POSITIVE_MOODS),
      'neutral' => $build(self::NEUTRAL_MOODS),
      'negative' => $build(self::NEGATIVE_MOODS),
    ];
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
   * @return list<array{mood: string, amount: int, share: float, impulsive_amount: int}>
   */
  private function moodSpendingMatrix(Collection $expenses): array
  {
    $total = max(1, (int) $expenses->sum('amount'));

    return collect(self::MOOD_ORDER)
      ->map(function (string $mood) use ($expenses, $total) {
        $items = $expenses->where('mood', $mood);
        $amount = (int) $items->sum('amount');
        if ($amount === 0) {
          return null;
        }

        return [
          'mood' => $mood,
          'amount' => $amount,
          'share' => round(($amount / $total) * 100, 1),
          'impulsive_amount' => (int) $items->where('is_impulsive', true)->sum('amount'),
        ];
      })
      ->filter()
      ->values()
      ->all();
  }

  /**
   * @return list<array{date: string, label: string, mood_score: int|null, expense: int}>
   */
  private function moodTimeline(Collection $rows, string $anchorMonth, int $periodMonths): array
  {
    $end = Carbon::createFromFormat('Y-m', $anchorMonth)->endOfMonth();
    $start = $end->copy()->subMonths($periodMonths - 1)->startOfDay();
    $moodScore = [
      'Happy' => 5, 'Neutral' => 3, 'Sad' => 2, 'Stressed' => 1, 'Angry' => 1, 'Tired' => 2,
    ];

    $points = [];
    $cursor = $start->copy();
    while ($cursor->lte($end)) {
      $dayKey = $cursor->format('Y-m-d');
      $dayRows = $rows->filter(fn (BotTransaction $t) => $t->recorded_at->format('Y-m-d') === $dayKey);
      $expense = (int) $dayRows->where('type', 'Pengeluaran')->sum('amount');
      $dominantMood = $dayRows->isNotEmpty()
        ? (string) $dayRows->groupBy('mood')->sortByDesc(fn (Collection $g) => $g->count())->keys()->first()
        : null;

      $points[] = [
        'date' => $dayKey,
        'label' => $cursor->format('d/m'),
        'mood_score' => $dominantMood ? ($moodScore[$dominantMood] ?? 3) : null,
        'expense' => $expense,
      ];
      $cursor->addDay();
    }

    return $points;
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
   * @return list<array{category: string, amount: int, count: int}>
   */
  private function topImpulsiveCategories(Collection $impulsiveRows): array
  {
    if ($impulsiveRows->isEmpty()) {
      return [];
    }

    return $impulsiveRows
      ->groupBy('category')
      ->map(function (Collection $items, string $category) {
        return [
          'category' => $category,
          'amount' => (int) $items->sum('amount'),
          'count' => $items->count(),
        ];
      })
      ->sortByDesc('amount')
      ->take(5)
      ->values()
      ->all();
  }

  /**
   * @return list<array{mood: string, count: int, amount: int, average: int, impulsive_rate: float}>
   */
  private function moodTableRows(Collection $expenses): array
  {
    return collect(self::MOOD_ORDER)
      ->map(function (string $mood) use ($expenses) {
        $items = $expenses->where('mood', $mood);
        $count = $items->count();
        if ($count === 0) {
          return null;
        }
        $amount = (int) $items->sum('amount');
        $impulsiveCount = $items->where('is_impulsive', true)->count();

        return [
          'mood' => $mood,
          'count' => $count,
          'amount' => $amount,
          'average' => (int) round($amount / $count),
          'impulsive_rate' => round(($impulsiveCount / $count) * 100, 1),
        ];
      })
      ->filter()
      ->values()
      ->all();
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
      $dayRows = $rows->filter(fn (BotTransaction $t) => (int) $t->recorded_at->format('d') === $day
        && $t->recorded_at->format('Y-m') === $month);
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
      fn (BotTransaction $t) => $t->nature === 'Wants' || $t->category === 'Jajan'
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

  /**
   * @return array<string, mixed>|null
   */
  private function ftsaProfile(?FinancialBaseline $baseline): ?array
  {
    if ($baseline === null) {
      return null;
    }

    if (! app(FtsaAnswerSummaryService::class)->hasCompletedFtsa($baseline)) {
      return null;
    }

    return [
      'archetype' => $baseline->dominant_archetype_label ?? $baseline->dominant_archetype,
      'domains' => [
        ['key' => 'chd', 'label' => 'CHD', 'score' => $baseline->ftsa_chd, 'level' => $baseline->chd_level],
        ['key' => 'rvd', 'label' => 'RVD', 'score' => $baseline->ftsa_rvd, 'level' => $baseline->rvd_level],
        ['key' => 'ssd', 'label' => 'SSD', 'score' => $baseline->ftsa_ssd, 'level' => $baseline->ssd_level],
        ['key' => 'esd', 'label' => 'ESD', 'score' => $baseline->ftsa_esd, 'level' => $baseline->esd_level],
      ],
    ];
  }

  /**
   * @param  array{positive: array, neutral: array, negative: array}  $moodGroups
   * @return list<string>
   */
  private function autoInsights(
    float $impulsiveRate,
    string $dominantMood,
    string $dominantPattern,
    array $moodGroups,
    ?array $highestLeakage,
    ?array $ftsaProfile,
  ): array {
    $insights = [];

    if ($impulsiveRate >= 30) {
      $insights[] = "Impulsive rate {$impulsiveRate}% — di atas ambang risiko (30%).";
    } elseif ($impulsiveRate <= 15) {
      $insights[] = 'Kebiasaan belanja relatif terkendali (impulsive rate ≤15%).';
    }

    if ($moodGroups['negative']['share'] >= 40) {
      $insights[] = "Mood negatif mendominasi ({$moodGroups['negative']['share']}% transaksi) — pantau korelasi dengan pengeluaran impulsif.";
    }

    if ($highestLeakage) {
      $insights[] = "Kebocoran terbesar: {$highestLeakage['category']} (nominal impulsif tertinggi).";
    }

    if ($ftsaProfile && str_contains(strtolower((string) $ftsaProfile['archetype']), 'impulsive')) {
      $insights[] = 'Profil FTSA dominan Impulsive — selaraskan dengan strategi jeda sebelum belanja.';
    } elseif (str_contains($dominantPattern, 'Impulsif')) {
      $insights[] = "Pola dominan: {$dominantPattern} saat mood {$dominantMood}.";
    }

    return array_slice($insights, 0, 3);
  }

  /**
   * @param  array{positive: array, neutral: array, negative: array}  $moodGroups
   * @return array{personalized: list<string>, general: list<string>}
   */
  private function recommendations(
    float $impulsiveRate,
    string $dominantMood,
    ?array $ftsaProfile,
    array $moodGroups,
  ): array {
    $personalized = [];
    $general = [
      'Gunakan preview konfirmasi di bot sebelum menyimpan transaksi.',
      'Catat mood setiap pengeluaran untuk melihat pola emosional.',
      'Tinjau dashboard behavioral setiap akhir minggu.',
    ];

    if ($impulsiveRate >= 25) {
      $personalized[] = 'Terapkan aturan jeda 10 menit untuk pembelian di luar daftar belanja.';
    }
    if (in_array($dominantMood, self::NEGATIVE_MOODS, true)) {
      $personalized[] = "Mood dominan {$dominantMood} — siapkan coping non-finansial (jalan kaki, napas, obrolan) sebelum belanja.";
    }
    if ($moodGroups['negative']['amount'] > $moodGroups['positive']['amount']) {
      $personalized[] = 'Nominal pengeluaran saat mood negatif lebih tinggi — batasi akses e-wallet di malam hari.';
    }
    if ($ftsaProfile) {
      $personalized[] = "Sesuaikan strategi dengan archetype {$ftsaProfile['archetype']} dari baseline FTSA-32.";
    }

    if (empty($personalized)) {
      $personalized[] = 'Pertahankan ritme pencatatan harian — konsistensi adalah kunci diagnosis akurat.';
    }

    return [
      'personalized' => array_slice($personalized, 0, 3),
      'general' => $general,
    ];
  }

  /**
   * @return array{summary: string, findings: list<string>, interpretation: string, priority: string}
   */
  private function doctorsNote(
    float $impulsiveRate,
    string $dominantMood,
    string $dominantPattern,
    ?array $highestLeakage,
    ?array $ftsaProfile,
  ): array {
    $findings = [];
    if ($ftsaProfile) {
      $findings[] = "Archetype FTSA: {$ftsaProfile['archetype']}.";
    }
    $findings[] = "Pola dominan: {$dominantPattern}, mood dominan: {$dominantMood}.";

    if ($impulsiveRate >= 40) {
      $leak = $highestLeakage['category'] ?? 'beberapa kategori';
      $summary = "Tingkat impulsifitas tinggi ({$impulsiveRate}%). Kebocoran terbesar di {$leak}.";
      $interpretation = 'Emosi dan impuls berkontribusi signifikan pada pola pengeluaran.';
      $priority = 'Coba jeda 10 menit sebelum transaksi non-kebutuhan; batasi notifikasi promo.';
    } elseif (in_array($dominantMood, self::NEGATIVE_MOODS, true) && str_contains($dominantPattern, 'Impulsif')) {
      $summary = "Pola dominan {$dominantPattern} saat mood {$dominantMood}.";
      $interpretation = 'Pengeluaran impulsif cenderung muncul saat regulasi emosi menurun.';
      $priority = 'Pertimbangkan coping non-finansial saat emosi negatif.';
    } elseif ($impulsiveRate <= 15) {
      $summary = 'Kebiasaan belanja cukup terkendali.';
      $interpretation = 'Kontrol impuls relatif baik — fokus pada optimasi alokasi bucket.';
      $priority = 'Pertahankan konfirmasi preview sebelum simpan di bot.';
    } else {
      $summary = "Pola dominan {$dominantPattern} dengan mood {$dominantMood}.";
      $interpretation = 'Ada ruang perbaikan pada keputusan belanja spontan.';
      $priority = 'Pantau kategori impulsif secara mingguan.';
    }

    return [
      'summary' => $summary,
      'findings' => $findings,
      'interpretation' => $interpretation,
      'priority' => $priority,
    ];
  }
}
