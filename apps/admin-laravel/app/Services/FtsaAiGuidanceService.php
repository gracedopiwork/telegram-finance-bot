<?php

namespace App\Services;

use App\Models\FinancialBaseline;

/**
 * @deprecated Use PortalAiGuidanceService directly
 */
class FtsaAiGuidanceService
{
    public function __construct(
        private readonly PortalAiGuidanceService $portalAi,
    ) {}

    /**
     * @return array{
     *     insights: list<string>,
     *     recommendations: list<string>,
     *     source: string,
     *     generated_at: ?string
     * }
     */
    public function forBaseline(?FinancialBaseline $baseline): array
    {
        return $this->portalAi->ftsaForBaseline($baseline);
    }
}
