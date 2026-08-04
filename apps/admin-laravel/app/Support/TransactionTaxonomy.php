<?php

namespace App\Support;

/**
 * YFD First Aid — jenis transaksi & sifat (taxonomy v1.3 + §5 Piutang).
 *
 * Jenis: Pemasukan | Pengeluaran | Saving/Investment | Kewajiban Pajak | Piutang Keluar | Piutang Masuk
 * Sifat: Need | Wants (hanya dua nilai)
 *
 * Kewajiban Pajak & Piutang dikecualikan dari 4 bucket.
 * Piutang Masuk bukan pemasukan baru — menutup Piutang Keluar.
 */
class TransactionTaxonomy
{
    public const TYPE_INCOME = 'Pemasukan';

    public const TYPE_EXPENSE = 'Pengeluaran';

    public const TYPE_SAVING = 'Saving/Investment';

    public const TYPE_TAX = 'Kewajiban Pajak';

    public const TYPE_RECEIVABLE_OUT = 'Piutang Keluar';

    public const TYPE_RECEIVABLE_IN = 'Piutang Masuk';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_INCOME,
        self::TYPE_EXPENSE,
        self::TYPE_SAVING,
        self::TYPE_TAX,
        self::TYPE_RECEIVABLE_OUT,
        self::TYPE_RECEIVABLE_IN,
    ];

    public const NATURE_NEED = 'Need';

    public const NATURE_WANTS = 'Wants';

    /** @var list<string> */
    public const NATURES = [
        self::NATURE_NEED,
        self::NATURE_WANTS,
    ];

    public static function isExcludedFromBuckets(string $type): bool
    {
        return in_array($type, [
            self::TYPE_INCOME,
            self::TYPE_TAX,
            self::TYPE_RECEIVABLE_OUT,
            self::TYPE_RECEIVABLE_IN,
        ], true);
    }

    public static function isReceivable(string $type): bool
    {
        return in_array($type, [self::TYPE_RECEIVABLE_OUT, self::TYPE_RECEIVABLE_IN], true);
    }

    /**
     * @return array{type: string, nature: string, category: ?string}
     */
    public static function normalize(string $typeInput, string $natureInput, ?string $category = null): array
    {
        $type = self::normalizeType($typeInput);
        $natureRaw = trim($natureInput);
        $natureKey = mb_strtolower($natureRaw);

        if (in_array($natureKey, ['saving/investement', 'saving/investment', 'saving', 'investasi', 'investment'], true)) {
            $type = self::TYPE_SAVING;
            $natureRaw = self::NATURE_NEED;
        }

        if (in_array($natureKey, ['donation', 'donasi', 'sedekah', 'persembahan'], true)) {
            $type = self::TYPE_EXPENSE;
            $category = $category && trim($category) !== '' ? $category : 'Social';
            if (mb_strtolower(trim($category)) === 'jajan') {
                $category = 'Social';
            }
            $natureRaw = in_array($natureKey, ['donation', 'donasi'], true) ? self::NATURE_NEED : $natureRaw;
        }

        if ($type === null) {
            $typeKey = mb_strtolower(trim($typeInput));
            if (in_array($typeKey, ['saving/investement', 'saving/investment', 'saving', 'investasi', 'investment', 'nabung'], true)) {
                $type = self::TYPE_SAVING;
            }
        }

        $type ??= self::TYPE_EXPENSE;

        $nature = self::normalizeNature($natureRaw);

        return [
            'type' => $type,
            'nature' => $nature,
            'category' => $category,
        ];
    }

    public static function normalizeType(?string $value): ?string
    {
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['pemasukan', 'income', 'masuk'], true)) {
            return self::TYPE_INCOME;
        }
        if (in_array($v, ['pengeluaran', 'expense', 'keluar'], true)) {
            return self::TYPE_EXPENSE;
        }
        if (in_array($v, ['saving/investement', 'saving/investment', 'saving', 'investasi', 'investment', 'nabung'], true)) {
            return self::TYPE_SAVING;
        }
        if (in_array($v, ['kewajiban pajak', 'pajak', 'tax', 'pph', 'pph 25', 'pph 29', 'pph 28a'], true)) {
            return self::TYPE_TAX;
        }
        if (in_array($v, ['piutang keluar', 'piutang out', 'receivable out', 'pinjaman keluar', 'pinjamin'], true)) {
            return self::TYPE_RECEIVABLE_OUT;
        }
        if (in_array($v, ['piutang masuk', 'piutang in', 'receivable in', 'pelunasan piutang', 'pengembalian piutang'], true)) {
            return self::TYPE_RECEIVABLE_IN;
        }
        foreach (self::TYPES as $type) {
            if (strcasecmp($type, (string) $value) === 0) {
                return $type;
            }
        }

        return null;
    }

    public static function normalizeNature(?string $value): string
    {
        $v = trim((string) $value);
        if (strcasecmp($v, self::NATURE_WANTS) === 0 || strcasecmp($v, 'want') === 0) {
            return self::NATURE_WANTS;
        }
        if (strcasecmp($v, self::NATURE_NEED) === 0 || strcasecmp($v, 'needs') === 0) {
            return self::NATURE_NEED;
        }

        return self::NATURE_NEED;
    }

    public static function mappingTransactionType(string $type): string
    {
        return match ($type) {
            self::TYPE_INCOME => 'income',
            self::TYPE_SAVING => 'saving',
            self::TYPE_TAX => 'tax',
            self::TYPE_RECEIVABLE_OUT => 'receivable_out',
            self::TYPE_RECEIVABLE_IN => 'receivable_in',
            default => 'expense',
        };
    }
}
