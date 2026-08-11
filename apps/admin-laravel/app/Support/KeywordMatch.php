<?php

namespace App\Support;

/**
 * Phrase matching for taxonomy rules.
 * Short tokens (≤3 chars, no space) use letter-boundaries so "les" ≠ "sales"
 * and "idi" ≠ "pendidikan". Longer / multi-word phrases stay substring matches.
 */
final class KeywordMatch
{
    public static function contains(string $haystack, string $needle): bool
    {
        $haystack = mb_strtolower($haystack);
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }

        if (str_contains($needle, ' ') || mb_strlen($needle) > 3) {
            return str_contains($haystack, $needle);
        }

        // Batas huruf saja: "pph21" masih cocok "pph"; "sales" tidak cocok "les".
        return preg_match(
            '/(?<![\p{L}_])'.preg_quote($needle, '/').'(?![\p{L}_])/u',
            $haystack,
        ) === 1;
    }

    /**
     * @param  list<string>  $needles
     */
    public static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (self::contains($haystack, (string) $needle)) {
                return true;
            }
        }

        return false;
    }
}
