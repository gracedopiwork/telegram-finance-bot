<?php

namespace App\Services\ConsultationPricing;

/**
 * Tarif tetap dari config. Tahap FHCU tidak pernah memengaruhi nominal; bila
 * $requiresFhcu, email tetap wajib punya FHCU yang berlaku (mis. Recovery).
 */
final class FixedPriceRule implements PriceRule
{
    public function __construct(
        private readonly int $amount,
        private readonly string $label,
        private readonly FhcuStatusLookup $fhcu,
        private readonly bool $requiresFhcu,
    ) {}

    public function quote(string $email): PriceQuote
    {
        if ($this->requiresFhcu) {
            $this->fhcu->requireFor($email);
        }

        return new PriceQuote($this->amount, $this->label);
    }

    public function display(): PriceDisplay
    {
        return PriceDisplay::fixed($this->amount);
    }

    public function requiresFhcu(): bool
    {
        return $this->requiresFhcu;
    }
}
