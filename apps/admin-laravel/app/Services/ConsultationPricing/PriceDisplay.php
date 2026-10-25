<?php

namespace App\Services\ConsultationPricing;

/**
 * Tarif untuk ditampilkan: satu nominal tetap, atau daftar tarif per tahap FHCU.
 */
final readonly class PriceDisplay
{
    /**
     * @param  list<array{key: string, label: string, amount: int}>  $stages
     */
    private function __construct(
        public ?int $amount,
        public array $stages,
    ) {}

    public static function fixed(int $amount): self
    {
        return new self($amount, []);
    }

    /**
     * @param  list<array{key: string, label: string, amount: int}>  $stages
     */
    public static function byStage(array $stages): self
    {
        return new self(null, $stages);
    }

    public function isFixed(): bool
    {
        return $this->amount !== null;
    }
}
