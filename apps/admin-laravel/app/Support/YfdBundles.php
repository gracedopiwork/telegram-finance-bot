<?php

namespace App\Support;

/**
 * Bundle layanan (recovery / education / premarital) — config + override Site Settings.
 */
final class YfdBundles
{
    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        $bundle = config("yfd_bundles.{$key}");
        if (! is_array($bundle)) {
            return null;
        }

        $prefix = "bundle.{$key}.";

        foreach (['eyebrow', 'title', 'description', 'features_label', 'footnote', 'number', 'icon'] as $field) {
            $override = SiteCopy::get($prefix.$field, '');
            if ($override !== '') {
                $bundle[$field] = $override;
            }
        }

        $features = SiteCopy::lines($prefix.'features', []);
        if ($features !== []) {
            $bundle['features'] = $features;
        }

        $ctaPrimary = SiteCopy::get($prefix.'cta_primary_label', '');
        if ($ctaPrimary !== '' && isset($bundle['cta_primary']) && is_array($bundle['cta_primary'])) {
            $bundle['cta_primary']['label'] = $ctaPrimary;
        }

        $ctaSecondary = SiteCopy::get($prefix.'cta_secondary_label', '');
        if ($ctaSecondary !== '' && isset($bundle['cta_secondary']) && is_array($bundle['cta_secondary'])) {
            $bundle['cta_secondary']['label'] = $ctaSecondary;
        }

        // Pricing rows: "Label|amount|note" per line (amount kosong = Sesuai kebutuhan)
        $pricingLines = SiteCopy::lines($prefix.'pricing_rows', []);
        if ($pricingLines !== []) {
            $pricing = [];
            foreach ($pricingLines as $line) {
                $parts = array_map('trim', explode('|', $line, 3));
                $amountRaw = $parts[1] ?? '';
                $amount = $amountRaw === '' ? null : (int) preg_replace('/[^\d]/', '', $amountRaw);
                $pricing[] = [
                    'label' => $parts[0] !== '' ? $parts[0] : 'Tarif',
                    'amount' => $amount && $amount > 0 ? $amount : null,
                    'note' => $parts[2] ?? '',
                ];
            }
            $bundle['pricing'] = $pricing;
        }

        return $bundle;
    }
}
