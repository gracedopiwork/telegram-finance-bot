<?php

namespace App\Support;

final class ConsultationPricing
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function stages(): array
    {
        return config('consultation_pricing.stages', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forStage(?string $stageKey): ?array
    {
        if ($stageKey === null || $stageKey === '') {
            return null;
        }

        $tier = config("consultation_pricing.stages.{$stageKey}");

        return is_array($tier) ? $tier : null;
    }

    public static function formatRupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * @param  array<string, mixed>  $tier
     */
    public static function formatRange(array $tier): string
    {
        $min = (int) ($tier['price_min'] ?? 0);
        $max = (int) ($tier['price_max'] ?? $min);

        if ($max <= 0 || $min === $max) {
            return self::formatRupiah($min);
        }

        return self::formatRupiah($min).' – '.self::formatRupiah($max);
    }

    public static function bookingUrl(?string $stageKey = null, string $consultationType = 'standard'): string
    {
        $params = array_filter([
            'stage' => $stageKey,
            'type' => $consultationType !== 'standard' ? $consultationType : null,
        ]);

        return route('company.pertemuan', $params);
    }
}
