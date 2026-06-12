<?php

namespace App\Support;

final class PhoneNumber
{
    /**
     * Normalisasi ke digit internasional Indonesia (628…).
     */
    public static function normalizeIndonesia(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return trim($phone);
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return '62'.$digits;
    }

    public static function isValidIndonesiaMobile(string $phone): bool
    {
        $normalized = self::normalizeIndonesia($phone);

        return (bool) preg_match('/^628\d{8,12}$/', $normalized);
    }
}
