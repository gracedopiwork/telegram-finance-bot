<?php

namespace Database\Seeders;

use App\Models\DiagnosticQuestion;
use App\Models\DiagnosticQuestionOption;
use App\Models\DiagnosticStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DiagnosticContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedStages();
        $this->syncCanonicalQuestions();
        $this->syncFtsaQuestions();
    }

    public function syncFtsaQuestions(): void
    {
        if (! Schema::hasTable('ftsa_questions')) {
            return;
        }

        app(\App\Services\FtsaConfigService::class)->syncFromConfig();
    }

    public function syncCanonicalQuestions(): void
    {
        if (! Schema::hasTable('diagnostic_questions')) {
            return;
        }

        $canonical = (array) config('diagnostic_questions_canonical.questions', []);
        if ($canonical === []) {
            return;
        }

        $keys = collect($canonical)->pluck('question_key')->all();
        DiagnosticQuestion::query()->whereNotIn('question_key', $keys)->delete();

        foreach ($canonical as $q) {
            $question = DiagnosticQuestion::query()->updateOrCreate(
                ['question_key' => $q['question_key']],
                [
                    'wizard_step' => (int) ($q['wizard_step'] ?? 1),
                    'section' => $q['section'],
                    'text' => $q['text'],
                    'note' => $q['note'] ?? null,
                    'is_scored' => (bool) ($q['is_scored'] ?? true),
                    'sort_order' => (int) ($q['sort_order'] ?? 0),
                    'is_active' => true,
                ]
            );

            $question->options()->delete();

            $optSort = 0;
            foreach ($q['options'] ?? [] as $optionKey => $optionValue) {
                $isScored = (bool) ($q['is_scored'] ?? true);
                if ($isScored && is_array($optionValue)) {
                    DiagnosticQuestionOption::query()->create([
                        'diagnostic_question_id' => $question->id,
                        'option_key' => $optionKey,
                        'label' => $optionValue['label'],
                        'score' => $optionValue['score'] ?? 0,
                        'sort_order' => $optSort++,
                    ]);
                } else {
                    DiagnosticQuestionOption::query()->create([
                        'diagnostic_question_id' => $question->id,
                        'option_key' => $optionKey,
                        'label' => is_string($optionValue) ? $optionValue : (string) $optionKey,
                        'score' => null,
                        'sort_order' => $optSort++,
                    ]);
                }
            }
        }
    }

    private function seedStages(): void
    {
        $defaults = [
            'surviving' => [
                'label' => 'Surviving',
                'emoji' => '🟥',
                'phase' => 'Fase 1',
                'diagnosis' => 'Financial Emergency Stage',
                'risk_label' => 'Risiko keuangan',
                'risk_description' => 'Kondisi keuangan masih dalam tahap darurat. Pemasukan dan pengeluaran perlu dikendalikan segera agar tidak semakin tertekan.',
                'panel_color' => '#F4A6A6',
                'score_min' => 0,
                'score_max' => 12,
                'sort_order' => 1,
            ],
            'growing' => [
                'label' => 'Growing',
                'emoji' => '🟨',
                'phase' => 'Fase 2',
                'diagnosis' => 'Financial Recovery & Structuring Stage',
                'risk_label' => 'Risiko keuangan',
                'risk_description' => 'Kamu sudah lebih bisa menghadapi krisis finansial, namun tetap rentan pada krisis yang besar.',
                'panel_color' => '#7EC8C8',
                'score_min' => 13,
                'score_max' => 22,
                'sort_order' => 2,
            ],
            'steady' => [
                'label' => 'Steady',
                'emoji' => '🟩',
                'phase' => 'Fase 3',
                'diagnosis' => 'Financial Stable and Accumulation Stage',
                'risk_label' => 'Risiko keuangan',
                'risk_description' => 'Fondasi keuangan relatif stabil. Fokus berikutnya adalah konsistensi menabung dan melindungi aset jangka panjang.',
                'panel_color' => '#8FD9A8',
                'score_min' => 23,
                'score_max' => 33,
                'sort_order' => 3,
            ],
            'comfortable' => [
                'label' => 'Comfortable',
                'emoji' => '🟦',
                'phase' => 'Fase 4',
                'diagnosis' => 'Financial Freedom and Stewardship Stage',
                'risk_label' => 'Risiko keuangan',
                'risk_description' => 'Kondisi keuangan kuat. Pertahankan disiplin dan arahkan kekayaan untuk tujuan jangka panjang serta dampak positif.',
                'panel_color' => '#8BB8E8',
                'score_min' => 34,
                'score_max' => 39,
                'sort_order' => 4,
            ],
        ];

        foreach ($defaults as $key => $data) {
            DiagnosticStage::query()->updateOrCreate(
                ['stage_key' => $key],
                array_merge($data, ['stage_key' => $key])
            );
        }
    }
}
