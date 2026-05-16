<?php

namespace App\Support;

use App\Models\Setting;

/**
 * URL utama untuk CTA "Mulai Health Check Up" / diagnosa — sama dengan hero.cta_primary_url.
 */
final class PrimaryCheckupUrl
{
    /**
     * @return array{url: string, new_tab: bool}
     */
    public static function resolve(): array
    {
        $fallback = self::fallbackPaketUrl();

        try {
            $raw = trim((string) (Setting::val('hero.cta_primary_url') ?? ''));
        } catch (\Throwable) {
            $raw = '';
        }

        if ($raw === '') {
            return ['url' => $fallback, 'new_tab' => false];
        }

        if (preg_match('#^https?://#i', $raw)) {
            $host = parse_url($raw, PHP_URL_HOST);
            $newTab = $host && strcasecmp((string) $host, request()->getHost()) !== 0;

            return ['url' => $raw, 'new_tab' => $newTab];
        }

        return ['url' => url('/' . ltrim($raw, '/')), 'new_tab' => false];
    }

    private static function fallbackPaketUrl(): string
    {
        try {
            return route('company.paket');
        } catch (\Throwable) {
            return '#';
        }
    }
}
