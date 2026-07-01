<?php

namespace App\Services;

use Carbon\Carbon;

class BaselineAssessmentService
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function assess(array $answers, bool $includeFtsa = true): array
    {
        $financialScore = $this->scoreFinancialStage($answers);
        $stage = $this->resolveStage($financialScore);
        $stageMeta = config("baseline_assessment.stage_labels.{$stage}", []);

        if ($includeFtsa) {
            $domainScores = $this->scoreFtsaDomains($answers);
            $dominant = $this->resolveDominantArchetype($domainScores);
        } else {
            $domainScores = ['chd' => 0, 'rvd' => 0, 'ssd' => 0, 'esd' => 0];
            $dominant = [
                'domain' => '',
                'archetype' => 'locked',
                'label' => 'FTSA Premium Locked',
                'score' => 0,
            ];
        }

        $domainLevels = [];
        foreach ($domainScores as $key => $score) {
            $domainLevels[$key] = $includeFtsa
                ? $this->dysregulationLevel($score)
                : ['key' => 'locked', 'label' => null];
        }

        $reviewMonths = (int) config('baseline_assessment.review_months', 6);
        $assessedAt = Carbon::now();

        return [
            'assessed_at' => $assessedAt,
            'next_review_at' => $assessedAt->copy()->addMonths($reviewMonths),
            'financial_stage_score' => $financialScore,
            'financial_stage' => $stage,
            'stage_label' => $stageMeta['label'] ?? ucfirst($stage),
            'stage_diagnosis' => $stageMeta['diagnosis'] ?? '',
            'stage_phase' => $stageMeta['phase'] ?? '',
            'stage_emoji' => $stageMeta['emoji'] ?? '',
            'ftsa_chd' => $domainScores['chd'],
            'ftsa_rvd' => $domainScores['rvd'],
            'ftsa_ssd' => $domainScores['ssd'],
            'ftsa_esd' => $domainScores['esd'],
            'dominant_archetype' => $dominant['archetype'],
            'dominant_archetype_label' => $dominant['label'],
            'dominant_domain' => $dominant['domain'],
            'chd_level' => $domainLevels['chd']['label'],
            'rvd_level' => $domainLevels['rvd']['label'],
            'ssd_level' => $domainLevels['ssd']['label'],
            'esd_level' => $domainLevels['esd']['label'],
            'domain_scores' => $domainScores,
            'domain_levels' => $domainLevels,
            'answers' => $answers,
        ];
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public function scoreFinancialStage(array $answers): int
    {
        $total = 0;

        foreach (config('baseline_assessment.financial_stage.scored', []) as $question) {
            $key = $question['key'];
            $value = $answers['fs'][$key] ?? null;
            if (! is_string($value)) {
                continue;
            }
            $option = $question['options'][$value] ?? null;
            if ($option !== null) {
                $total += (int) $option['score'];
            }
        }

        return max(0, $total);
    }

    public function resolveStage(int $score): string
    {
        foreach (config('baseline_assessment.stage_thresholds', []) as $stage => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $stage;
            }
        }

        return 'surviving';
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, int>
     */
    public function scoreFtsaDomains(array $answers): array
    {
        $scores = ['chd' => 0, 'rvd' => 0, 'ssd' => 0, 'esd' => 0];

        foreach (config('baseline_assessment.ftsa_domains', []) as $domainKey => $domain) {
            foreach ($domain['questions'] as $qNum) {
                $raw = $answers['ftsa'][(string) $qNum] ?? $answers['ftsa'][$qNum] ?? null;
                if ($raw === null || $raw === '') {
                    continue;
                }
                $value = (int) $raw;
                $scores[$domainKey] += max(1, min(5, $value));
            }
        }

        return $scores;
    }

    /**
     * @param  array<string, int>  $domainScores
     * @return array{domain: string, archetype: string, label: string, score: int}
     */
    public function resolveDominantArchetype(array $domainScores): array
    {
        $domains = config('baseline_assessment.ftsa_domains', []);
        $maxScore = max($domainScores);
        $candidates = array_keys(array_filter($domainScores, fn (int $s) => $s === $maxScore));
        $domain = $candidates[0];
        $meta = $domains[$domain] ?? [];

        return [
            'domain' => $domain,
            'archetype' => $meta['archetype'] ?? $domain,
            'label' => $meta['archetype_label'] ?? ucfirst($domain),
            'score' => $maxScore,
        ];
    }

    /**
     * @return array{key: string, label: string}
     */
    public function dysregulationLevel(int $score): array
    {
        foreach (config('baseline_assessment.dysregulation_levels', []) as $level) {
            if ($score >= $level['min'] && $score <= $level['max']) {
                return ['key' => $level['key'], 'label' => $level['label']];
            }
        }

        return ['key' => 'stable', 'label' => 'Regulasi Stabil'];
    }

    /**
     * @return array<string, string>
     */
    public function validationRules(): array
    {
        $rules = [];

        foreach (config('baseline_assessment.financial_stage.profile', []) as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        foreach (config('baseline_assessment.financial_stage.scored', []) as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        for ($i = 1; $i <= 32; $i++) {
            $rules["ftsa.{$i}"] = 'required|integer|min:1|max:5';
        }

        $rules['snapshot.current_goal'] = 'nullable|string|max:512';
        $rules['snapshot.avg_monthly_income'] = 'nullable|integer|min:0';
        $rules['snapshot.emergency_fund'] = 'nullable|integer|min:0';
        $rules['snapshot.cash_savings'] = 'nullable|integer|min:0';
        $rules['snapshot.total_investment'] = 'nullable|integer|min:0';
        $rules['snapshot.total_asset'] = 'nullable|integer|min:0';
        $rules['snapshot.total_debt'] = 'nullable|integer|min:0';
        $rules['snapshot.has_bpjs'] = 'sometimes|boolean';
        $rules['snapshot.has_health_insurance'] = 'sometimes|boolean';
        $rules['snapshot.has_income_protection'] = 'sometimes|boolean';
        $rules['snapshot.has_life_insurance'] = 'sometimes|boolean';

        return $rules;
    }
}
