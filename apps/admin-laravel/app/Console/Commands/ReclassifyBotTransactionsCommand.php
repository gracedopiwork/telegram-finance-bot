<?php

namespace App\Console\Commands;

use App\Models\BotTransaction;
use App\Services\CategoryAutoRegisterService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Reprocess transaksi tersimpan agar ikut YFD AI Taxonomy (kategori + Need/Wants + impulsif).
 * Bucket dashboard tetap dihitung live — tidak perlu disimpan.
 */
class ReclassifyBotTransactionsCommand extends Command
{
    protected $signature = 'bot:reclassify-transactions
                            {--from=2026-07-01 : Tanggal mulai recorded_at (Y-m-d)}
                            {--to=2026-07-31 : Tanggal akhir recorded_at inclusive (Y-m-d)}
                            {--user= : Filter telegram_user_id}
                            {--chunk=200 : Ukuran batch ke Python}
                            {--dry-run : Tampilkan ringkasan tanpa menulis DB}
                            {--skip-python : Hanya normalisasi kategori di Laravel}
                            {--python= : Path binary Python (default: BOT_PYTHON / python3 / python)}';

    protected $description = 'Reclassify transaksi bot Juli (atau rentang tanggal) sesuai taxonomy rules terbaru';

    public function handle(CategoryAutoRegisterService $categories): int
    {
        try {
            $from = Carbon::parse((string) $this->option('from'))->startOfDay();
            $to = Carbon::parse((string) $this->option('to'))->endOfDay();
        } catch (Throwable) {
            $this->error('Format --from / --to harus Y-m-d.');

            return self::FAILURE;
        }

        if ($from->gt($to)) {
            $this->error('--from tidak boleh setelah --to.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $skipPython = (bool) $this->option('skip-python');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $userId = $this->option('user');

        $query = BotTransaction::query()
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('id');

        if ($userId !== null && $userId !== '') {
            $query->where('telegram_user_id', (int) $userId);
        }

        $total = (clone $query)->count();
        $this->info(sprintf(
            'Reclassify %d transaksi (%s s/d %s)%s%s',
            $total,
            $from->toDateString(),
            $to->toDateString(),
            $dryRun ? ' [dry-run]' : '',
            $skipPython ? ' [skip-python]' : '',
        ));

        if ($total === 0) {
            $this->warn('Tidak ada transaksi di rentang ini.');

            return self::SUCCESS;
        }

        $stats = [
            'scanned' => 0,
            'updated' => 0,
            'category' => 0,
            'nature' => 0,
            'is_impulsive' => 0,
            'unchanged' => 0,
            'errors' => 0,
        ];

        $pythonBin = $skipPython ? null : $this->resolvePythonBinary();
        if (! $skipPython && $pythonBin === null) {
            $this->warn('Python tidak ditemukan — lanjut hanya normalisasi kategori Laravel. Set --python= atau BOT_PYTHON.');
            $skipPython = true;
        }

        $scriptCandidates = [
            base_path('../bot-python/reclassify_offline.py'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'bot-python'.DIRECTORY_SEPARATOR.'reclassify_offline.py',
        ];
        $scriptPath = null;
        foreach ($scriptCandidates as $candidate) {
            if (is_file($candidate)) {
                $scriptPath = $candidate;
                break;
            }
        }

        if (! $skipPython && $scriptPath === null) {
            $this->warn('reclassify_offline.py tidak ditemukan — lanjut hanya kategori Laravel.');
            $skipPython = true;
        }

        $query->chunkById($chunkSize, function ($rows) use (
            $categories,
            $dryRun,
            &$skipPython,
            $pythonBin,
            $scriptPath,
            &$stats,
        ): void {
            $payloadRows = [];
            $modelsById = [];

            foreach ($rows as $row) {
                /** @var BotTransaction $row */
                $stats['scanned']++;
                $modelsById[$row->id] = $row;

                $canonical = $categories->resolveWithoutRegister(
                    (string) $row->category,
                    (string) $row->type,
                );

                $payloadRows[] = [
                    'id' => $row->id,
                    'type' => (string) $row->type,
                    'category' => $canonical,
                    'nature' => (string) ($row->nature ?: 'Need'),
                    'is_impulsive' => (bool) $row->is_impulsive,
                    'notes' => (string) ($row->notes ?? ''),
                    'amount' => (int) $row->amount,
                    'mood' => (string) ($row->mood ?: 'Neutral'),
                    '_laravel_category' => $canonical,
                ];
            }

            $results = $skipPython
                ? $this->laravelOnlyResults($payloadRows)
                : $this->runPythonBatch($pythonBin, $scriptPath, $payloadRows);

            if ($results === null) {
                $this->warn('Batch Python gagal — fallback kategori Laravel saja untuk chunk ini.');
                $results = $this->laravelOnlyResults($payloadRows);
                $stats['errors']++;
            }

            foreach ($results as $result) {
                $id = (int) ($result['id'] ?? 0);
                $model = $modelsById[$id] ?? null;
                if ($model === null) {
                    continue;
                }

                $newCategory = (string) ($result['category'] ?? $model->category);
                $newCategory = $categories->resolveWithoutRegister($newCategory, (string) $model->type);
                $newNature = (string) ($result['nature'] ?? $model->nature ?: 'Need');
                if (! in_array($newNature, ['Need', 'Wants'], true)) {
                    $newNature = 'Need';
                }
                $newImpulsive = (bool) ($result['is_impulsive'] ?? $model->is_impulsive);

                $dirty = [];
                if ($newCategory !== (string) $model->category) {
                    $dirty['category'] = $newCategory;
                    $stats['category']++;
                }
                if ($newNature !== (string) $model->nature) {
                    $dirty['nature'] = $newNature;
                    $stats['nature']++;
                }
                if ($newImpulsive !== (bool) $model->is_impulsive) {
                    $dirty['is_impulsive'] = $newImpulsive;
                    $stats['is_impulsive']++;
                }

                if ($dirty === []) {
                    $stats['unchanged']++;

                    continue;
                }

                $stats['updated']++;
                $this->line(sprintf(
                    '  #%d [%s] %s',
                    $id,
                    implode(',', array_keys($dirty)),
                    mb_strimwidth((string) $model->notes, 0, 60, '…'),
                ));

                if (! $dryRun) {
                    $model->fill($dirty);
                    $model->save();
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($dryRun) {
            $this->comment('Dry-run selesai — tidak ada perubahan DB. Jalankan tanpa --dry-run untuk apply.');
        } else {
            $this->info('Reclassify selesai. Bucket dashboard akan ikut rules baru saat dibuka.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $payloadRows
     * @return list<array<string, mixed>>
     */
    private function laravelOnlyResults(array $payloadRows): array
    {
        $out = [];
        foreach ($payloadRows as $row) {
            $out[] = [
                'id' => $row['id'],
                'category' => $row['category'],
                'nature' => $row['nature'],
                'is_impulsive' => $row['is_impulsive'],
                'changed' => false,
                'changes' => [],
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $payloadRows
     * @return list<array<string, mixed>>|null
     */
    private function runPythonBatch(?string $pythonBin, ?string $scriptPath, array $payloadRows): ?array
    {
        if ($pythonBin === null || $scriptPath === null) {
            return null;
        }

        $cleanRows = array_map(static function (array $row): array {
            unset($row['_laravel_category']);

            return $row;
        }, $payloadRows);

        $tmp = tempnam(sys_get_temp_dir(), 'yfd_reclass_');
        if ($tmp === false) {
            return null;
        }

        $inputPath = $tmp.'.json';
        rename($tmp, $inputPath);
        file_put_contents($inputPath, json_encode(['rows' => $cleanRows], JSON_UNESCAPED_UNICODE));

        try {
            $result = Process::path(dirname($scriptPath))
                ->timeout(120)
                ->run([
                    $pythonBin,
                    $scriptPath,
                    '--file',
                    $inputPath,
                ]);

            if (! $result->successful()) {
                $this->warn(trim($result->errorOutput() ?: $result->output()) ?: 'Python exit non-zero');

                return null;
            }

            $decoded = json_decode($result->output(), true);
            if (! is_array($decoded) || ! isset($decoded['rows']) || ! is_array($decoded['rows'])) {
                $this->warn('Output Python tidak valid JSON.');

                return null;
            }

            return $decoded['rows'];
        } catch (Throwable $e) {
            $this->warn('Gagal menjalankan Python: '.$e->getMessage());

            return null;
        } finally {
            @unlink($inputPath);
        }
    }

    private function resolvePythonBinary(): ?string
    {
        $configured = trim((string) ($this->option('python') ?: env('BOT_PYTHON', '')));
        $candidates = array_values(array_filter([
            $configured !== '' ? $configured : null,
            '/home/yfd/telegram-finance-bot/apps/bot-python/.venv/bin/python',
            '/home/yfd/telegram-finance-bot/apps/bot-python/venv/bin/python',
            'python3',
            'python',
        ]));

        foreach ($candidates as $bin) {
            if (str_contains($bin, DIRECTORY_SEPARATOR) || str_starts_with($bin, '/')) {
                if (is_file($bin) && is_executable($bin)) {
                    return $bin;
                }

                continue;
            }

            $probe = Process::timeout(5)->run([$bin, '--version']);
            if ($probe->successful()) {
                return $bin;
            }
        }

        return null;
    }
}
