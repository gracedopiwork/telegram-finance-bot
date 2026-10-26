<?php

namespace App\Support;

use App\Services\ConsultationPricing\ConsultationPriceRules;

/**
 * Bundle layanan (konsultasi / recovery / education / premarital) — config + override Site Settings.
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
        } elseif ($ctaSecondary !== '' && empty($bundle['cta_secondary'])) {
            $bundle['cta_secondary'] = [
                'label' => $ctaSecondary,
                'type' => 'wa',
                'wa_topic' => (string) ($bundle['title'] ?? 'YFD'),
            ];
        }

        // Produk edukasi / kartu offerings: "Judul|status|icon|deskripsi" per baris
        // status: available | soon | free
        $offeringLines = SiteCopy::lines($prefix.'offerings', []);
        if ($offeringLines !== []) {
            $offerings = [];
            foreach ($offeringLines as $line) {
                $parts = array_map('trim', explode('|', $line, 4));
                if (($parts[0] ?? '') === '') {
                    continue;
                }
                $status = strtolower((string) ($parts[1] ?? 'soon'));
                if (! in_array($status, ['available', 'soon', 'free'], true)) {
                    $status = 'soon';
                }
                $row = [
                    'title' => $parts[0],
                    'status' => $status,
                    'icon' => ($parts[2] ?? '') !== '' ? $parts[2] : 'school',
                    'desc' => $parts[3] ?? '',
                ];
                if ($status === 'free') {
                    $row['route'] = 'company.wealthpedia';
                }
                $offerings[] = $row;
            }
            if ($offerings !== []) {
                $bundle['offerings'] = $offerings;
            }
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

        // Tarif per tahap FHCU: dari aturan yang sama dengan tagihan, menimpa override Site Settings
        // agar halaman tidak pernah berbeda dari yang ditagih.
        if (! empty($bundle['pricing_from_stages'])) {
            $bundle['pricing'] = array_map(
                fn (array $stage) => ['label' => $stage['label'], 'amount' => $stage['amount']],
                app(ConsultationPriceRules::class)->for('standard', null)->display()->stages,
            );
        }

        return $bundle;
    }
}
