<?php

namespace App\Services\ConsultationPricing;

/**
 * Tarif tetap dari config — tidak melihat email / FHCU.
 */
final class FixedPriceRule implements PriceRule
{
    public function __construct(
        private readonly int $amount,
        private readonly string $label,
    ) {}

    public function quote(string $email): PriceQuote
    {
        return new PriceQuote($this->amount, $this->label);
    }

    public function display(): PriceDisplay
    {
        return PriceDisplay::fixed($this->amount);
    }

    public function requiresFhcu(): bool
    {
        return false;
    }
}
