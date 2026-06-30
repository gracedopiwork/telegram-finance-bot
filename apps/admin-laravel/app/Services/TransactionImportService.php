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
    private const VALID_CATEGORIES = ['Makan', 'Transport', 'Listrik', 'Air', 'Jajan', 'Social', 'Gaji'];

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
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['imported' => 0, 'failed' => 0, 'errors' => ['File tidak bisa dibaca.']];
        }

        $headerRow = fgetcsv($handle);
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

        while (($row = fgetcsv($handle)) !== false) {
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
            return ['error' => 'nominal tidak valid'];
        }

        $category = $this->normalizeCategory($data['category'] ?? '', $type);
        if ($category === null) {
            return ['error' => 'kategori tidak valid (Makan, Transport, Listrik, Air, Jajan, Social, Gaji)'];
        }

        $recordedAt = $this->parseDate($data['recorded_at'] ?? '') ?? now();

        $nature = $this->normalizeNature($data['nature'] ?? '', $type);
        $mood = $this->normalizeMood($data['mood'] ?? '');
        $isImpulsive = $this->parseBool($data['is_impulsive'] ?? '');

        $subCategory = trim($data['sub_category'] ?? '');
        if ($subCategory === '') {
            $subCategory = '-';
        }

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

    private function normalizeCategory(string $value, string $type): ?string
    {
        if ($value === '' && $type === 'Pemasukan') {
            return 'Gaji';
        }

        foreach (self::VALID_CATEGORIES as $cat) {
            if (mb_strtolower($cat) === mb_strtolower($value)) {
                return $cat;
            }
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

        if ($type === 'Pemasukan') {
            return 'Need';
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
        if ($raw === '') {
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

        $digits = preg_replace('/[^\d.,]/', '', $raw) ?? '';
        $digits = str_replace('.', '', $digits);
        $digits = str_replace(',', '.', $digits);
        if ($digits === '' || ! is_numeric($digits)) {
            return null;
        }

        return (int) round((float) $digits * $multiplier);
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
