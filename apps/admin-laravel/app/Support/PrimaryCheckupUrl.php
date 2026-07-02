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
        $fallback = self::fallbackPortalLoginUrl();

        try {
            $raw = trim((string) (Setting::val('hero.cta_primary_url') ?? ''));
        } catch (\Throwable) {
            $raw = '';
        }

        // Semua CTA Financial Check-Up diarahkan ke diagnostik internal portal.
        // Nilai setting lama (mis. Typeform) sengaja diabaikan.
        if ($raw !== '') {
            return ['url' => $fallback, 'new_tab' => false];
        }

        if ($raw === '') {
            return ['url' => $fallback, 'new_tab' => false];
        }
        return ['url' => $fallback, 'new_tab' => false];
    }

    private static function fallbackPortalLoginUrl(): string
    {
        try {
            return route('portal.login');
        } catch (\Throwable) {
            return '#';
        }
    }
}
