<?php

namespace App\Services;

use Carbon\Carbon;

class BaselineAssessmentService
{
    public function __construct(
        private readonly DiagnosticConfigService $diagnosticConfig,
    ) {}

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function assess(array $answers, bool $includeFtsa = true): array
    {
        $financialScore = $this->scoreFinancialStage($answers);
        $stage = $this->resolveStage($financialScore);
        $stageMeta = $this->diagnosticConfig->stageLabels()[$stage] ?? [];

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

        foreach ($this->financialStageScored() as $question) {
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
        foreach ($this->diagnosticConfig->stageThresholds() as $stage => $range) {
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
        if ($domainScores === []) {
            return [
                'domain' => '',
                'archetype' => 'unknown',
                'label' => 'Belum dinilai',
                'score' => 0,
            ];
        }

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

        foreach ($this->financialStageProfile() as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        foreach ($this->financialStageScored() as $q) {
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

    /**
     * Snapshot angka keuangan (Sheet 1A) — tanpa FS / FTSA.
     *
     * @return array<string, string>
     */
    public function validationRulesSnapshotOnly(): array
    {
        return [
            'snapshot.current_goal' => 'nullable|string|max:512',
            'snapshot.avg_monthly_income' => 'nullable|integer|min:0',
            'snapshot.emergency_fund' => 'nullable|integer|min:0',
            'snapshot.cash_savings' => 'nullable|integer|min:0',
            'snapshot.total_investment' => 'nullable|integer|min:0',
            'snapshot.total_asset' => 'nullable|integer|min:0',
            'snapshot.total_debt' => 'nullable|integer|min:0',
            'snapshot.has_bpjs' => 'sometimes|boolean',
            'snapshot.has_health_insurance' => 'sometimes|boolean',
            'snapshot.has_income_protection' => 'sometimes|boolean',
            'snapshot.has_life_insurance' => 'sometimes|boolean',
        ];
    }

    /**
     * Snapshot ringkas untuk pembeli FTSA-only (bukan baseline bot penuh).
     *
     * @return array<string, string>
     */
    public function validationRulesFtsaSnapshotOnly(): array
    {
        return [
            'snapshot.avg_monthly_income' => 'nullable|integer|min:0',
            'snapshot.emergency_fund' => 'nullable|integer|min:0',
            'snapshot.cash_savings' => 'nullable|integer|min:0',
            'snapshot.total_debt' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Kuesioner FTSA 1–32 saja.
     *
     * @return array<string, string>
     */
    public function validationRulesFtsaQuestionnaire(): array
    {
        $rules = [];
        for ($i = 1; $i <= 32; $i++) {
            $rules["ftsa.{$i}"] = 'required|integer|min:1|max:5';
        }

        return $rules;
    }

    /**
     * Portal pembeli FTSA saja — tanpa blok soal diagnostik tahap keuangan.
     *
     * @return array<string, string>
     */
    public function validationRulesFtsaOnly(bool $includeFtsa = true): array
    {
        $rules = [
            'snapshot.current_goal' => 'nullable|string|max:512',
            'snapshot.avg_monthly_income' => 'nullable|integer|min:0',
            'snapshot.emergency_fund' => 'nullable|integer|min:0',
            'snapshot.cash_savings' => 'nullable|integer|min:0',
            'snapshot.total_investment' => 'nullable|integer|min:0',
            'snapshot.total_asset' => 'nullable|integer|min:0',
            'snapshot.total_debt' => 'nullable|integer|min:0',
            'snapshot.has_bpjs' => 'sometimes|boolean',
            'snapshot.has_health_insurance' => 'sometimes|boolean',
            'snapshot.has_income_protection' => 'sometimes|boolean',
            'snapshot.has_life_insurance' => 'sometimes|boolean',
        ];

        if ($includeFtsa) {
            for ($i = 1; $i <= 32; $i++) {
                $rules["ftsa.{$i}"] = 'required|integer|min:1|max:5';
            }
        }

        return $rules;
    }

    /**
     * Aturan validasi untuk check-up gratis di landing (tahap keuangan saja + email).
     *
     * @return array<string, string>
     */
    public function validationRulesFinancialStageOnly(): array
    {
        $rules = ['email' => 'required|email|max:255'];

        foreach ($this->financialStageProfile() as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        foreach ($this->financialStageScored() as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        return $rules;
    }

    /**
     * Portal baseline — diagnostik tahap keuangan + snapshot (tanpa FTSA).
     *
     * @return array<string, string>
     */
    public function validationRulesBaselinePortal(): array
    {
        $rules = [];

        foreach ($this->financialStageProfile() as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        foreach ($this->financialStageScored() as $q) {
            $options = implode(',', array_keys($q['options']));
            $rules["fs.{$q['key']}"] = "required|in:{$options}";
        }

        return array_merge($rules, $this->validationRulesSnapshotOnly());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function financialStageProfile(): array
    {
        return $this->diagnosticConfig->financialStageQuestions()['profile'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function financialStageScored(): array
    {
        return $this->diagnosticConfig->financialStageQuestions()['scored'] ?? [];
    }
}
