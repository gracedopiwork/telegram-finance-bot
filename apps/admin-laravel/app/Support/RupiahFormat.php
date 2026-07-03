<?php

namespace App\Support;

class RupiahFormat
{
    public static function format(?int $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Format angka polos di teks (mis. current goal "1000000") menjadi Rp 1.000.000.
     */
    public static function formatText(?string $value): string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return '—';
        }

        $digitsOnly = preg_replace('/\D/', '', $value);
        $compact = preg_replace('/[\s.,]/', '', $value);

        if ($digitsOnly !== '' && $compact !== '' && ctype_digit($compact)) {
            return self::format((int) $digitsOnly);
        }

        return $value;
    }
}
