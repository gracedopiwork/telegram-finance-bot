<?php

namespace App\Services;

use App\Models\DiagnosticQuestion;
use App\Models\DiagnosticStage;
use Illuminate\Support\Facades\Schema;

class DiagnosticConfigService
{
    /**
     * @return array{profile: list<array<string, mixed>>, scored: list<array<string, mixed>>}
     */
    public function financialStageQuestions(): array
    {
        if (! $this->usesDatabase()) {
            return (array) config('baseline_assessment.financial_stage', ['profile' => [], 'scored' => []]);
        }

        $questions = DiagnosticQuestion::query()
            ->with('options')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($questions->isEmpty()) {
            return (array) config('baseline_assessment.financial_stage', ['profile' => [], 'scored' => []]);
        }

        $profile = [];
        $scored = [];
        foreach ($questions as $question) {
            $item = $question->toAssessmentArray();
            if ($question->is_scored) {
                $scored[] = $item;
            } else {
                $profile[] = $item;
            }
        }

        return ['profile' => $profile, 'scored' => $scored];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function stageLabels(): array
    {
        if (! $this->usesDatabase()) {
            return (array) config('baseline_assessment.stage_labels', []);
        }

        $stages = DiagnosticStage::query()->orderBy('sort_order')->get();
        if ($stages->isEmpty()) {
            return (array) config('baseline_assessment.stage_labels', []);
        }

        $labels = [];
        foreach ($stages as $stage) {
            $labels[$stage->stage_key] = [
                'label' => $stage->label,
                'emoji' => $stage->emoji,
                'diagnosis' => $stage->diagnosis,
                'phase' => $stage->phase,
                'risk_label' => $stage->risk_label,
                'risk_description' => $stage->risk_description,
                'panel_color' => $stage->panel_color,
                'illustration_url' => $stage->illustration_url,
            ];
        }

        return $labels;
    }

    /**
     * @return array<string, array{min: int, max: int}>
     */
    public function stageThresholds(): array
    {
        if (! $this->usesDatabase()) {
            return (array) config('baseline_assessment.stage_thresholds', []);
        }

        $stages = DiagnosticStage::query()->orderBy('sort_order')->get();
        if ($stages->isEmpty()) {
            return (array) config('baseline_assessment.stage_thresholds', []);
        }

        $thresholds = [];
        foreach ($stages as $stage) {
            $thresholds[$stage->stage_key] = [
                'min' => (int) $stage->score_min,
                'max' => (int) $stage->score_max,
            ];
        }

        return $thresholds;
    }

    public function stageDisplay(string $stageKey, int $score): array
    {
        $labels = $this->stageLabels();
        $meta = $labels[$stageKey] ?? [];

        if ($this->usesDatabase()) {
            $row = DiagnosticStage::forKey($stageKey) ?? DiagnosticStage::forScore($score);
            if ($row !== null) {
                return [
                    'key' => $row->stage_key,
                    'label' => $row->label,
                    'emoji' => $row->emoji,
                    'phase' => $row->phase,
                    'diagnosis' => $row->diagnosis,
                    'risk_label' => $row->risk_label ?: 'Risiko keuangan',
                    'risk_description' => $row->risk_description,
                    'panel_color' => $row->panel_color ?: '#7EC8C8',
                    'illustration_url' => $row->illustration_url,
                ];
            }
        }

        return [
            'key' => $stageKey,
            'label' => $meta['label'] ?? ucfirst($stageKey),
            'emoji' => $meta['emoji'] ?? '',
            'phase' => $meta['phase'] ?? '',
            'diagnosis' => $meta['diagnosis'] ?? '',
            'risk_label' => $meta['risk_label'] ?? 'Risiko keuangan',
            'risk_description' => $meta['risk_description'] ?? ($meta['diagnosis'] ?? ''),
            'panel_color' => $meta['panel_color'] ?? '#7EC8C8',
            'illustration_url' => $meta['illustration_url'] ?? null,
        ];
    }

    public function usesDatabase(): bool
    {
        return Schema::hasTable('diagnostic_questions')
            && Schema::hasTable('diagnostic_stages');
    }

    /**
     * @return array<string, mixed>
     */
    public function fullBaselineConfig(): array
    {
        $config = config('baseline_assessment');
        if (! is_array($config)) {
            $config = [];
        }
        $config['financial_stage'] = $this->financialStageQuestions();

        return $config;
    }
}
