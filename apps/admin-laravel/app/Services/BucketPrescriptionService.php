<?php

namespace App\Services;

use App\Models\FinancialBaseline;

class BucketPrescriptionService
{
    /** @var array<string, array<string, float>> */
    private const STAGE_IDEALS = [
        'surviving' => [
            'Essential Living' => 80.0,
            'Future Building' => 5.0,
            'Protection' => 5.0,
            'Flexible + Social' => 10.0,
        ],
        'growing' => [
            'Essential Living' => 60.0,
            'Future Building' => 20.0,
            'Protection' => 10.0,
            'Flexible + Social' => 10.0,
        ],
        'steady' => [
            'Essential Living' => 50.0,
            'Future Building' => 30.0,
            'Protection' => 10.0,
            'Flexible + Social' => 10.0,
        ],
        'comfortable' => [
            'Essential Living' => 40.0,
            'Future Building' => 35.0,
            'Protection' => 10.0,
            'Flexible + Social' => 15.0,
        ],
    ];

    private const DEFAULT_STAGE = 'growing';

    /**
     * @return array<string, float>
     */
    public function idealsForUser(int $telegramUserId): array
    {
        $baseline = FinancialBaseline::latestForUser($telegramUserId);
        $stage = $baseline?->financial_stage ?? self::DEFAULT_STAGE;

        return self::STAGE_IDEALS[$stage] ?? self::STAGE_IDEALS[self::DEFAULT_STAGE];
    }

    /**
     * @return array<string, float>
     */
    public function idealsForStage(string $stage): array
    {
        return self::STAGE_IDEALS[$stage] ?? self::STAGE_IDEALS[self::DEFAULT_STAGE];
    }

    public function stageMeta(string $stage): array
    {
        $labels = config('baseline_assessment.stage_labels', []);

        return [
            'key' => $stage,
            'label' => $labels[$stage]['label'] ?? ucfirst($stage),
            'emoji' => $labels[$stage]['emoji'] ?? '',
            'diagnosis' => $labels[$stage]['diagnosis'] ?? '',
        ];
    }
}
