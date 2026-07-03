<?php

namespace App\Services;

use App\Models\BotTransaction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TransactionImportService
{
    /** @var list<string> */
    private const VALID_TYPES = ['Pemasukan', 'Pengeluaran'];

    /** @var list<string> */
    private const VALID_NATURES = ['Need', 'Wants', 'Saving/Investement', 'Donation'];

    /** @var list<string> */
    private const VALID_MOODS = ['Happy', 'Neutral', 'Sad', 'Stressed', 'Angry', 'Tired'];

    private const MAX_ROWS = 500;

    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'tanggal' => 'recorded_at',
        'date' => 'recorded_at',
        'recorded_at' => 'recorded_at',
        'waktu' => 'recorded_at',
        'jenis' => 'type',
        'type' => 'type',
        'kategori' => 'category',
        'category' => 'category',
        'sub_kategori' => 'sub_category',
        'sub_kategory' => 'sub_category',
        'sub_category' => 'sub_category',
        'sub' => 'sub_category',
        'nominal' => 'amount',
        'amount' => 'amount',
        'jumlah' => 'amount',
        'sifat' => 'nature',
        'nature' => 'nature',
        'mood' => 'mood',
        'impulsif' => 'is_impulsive',
        'is_impulsive' => 'is_impulsive',
        'impulsive' => 'is_impulsive',
        'keterangan' => 'notes',
        'notes' => 'notes',
        'catatan' => 'notes',
    ];

    /** @var array<string, mixed>|null */
    private ?array $categoryRules = null;

    /** @var array<string, string> */
    private const CATEGORY_ALIASES = [
        'makanan' => 'Makan',
        'makan' => 'Makan',
        'muen' => 'Makan',
        'grab/maxime' => 'Transport',
        'grab' => 'Transport',
        'maxime' => 'Transport',
        'gojek' => 'Transport',
        'ojek' => 'Transport',
        'sosial' => 'Social',
        'hiburan' => 'Social',
        'networking' => 'Social',
        'yfd' => 'Social',
        'admin bank' => 'Jajan',
        'skin care-beauty' => 'Jajan',
        'skincare' => 'Jajan',
        'beauty' => 'Jajan',
        'lain-lain' => 'Jajan',
        'lain lain' => 'Jajan',
        'dipinjam' => 'Social',
    ];

    public function __construct(
        private readonly BotCategoryRulesService $categoryRulesService,
    ) {}

    public function templateCsv(): string
    {
        $header = 'tanggal,jenis,kategori,sub_kategori,nominal,sifat,mood,impulsif,keterangan';
        $examples = [
            '2026-06-15,Pengeluaran,Makan,Jajan / Makan diluar,35000,Need,Neutral,No,Makan siang kantor',
            '2026-06-14,Pengeluaran,Transport,Angkutan Umum,15000,Need,Neutral,No,Ojek ke kantor',
            '2026-06-01,Pemasukan,Gaji,,5000000,Need,Happy,No,Gaji bulan Juni',
        ];

        return "\xEF\xBB\xBF".$header."\n".implode("\n", $examples)."\n";
    }

    /**
     * @return array{imported: int, failed: int, errors: list<string>}
     */
    public function importFromFile(int $telegramUserId, UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return ['imported' => 0, 'failed' => 0, 'errors' => ['File tidak bisa dibaca.']];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['imported' => 0, 'failed' => 0, 'errors' => ['File tidak bisa dibaca.']];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return ['imported' => 0, 'failed' => 0, 'errors' => ['File CSV kosong.']];
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if ($headerRow === false) {
            fclose($handle);

            return ['imported' => 0, 'failed' => 0, 'errors' => ['File CSV kosong.']];
        }

        $map = $this->mapHeaders($headerRow);
        if (! in_array('type', $map, true) || ! in_array('amount', $map, true)) {
            fclose($handle);

            return ['imported' => 0, 'failed' => 0, 'errors' => ['Header wajib minimal: jenis, nominal (atau type, amount).']];
        }

        $imported = 0;
        $failed = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($this->isEmptyRow($row)) {
                continue;
            }
            if ($imported + $failed >= self::MAX_ROWS) {
                $errors[] = 'Batas '.self::MAX_ROWS.' baris per impor tercapai; sisa baris diabaikan.';
                break;
            }

            $parsed = $this->parseRow($map, $row);
            if (isset($parsed['error'])) {
                $failed++;
                $errors[] = "Baris {$line}: {$parsed['error']}";

                continue;
            }

            BotTransaction::query()->create([
                'telegram_user_id' => $telegramUserId,
                'recorded_at' => $parsed['recorded_at'],
                'type' => $parsed['type'],
                'category' => $parsed['category'],
                'sub_category' => $parsed['sub_category'],
                'amount' => $parsed['amount'],
                'nature' => $parsed['nature'],
                'mood' => $parsed['mood'],
                'is_impulsive' => $parsed['is_impulsive'],
                'notes' => $parsed['notes'],
                'source' => 'manual',
            ]);
            $imported++;
        }

        fclose($handle);

        return compact('imported', 'failed', 'errors');
    }

    /**
     * @param  list<string|null>  $headerRow
     * @return list<string>
     */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $cell) {
            $key = Str::slug(trim((string) $cell), '_');
            $key = self::HEADER_ALIASES[$key] ?? $key;
            $map[] = $key;
        }

        return $map;
    }

    /**
     * @param  list<string>  $map
     * @param  list<string|null>  $row
     * @return array<string, mixed>|array{error: string}
     */
    private function parseRow(array $map, array $row): array
    {
        $data = [];
        foreach ($map as $i => $field) {
            $data[$field] = trim((string) ($row[$i] ?? ''));
        }

        $type = $this->normalizeType($data['type'] ?? '');
        if ($type === null) {
            return ['error' => 'jenis harus Pemasukan atau Pengeluaran'];
        }

        $amount = $this->parseAmount($data['amount'] ?? '');
        if ($amount === null || $amount < 1) {
            $rawAmount = trim($data['amount'] ?? '');

            return ['error' => $rawAmount === ''
                ? 'nominal kosong'
                : "nominal tidak valid (nilai: \"{$rawAmount}\")"];
        }

        $rules = $this->rules();
        $categoryInput = $data['category'] ?? '';
        $subCategoryInput = trim($data['sub_category'] ?? '');

        $category = $this->resolveCategory($categoryInput, $subCategoryInput, $type, $rules);
        if ($category === null) {
            $allowed = implode(', ', $rules['categories']);
            $hint = $categoryInput !== ''
                ? "nilai \"{$categoryInput}\" tidak dikenali"
                : 'kolom kategori kosong';

            return ['error' => "kategori tidak valid — {$hint}. Gunakan: {$allowed} (sub_kategori juga bisa di kolom kategori, mis. Angkutan Umum → Transport)"];
        }

        $recordedAt = $this->parseDate($data['recorded_at'] ?? '') ?? now();

        $nature = $this->normalizeNature($data['nature'] ?? '', $type);
        $mood = $this->normalizeMood($data['mood'] ?? '');
        $isImpulsive = $this->parseBool($data['is_impulsive'] ?? '');

        $subCategory = $this->resolveSubCategory($subCategoryInput, $categoryInput, $category, $rules);

        $notes = trim($data['notes'] ?? '');
        if ($notes === '') {
            $notes = '-';
        }

        return [
            'recorded_at' => $recordedAt,
            'type' => $type,
            'category' => $category,
            'sub_category' => mb_substr($subCategory, 0, 128),
            'amount' => $amount,
            'nature' => $nature,
            'mood' => $mood,
            'is_impulsive' => $isImpulsive,
            'notes' => mb_substr($notes, 0, 2000),
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function resolveCategory(string $categoryInput, string $subCategoryInput, string $type, array $rules): ?string
    {
        $categoryInput = trim($categoryInput);
        $subCategoryInput = trim($subCategoryInput);

        if ($categoryInput === '' && $type === 'Pemasukan') {
            return 'Gaji';
        }

        if ($categoryInput === '' && $type === 'Pengeluaran') {
            if ($subCategoryInput !== '') {
                $fromSub = $this->categoryFromSubCategory($subCategoryInput, $rules);
                if ($fromSub !== null) {
                    return $fromSub;
                }
            }

            return (string) ($rules['fallback_category'] ?? 'Jajan');
        }

        $canonical = $this->matchCategoryName($categoryInput, $rules);
        if ($canonical !== null) {
            return $canonical;
        }

        return $this->categoryFromSubCategory($categoryInput, $rules);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function resolveSubCategory(
        string $subCategoryInput,
        string $categoryInput,
        string $resolvedCategory,
        array $rules,
    ): string {
        $subCategoryInput = trim($subCategoryInput);
        $categoryInput = trim($categoryInput);

        if ($subCategoryInput !== '') {
            $canonical = $this->matchSubCategoryName($subCategoryInput, $resolvedCategory, $rules);
            if ($canonical !== null) {
                return $canonical;
            }

            return $subCategoryInput;
        }

        if ($categoryInput !== '' && $this->matchSubCategoryName($categoryInput, $resolvedCategory, $rules) !== null) {
            return $categoryInput;
        }

        $subs = $rules['category_sub_map'][$resolvedCategory] ?? [];
        if (is_array($subs) && $subs !== []) {
            return (string) $subs[0];
        }

        return (string) ($rules['fallback_sub'] ?? 'Pengeluaran lain-lain');
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function matchCategoryName(string $value, array $rules): ?string
    {
        $normalized = $this->normalizeAliasKey($value);
        if ($normalized !== '' && isset(self::CATEGORY_ALIASES[$normalized])) {
            $aliasTarget = self::CATEGORY_ALIASES[$normalized];
            foreach ($rules['categories'] as $cat) {
                if ((string) $cat === $aliasTarget) {
                    return $aliasTarget;
                }
            }
        }

        foreach ($rules['categories'] as $cat) {
            if (mb_strtolower((string) $cat) === mb_strtolower($value)) {
                return (string) $cat;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function categoryFromSubCategory(string $value, array $rules): ?string
    {
        $needle = $this->normalizeAliasKey($value);
        if ($needle === '') {
            return null;
        }

        /** @var array<string, list<string>> $categorySubMap */
        $categorySubMap = $rules['category_sub_map'] ?? [];
        foreach ($categorySubMap as $category => $subs) {
            foreach ($subs as $sub) {
                if (mb_strtolower((string) $sub) === $needle) {
                    return (string) $category;
                }
            }
        }

        foreach ($rules['rules'] as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $sub = trim((string) ($rule['sub_category'] ?? ''));
            $cat = trim((string) ($rule['category'] ?? ''));
            if ($sub !== '' && $cat !== '' && $cat !== '*' && mb_strtolower($sub) === $needle) {
                return $cat;
            }
        }

        return null;
    }

    private function normalizeAliasKey(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['_', '-'], ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        return trim($v);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function matchSubCategoryName(string $value, string $category, array $rules): ?string
    {
        $subs = $rules['category_sub_map'][$category] ?? [];
        if (! is_array($subs)) {
            return null;
        }

        foreach ($subs as $sub) {
            if (mb_strtolower((string) $sub) === mb_strtolower($value)) {
                return (string) $sub;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        if ($this->categoryRules === null) {
            $this->categoryRules = $this->categoryRulesService->export();
        }

        return $this->categoryRules;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function detectDelimiter(string $line): string
    {
        $semicolons = substr_count($line, ';');
        $commas = substr_count($line, ',');

        return $semicolons > $commas ? ';' : ',';
    }

    private function normalizeType(string $value): ?string
    {
        $v = mb_strtolower(trim($value));
        if (in_array($v, ['pemasukan', 'income', 'masuk'], true)) {
            return 'Pemasukan';
        }
        if (in_array($v, ['pengeluaran', 'expense', 'keluar'], true)) {
            return 'Pengeluaran';
        }
        if (in_array($value, self::VALID_TYPES, true)) {
            return $value;
        }

        return null;
    }

    private function normalizeNature(string $value, string $type): string
    {
        $v = trim($value);
        foreach (self::VALID_NATURES as $nature) {
            if (strcasecmp($nature, $v) === 0) {
                return $nature;
            }
        }

        return 'Need';
    }

    private function normalizeMood(string $value): string
    {
        $v = trim($value);
        foreach (self::VALID_MOODS as $mood) {
            if (strcasecmp($mood, $v) === 0) {
                return $mood;
            }
        }

        return 'Neutral';
    }

    private function parseBool(string $value): bool
    {
        $v = mb_strtolower(trim($value));

        return in_array($v, ['1', 'yes', 'ya', 'y', 'true', 'benar'], true);
    }

    private function parseAmount(string $value): ?int
    {
        $raw = mb_strtolower(trim($value));
        if ($raw === '' || in_array($raw, ['-', '—', 'n/a', 'na', 'null', '#n/a'], true)) {
            return null;
        }

        $multiplier = 1;
        if (preg_match('/\b(jt|juta|milyun|miliar|rb|ribu|k)\b/u', $raw, $m)) {
            $multiplier = match ($m[1]) {
                'jt', 'juta' => 1_000_000,
                'milyun', 'miliar' => 1_000_000_000,
                default => 1_000,
            };
            $raw = preg_replace('/\b(jt|juta|milyun|miliar|rb|ribu|k)\b/u', '', $raw) ?? $raw;
        }

        $raw = preg_replace('/\brp\.?\s*/u', '', $raw) ?? $raw;
        $digits = preg_replace('/[^\d.,]/', '', trim($raw)) ?? '';
        if ($digits === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $digits)) {
            $digits = str_replace('.', '', $digits);
            $digits = str_replace(',', '.', $digits);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $digits)) {
            $digits = str_replace(',', '', $digits);
        } elseif (preg_match('/^\d+\.\d{3}$/', $digits)) {
            $digits = str_replace('.', '', $digits);
        } else {
            $digits = str_replace('.', '', $digits);
            $digits = str_replace(',', '.', $digits);
        }

        if (! is_numeric($digits)) {
            return null;
        }

        return max(0, (int) round((float) $digits * $multiplier));
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd-m-Y', 'd/m/Y', 'd-m-Y H:i', 'd/m/Y H:i'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
