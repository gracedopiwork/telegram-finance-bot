<?php

namespace App\Services\ConsultationPricing;

/**
 * Tarif final satu booking. $label = nama produk di order;
 * $stageKey/$stageLabel hanya terisi bila tarif mengikuti tahap FHCU.
 */
final readonly class PriceQuote
{
    public function __construct(
        public int $amount,
        public string $label,
        public ?string $stageKey = null,
        public ?string $stageLabel = null,
    ) {}
}
