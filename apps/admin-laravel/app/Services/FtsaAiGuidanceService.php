<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FtsaAiGuidanceService
{
    public function __construct(
        private readonly FtsaAnswerSummaryService $ftsaSummary,
    ) {}

    /**
     * @return array{
     *     insights: list<string>,
     *     recommendations: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    public function forBaseline(?FinancialBaseline $baseline): array
    {
        if ($baseline === null || ! $this->ftsaSummary->hasFtsaAnswers($baseline)) {
            return $this->emptyGuidance();
        }

        $cacheKey = sprintf(
            'ftsa_ai_guidance:%d:%s',
            (int) $baseline->id,
            $baseline->assessed_at?->timestamp ?? '0'
        );

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($baseline) {
            if ((bool) config('ftsa_ai.enabled', true)) {
                try {
                    $ai = $this->generateWithGemini($baseline);
                    if ($ai !== null) {
                        return $ai;
                    }
                } catch (\Throwable $e) {
                    Log::warning('FTSA AI guidance failed', [
                        'baseline_id' => $baseline->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            return $this->fallbackGuidance($baseline);
        });
    }

    /**
     * @return array{insights: list<string>, recommendations: list<string>, source: string, generated_at: ?string}|null
     */
    private function generateWithGemini(FinancialBaseline $baseline): ?array
    {
        $apiKey = (string) config('ftsa_ai.api_key', '');
        if ($apiKey === '') {
            return null;
        }

        $summary = $this->ftsaSummary->scoreSummary($baseline);
        $prompt = $this->buildPrompt($baseline, $summary);
        $models = (array) config('ftsa_ai.models', ['gemini-2.0-flash']);
        $timeout = max(10, (int) config('ftsa_ai.timeout_seconds', 45));
        $temperature = (float) config('ftsa_ai.temperature', 0.3);

        foreach ($models as $model) {
            $response = Http::timeout($timeout)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.urlencode($apiKey),
                    [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]],
                        ],
                        'generationConfig' => [
                            'temperature' => $temperature,
                            'responseMimeType' => 'application/json',
                        ],
                    ]
                );

            if (! $response->successful()) {
                continue;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (! is_string($text) || trim($text) === '') {
                continue;
            }

            $parsed = json_decode($text, true);
            if (! is_array($parsed)) {
                continue;
            }

            $insights = $this->normalizeLines($parsed['insights'] ?? [], (int) config('ftsa_ai.max_insights', 3));
            $recommendations = $this->normalizeLines($parsed['recommendations'] ?? [], (int) config('ftsa_ai.max_recommendations', 3));

            if ($insights === [] && $recommendations === []) {
                continue;
            }

            return [
                'insights' => $insights !== [] ? $insights : $this->fallbackGuidance($baseline)['insights'],
                'recommendations' => $recommendations !== [] ? $recommendations : $this->fallbackGuidance($baseline)['recommendations'],
                'source' => 'ai',
                'generated_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * @param  array{domains: list<array{key: string, code: string, label: string, score: int, level: ?string}>, archetype_label: ?string}  $summary
     */
    private function buildPrompt(FinancialBaseline $baseline, array $summary): string
    {
        $rules = implode("\n", array_map(
            fn (string $rule, int $i) => ($i + 1).'. '.$rule,
            (array) config('ftsa_ai.rules', []),
            array_keys((array) config('ftsa_ai.rules', []))
        ));

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

        $maxInsights = (int) config('ftsa_ai.max_insights', 3);
        $maxRecs = (int) config('ftsa_ai.max_recommendations', 3);

        return <<<PROMPT
Anda adalah dr. Financial dari Your Financial Doctor (YFD). Berikan insight dan rekomendasi behavioral finansial berdasarkan hasil FTSA-32 berikut.

DATA HASIL:
- Dominant archetype: {$summary['archetype_label']}
- Domain scores:
{$this->indentLines($domainLines)}

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
     * @param  list<string>  $lines
     */
    private function indentLines(array $lines): string
    {
        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function normalizeLines(mixed $value, int $limit): array
    {
        if (! is_array($value)) {
            return [];
        }

        $lines = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }
            $trimmed = trim($item);
            if ($trimmed !== '') {
                $lines[] = $trimmed;
            }
        }

        return array_slice(array_values($lines), 0, max(1, $limit));
    }

    /**
     * @return array{insights: list<string>, recommendations: list<string>, source: string, generated_at: ?string}
     */
    private function fallbackGuidance(FinancialBaseline $baseline): array
    {
        $summary = $this->ftsaSummary->scoreSummary($baseline);
        $archetypeKey = strtolower((string) ($summary['archetype'] ?? ''));
        $fallbacks = (array) config('ftsa_ai.archetype_fallback', []);
        $match = $fallbacks[$archetypeKey] ?? null;

        if (! is_array($match)) {
            foreach ($fallbacks as $key => $row) {
                if (str_contains(strtolower((string) $summary['archetype_label']), $key)) {
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
        ];
    }

    /**
     * @return array{insights: list<string>, recommendations: list<string>, source: string, generated_at: ?string}
     */
    private function emptyGuidance(): array
    {
        return [
            'insights' => [],
            'recommendations' => [],
            'source' => 'none',
            'generated_at' => null,
        ];
    }
}
