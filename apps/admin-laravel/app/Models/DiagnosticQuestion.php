<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticQuestion extends Model
{
    protected $fillable = [
        'question_key',
        'wizard_step',
        'section',
        'text',
        'note',
        'is_scored',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_scored' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(DiagnosticQuestionOption::class)->orderBy('sort_order');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAssessmentArray(): array
    {
        $options = [];
        foreach ($this->options as $opt) {
            if ($this->is_scored) {
                $options[$opt->option_key] = [
                    'label' => $opt->label,
                    'score' => (int) ($opt->score ?? 0),
                ];
            } else {
                $options[$opt->option_key] = $opt->label;
            }
        }

        return [
            'key' => $this->question_key,
            'wizard_step' => (int) $this->wizard_step,
            'section' => $this->section,
            'text' => $this->text,
            'note' => $this->note,
            'options' => $options,
        ];
    }
}
