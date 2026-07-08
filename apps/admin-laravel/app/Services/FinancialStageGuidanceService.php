<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FinancialStageGuidanceService
{
    public function __construct(
        private readonly ClaudeJsonService $claude,
        private readonly DiagnosticAnswerSummaryService $diagnosticSummary,
        private readonly DiagnosticConfigService $diagnosticConfig,
    ) {}

    /**
     * @return array{
     *     summary: string,
     *     diagnosis: string,
     *     therapy_plan: list<string>,
     *     bridge: string,
     *     targets: array{3m: string, 12m: string},
     *     doctor_notes: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    public function forBaseline(?FinancialBaseline $baseline): array
    {
        if ($baseline === null || trim((string) $baseline->financial_stage) === '') {
            return $this->emptyGuidance();
        }

        $stageKey = (string) $baseline->financial_stage;
        $fallback = $this->playbookGuidance($baseline, $stageKey);

        if (! $this->claude->isConfigured()) {
            return $fallback;
        }

        $cacheKey = sprintf(
            'portal_ai:financial_stage:v1:%d:%s:%s',
            (int) $baseline->id,
            $baseline->assessed_at?->timestamp ?? '0',
            $stageKey,
        );

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ($cached['source'] ?? '') === 'ai') {
            return $cached;
        }

        $result = $this->generateWithAi($baseline, $stageKey, $fallback);
        if (($result['source'] ?? '') === 'ai') {
            Cache::put($cacheKey, $result, now()->addDays(max(1, (int) config('portal_ai.cache_ttl_days_ftsa', 30))));
        }

        return $result;
    }

    /**
     * @return array{
     *     summary: string,
     *     diagnosis: string,
     *     therapy_plan: list<string>,
     *     bridge: string,
     *     targets: array{3m: string, 12m: string},
     *     doctor_notes: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    private function playbookGuidance(FinancialBaseline $baseline, string $stageKey): array
    {
        $playbook = $this->playbook($stageKey);
        $display = $this->diagnosticConfig->stageDisplay($stageKey, (int) $baseline->financial_stage_score);

        return [
            'summary' => (string) ($playbook['summary'] ?? ($display['risk_description'] ?? '')),
            'diagnosis' => (string) ($playbook['diagnosis'] ?? ($display['diagnosis'] ?? '')),
            'therapy_plan' => array_values(array_filter(array_map('strval', (array) ($playbook['therapy_plan'] ?? [])))),
            'bridge' => (string) ($playbook['bridge'] ?? ''),
            'targets' => [
                '3m' => (string) ($playbook['targets']['3m'] ?? ''),
                '12m' => (string) ($playbook['targets']['12m'] ?? ''),
            ],
            'doctor_notes' => array_values(array_filter(array_map('strval', (array) ($playbook['doctor_notes'] ?? [])))),
            'source' => 'playbook',
            'generated_at' => null,
        ];
    }

    /**
     * @param  array{
     *     summary: string,
     *     diagnosis: string,
     *     therapy_plan: list<string>,
     *     bridge: string,
     *     targets: array{3m: string, 12m: string},
     *     doctor_notes: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }  $fallback
     * @return array{
     *     summary: string,
     *     diagnosis: string,
     *     therapy_plan: list<string>,
     *     bridge: string,
     *     targets: array{3m: string, 12m: string},
     *     doctor_notes: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    private function generateWithAi(FinancialBaseline $baseline, string $stageKey, array $fallback): array
    {
        try {
            $parsed = $this->claude->generate($this->prompt($baseline, $stageKey, $fallback));
            if ($parsed === null) {
                return $fallback;
            }

            $summary = trim((string) ($parsed['summary'] ?? ''));
            $therapy = $this->claude->normalizeLines($parsed['therapy_plan'] ?? [], 6);
            $notes = $this->claude->normalizeLines($parsed['doctor_notes'] ?? [], 4);

            if ($summary === '' && $therapy === [] && $notes === []) {
                return $fallback;
            }

            return [
                'summary' => $summary !== '' ? $summary : $fallback['summary'],
                'diagnosis' => $fallback['diagnosis'],
                'therapy_plan' => $therapy !== [] ? $therapy : $fallback['therapy_plan'],
                'bridge' => $fallback['bridge'],
                'targets' => $fallback['targets'],
                'doctor_notes' => $notes !== [] ? $notes : $fallback['doctor_notes'],
                'source' => 'ai',
                'generated_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Financial stage guidance AI failed', [
                'baseline_id' => $baseline->id,
                'message' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    /**
     * @param  array{
     *     summary: string,
     *     diagnosis: string,
     *     therapy_plan: list<string>,
     *     bridge: string,
     *     targets: array{3m: string, 12m: string},
     *     doctor_notes: list<string>
     * }  $playbook
     */
    private function prompt(FinancialBaseline $baseline, string $stageKey, array $playbook): string
    {
        $rules = $this->claude->rulesBlock(['shared_rules', 'financial_stage_rules']);
        $answerLines = [];
        foreach ($this->diagnosticSummary->summarize($baseline) as $row) {
            if (! ($row['is_scored'] ?? false)) {
                continue;
            }
            $answerLines[] = sprintf('- %s → %s', $row['question'], $row['answer_label']);
        }

        $therapyLines = implode("\n", array_map(fn (string $l) => "- {$l}", $playbook['therapy_plan']));
        $noteLines = implode("\n", array_map(fn (string $l) => "- {$l}", $playbook['doctor_notes']));

        $answersBlock = $answerLines !== []
            ? "JAWABAN DIAGNOSTIK USER:\n".implode("\n", $answerLines)
            : 'JAWABAN DIAGNOSTIK USER: (belum lengkap)';

        $display = $this->diagnosticConfig->stageDisplay($stageKey, (int) $baseline->financial_stage_score);
        $phase = (string) ($display['phase'] ?? '');

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Personalisasi penjelasan tahap keuangan user berdasarkan playbook resmi YFD dan hasil check-up diagnostik.

TAHAP: {$baseline->stage_label} ({$playbook['diagnosis']})
SKOR: {$baseline->financial_stage_score}/39
FASE: {$phase}

PLAYBOOK RESMI (jangan diubah maknanya):
Ringkasan: {$playbook['summary']}
Rencana terapi:
{$therapyLines}
Jembatan: {$playbook['bridge']}
Target 3 bulan: {$playbook['targets']['3m']}
Target 12 bulan: {$playbook['targets']['12m']}
Catatan dokter:
{$noteLines}

{$answersBlock}

ATURAN:
{$rules}

OUTPUT JSON valid saja:
{
  "summary": "2-3 kalimat personal — seperti penjelasan archetype FTSA, merujuk kondisi user dari jawaban diagnostik",
  "therapy_plan": ["maks 4 poin — adaptasi playbook, prioritaskan yang paling relevan untuk user"],
  "doctor_notes": ["maks 3 poin — hangat, personal, tidak menghakimi"]
}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function playbook(string $stageKey): array
    {
        $all = (array) config('financial_stage_playbooks', []);

        return is_array($all[$stageKey] ?? null) ? $all[$stageKey] : [];
    }

    /**
     * @return array{
     *     summary: string,
     *     diagnosis: string,
     *     therapy_plan: list<string>,
     *     bridge: string,
     *     targets: array{3m: string, 12m: string},
     *     doctor_notes: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    private function emptyGuidance(): array
    {
        return [
            'summary' => '',
            'diagnosis' => '',
            'therapy_plan' => [],
            'bridge' => '',
            'targets' => ['3m' => '', '12m' => ''],
            'doctor_notes' => [],
            'source' => 'none',
            'generated_at' => null,
        ];
    }
}
