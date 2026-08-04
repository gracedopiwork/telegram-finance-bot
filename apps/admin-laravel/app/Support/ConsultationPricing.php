<?php

namespace App\Support;

final class ConsultationPricing
{
    /**
     * @return array<string, mixed>
     */
    public static function meta(): array
    {
        $meta = config('consultation_pricing', []);
        if (! is_array($meta)) {
            $meta = [];
        }

        $period = SiteCopy::get('pricing.period', '');
        if ($period !== '') {
            $meta['period'] = $period;
        }
        $note = SiteCopy::get('pricing.multi_session_note', '');
        if ($note !== '') {
            $meta['multi_session_note'] = $note;
        }
        $std = SiteCopy::int('pricing.standard_from', 0);
        if ($std > 0) {
            $meta['standard_from'] = $std;
        }
        $rec = SiteCopy::int('pricing.recovery_from', 0);
        if ($rec > 0) {
            $meta['recovery_from'] = $rec;
        }

        return $meta;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function stages(): array
    {
        $stages = config('consultation_pricing.stages', []);
        if (! is_array($stages)) {
            return [];
        }

        foreach (array_keys($stages) as $key) {
            $prefix = "pricing.stage.{$key}.";
            foreach (['label', 'phase', 'description'] as $field) {
                $v = SiteCopy::get($prefix.$field, '');
                if ($v !== '') {
                    $stages[$key][$field] = $v;
                }
            }
            $min = SiteCopy::int($prefix.'price_min', 0);
            if ($min > 0) {
                $stages[$key]['price_min'] = $min;
            }
            $max = SiteCopy::int($prefix.'price_max', 0);
            if ($max > 0) {
                $stages[$key]['price_max'] = $max;
            }
        }

        return $stages;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forStage(?string $stageKey): ?array
    {
        if ($stageKey === null || $stageKey === '') {
            return null;
        }

        $tier = self::stages()[$stageKey] ?? null;

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
