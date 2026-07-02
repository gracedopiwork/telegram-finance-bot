<?php

namespace App\Services;

use App\Models\DiagnosticQuestion;
use App\Models\FinancialBaseline;

class DiagnosticAnswerSummaryService
{
    public function __construct(
        private readonly DiagnosticConfigService $diagnosticConfig,
    ) {}

    public function resolvedEmail(FinancialBaseline $baseline): ?string
    {
        $email = $baseline->email;
        if (is_string($email) && trim($email) !== '') {
            return strtolower(trim($email));
        }

        $answers = $baseline->answers_json;
        if (! is_array($answers)) {
            return null;
        }

        $fromAnswers = $answers['email'] ?? null;

        return is_string($fromAnswers) && trim($fromAnswers) !== ''
            ? strtolower(trim($fromAnswers))
            : null;
    }

    /**
     * @return list<array{
     *     step: int,
     *     question_key: string,
     *     question: string,
     *     note: ?string,
     *     is_scored: bool,
     *     answer_key: string,
     *     answer_label: string,
     *     score: ?int
     * }>
     */
    public function summarize(FinancialBaseline $baseline): array
    {
        $answers = $baseline->answers_json;
        if (! is_array($answers)) {
            return [];
        }

        $fs = $answers['fs'] ?? [];
        if (! is_array($fs) || $fs === []) {
            return [];
        }

        $questionMap = $this->questionMap();
        $rows = [];

        foreach ($questionMap as $questionKey => $meta) {
            $selectedKey = $fs[$questionKey] ?? null;
            if (! is_string($selectedKey) || $selectedKey === '') {
                continue;
            }

            $optionLabel = $meta['options'][$selectedKey]['label'] ?? $selectedKey;
            $score = $meta['is_scored']
                ? ($meta['options'][$selectedKey]['score'] ?? null)
                : null;

            $rows[] = [
                'step' => (int) $meta['wizard_step'],
                'question_key' => $questionKey,
                'question' => $meta['text'],
                'note' => $meta['note'],
                'is_scored' => (bool) $meta['is_scored'],
                'answer_key' => $selectedKey,
                'answer_label' => $optionLabel,
                'score' => $score !== null ? (int) $score : null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, array{
     *     wizard_step: int,
     *     text: string,
     *     note: ?string,
     *     is_scored: bool,
     *     options: array<string, array{label: string, score?: int}>
     * }>
     */
    private function questionMap(): array
    {
        if ($this->diagnosticConfig->usesDatabase()) {
            $questions = DiagnosticQuestion::query()
                ->with('options')
                ->orderBy('wizard_step')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($questions->isNotEmpty()) {
                $map = [];
                foreach ($questions as $question) {
                    $options = [];
                    foreach ($question->options as $opt) {
                        $options[$opt->option_key] = [
                            'label' => $opt->label,
                            'score' => $opt->score,
                        ];
                    }
                    $map[$question->question_key] = [
                        'wizard_step' => (int) $question->wizard_step,
                        'text' => $question->text,
                        'note' => $question->note,
                        'is_scored' => $question->is_scored,
                        'options' => $options,
                    ];
                }

                return $map;
            }
        }

        $map = [];
        foreach ((array) config('diagnostic_questions_canonical.questions', []) as $q) {
            $options = [];
            foreach ($q['options'] ?? [] as $key => $val) {
                if (($q['is_scored'] ?? false) && is_array($val)) {
                    $options[$key] = [
                        'label' => $val['label'],
                        'score' => $val['score'] ?? 0,
                    ];
                } else {
                    $options[$key] = ['label' => is_string($val) ? $val : (string) $key];
                }
            }
            $map[$q['question_key']] = [
                'wizard_step' => (int) ($q['wizard_step'] ?? 1),
                'text' => $q['text'],
                'note' => $q['note'] ?? null,
                'is_scored' => (bool) ($q['is_scored'] ?? false),
                'options' => $options,
            ];
        }

        return $map;
    }
}
