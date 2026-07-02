<?php

namespace App\Support;

use App\Models\Setting;

/**
 * URL utama untuk CTA "Mulai Health Check Up" / diagnosa — halaman check-up gratis di landing.
 */
final class PrimaryCheckupUrl
{
    /**
     * @return array{url: string, new_tab: bool}
     */
    public static function resolve(): array
    {
        try {
            return ['url' => route('checkup.show'), 'new_tab' => false];
        } catch (\Throwable) {
            try {
                $raw = trim((string) (Setting::val('hero.cta_primary_url') ?? ''));
            } catch (\Throwable) {
                $raw = '';
            }

            return ['url' => $raw !== '' ? $raw : '#', 'new_tab' => false];
        }
    }
}
