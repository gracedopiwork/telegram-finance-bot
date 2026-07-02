<?php

namespace App\Services;

use App\Models\FinancialBaseline;

class FtsaAnswerSummaryService
{
    /**
     * @return array<int, string>
     */
    public function questionTexts(): array
    {
        $fromConfig = app(DiagnosticConfigService::class)->fullBaselineConfig()['ftsa_questions'] ?? [];

        if (is_array($fromConfig) && $fromConfig !== []) {
            return array_map('strval', $fromConfig);
        }

        return (array) config('baseline_assessment.ftsa_questions', []);
    }

    /**
     * @return array<int, string>
     */
    public function likertLabels(): array
    {
        return (array) config('baseline_assessment.likert_labels', []);
    }

    /**
     * @return array<string, array{code: string, label: string, questions: list<int>}>
     */
    public function domainMap(): array
    {
        $map = [];
        foreach ((array) config('baseline_assessment.ftsa_domains', []) as $key => $domain) {
            if (! is_array($domain)) {
                continue;
            }
            foreach ($domain['questions'] ?? [] as $qNum) {
                $map[(int) $qNum] = [
                    'key' => $key,
                    'code' => (string) ($domain['code'] ?? strtoupper($key)),
                    'label' => (string) ($domain['label'] ?? $key),
                ];
            }
        }

        return $map;
    }

    public function hasFtsaAnswers(FinancialBaseline $baseline): bool
    {
        $ftsa = $this->rawFtsaAnswers($baseline);

        return count($ftsa) > 0;
    }

    public function isFtsaLocked(FinancialBaseline $baseline): bool
    {
        return ($baseline->dominant_archetype ?? '') === 'locked' && ! $this->hasFtsaAnswers($baseline);
    }

    /**
     * @return list<array{
     *     num: int,
     *     domain_key: ?string,
     *     domain_code: ?string,
     *     domain_label: ?string,
     *     question: string,
     *     score: int,
     *     score_label: string
     * }>
     */
    public function summarizeAnswers(FinancialBaseline $baseline): array
    {
        $questions = $this->questionTexts();
        $likert = $this->likertLabels();
        $domains = $this->domainMap();
        $rows = [];

        foreach ($this->rawFtsaAnswers($baseline) as $num => $score) {
            $domain = $domains[$num] ?? null;

            $rows[] = [
                'num' => $num,
                'domain_key' => $domain['key'] ?? null,
                'domain_code' => $domain['code'] ?? null,
                'domain_label' => $domain['label'] ?? null,
                'question' => $questions[$num] ?? "Pertanyaan {$num}",
                'score' => $score,
                'score_label' => $likert[$score] ?? (string) $score,
            ];
        }

        usort($rows, fn (array $a, array $b) => $a['num'] <=> $b['num']);

        return $rows;
    }

    /**
     * @return array{
     *     filled: int,
     *     total: int,
     *     domains: list<array{key: string, code: string, label: string, score: int, level: ?string}>,
     *     archetype: ?string,
     *     archetype_label: ?string
     * }
     */
    public function scoreSummary(FinancialBaseline $baseline): array
    {
        $domains = [];
        foreach ((array) config('baseline_assessment.ftsa_domains', []) as $key => $meta) {
            if (! is_array($meta)) {
                continue;
            }
            $scoreCol = 'ftsa_'.$key;
            $levelCol = $key.'_level';

            $domains[] = [
                'key' => $key,
                'code' => (string) ($meta['code'] ?? strtoupper($key)),
                'label' => (string) ($meta['label'] ?? $key),
                'score' => (int) ($baseline->{$scoreCol} ?? 0),
                'level' => $baseline->{$levelCol},
            ];
        }

        $filled = count($this->rawFtsaAnswers($baseline));

        return [
            'filled' => $filled,
            'total' => 32,
            'domains' => $domains,
            'archetype' => $baseline->dominant_archetype,
            'archetype_label' => $baseline->dominant_archetype_label,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function rawFtsaAnswers(FinancialBaseline $baseline): array
    {
        $answers = $baseline->answers_json;
        if (! is_array($answers)) {
            return [];
        }

        $ftsa = $answers['ftsa'] ?? [];
        if (! is_array($ftsa)) {
            return [];
        }

        $parsed = [];
        foreach ($ftsa as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $num = (int) $key;
            if ($num < 1 || $num > 32) {
                continue;
            }
            $parsed[$num] = max(1, min(5, (int) $value));
        }

        ksort($parsed);

        return $parsed;
    }
}
