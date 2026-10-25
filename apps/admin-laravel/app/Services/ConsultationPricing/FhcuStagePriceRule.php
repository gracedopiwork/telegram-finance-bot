<?php

namespace App\Services\ConsultationPricing;

use App\Support\ConsultationPricing;
use Illuminate\Validation\ValidationException;

/**
 * Tarif mengikuti tahap FHCU customer. Nominal hanya dari
 * config('consultation_pricing.stages.*.session_price') — bukan dari Site Settings;
 * label tahap dari ConsultationPricing::stages() agar edit label admin tetap tampil.
 */
final class FhcuStagePriceRule implements PriceRule
{
    public function __construct(
        private readonly FhcuStatusLookup $fhcu,
        private readonly string $label,
    ) {}

    public function quote(string $email): PriceQuote
    {
        $status = $this->fhcu->forEmail($email);

        if (in_array($status['status'], ['missing', 'expired'], true)) {
            throw new FhcuRequiredException($status['status'], $status['message']);
        }

        if (! $status['valid']) {
            throw ValidationException::withMessages(['email' => $status['message']]);
        }

        $stageKey = (string) $status['stage_key'];
        $amount = $this->amountFor($stageKey);
        if ($amount < 1) {
            throw ValidationException::withMessages([
                'stage' => 'Tahap finansial / tarif sesi tidak valid.',
            ]);
        }

        $stageLabel = $this->stageLabels()[$stageKey] ?? ucfirst($stageKey);

        return new PriceQuote($amount, $this->label.' ('.$stageLabel.')', $stageKey, $stageLabel);
    }

    public function display(): PriceDisplay
    {
        $labels = $this->stageLabels();
        $rows = [];
        foreach (array_keys((array) config('consultation_pricing.stages', [])) as $key) {
            $rows[] = [
                'key' => (string) $key,
                'label' => $labels[$key] ?? ucfirst((string) $key),
                'amount' => $this->amountFor((string) $key),
            ];
        }

        return PriceDisplay::byStage($rows);
    }

    public function requiresFhcu(): bool
    {
        return true;
    }

    private function amountFor(string $stageKey): int
    {
        $stages = (array) config('consultation_pricing.stages', []);

        return (int) ($stages[$stageKey]['session_price'] ?? 0);
    }

    /**
     * @return array<string, string>
     */
    private function stageLabels(): array
    {
        $labels = [];
        foreach (ConsultationPricing::stages() as $key => $tier) {
            $labels[$key] = (string) ($tier['label'] ?? ucfirst((string) $key));
        }

        return $labels;
    }
}
