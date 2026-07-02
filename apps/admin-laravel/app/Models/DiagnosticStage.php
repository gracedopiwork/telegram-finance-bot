<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticStage extends Model
{
    protected $fillable = [
        'stage_key',
        'label',
        'emoji',
        'phase',
        'diagnosis',
        'risk_label',
        'risk_description',
        'panel_color',
        'illustration_url',
        'score_min',
        'score_max',
        'sort_order',
    ];

    public static function forScore(int $score): ?self
    {
        return self::query()
            ->where('score_min', '<=', $score)
            ->where('score_max', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }

    public static function forKey(string $stageKey): ?self
    {
        return self::query()->where('stage_key', $stageKey)->first();
    }
}
