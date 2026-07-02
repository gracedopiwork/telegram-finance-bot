<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticQuestionOption extends Model
{
    protected $fillable = [
        'diagnostic_question_id',
        'option_key',
        'label',
        'score',
        'sort_order',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(DiagnosticQuestion::class, 'diagnostic_question_id');
    }
}
