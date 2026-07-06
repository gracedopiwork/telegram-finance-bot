<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Models\PortalGuidanceSnapshot;
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
        if ((int) ($metrics['expense_count'] ?? 0) === 0) {
            return array_merge($fallback, [
                'ai_source' => 'none',
                'generated_at' => null,
            ]);
        }

        $cacheKey = sprintf(
            'portal_ai:behavioral:%d:%s:%d:%s',
            $telegramUserId,
            $month,
            $periodMonths,
            md5(json_encode($this->behavioralFingerprint($metrics)))
        );

        $ttl = now()->addHours(max(1, (int) config('portal_ai.cache_ttl_hours_dashboard', 24)));

        return Cache::remember($cacheKey, $ttl, function () use ($metrics, $baseline, $fallback) {
            if (! $this->claude->isConfigured()) {
                return array_merge($fallback, [
                    'ai_source' => 'rules',
                    'generated_at' => null,
                ]);
            }

            try {
                $parsed = $this->claude->generate($this->behavioralPrompt($metrics, $baseline));
                if ($parsed === null) {
                    return array_merge($fallback, [
                        'ai_source' => 'rules',
                        'generated_at' => null,
                    ]);
                }

                $result = $this->normalizeBehavioralResponse($parsed, $fallback);

                return array_merge($result, [
                    'ai_source' => 'ai',
                    'generated_at' => now()->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Portal AI behavioral guidance failed', [
                    'message' => $e->getMessage(),
                ]);

                return array_merge($fallback, [
                    'ai_source' => 'rules',
                    'generated_at' => null,
                ]);
            }
        });
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

        $weekKey = PortalGuidanceSnapshot::weekPeriodKey();
        $monthKey = $month;

        $weeklyStored = $this->guidanceSnapshots->get(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_CLINICAL_SUMMARY_WEEKLY,
            $weekKey,
        );
        $monthlyStored = $this->guidanceSnapshots->get(
            $telegramUserId,
            PortalGuidanceSnapshot::TYPE_DOCTORS_NOTE_MONTHLY,
            $monthKey,
        );

        $clinical = $weeklyStored !== null
            ? ($weeklyStored['payload']['clinical_summary'] ?? $fallback['clinical_summary'])
            : $this->pendingClinicalSummary($fallback['clinical_summary']);

        $doctors = $monthlyStored !== null
            ? ($monthlyStored['payload']['doctors_note'] ?? $fallback['doctors_note'])
            : $this->pendingDoctorsNote($month, $fallback['doctors_note']);

        $clinicalPending = $weeklyStored === null;
        $doctorsPending = $monthlyStored === null;

        $aiSource = 'rules';
        $weeklyAi = ($weeklyStored['ai_source'] ?? '') === 'ai';
        $monthlyAi = ($monthlyStored['ai_source'] ?? '') === 'ai';
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
            'doctors_generated_at' => $monthlyStored['generated_at'] ?? null,
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
            ['clinical_summary' => $clinical],
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
            ['doctors_note' => $note],
            $provider,
        );

        return true;
    }

    /**
     * @param  array{headline: string, findings: list<string>, status: string}  $fallback
     * @return array{headline: string, findings: list<string>, status: string}
     */
    private function pendingClinicalSummary(array $fallback): array
    {
        $weeklyAt = (string) config('portal_ai.guidance_weekly_label', 'Minggu pukul 22.00 WIB');

        return [
            'headline' => 'Insight mingguan menunggu jadwal generate',
            'findings' => array_merge(
                ["Insight AI diperbarui setiap {$weeklyAt}. Sementara ini, gunakan ringkasan angka dan grafik di bawah."],
                array_slice($fallback['findings'] ?? [], 0, 3),
            ),
            'status' => $fallback['status'] ?? 'fair',
        ];
    }

    /**
     * @param  array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}  $fallback
     * @return array{summary: string, findings: list<string>, interpretation: string, priority: string, education: string}
     */
    private function pendingDoctorsNote(string $monthKey, array $fallback): array
    {
        try {
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->endOfMonth();
            $release = $monthEnd->translatedFormat('d F Y');
        } catch (\Throwable) {
            $release = 'akhir bulan';
        }

        return array_merge($fallback, [
            'summary' => "Rekomendasi dokter untuk periode ini akan dirilis pada {$release} pukul 22.00 WIB.",
            'interpretation' => 'Doctor\'s note bulanan di-generate otomatis agar konsisten dan hemat kuota AI.',
            'priority' => 'Lanjutkan pencatatan transaksi hingga akhir bulan untuk analisis yang lebih akurat.',
        ]);
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

ATURAN WAJIB:
{$rules}

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "insights": ["..."],
  "recommendations_personalized": ["..."],
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
        $bucketLines = [];
        foreach ((array) ($metrics['buckets'] ?? []) as $bucket) {
            if (! is_array($bucket)) {
                continue;
            }
            $bucketLines[] = sprintf(
                '- %s: aktual %s%% (ideal %s%%) — %s',
                $bucket['bucket'] ?? '—',
                $bucket['share'] ?? '0',
                $bucket['ideal'] ?? '0',
                $bucket['status_label'] ?? '—'
            );
        }

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
- Cashflow: Rp {$this->formatIdr((int) $metrics['cashflow'])}
- Saving rate: {$metrics['saving_rate']}%
- Financial pulse: {$metrics['pulse_score']}/100
- Jumlah transaksi: {$metrics['transaction_count']}

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
        $bucketLines = [];
        foreach ((array) ($metrics['buckets'] ?? []) as $bucket) {
            if (! is_array($bucket)) {
                continue;
            }
            $bucketLines[] = sprintf(
                '- %s: aktual %s%% (ideal %s%%) — %s',
                $bucket['bucket'] ?? '—',
                $bucket['share'] ?? '0',
                $bucket['ideal'] ?? '0',
                $bucket['status_label'] ?? '—'
            );
        }

        $stage = $baseline?->stage_label ? "Tahap finansial: {$baseline->stage_label}" : 'Tahap finansial: belum diisi';
        $maxFindings = (int) config('portal_ai.max_findings', 5);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan clinical summary mingguan keuangan berdasarkan data berikut.

PERIODE MINGGUAN: {$metrics['period_label']}
{$stage}

METRIK:
- Pendapatan: Rp {$this->formatIdr((int) $metrics['income'])}
- Pengeluaran: Rp {$this->formatIdr((int) $metrics['expense'])}
- Cashflow: Rp {$this->formatIdr((int) $metrics['cashflow'])}
- Saving rate: {$metrics['saving_rate']}%
- Financial pulse: {$metrics['pulse_score']}/100
- Jumlah transaksi: {$metrics['transaction_count']}

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

Maksimal {$maxFindings} findings. Fokus pola minggu ini, bukan archetype FTSA.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function monthlyDoctorsNotePrompt(array $metrics, ?FinancialBaseline $baseline): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'financial_rules']);
        $bucketLines = [];
        foreach ((array) ($metrics['buckets'] ?? []) as $bucket) {
            if (! is_array($bucket)) {
                continue;
            }
            $bucketLines[] = sprintf(
                '- %s: aktual %s%% (ideal %s%%) — %s',
                $bucket['bucket'] ?? '—',
                $bucket['share'] ?? '0',
                $bucket['ideal'] ?? '0',
                $bucket['status_label'] ?? '—'
            );
        }

        $stage = $baseline?->stage_label ? "Tahap finansial: {$baseline->stage_label}" : 'Tahap finansial: belum diisi';
        $maxFindings = (int) config('portal_ai.max_findings', 5);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan doctor's note bulanan keuangan berdasarkan data berikut.

PERIODE BULANAN: {$metrics['period_label']}
{$stage}

METRIK:
- Pendapatan: Rp {$this->formatIdr((int) $metrics['income'])}
- Pengeluaran: Rp {$this->formatIdr((int) $metrics['expense'])}
- Cashflow: Rp {$this->formatIdr((int) $metrics['cashflow'])}
- Saving rate: {$metrics['saving_rate']}%
- Financial pulse: {$metrics['pulse_score']}/100
- Jumlah transaksi: {$metrics['transaction_count']}

BUCKET PRESCRIPTION:
{$this->linesBlock($bucketLines)}

ATURAN WAJIB:
{$rules}
Jangan menyebut archetype FTSA — itu ada di dashboard behavioral.

OUTPUT: JSON valid saja, tanpa markdown, format:
{
  "doctors_note": {
    "summary": "...",
    "findings": ["..."],
    "interpretation": "...",
    "priority": "...",
    "education": "..."
  }
}

Maksimal {$maxFindings} findings.
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
            'insights' => $insights !== [] ? $insights : $fallback['insights'],
            'recommendations' => [
                'personalized' => $personal !== [] ? $personal : $fallback['recommendations']['personalized'],
                'general' => $general !== [] ? $general : $fallback['recommendations']['general'],
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
    private function normalizeFinancialResponse(array $parsed, array $fallback): array
    {
        $clinical = is_array($parsed['clinical_summary'] ?? null) ? $parsed['clinical_summary'] : [];
        $headline = trim((string) ($clinical['headline'] ?? ''));
        $findings = $this->claude->normalizeLines($clinical['findings'] ?? [], (int) config('portal_ai.max_findings', 5));
        $status = trim((string) ($clinical['status'] ?? ''));
        $allowedStatuses = ['healthy', 'fair', 'attention', 'critical', 'no_data'];

        $clinicalSummary = [
            'headline' => $headline !== '' ? $headline : $fallback['clinical_summary']['headline'],
            'findings' => $findings !== [] ? $findings : $fallback['clinical_summary']['findings'],
            'status' => in_array($status, $allowedStatuses, true) ? $status : $fallback['clinical_summary']['status'],
        ];

        $note = $this->normalizeDoctorsNote($parsed['doctors_note'] ?? null, $fallback['doctors_note'], true);

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
            'summary' => $summary !== '' ? $summary : $fallback['summary'],
            'findings' => $findings !== [] ? $findings : $fallback['findings'],
            'interpretation' => $interpretation !== '' ? $interpretation : $fallback['interpretation'],
            'priority' => $priority !== '' ? $priority : $fallback['priority'],
        ];

        if ($withEducation) {
            $education = trim((string) ($raw['education'] ?? ''));
            $note['education'] = $education !== '' ? $education : ($fallback['education'] ?? '');
        }

        return $note;
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
            'pulse_score' => (int) ($metrics['pulse_score'] ?? 0),
            'transaction_count' => (int) ($metrics['transaction_count'] ?? 0),
        ];
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
