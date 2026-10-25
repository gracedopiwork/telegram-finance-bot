<?php

namespace App\Services\ConsultationPricing;

use Illuminate\Validation\ValidationException;

/**
 * Memilih aturan tarif untuk (service_type, session_role) dari
 * config('consultation_pricing.services'). Kombinasi tak dikenal ditolak —
 * tidak ada fallback ke tarif lain.
 */
class ConsultationPriceRules
{
    public function __construct(private readonly FhcuStatusLookup $fhcu) {}

    public function for(string $serviceType, ?string $sessionRole): PriceRule
    {
        $services = (array) config('consultation_pricing.services', []);
        $config = $services[$serviceType][$sessionRole ?? 'default'] ?? null;
        if (! is_array($config)) {
            throw $this->unknownService();
        }

        $label = (string) ($config['label'] ?? '');

        return match ($config['rule'] ?? null) {
            'fixed' => new FixedPriceRule((int) ($config['price'] ?? 0), $label),
            'fhcu_stage' => new FhcuStagePriceRule($this->fhcu, $label),
            default => throw $this->unknownService(),
        };
    }

    private function unknownService(): ValidationException
    {
        return ValidationException::withMessages([
            'service_type' => 'Layanan tidak dikenal.',
        ]);
    }
}
