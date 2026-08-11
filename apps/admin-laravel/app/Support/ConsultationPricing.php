<?php

namespace App\Support;

use App\Models\FinancialBaseline;

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
            foreach (['price_min', 'price_max', 'session_price', 'overtime_price'] as $field) {
                $n = SiteCopy::int($prefix.$field, 0);
                if ($n > 0) {
                    $stages[$key][$field] = $n;
                }
            }
            if (! isset($stages[$key]['session_price'])) {
                $stages[$key]['session_price'] = (int) ($stages[$key]['price_min'] ?? 0);
            }
            if (! isset($stages[$key]['overtime_price'])) {
                $stages[$key]['overtime_price'] = (int) ($stages[$key]['session_price'] ?? 0);
            }
        }

        return $stages;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forStage(?string $stageKey): ?array
    {
        $key = self::normalizeStageKey($stageKey);
        if ($key === null) {
            return null;
        }

        $tier = self::stages()[$key] ?? null;

        return is_array($tier) ? $tier : null;
    }

    public static function normalizeStageKey(?string $stage): ?string
    {
        $raw = strtolower(trim((string) $stage));
        if ($raw === '') {
            return null;
        }

        if (isset(self::stages()[$raw])) {
            return $raw;
        }

        return match (true) {
            str_contains($raw, 'surviv') => 'surviving',
            str_contains($raw, 'grow') => 'growing',
            str_contains($raw, 'stead') => 'steady',
            str_contains($raw, 'comfort') => 'comfortable',
            default => null,
        };
    }

    public static function sessionAmount(?string $stageKey): int
    {
        $tier = self::forStage($stageKey);

        return (int) ($tier['session_price'] ?? $tier['price_min'] ?? 0);
    }

    public static function overtimeAmount(?string $stageKey): int
    {
        $tier = self::forStage($stageKey);

        return (int) ($tier['overtime_price'] ?? 0);
    }

    public static function overtimeDisclosure(): string
    {
        $custom = SiteCopy::get('pricing.overtime_disclosure', '');
        if ($custom !== '') {
            return $custom;
        }

        return (string) (config('consultation_pricing.overtime_disclosure') ?? '');
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
        $session = (int) ($tier['session_price'] ?? $tier['price_min'] ?? 0);
        $ot = (int) ($tier['overtime_price'] ?? 0);
        if ($ot > 0) {
            return self::formatRupiah($session).' /jam · OT '.self::formatRupiah($ot).'/jam';
        }

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

    /**
     * @return array{valid: bool, status: string, baseline: ?FinancialBaseline, stage_key: ?string, message: string}
     */
    public static function fhcuStatusForEmail(?string $email): array
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return [
                'valid' => false,
                'status' => 'missing_email',
                'baseline' => null,
                'stage_key' => null,
                'message' => 'Email diperlukan untuk memverifikasi Financial Health Check-Up.',
            ];
        }

        $baseline = FinancialBaseline::latestForEmail($email);
        if ($baseline === null) {
            return [
                'valid' => false,
                'status' => 'missing',
                'baseline' => null,
                'stage_key' => null,
                'message' => 'Belum ada data FHCU. Selesaikan Financial Health Check-Up terlebih dahulu.',
            ];
        }

        if ($baseline->isReviewDue()) {
            return [
                'valid' => false,
                'status' => 'expired',
                'baseline' => $baseline,
                'stage_key' => self::normalizeStageKey($baseline->financial_stage ?: $baseline->stage_label),
                'message' => 'Data FHCU sudah lebih dari 3 bulan. Silakan isi ulang Check-Up sebelum booking.',
            ];
        }

        $stageKey = self::normalizeStageKey($baseline->financial_stage ?: $baseline->stage_label);
        if ($stageKey === null) {
            return [
                'valid' => false,
                'status' => 'no_stage',
                'baseline' => $baseline,
                'stage_key' => null,
                'message' => 'Tahap finansial dari FHCU belum terdeteksi. Hubungi admin YFD atau isi ulang Check-Up.',
            ];
        }

        return [
            'valid' => true,
            'status' => 'valid',
            'baseline' => $baseline,
            'stage_key' => $stageKey,
            'message' => 'FHCU valid. Tarif mengikuti tahap '.$stageKey.'.',
        ];
    }
}
