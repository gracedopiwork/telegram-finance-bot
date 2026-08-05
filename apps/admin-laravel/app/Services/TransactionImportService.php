<?php

namespace App\Services;

use App\Support\PortalTimezone;
use App\Support\TransactionTaxonomy;
use App\Models\BotTransaction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TransactionImportService
{
    /** @var list<string> */
    private const VALID_TYPES = TransactionTaxonomy::TYPES;

    /** @var list<string> */
    private const VALID_NATURES = TransactionTaxonomy::NATURES;

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
        'mood_spending' => 'mood',
        'impulsif' => 'is_impulsive',
        'is_impulsive' => 'is_impulsive',
        'impulsive' => 'is_impulsive',
        'keterangan' => 'notes',
        'notes' => 'notes',
        'catatan' => 'notes',
    ];


    public function __construct(
        private readonly CategoryAutoRegisterService $categoryAutoRegister,
    ) {}

    public function templateCsv(): string
    {
        $header = 'tanggal,jenis,kategori,nominal,sifat,mood,impulsif,keterangan';
        $examples = [
            '2026-06-15,Pengeluaran,Makanan & Minuman,35000,Need,Neutral,No,Makan siang kantor',
            '2026-06-14,Pengeluaran,Transportasi,15000,Need,Neutral,No,Ojek ke kantor',
            '2026-06-10,Saving/Investment,Investasi & Tabungan,500000,Need,Neutral,No,Beli saham BBCA',
            '2026-06-08,Pengeluaran,Sosial & Keluarga,50000,Need,Neutral,No,Persembahan ibadah',
            '2026-06-01,Pemasukan,Gaji,5000000,Need,Happy,No,Gaji bulan Juni',
        ];

        return "\xEF\xBB\xBF".$header."\n".implode("\n", $examples)."\n";
    }

    /**
     * Export semua transaksi user sebagai .xlsx (Excel) tanpa dependency ekstra.
     */
    public function exportXlsxForUser(int $telegramUserId, ?CategoryBucketService $buckets = null): string
    {
        $buckets ??= app(CategoryBucketService::class);
        $headers = ['tanggal', 'jenis', 'kategori', 'sub_kategori', 'nominal', 'sifat', 'mood', 'impulsif', 'bucket', 'keterangan'];
        $rows = [];

        BotTransaction::query()
            ->where('telegram_user_id', $telegramUserId)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->cursor()
            ->each(function (BotTransaction $row) use (&$rows, $telegramUserId, $buckets): void {
                $rows[] = [
                    PortalTimezone::formatRecordedAt($row->recorded_at, $telegramUserId),
                    (string) $row->type,
                    (string) $row->category,
                    (string) ($row->sub_category ?: ''),
                    (int) $row->amount,
                    (string) $row->nature,
                    (string) ($row->mood ?: ''),
                    $row->is_impulsive ? 'Yes' : 'No',
                    $buckets->resolve($row) ?? '',
                    (string) ($row->notes ?: ''),
                ];
            });

        return $this->buildSimpleXlsx($headers, $rows);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int>>  $rows
     */
    private function buildSimpleXlsx(array $headers, array $rows): string
    {
        $sheetRows = [];
        $sheetRows[] = $this->xlsxRow(1, $headers, true);
        foreach ($rows as $i => $row) {
            $sheetRows[] = $this->xlsxRow($i + 2, $row, false);
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Transaksi" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'yfdxlsx');
        if ($tmp === false) {
            throw new \RuntimeException('Tidak bisa membuat file sementara untuk export Excel.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('Tidak bisa menulis file Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);
        if ($binary === false) {
            throw new \RuntimeException('Gagal membaca hasil export Excel.');
        }

        return $binary;
    }

    /**
     * @param  list<string|int>  $cells
     */
    private function xlsxRow(int $rowNumber, array $cells, bool $asText): string
    {
        $xml = '<row r="'.$rowNumber.'">';
        foreach ($cells as $index => $value) {
            $ref = $this->xlsxColumnLetter($index + 1).$rowNumber;
            if (! $asText && $index === 4 && is_numeric($value)) {
                $xml .= '<c r="'.$ref.'"><v>'.(int) $value.'</v></c>';
                continue;
            }
            $text = htmlspecialchars(str_replace(["\r\n", "\r", "\n"], ' ', (string) $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$text.'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private function xlsxColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    /**
     * @return array{imported: int, failed: int, errors: list<string>, focus_month: ?string}
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
        /** @var array<string, bool> $months */
        $months = [];
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

            $parsed = $this->parseRow($map, $row, $telegramUserId);
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
            $monthKey = $parsed['recorded_at'] instanceof Carbon
                ? $parsed['recorded_at']->format('Y-m')
                : null;
            if ($monthKey !== null) {
                $months[$monthKey] = true;
            }
        }

        fclose($handle);

        $focusMonth = null;
        if ($months !== []) {
            $keys = array_keys($months);
            rsort($keys);
            $focusMonth = $keys[0] ?? null;
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
            'focus_month' => $focusMonth,
        ];
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
    private function parseRow(array $map, array $row, int $telegramUserId): array
    {
        $data = [];
        foreach ($map as $i => $field) {
            $data[$field] = trim((string) ($row[$i] ?? ''));
        }

        $rawType = trim($data['type'] ?? '');
        $rawNature = trim($data['nature'] ?? '');
        $taxonomy = TransactionTaxonomy::normalize(
            $rawType,
            $rawNature,
            $data['category'] ?? null,
        );
        $type = $taxonomy['type'];

        if ($rawType !== '' && TransactionTaxonomy::normalizeType($rawType) === null) {
            $legacyOk = in_array(mb_strtolower($rawType), [
                'saving/investement', 'saving/investment', 'saving', 'investasi', 'investment', 'nabung',
            ], true) || in_array(mb_strtolower($rawNature), [
                'saving/investement', 'saving/investment', 'saving', 'investasi', 'investment',
                'donation', 'donasi', 'sedekah', 'persembahan',
            ], true);

            if (! $legacyOk) {
                return ['error' => "jenis tidak valid (\"{$rawType}\") — gunakan Pemasukan, Pengeluaran, atau Saving/Investment"];
            }
        }

        $amount = $this->parseAmount($data['amount'] ?? '');
        if ($amount === null || $amount < 1) {
            $rawAmount = trim($data['amount'] ?? '');

            return ['error' => $rawAmount === ''
                ? 'nominal kosong'
                : "nominal tidak valid (nilai: \"{$rawAmount}\")"];
        }

        $recordedAt = $this->parseDate($data['recorded_at'] ?? '', $telegramUserId) ?? PortalTimezone::nowUtc();

        $nature = $taxonomy['nature'];
        $mood = $this->normalizeMood($data['mood'] ?? '');
        $isImpulsive = $this->parseBool($data['is_impulsive'] ?? '');

        $notes = trim($data['notes'] ?? '');
        if ($notes === '') {
            $notes = '-';
        }

        $categoryInput = $taxonomy['category'] ?? ($data['category'] ?? '');

        if ($type === TransactionTaxonomy::TYPE_SAVING) {
            $savingLabel = $this->inferSavingLabel($notes !== '-' ? $notes : (string) $categoryInput);
            $categoryInput = $savingLabel;
            if ($notes === '-' || $notes === '') {
                $notes = "Investasi {$savingLabel}";
            } elseif (! str_contains(mb_strtolower($notes), mb_strtolower($savingLabel))) {
                $notes = "{$savingLabel}: {$notes}";
            }
        }

        $category = $this->categoryAutoRegister->resolveOrRegister(
            $categoryInput,
            $type,
            $nature,
            $notes !== '-' ? $notes : $categoryInput,
        );

        return [
            'recorded_at' => $recordedAt,
            'type' => $type,
            'category' => $category,
            'sub_category' => '-',
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

    private function detectDelimiter(string $line): string
    {
        $semicolons = substr_count($line, ';');
        $commas = substr_count($line, ',');

        return $semicolons > $commas ? ';' : ',';
    }

    private function normalizeType(string $value): ?string
    {
        return TransactionTaxonomy::normalizeType($value);
    }

    private function normalizeNature(string $value, string $type): string
    {
        return TransactionTaxonomy::normalize($type, $value)['nature'];
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

    private function parseDate(string $value, int $telegramUserId): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $tz = PortalTimezone::forUser($telegramUserId);
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd-m-Y', 'd/m/Y', 'd-m-Y H:i', 'd/m/Y H:i'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value, $tz)->utc();
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value, $tz)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function inferSavingLabel(string $text): string
    {
        $lower = mb_strtolower(trim($text));
        foreach ([
            'reksadana' => 'Reksadana',
            'reksa dana' => 'Reksadana',
            'saham' => 'Saham',
            'obligasi' => 'Obligasi',
            'emas' => 'Emas',
            'deposito' => 'Deposito',
            'crypto' => 'Crypto',
            'bitcoin' => 'Crypto',
            'dana darurat' => 'Dana darurat',
            'nabung' => 'Tabungan',
            'tabungan' => 'Tabungan',
            'investasi' => 'Investasi',
            'sbn' => 'Obligasi',
        ] as $keyword => $label) {
            if (str_contains($lower, $keyword)) {
                return $label;
            }
        }

        return 'Tabungan/Investasi';
    }
}
