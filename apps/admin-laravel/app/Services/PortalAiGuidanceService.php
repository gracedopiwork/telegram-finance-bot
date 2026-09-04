<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Models\PortalGuidanceSnapshot;
use App\Support\PortalTimezone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PortalAiGuidanceService
{
    public function __construct(
        private readonly ClaudeJsonService $claude,
        private readonly FtsaAnswerSummaryService $ftsaSummary,
        private readonly PortalGuidanceSnapshotService $guidanceSnapshots,
    ) {}

    /**
     * @return array{
     *     insights: list<string>,
     *     recommendations: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    public function ftsaForBaseline(?FinancialBaseline $baseline): array
    {
        if ($baseline === null || ! $this->ftsaSummary->hasFtsaAnswers($baseline)) {
            return $this->emptyFtsaGuidance();
        }

        $cacheKey = sprintf(
            'portal_ai:ftsa:v2:%d:%s:%s',
            (int) $baseline->id,
            $baseline->assessed_at?->timestamp ?? '0',
            $this->claude->isConfigured() ? 'claude' : 'off',
        );

        $ttl = now()->addDays(max(1, (int) config('portal_ai.cache_ttl_days_ftsa', 30)));

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ($cached['source'] ?? '') === 'ai') {
            return $cached;
        }

        $result = $this->generateFtsaGuidance($baseline);

        if (($result['source'] ?? '') === 'ai') {
            Cache::put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    /**
     * @return array{
     *     insights: list<string>,
     *     recommendations: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    private function generateFtsaGuidance(FinancialBaseline $baseline): array
    {
        $fallback = $this->ftsaFallback($baseline);

        if (! $this->claude->isConfigured()) {
            return $fallback;
        }

        try {
            $summary = $this->ftsaSummary->scoreSummary($baseline);
            $parsed = $this->claude->generate($this->ftsaPrompt($baseline, $summary));
            if ($parsed === null) {
                Log::warning('Portal AI FTSa guidance fell back to rules', [
                    'baseline_id' => $baseline->id,
                    'reason' => 'claude_parse_failed',
                ]);

                return array_merge($fallback, ['claude_configured' => true]);
            }

            $insights = $this->claude->normalizeLines($parsed['insights'] ?? [], (int) config('portal_ai.max_insights', 3));
            $recommendations = $this->claude->normalizeLines($parsed['recommendations'] ?? [], (int) config('portal_ai.max_recommendations', 3));

            if ($insights === [] && $recommendations === []) {
                Log::warning('Portal AI FTSa guidance fell back to rules', [
                    'baseline_id' => $baseline->id,
                    'reason' => 'empty_ai_lines',
                ]);

                return array_merge($fallback, ['claude_configured' => true]);
            }

            return [
                'insights' => $insights !== [] ? $insights : $fallback['insights'],
                'recommendations' => $recommendations !== [] ? $recommendations : $fallback['recommendations'],
                'source' => 'ai',
                'generated_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Portal AI FTSa guidance failed', [
                'baseline_id' => $baseline->id,
                'message' => $e->getMessage(),
            ]);

            return array_merge($fallback, ['claude_configured' => true]);
        }
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string}
     * }  $fallback
     * @return array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string},
     *     ai_source: string,
     *     generated_at: ?string
     * }
     */
  public function behavioral(int $telegramUserId, string $month, int $periodMonths, array $metrics, ?FinancialBaseline $baseline, array $fallback): array
    {
        unset($periodMonths);

        if ((int) ($metrics['expense_count'] ?? 0) === 0) {
            return array_merge($fallback, [
                'ai_source' => 'none',
                'generated_at' => null,
                'monthly_stored' => false,
                'monthly_pending' => false,
            ]);
        }

        $stored = $this->guidanceSnapshots->get(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_BEHAVIORAL_MONTHLY,
            $month,
        );

        if ($stored !== null) {
            $payload = is_array($stored['payload']) ? $stored['payload'] : [];

            return [
                'insights' => $payload['insights'] ?? $fallback['insights'],
                'recommendations' => $payload['recommendations'] ?? $fallback['recommendations'],
                'doctors_note' => $payload['doctors_note'] ?? $fallback['doctors_note'],
                'ai_source' => ($stored['ai_source'] ?? '') === 'ai' ? 'ai' : 'rules',
                'generated_at' => $stored['generated_at'] ?? null,
                'monthly_stored' => true,
                'monthly_pending' => false,
            ];
        }

        return array_merge($this->genericBehavioralGuidance($month, $fallback), [
            'ai_source' => 'rules',
            'generated_at' => null,
            'monthly_stored' => false,
            'monthly_pending' => true,
        ]);
    }

    /**
     * Generate & simpan behavioral guidance bulanan (recommendation, insight, doctor's note transaksi).
     *
     * @param  array<string, mixed>  $metrics
     * @param  array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string}
     * }  $fallback
     */
    public function generateAndStoreMonthlyBehavioralGuidance(
        int $telegramUserId,
        string $monthKey,
        array $metrics,
        ?FinancialBaseline $baseline,
        array $fallback,
    ): bool {
        if ((int) ($metrics['expense_count'] ?? 0) === 0) {
            return false;
        }

        $guidance = $fallback;
        $provider = 'rules';

        if ($this->claude->isConfigured()) {
            try {
                $parsed = $this->claude->generate($this->behavioralPrompt($metrics, $baseline));
                if (is_array($parsed)) {
                    $guidance = $this->normalizeBehavioralResponse($parsed, $fallback);
                    $provider = 'claude';
                }
            } catch (\Throwable $e) {
                Log::warning('Portal monthly behavioral guidance failed', [
                    'telegram_user_id' => $telegramUserId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->guidanceSnapshots->store(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_BEHAVIORAL_MONTHLY,
            $monthKey,
            [
                'insights' => $guidance['insights'],
                'recommendations' => $guidance['recommendations'],
                'doctors_note' => $guidance['doctors_note'],
            ],
            $provider,
        );

        return true;
    }

    /**
     * @param  array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string}
     * }  $fallback
     * @return array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string}
     * }
     */
    private function genericBehavioralGuidance(string $monthKey, array $fallback): array
    {
        try {
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth()->endOfMonth();
            $release = $monthEnd->format('d/m/Y');
        } catch (\Throwable) {
            $release = 'akhir bulan';
        }

        return [
            'insights' => [],
            'recommendations' => [
                'personalized' => [],
                'general' => $fallback['recommendations']['general'],
            ],
            'doctors_note' => array_merge($fallback['doctors_note'], [
                'summary' => "Behavioral recommendation bulan ini akan dirilis pada tgl {$release} pukul 22.00.",
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array{
     *     clinical_summary: array{headline: string, findings: list<string>, status: string},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}
     * }  $fallback
     * @return array{
     *     clinical_summary: array{headline: string, findings: list<string>, status: string},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string},
     *     ai_source: string,
     *     generated_at: ?string
     * }
     */
    public function financial(int $telegramUserId, string $month, int $periodMonths, array $metrics, ?FinancialBaseline $baseline, array $fallback): array
    {
        if ((int) ($metrics['transaction_count'] ?? 0) === 0) {
            return array_merge($fallback, [
                'ai_source' => 'none',
                'generated_at' => null,
                'clinical_pending' => false,
                'doctors_pending' => false,
                'clinical_generated_at' => null,
                'doctors_generated_at' => null,
            ]);
        }

        $fingerprint = $this->metricsFingerprint($metrics);
        $weekAnchor = $this->clinicalWeekAnchor($month);
        $weekKey = PortalGuidanceSnapshot::monthCumulativeWeekPeriodKey($weekAnchor);
        $monthKey = $month;
        $isPastMonth = $this->isPastMonth($monthKey);

        $weeklyStored = $this->guidanceSnapshots->get(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_CLINICAL_SUMMARY_WEEKLY,
            $weekKey,
        );
        if ($weeklyStored !== null && ! $this->snapshotMatchesMetrics($weeklyStored, $fingerprint)) {
            // Snapshot lama/salah bulan — regenerasi dari metrik periode yang sedang dilihat.
            $weeklyStored = null;
        }

        if ($weeklyStored === null) {
            $weeklyStored = $this->ensureWeeklyClinicalSummary(
                $telegramUserId,
                $weekKey,
                $baseline,
            );
        }

        $clinical = $weeklyStored !== null
            ? ($weeklyStored['payload']['clinical_summary'] ?? $fallback['clinical_summary'])
            : $fallback['clinical_summary'];

        // Angka di clinical harus selaras dengan kartu KPI bulan yang dipilih.
        if (! $this->snapshotMatchesMetrics($weeklyStored, $fingerprint)) {
            $clinical = $fallback['clinical_summary'];
        }

        $monthlyStored = $this->guidanceSnapshots->get(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_DOCTORS_NOTE_MONTHLY,
            $monthKey,
        );
        if ($monthlyStored !== null && ! $this->snapshotMatchesMetrics($monthlyStored, $fingerprint)) {
            $monthlyStored = null;
        }

        $doctorsReleased = $this->monthlyDoctorsNoteReleased($month, $monthlyStored['generated_at'] ?? null);

        // Bulan lampau: jangan biarkan "pending akhir bulan" — buat/muat note dari data bulan itu.
        if ($monthlyStored === null && ($isPastMonth || $doctorsReleased)) {
            $this->generateAndStoreMonthlyDoctorsNote(
                $telegramUserId,
                $monthKey,
                $metrics,
                $baseline,
                $fallback['doctors_note'],
            );
            $monthlyStored = $this->guidanceSnapshots->get(
                $telegramUserId,
                PortalGuidanceSnapshot::TYPE_DOCTORS_NOTE_MONTHLY,
                $monthKey,
            );
            $doctorsReleased = $monthlyStored !== null;
        }

        $doctors = ($monthlyStored !== null && ($doctorsReleased || $isPastMonth))
            ? ($monthlyStored['payload']['doctors_note'] ?? $fallback['doctors_note'])
            : $this->pendingDoctorsNote($month, $fallback['doctors_note']);

        if (($doctorsReleased || $isPastMonth) && ! $this->snapshotMatchesMetrics($monthlyStored, $fingerprint)) {
            $doctors = $fallback['doctors_note'];
        }

        $buckets = (array) ($metrics['buckets'] ?? []);
        $clinical = $this->reconcileClinicalSummaryBucketPercents($clinical, $buckets);
        $doctors = $this->reconcileDoctorsNoteBucketPercents($doctors, $buckets, $fallback['doctors_note']);

        $clinicalPending = $weeklyStored === null;
        $doctorsPending = ! $isPastMonth && ($monthlyStored === null || ! $doctorsReleased);

        $aiSource = 'rules';
        $weeklyAi = $weeklyStored !== null && ($weeklyStored['ai_source'] ?? '') === 'ai';
        $monthlyAi = ($doctorsReleased || $isPastMonth) && $monthlyStored !== null && ($monthlyStored['ai_source'] ?? '') === 'ai';
        if ($weeklyAi && $monthlyAi) {
            $aiSource = 'ai';
        } elseif ($weeklyAi || $monthlyAi) {
            $aiSource = 'partial';
        }

        return [
            'clinical_summary' => $clinical,
            'doctors_note' => $doctors,
            'ai_source' => $aiSource,
            'generated_at' => $monthlyStored['generated_at'] ?? $weeklyStored['generated_at'] ?? null,
            'clinical_pending' => $clinicalPending,
            'doctors_pending' => $doctorsPending,
            'clinical_generated_at' => $weeklyStored['generated_at'] ?? null,
            'doctors_generated_at' => (($doctorsReleased || $isPastMonth) && $monthlyStored !== null)
                ? ($monthlyStored['generated_at'] ?? null)
                : null,
        ];
    }

    /**
     * Generate & simpan clinical summary mingguan (dipanggil scheduler).
     *
     * @param  array<string, mixed>  $metrics
     * @param  array{headline: string, findings: list<string>, status: string}  $fallbackClinical
     */
    public function generateAndStoreWeeklyClinicalSummary(
        int $telegramUserId,
        string $weekKey,
        array $metrics,
        ?FinancialBaseline $baseline,
        array $fallbackClinical,
    ): bool {
        if ((int) ($metrics['transaction_count'] ?? 0) === 0) {
            return false;
        }

        $clinical = $fallbackClinical;
        $provider = 'rules';

        if ($this->claude->isConfigured()) {
            try {
                $parsed = $this->claude->generate($this->weeklyClinicalPrompt($metrics, $baseline));
                if (is_array($parsed)) {
                    $normalized = $this->normalizeFinancialResponse(
                        ['clinical_summary' => $parsed['clinical_summary'] ?? $parsed],
                        ['clinical_summary' => $fallbackClinical, 'doctors_note' => ['summary' => '', 'findings' => [], 'interpretation' => '', 'priority' => '', 'education' => '']],
                        $metrics,
                    );
                    $clinical = $normalized['clinical_summary'];
                    $provider = 'claude';
                }
            } catch (\Throwable $e) {
                Log::warning('Portal weekly clinical summary failed', [
                    'telegram_user_id' => $telegramUserId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->guidanceSnapshots->store(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_CLINICAL_SUMMARY_WEEKLY,
            $weekKey,
            [
                'clinical_summary' => $clinical,
                'metrics_fingerprint' => $this->metricsFingerprint($metrics),
            ],
            $provider,
        );

        return true;
    }

    /**
     * Generate & simpan doctor's note bulanan (dipanggil scheduler akhir bulan).
     *
     * @param  array<string, mixed>  $metrics
     * @param  array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}  $fallbackDoctorsNote
     */
    public function generateAndStoreMonthlyDoctorsNote(
        int $telegramUserId,
        string $monthKey,
        array $metrics,
        ?FinancialBaseline $baseline,
        array $fallbackDoctorsNote,
    ): bool {
        if ((int) ($metrics['transaction_count'] ?? 0) === 0) {
            return false;
        }

        $note = $fallbackDoctorsNote;
        $provider = 'rules';

        if ($this->claude->isConfigured()) {
            try {
                $parsed = $this->claude->generate($this->monthlyDoctorsNotePrompt($metrics, $baseline));
                if (is_array($parsed)) {
                    $normalized = $this->normalizeFinancialResponse(
                        ['doctors_note' => $parsed['doctors_note'] ?? $parsed],
                        ['clinical_summary' => ['headline' => '', 'findings' => [], 'status' => 'fair'], 'doctors_note' => $fallbackDoctorsNote],
                        $metrics,
                    );
                    $note = $normalized['doctors_note'];
                    $provider = 'claude';
                }
            } catch (\Throwable $e) {
                Log::warning('Portal monthly doctors note failed', [
                    'telegram_user_id' => $telegramUserId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->guidanceSnapshots->store(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_DOCTORS_NOTE_MONTHLY,
            $monthKey,
            [
                'doctors_note' => $note,
                'metrics_fingerprint' => $this->metricsFingerprint($metrics),
            ],
            $provider,
        );

        return true;
    }

    /**
     * Generate insight mingguan penuh saat pertama kali dibuka (AI jika tersedia, else rules).
     *
     * @return array{payload: array<string, mixed>, ai_source: string, generated_at: string}|null
     */
    private function ensureWeeklyClinicalSummary(
        int $telegramUserId,
        string $weekKey,
        ?FinancialBaseline $baseline,
    ): ?array {
        $anchor = $this->anchorFromWeekKey($weekKey);
        $week = $this->guidanceSnapshots->monthCumulativeWeekRange($anchor);
        $periodLabel = $week['label'];

        $context = app(TransactionDashboardService::class)->financialGuidanceContext(
            $telegramUserId,
            $week['start'],
            $week['end'],
            $periodLabel,
            1,
        );

        if ($context['transaction_count'] === 0) {
            return null;
        }

        $this->generateAndStoreWeeklyClinicalSummary(
            $telegramUserId,
            $weekKey,
            $context['metrics'],
            $baseline,
            $context['fallback_clinical'],
        );

        return $this->guidanceSnapshots->get(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_CLINICAL_SUMMARY_WEEKLY,
            $weekKey,
        );
    }

    /**
     * @param  array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}  $fallback
     * @return array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}
     */
    private function pendingDoctorsNote(string $monthKey, array $fallback): array
    {
        try {
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth()->endOfMonth();
            $release = $monthEnd->translatedFormat('d F Y');
        } catch (\Throwable) {
            $release = 'akhir bulan';
        }

        return array_merge($fallback, [
            'summary' => "Rekomendasi dokter untuk periode ini akan dirilis pada {$release} pukul 22.00 WIB.",
            'interpretation' => 'Doctor\'s Note bulanan dibuat otomatis di akhir bulan, memakai Budget Prescription yang sama dengan clinical summary. Clinical summary mingguan tetap update lebih dulu.',
            'priority' => 'Lanjutkan pencatatan transaksi hingga akhir bulan. Angka Aktual vs Ideal di Budget Prescription sudah memakai data terbaru.',
            'findings' => [],
        ]);
    }

    private function monthlyDoctorsNoteReleased(string $monthKey, ?string $generatedAt): bool
    {
        $tz = (string) config('portal_ai.guidance_timezone', 'Asia/Jakarta');
        $releaseTime = (string) config('portal_ai.guidance_monthly_time', '22:00');
        try {
            $month = \Carbon\Carbon::createFromFormat('Y-m', $monthKey, $tz)->startOfMonth();
        } catch (\Throwable) {
            return $generatedAt !== null && $generatedAt !== '';
        }

        $releaseAt = $month->copy()->endOfMonth();
        try {
            $releaseAt->setTimeFromTimeString($releaseTime);
        } catch (\Throwable) {
            $releaseAt->setTime(22, 0);
        }

        $now = now($tz);
        if ($now->lt($releaseAt) && $month->isSameMonth($now)) {
            return false;
        }

        return $generatedAt !== null && $generatedAt !== '';
    }

    /**
     * @param  array{domains: list<array{key: string, code: string, label: string, score: int, level: ?string}>, archetype_label: ?string}  $summary
     */
    private function ftsaPrompt(FinancialBaseline $baseline, array $summary): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'ftsa_rules']);
        $domainLines = [];
        foreach ($summary['domains'] as $domain) {
            $level = $domain['level'] ?? '—';
            $domainLines[] = sprintf(
                '- %s (%s): %d/40 — level %s',
                $domain['code'],
                $domain['label'],
                $domain['score'],
                $level
            );
        }

        $maxInsights = (int) config('portal_ai.max_insights', 3);
        $maxRecs = (int) config('portal_ai.max_recommendations', 3);
        $baselineContext = $this->ftsaBaselineContext($baseline);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan insight dan rekomendasi behavioral finansial berdasarkan hasil FTSA-32 dan baseline keuangan berikut.

DATA HASIL:
- Dominant archetype: {$summary['archetype_label']}
- Domain scores:
{$this->linesBlock($domainLines)}
{$baselineContext}

ATURAN WAJIB:
{$rules}

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "insights": ["..."],
  "recommendations": ["..."]
}

Batasi insights maksimal {$maxInsights} poin, rekomendasi maksimal {$maxRecs} poin. Tiap poin 1–2 kalimat.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function behavioralPrompt(array $metrics, ?FinancialBaseline $baseline): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'behavioral_rules']);
        $ftsa = $metrics['ftsa_profile'] ?? null;
        $leakage = $metrics['highest_leakage'] ?? null;
        $moodGroups = $metrics['mood_groups'] ?? [];

        $ftsaLine = is_array($ftsa)
            ? 'Archetype FTSA: '.($ftsa['archetype'] ?? '—')
            : 'Archetype FTSA: belum tersedia';

        $leakageLine = is_array($leakage)
            ? 'Kebocoran impulsif terbesar: '.($leakage['category'] ?? '—').' (Rp '.number_format((int) ($leakage['amount'] ?? 0), 0, ',', '.').')'
            : 'Kebocoran impulsif: tidak terdeteksi';

        $maxInsights = (int) config('portal_ai.max_insights', 3);
        $maxPersonal = (int) config('portal_ai.max_recommendations', 3);
        $maxGeneral = (int) config('portal_ai.max_general_recommendations', 3);

        $stage = $baseline?->stage_label ? "Tahap finansial baseline: {$baseline->stage_label}" : 'Tahap finansial baseline: belum diisi';

        $moodTableLines = [];
        foreach ((array) ($metrics['mood_table'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $moodTableLines[] = sprintf(
                '- %s: %d transaksi, %s%% impulsif',
                $row['mood'] ?? '—',
                (int) ($row['count'] ?? 0),
                $row['impulsive_rate'] ?? '0',
            );
        }
        $moodTableBlock = $moodTableLines !== []
            ? "TABEL MOOD:\n".implode("\n", $moodTableLines)
            : 'TABEL MOOD: belum tersedia';

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Analisis behavioral finansial user dari data transaksi bot Telegram berikut.

PERIODE: {$metrics['period_label']}
{$stage}
{$ftsaLine}

METRIK:
- Skor impulsivitas: {$metrics['score']}/100 ({$metrics['grade']})
- Impulsive rate: {$metrics['impulsive_rate']}%
- Share nominal impulsif: {$metrics['impulsive_amount_share']}%
- Mood dominan: {$metrics['dominant_mood']}
- Pola dominan: {$metrics['dominant_pattern']}
- Emotional balance: {$metrics['emotional_balance']['score']}/100
- Mood positif: {$moodGroups['positive']['share']}% | netral: {$moodGroups['neutral']['share']}% | negatif: {$moodGroups['negative']['share']}%
- {$leakageLine}

{$moodTableBlock}

ATURAN WAJIB:
{$rules}

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "insights": ["ringkasan deskriptif + insight korelasi FTSA"],
  "recommendations_personalized": ["rekomendasi tindakan bulanan"],
  "recommendations_general": ["..."],
  "doctors_note": {
    "summary": "...",
    "findings": ["..."],
    "interpretation": "...",
    "priority": "..."
  }
}

Batasi insights {$maxInsights}, rekomendasi personal {$maxPersonal}, rekomendasi umum {$maxGeneral}. Tiap poin 1–2 kalimat.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function financialPrompt(array $metrics, ?FinancialBaseline $baseline): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'financial_rules']);
        $bucketLines = $this->formatBucketPrescriptionLines((array) ($metrics['buckets'] ?? []));
        $socialLines = $this->formatCashLiquidityLines($metrics);

        $stage = $baseline?->stage_label ? "Tahap finansial: {$baseline->stage_label}" : 'Tahap finansial: belum diisi';
        $archetype = $baseline?->dominant_archetype_label ? "Archetype: {$baseline->dominant_archetype_label}" : '';

        $maxFindings = (int) config('portal_ai.max_findings', 5);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan ringkasan klinis dan doctor's note keuangan berdasarkan dashboard berikut.

PERIODE: {$metrics['period_label']}
{$stage}
{$archetype}

METRIK:
- Pendapatan: Rp {$this->formatIdr((int) $metrics['income'])}
- Pengeluaran: Rp {$this->formatIdr((int) $metrics['expense'])}
- Cashflow (prescription, tanpa likuiditas sosial): Rp {$this->formatIdr((int) $metrics['cashflow'])}
- Saving / Future Building tercatat: Rp {$this->formatIdr((int) ($metrics['saving_investment'] ?? 0))}
- Saving rate: {$metrics['saving_rate']}%
- Jumlah transaksi: {$metrics['transaction_count']}

LIKUIDITAS SOSIAL / KAS:
{$this->linesBlock($socialLines)}

BUCKET PRESCRIPTION:
{$this->linesBlock($bucketLines)}

ATURAN WAJIB:
{$rules}

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "clinical_summary": {
    "headline": "...",
    "findings": ["..."],
    "status": "healthy|fair|attention|critical"
  },
  "doctors_note": {
    "summary": "...",
    "findings": ["..."],
    "interpretation": "...",
    "priority": "...",
    "education": "..."
  }
}

Maksimal {$maxFindings} findings. Status harus salah satu: healthy, fair, attention, critical.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function weeklyClinicalPrompt(array $metrics, ?FinancialBaseline $baseline): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'financial_rules']);
        $bucketLines = $this->formatBucketPrescriptionLines((array) ($metrics['buckets'] ?? []));
        $socialLines = $this->formatCashLiquidityLines($metrics);

        $stage = $baseline?->stage_label ? "Tahap finansial: {$baseline->stage_label}" : 'Tahap finansial: belum diisi';
        $maxFindings = (int) config('portal_ai.max_findings', 5);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan clinical summary keuangan berdasarkan data kumulatif bulan ini.

PERIODE KUMULATIF: {$metrics['period_label']}
(Data dijumlahkan dari awal bulan hingga akhir minggu berjalan — bukan hanya 7 hari terakhir.)
{$stage}

METRIK:
- Pendapatan: Rp {$this->formatIdr((int) $metrics['income'])}
- Pengeluaran: Rp {$this->formatIdr((int) $metrics['expense'])}
- Cashflow (prescription): Rp {$this->formatIdr((int) $metrics['cashflow'])}
- Saving / Future Building tercatat: Rp {$this->formatIdr((int) ($metrics['saving_investment'] ?? 0))}
- Saving rate: {$metrics['saving_rate']}%
- Jumlah transaksi: {$metrics['transaction_count']}

LIKUIDITAS SOSIAL / KAS:
{$this->linesBlock($socialLines)}

BUCKET PRESCRIPTION:
{$this->linesBlock($bucketLines)}

ATURAN WAJIB:
{$rules}

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "clinical_summary": {
    "headline": "...",
    "findings": ["..."],
    "status": "healthy|fair|attention|critical"
  }
}

Maksimal {$maxFindings} findings. Fokus kondisi kumulatif bulan ini (deskriptif), bukan archetype FTSA.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function monthlyDoctorsNotePrompt(array $metrics, ?FinancialBaseline $baseline): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'financial_rules']);
        $bucketLines = $this->formatBucketPrescriptionLines((array) ($metrics['buckets'] ?? []));
        $socialLines = $this->formatCashLiquidityLines($metrics);

        $stage = $baseline?->stage_label ? "Tahap finansial: {$baseline->stage_label}" : 'Tahap finansial: belum diisi';
        $maxFindings = (int) config('portal_ai.max_findings', 5);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan doctor's note bulanan keuangan berdasarkan data berikut.

PERIODE BULANAN: {$metrics['period_label']}
{$stage}

METRIK:
- Pendapatan: Rp {$this->formatIdr((int) $metrics['income'])}
- Pengeluaran: Rp {$this->formatIdr((int) $metrics['expense'])}
- Cashflow (prescription): Rp {$this->formatIdr((int) $metrics['cashflow'])}
- Saving / Future Building tercatat: Rp {$this->formatIdr((int) ($metrics['saving_investment'] ?? 0))}
- Saving rate: {$metrics['saving_rate']}%
- Jumlah transaksi: {$metrics['transaction_count']}

LIKUIDITAS SOSIAL / KAS:
{$this->linesBlock($socialLines)}

BUCKET PRESCRIPTION:
{$this->linesBlock($bucketLines)}

ATURAN WAJIB:
{$rules}
JANGAN menyebut archetype FTSA — itu ada di dashboard behavioral.
Setiap findings WAJIB menyentuh fakta kritis bila ada: (1) saving Rp0 / rate 0%, (2) nominal+persen Flexible + Social vs batas, (3) verdict sehat/tidak sehat, (4) Essential Living over-max yang menyebabkan cashflow minus.
Tiap rekomendasi harus konkret, bisa dilakukan, dan spesifik (contoh: alokasikan cashflow positif ke Future Building, mulai saving otomatis 10%, batasi Flexible+Social ≤10%).
JANGAN sarankan menaikkan Essential Living jika aktual sudah di bawah 50% — itu justru sehat.
Jika ada defisit yang dibiayai Utang Masuk, prioritaskan rekomendasi pelunasan utang sosial tanpa mengoreksi Income.
KRITIS: Jangan menukar angka Protection dengan Flexible + Social. Salin persentase aktual PERSIS dari BUCKET PRESCRIPTION di atas.

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "doctors_note": {
    "summary": "Rekomendasi untuk periode ini",
    "findings": ["rekomendasi 1", "rekomendasi 2"],
    "interpretation": "",
    "priority": "rekomendasi prioritas utama (1 kalimat)",
    "education": ""
  }
}

Maksimal {$maxFindings} rekomendasi di findings. summary singkat saja; interpretation & education boleh kosong.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string}
     * }  $fallback
     * @return array{
     *     insights: list<string>,
     *     recommendations: array{personalized: list<string>, general: list<string>},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string}
     * }
     */
    private function normalizeBehavioralResponse(array $parsed, array $fallback): array
    {
        $insights = $this->claude->normalizeLines($parsed['insights'] ?? [], (int) config('portal_ai.max_insights', 3));
        $personal = $this->claude->normalizeLines($parsed['recommendations_personalized'] ?? [], (int) config('portal_ai.max_recommendations', 3));
        $general = $this->claude->normalizeLines($parsed['recommendations_general'] ?? [], (int) config('portal_ai.max_general_recommendations', 3));
        $note = $this->normalizeDoctorsNote($parsed['doctors_note'] ?? null, $fallback['doctors_note'], false);

        return [
            'insights' => $this->normalizeMoneyAbbrevList($insights !== [] ? $insights : $fallback['insights']),
            'recommendations' => [
                'personalized' => $this->normalizeMoneyAbbrevList($personal !== [] ? $personal : $fallback['recommendations']['personalized']),
                'general' => $this->normalizeMoneyAbbrevList($general !== [] ? $general : $fallback['recommendations']['general']),
            ],
            'doctors_note' => $note,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array{
     *     clinical_summary: array{headline: string, findings: list<string>, status: string},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}
     * }  $fallback
     * @return array{
     *     clinical_summary: array{headline: string, findings: list<string>, status: string},
     *     doctors_note: array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}
     * }
     */
    /**
     * @param  array<string, mixed>  $metrics
     */
    private function normalizeFinancialResponse(array $parsed, array $fallback, array $metrics = []): array
    {
        $clinical = is_array($parsed['clinical_summary'] ?? null) ? $parsed['clinical_summary'] : [];
        $headline = trim((string) ($clinical['headline'] ?? ''));
        $findings = $this->claude->normalizeLines($clinical['findings'] ?? [], (int) config('portal_ai.max_findings', 5));
        $status = trim((string) ($clinical['status'] ?? ''));
        $allowedStatuses = ['healthy', 'fair', 'attention', 'critical', 'no_data'];
        $buckets = (array) ($metrics['buckets'] ?? []);

        $clinicalSummary = [
            'headline' => $this->normalizeMoneyAbbrev($headline !== '' ? $headline : $fallback['clinical_summary']['headline']),
            'findings' => $this->normalizeMoneyAbbrevList($findings !== [] ? $findings : $fallback['clinical_summary']['findings']),
            'status' => in_array($status, $allowedStatuses, true) ? $status : $fallback['clinical_summary']['status'],
        ];
        $clinicalSummary = $this->reconcileClinicalSummaryBucketPercents($clinicalSummary, $buckets);

        $note = $this->normalizeDoctorsNote($parsed['doctors_note'] ?? null, $fallback['doctors_note'], true);
        $note = $this->reconcileDoctorsNoteBucketPercents($note, $buckets, $fallback['doctors_note']);

        return [
            'clinical_summary' => $clinicalSummary,
            'doctors_note' => $note,
        ];
    }

    /**
     * @param  array{summary: string, findings: list<string>, interpretation: string, priority: string, education?: string}  $fallback
     * @return array{summary: string, findings: list<string>, interpretation: string, priority: string, education?: string}
     */
    private function normalizeDoctorsNote(mixed $raw, array $fallback, bool $withEducation): array
    {
        if (! is_array($raw)) {
            return $fallback;
        }

        $summary = trim((string) ($raw['summary'] ?? ''));
        $findings = $this->claude->normalizeLines($raw['findings'] ?? [], (int) config('portal_ai.max_findings', 5));
        $interpretation = trim((string) ($raw['interpretation'] ?? ''));
        $priority = trim((string) ($raw['priority'] ?? ''));

        $note = [
            'summary' => $this->normalizeMoneyAbbrev($summary !== '' ? $summary : $fallback['summary']),
            'findings' => $this->normalizeMoneyAbbrevList($findings !== [] ? $findings : $fallback['findings']),
            'interpretation' => $this->normalizeMoneyAbbrev($interpretation !== '' ? $interpretation : $fallback['interpretation']),
            'priority' => $this->normalizeMoneyAbbrev($priority !== '' ? $priority : $fallback['priority']),
        ];

        if ($withEducation) {
            $education = trim((string) ($raw['education'] ?? ''));
            $note['education'] = $this->normalizeMoneyAbbrev($education !== '' ? $education : ($fallback['education'] ?? ''));
        }

        return $note;
    }

    /**
     * Ganti singkatan nominal "M"/"m" (million) menjadi "jt".
     * Contoh: "2M", "1.5 m", "0,8M" -> "2 jt", "1.5 jt", "0,8 jt".
     */
    private function normalizeMoneyAbbrev(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $trimmed;
        }

        return (string) preg_replace('/(?<=\d)\s*[mM]\b/u', ' jt', $trimmed);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function normalizeMoneyAbbrevList(array $lines): array
    {
        return array_values(array_map(
            fn (string $line) => $this->normalizeMoneyAbbrev($line),
            $lines
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $buckets
     * @return array<string, float>
     */
    private function bucketShareMap(array $buckets): array
    {
        $map = [];
        foreach ($buckets as $bucket) {
            if (! is_array($bucket)) {
                continue;
            }
            $name = trim((string) ($bucket['bucket'] ?? ''));
            if ($name === '') {
                continue;
            }
            $map[$name] = (float) ($bucket['share'] ?? 0);
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function percentFormats(float $share): array
    {
        $variants = [];
        foreach ([1, 0] as $decimals) {
            $dot = number_format($share, $decimals, '.', '');
            $comma = number_format($share, $decimals, ',', '');
            $variants[] = $dot;
            $variants[] = $comma;
            if ($decimals === 1 && str_ends_with($dot, '.0')) {
                $variants[] = number_format($share, 0, '.', '');
                $variants[] = number_format($share, 0, ',', '');
            }
        }

        return array_values(array_unique(array_filter($variants, static fn (string $v) => $v !== '')));
    }

    /**
     * @param  list<string>  $formats
     */
    private function textHasAnyPercent(string $text, array $formats): bool
    {
        foreach ($formats as $format) {
            if ($format === '') {
                continue;
            }
            if (preg_match('/(?<!\d)'.preg_quote($format, '/').'\s*%/u', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $fromFormats
     */
    private function replacePercentFormats(string $text, array $fromFormats, string $toFormat): string
    {
        $result = $text;
        usort($fromFormats, static fn (string $a, string $b) => strlen($b) <=> strlen($a));
        foreach ($fromFormats as $format) {
            if ($format === '') {
                continue;
            }
            $result = (string) preg_replace(
                '/(?<!\d)'.preg_quote($format, '/').'(\s*%)/u',
                $toFormat.'$1',
                $result
            );
        }

        return $result;
    }

    private function fixSwappedBucketPercentsInText(string $text, float $protectionShare, float $flexibleShare): string
    {
        $trimmed = trim($text);
        if ($trimmed === '' || abs($protectionShare - $flexibleShare) < 0.5) {
            return $text;
        }

        $protFmts = $this->percentFormats($protectionShare);
        $flexFmts = $this->percentFormats($flexibleShare);
        $hasProt = preg_match('/\bproteksi\b|\bprotection\b/iu', $text) === 1;
        $hasFlex = preg_match('/flexible\s*\+\s*social|\bflexible\b|\bfleksibel\b/iu', $text) === 1;

        if ($hasProt && ! $hasFlex
            && $this->textHasAnyPercent($text, $flexFmts)
            && ! $this->textHasAnyPercent($text, $protFmts)
        ) {
            return $this->replacePercentFormats($text, $flexFmts, $protFmts[0]);
        }

        if ($hasFlex && ! $hasProt
            && $this->textHasAnyPercent($text, $protFmts)
            && ! $this->textHasAnyPercent($text, $flexFmts)
        ) {
            return $this->replacePercentFormats($text, $protFmts, $flexFmts[0]);
        }

        return $text;
    }

    /**
     * @param  array{headline?: string, findings?: list<string>, status?: string}  $clinical
     * @param  list<array<string, mixed>>  $buckets
     * @return array{headline?: string, findings?: list<string>, status?: string}
     */
    private function reconcileClinicalSummaryBucketPercents(array $clinical, array $buckets): array
    {
        $map = $this->bucketShareMap($buckets);
        if (! isset($map['Protection'], $map['Flexible + Social'])) {
            return $clinical;
        }

        $protection = $map['Protection'];
        $flexible = $map['Flexible + Social'];

        if (isset($clinical['headline']) && is_string($clinical['headline'])) {
            $clinical['headline'] = $this->fixSwappedBucketPercentsInText($clinical['headline'], $protection, $flexible);
        }

        if (isset($clinical['findings']) && is_array($clinical['findings'])) {
            $clinical['findings'] = array_values(array_map(
                function ($line) use ($protection, $flexible) {
                    return is_string($line)
                        ? $this->fixSwappedBucketPercentsInText($line, $protection, $flexible)
                        : $line;
                },
                $clinical['findings']
            ));
        }

        return $clinical;
    }

    /**
     * @param  array{summary?: string, findings?: list<string>, interpretation?: string, priority?: string, education?: string}  $note
     * @param  list<array<string, mixed>>  $buckets
     * @param  array{summary?: string, findings?: list<string>, interpretation?: string, priority?: string, education?: string}  $fallback
     * @return array{summary?: string, findings?: list<string>, interpretation?: string, priority?: string, education?: string}
     */
    private function reconcileDoctorsNoteBucketPercents(array $note, array $buckets, array $fallback = []): array
    {
        $map = $this->bucketShareMap($buckets);
        if (! isset($map['Protection'], $map['Flexible + Social'])) {
            return $note;
        }

        $protection = $map['Protection'];
        $flexible = $map['Flexible + Social'];

        foreach (['summary', 'interpretation', 'priority', 'education'] as $key) {
            if (isset($note[$key]) && is_string($note[$key])) {
                $note[$key] = $this->fixSwappedBucketPercentsInText($note[$key], $protection, $flexible);
            }
        }

        if (isset($note['findings']) && is_array($note['findings'])) {
            $note['findings'] = array_values(array_map(
                function ($line) use ($protection, $flexible) {
                    return is_string($line)
                        ? $this->fixSwappedBucketPercentsInText($line, $protection, $flexible)
                        : $line;
                },
                $note['findings']
            ));
        }

        // Jika AI masih menempelkan % Flexible ke Protection (atau sebaliknya), pakai rules-based findings.
        if ($this->doctorsNoteStillHasBucketSwap($note, $protection, $flexible)
            && isset($fallback['findings'])
            && is_array($fallback['findings'])
            && $fallback['findings'] !== []
        ) {
            $note['findings'] = $fallback['findings'];
            if (! empty($fallback['priority'])) {
                $note['priority'] = (string) $fallback['priority'];
            }
            if (! empty($fallback['summary'])) {
                $note['summary'] = (string) $fallback['summary'];
            }
        }

        return $note;
    }

    /**
     * @param  array{summary?: string, findings?: list<string>, interpretation?: string, priority?: string, education?: string}  $note
     */
    private function doctorsNoteStillHasBucketSwap(array $note, float $protectionShare, float $flexibleShare): bool
    {
        if (abs($protectionShare - $flexibleShare) < 0.5) {
            return false;
        }

        $protFmts = $this->percentFormats($protectionShare);
        $flexFmts = $this->percentFormats($flexibleShare);
        $chunks = [];
        foreach (['summary', 'interpretation', 'priority', 'education'] as $key) {
            if (isset($note[$key]) && is_string($note[$key]) && $note[$key] !== '') {
                $chunks[] = $note[$key];
            }
        }
        if (isset($note['findings']) && is_array($note['findings'])) {
            foreach ($note['findings'] as $finding) {
                if (is_string($finding) && $finding !== '') {
                    $chunks[] = $finding;
                }
            }
        }

        foreach ($chunks as $text) {
            $hasProt = preg_match('/\bproteksi\b|\bprotection\b/iu', $text) === 1;
            $hasFlex = preg_match('/flexible\s*\+\s*social|\bflexible\b|\bfleksibel\b/iu', $text) === 1;
            if ($hasProt && ! $hasFlex && $this->textHasAnyPercent($text, $flexFmts) && ! $this->textHasAnyPercent($text, $protFmts)) {
                return true;
            }
            if ($hasFlex && ! $hasProt && $this->textHasAnyPercent($text, $protFmts) && ! $this->textHasAnyPercent($text, $flexFmts)) {
                return true;
            }
        }

        return false;
    }

    private function ftsaBaselineContext(FinancialBaseline $baseline): string
    {
        $lines = [];
        if (trim((string) ($baseline->stage_label ?? '')) !== '') {
            $lines[] = '- Tahap keuangan (diagnostik): '.$baseline->stage_label
                .($baseline->financial_stage_score ? " (skor {$baseline->financial_stage_score}/39)" : '');
        }
        if (trim((string) ($baseline->current_goal ?? '')) !== '') {
            $lines[] = '- Target saat ini: '.$baseline->current_goal;
        }
        foreach ([
            'avg_monthly_income' => 'Pendapatan/bulan',
            'cash_savings' => 'Tabungan',
            'total_investment' => 'Investasi',
            'total_asset' => 'Total aset',
            'total_debt' => 'Utang',
            'emergency_fund' => 'Dana darurat',
        ] as $field => $label) {
            if ($baseline->{$field} !== null && (int) $baseline->{$field} > 0) {
                $lines[] = sprintf('- %s: Rp %s', $label, number_format((int) $baseline->{$field}, 0, ',', '.'));
            }
        }

        if ($lines === []) {
            return '';
        }

        return "- Baseline keuangan:\n".$this->linesBlock($lines);
    }

    /**
     * @return array{insights: list<string>, recommendations: list<string>, source: string, generated_at: ?string}
     */
    private function ftsaFallback(FinancialBaseline $baseline): array
    {
        $summary = $this->ftsaSummary->scoreSummary($baseline);
        $archetypeKey = strtolower((string) ($summary['archetype'] ?? ''));
        $fallbacks = (array) config('portal_ai.archetype_fallback', []);
        $match = $fallbacks[$archetypeKey] ?? null;

        if (! is_array($match)) {
            foreach ($fallbacks as $key => $row) {
                if (str_contains(strtolower((string) $summary['archetype_label']), (string) $key)) {
                    $match = $row;
                    break;
                }
            }
        }

        $archetypeLabel = $summary['archetype_label'] ?? 'profil FTSA Anda';
        $insight = is_array($match) ? trim((string) ($match['insight'] ?? '')) : '';
        $recommendation = is_array($match) ? trim((string) ($match['recommendation'] ?? '')) : '';

        if ($insight === '') {
            $insight = "Archetype dominan Anda: {$archetypeLabel}.";
        }
        if ($recommendation === '') {
            $recommendation = 'Refleksikan satu pemicu emosional terkait uang minggu ini.';
        }

        return [
            'insights' => [$insight],
            'recommendations' => [$recommendation],
            'source' => 'rules',
            'generated_at' => null,
            'claude_configured' => $this->claude->isConfigured(),
        ];
    }

    /**
     * @return array{insights: list<string>, recommendations: list<string>, source: string, generated_at: ?string}
     */
    private function emptyFtsaGuidance(): array
    {
        return [
            'insights' => [],
            'recommendations' => [],
            'source' => 'none',
            'generated_at' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, int|float|string|null>
     */
    private function behavioralFingerprint(array $metrics): array
    {
        return [
            'expense_count' => (int) ($metrics['expense_count'] ?? 0),
            'impulsive_rate' => (float) ($metrics['impulsive_rate'] ?? 0),
            'score' => (int) ($metrics['score'] ?? 0),
            'dominant_mood' => (string) ($metrics['dominant_mood'] ?? ''),
            'dominant_pattern' => (string) ($metrics['dominant_pattern'] ?? ''),
            'archetype' => is_array($metrics['ftsa_profile'] ?? null) ? ($metrics['ftsa_profile']['archetype'] ?? '') : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, int|float|string>
     */
    private function financialFingerprint(array $metrics): array
    {
        return [
            'income' => (int) ($metrics['income'] ?? 0),
            'expense' => (int) ($metrics['expense'] ?? 0),
            'cashflow' => (int) ($metrics['cashflow'] ?? 0),
            'saving_rate' => (float) ($metrics['saving_rate'] ?? 0),
            'transaction_count' => (int) ($metrics['transaction_count'] ?? 0),
        ];
    }

  /**
   * @param  list<array<string, mixed>>  $buckets
   * @return list<string>
   */
  private function formatBucketPrescriptionLines(array $buckets): array
  {
    $directions = (array) config('portal_ai.bucket_prescription_directions', []);
    $lines = [];

    foreach ($buckets as $bucket) {
      if (! is_array($bucket)) {
        continue;
      }
      $name = (string) ($bucket['bucket'] ?? '—');
      $direction = (string) ($directions[$name] ?? '');
      $directionSuffix = $direction !== '' ? " [{$direction}]" : '';

      $lines[] = sprintf(
        '- %s: aktual %s%% (prescription %s%%)%s — %s',
        $name,
        $bucket['share'] ?? '0',
        $bucket['ideal'] ?? '0',
        $directionSuffix,
        $bucket['status_label'] ?? '—',
      );
    }

    return $lines;
  }

  /**
   * @param  array<string, mixed>  $metrics
   * @return list<string>
   */
  private function formatCashLiquidityLines(array $metrics): array
  {
      $cash = is_array($metrics['cash_liquidity'] ?? null) ? $metrics['cash_liquidity'] : [];
      if ($cash === []) {
          return ['- (tidak ada data likuiditas sosial pada periode ini)'];
      }

      $periodMonths = (int) ($metrics['period_months'] ?? 1);
      $cashScope = $periodMonths === 1 ? 'bulan ini' : 'periode (akumulasi)';
      $lines = [
          '- Defisit (expense>income) '.$cashScope.': Rp '.$this->formatIdr((int) ($cash['deficit'] ?? 0)),
          '- Utang Masuk (pinjaman sosial masuk) '.$cashScope.': Rp '.$this->formatIdr((int) ($cash['social_borrow_inflow'] ?? 0)),
          '- Utang Keluar (bayar balik) '.$cashScope.': Rp '.$this->formatIdr((int) ($cash['social_repay_outflow'] ?? 0)),
          '- Estimasi sisa kas '.$cashScope.' (cashflow ± likuiditas sosial periode): Rp '.$this->formatIdr((int) ($cash['estimated_cash'] ?? 0)),
          '- Outstanding utang sosial (posisi aktif, semua periode): Rp '.$this->formatIdr((int) ($cash['outstanding_debt'] ?? 0)),
          '- Outstanding piutang aktif (posisi aktif, semua periode): Rp '.$this->formatIdr((int) ($cash['outstanding_receivable'] ?? 0)),
      ];
      if ($periodMonths === 1) {
          $lines[] = '- Catatan: untuk filter 1 bulan, bahas surplus/defisit BULAN INI saja — jangan menyebut akumulasi lintas bulan.';
      }
      $insight = trim((string) ($cash['insight_text'] ?? ''));
      if ($insight !== '') {
          $lines[] = '- Insight: '.$insight;
      }

      return $lines;
  }

  private function clinicalWeekAnchor(string $monthKey): \Carbon\Carbon
  {
    $tz = (string) config('portal_ai.guidance_timezone', PortalTimezone::defaultName());
    try {
      $month = \Carbon\Carbon::createFromFormat('Y-m', $monthKey, $tz)->startOfMonth();
    } catch (\Throwable) {
      return now($tz);
    }

    return $month->isCurrentMonth() ? now($tz) : $month->copy()->endOfMonth();
  }

  private function anchorFromWeekKey(string $weekKey): \Carbon\Carbon
  {
    if (preg_match('/^(\d{4}-\d{2})-W(\d+)$/', $weekKey, $matches) === 1) {
      return $this->clinicalWeekAnchor($matches[1]);
    }

    return now((string) config('portal_ai.guidance_timezone', PortalTimezone::defaultName()));
  }

  /**
   * @param  array<string, mixed>  $metrics
   */
  private function metricsFingerprint(array $metrics): string
  {
      $bucketParts = [];
      foreach ((array) ($metrics['buckets'] ?? []) as $bucket) {
          if (! is_array($bucket)) {
              continue;
          }
          $bucketParts[] = sprintf(
              '%s:%s',
              (string) ($bucket['bucket'] ?? ''),
              round((float) ($bucket['share'] ?? 0), 1),
          );
      }
      sort($bucketParts);

      return sha1(implode('|', [
          (int) ($metrics['income'] ?? 0),
          (int) ($metrics['expense'] ?? 0),
          (int) ($metrics['saving_investment'] ?? 0),
          (int) ($metrics['cashflow'] ?? 0),
          (int) ($metrics['transaction_count'] ?? 0),
          round((float) ($metrics['saving_rate'] ?? 0), 1),
          implode(',', $bucketParts),
      ]));
  }

  /**
   * @param  array{payload: array<string, mixed>, ai_source: string, generated_at: string}|null  $stored
   */
  private function snapshotMatchesMetrics(?array $stored, string $fingerprint): bool
  {
      if ($stored === null || $fingerprint === '') {
          return false;
      }

      $storedFp = (string) ($stored['payload']['metrics_fingerprint'] ?? '');
      if ($storedFp === '') {
          // Snapshot lama tanpa fingerprint: anggap tidak match agar diregenerasi
          // dari metrik bulan yang sedang dilihat.
          return false;
      }

      return hash_equals($storedFp, $fingerprint);
  }

  private function isPastMonth(string $monthKey): bool
  {
      $tz = (string) config('portal_ai.guidance_timezone', PortalTimezone::defaultName());
      try {
          $month = \Carbon\Carbon::createFromFormat('Y-m', $monthKey, $tz)->startOfMonth();
      } catch (\Throwable) {
          return false;
      }

      return $month->lt(now($tz)->copy()->startOfMonth());
  }

  /**
   * @param  list<string>  $lines
   */
    private function linesBlock(array $lines): string
    {
        return implode("\n", $lines);
    }

    private function formatIdr(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
