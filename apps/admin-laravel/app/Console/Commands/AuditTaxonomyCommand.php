<?php

namespace App\Console\Commands;

use App\Models\BotTransaction;
use App\Services\CategoryAutoRegisterService;
use App\Services\CategoryBucketService;
use App\Support\TransactionTaxonomy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Audit kesesuaian taxonomy: config closed-list, mapping, dan data transaksi.
 */
class AuditTaxonomyCommand extends Command
{
    protected $signature = 'bot:audit-taxonomy
                            {--from=2026-07-01 : Awal rentang recorded_at}
                            {--to=2026-07-31 : Akhir rentang recorded_at}
                            {--samples : Tampilkan contoh klasifikasi expected (Affiliate, dll.)}';

    protected $description = 'Cek apakah kategori/mapping/data transaksi sudah sesuai YFD AI Taxonomy';

    public function handle(
        CategoryAutoRegisterService $categories,
        CategoryBucketService $buckets,
    ): int {
        $expense = (array) config('yfd_taxonomy.expense_categories', []);
        $income = (array) config('yfd_taxonomy.income_categories', []);
        $official = array_values(array_unique(array_merge($expense, $income)));
        $officialLower = array_map(static fn ($c) => mb_strtolower(trim((string) $c)), $official);

        $this->info('YFD AI Taxonomy audit');
        $this->line('Expense closed list: '.count($expense));
        $this->line('Income closed list: '.count($income));

        $issues = 0;

        // 1) Affiliate & income key categories must exist
        foreach (['Affiliate', 'Gaji', 'Freelance', 'Dividen', 'Bunga Investasi', 'Cashback', 'Refund'] as $must) {
            if (! in_array($must, $income, true)) {
                $this->error("Missing income category: {$must}");
                $issues++;
            } else {
                $this->line("OK income: {$must}");
            }
        }

        foreach (['Makanan & Minuman', 'Lifestyle & Hiburan', 'Proteksi', 'Bisnis & Karir', 'Lain-lain'] as $must) {
            if (! in_array($must, $expense, true)) {
                $this->error("Missing expense category: {$must}");
                $issues++;
            } else {
                $this->line("OK expense: {$must}");
            }
        }

        // 2) Alias sanity
        $aliases = (array) config('yfd_taxonomy.aliases', []);
        foreach ([
            'jajan' => 'Makanan & Minuman',
            'transport' => 'Transportasi',
            'affiliate' => 'Affiliate',
            'afiliasi' => 'Affiliate',
            'social' => 'Sosial & Keluarga',
            'asuransi' => 'Proteksi',
        ] as $from => $to) {
            $got = $aliases[$from] ?? null;
            if ($got !== $to) {
                $this->error("Alias '{$from}' expected '{$to}', got ".json_encode($got));
                $issues++;
            } else {
                $this->line("OK alias: {$from} → {$to}");
            }
        }

        // 3) Canonicalize smoke tests (Laravel path)
        $cases = [
            ['Jajan', TransactionTaxonomy::TYPE_EXPENSE, 'Makanan & Minuman'],
            ['Transport', TransactionTaxonomy::TYPE_EXPENSE, 'Transportasi'],
            ['Affiliate', TransactionTaxonomy::TYPE_INCOME, 'Affiliate'],
            ['afiliasi', TransactionTaxonomy::TYPE_INCOME, 'Affiliate'],
            ['Social', TransactionTaxonomy::TYPE_EXPENSE, 'Sosial & Keluarga'],
            ['Asuransi', TransactionTaxonomy::TYPE_EXPENSE, 'Proteksi'],
            ['Jasa', TransactionTaxonomy::TYPE_EXPENSE, 'Bisnis & Karir'],
        ];
        foreach ($cases as [$input, $type, $expected]) {
            $got = $categories->resolveWithoutRegister($input, $type);
            if ($got !== $expected) {
                $this->error("Canonicalize '{$input}' expected '{$expected}', got '{$got}'");
                $issues++;
            } else {
                $this->line("OK canonicalize: {$input} → {$got}");
            }
        }

        // 4) Defaults cover official categories (non-wildcard)
        $defaults = (array) config('category_bucket_mappings_defaults', []);
        $defaultCats = [];
        foreach ($defaults as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cat = (string) ($row['category'] ?? '');
            if ($cat !== '' && $cat !== '*') {
                $defaultCats[mb_strtolower($cat)] = true;
            }
        }
        foreach ($official as $cat) {
            if ($cat === 'Lain-lain') {
                continue;
            }
            if (! isset($defaultCats[mb_strtolower($cat)])) {
                $this->warn("Defaults mapping missing category: {$cat}");
                $issues++;
            }
        }
        $this->line('OK defaults rows: '.count($defaults));

        // 5) DB transactions outside closed list (if table exists)
        if (Schema::hasTable('bot_transactions')) {
            try {
                $from = Carbon::parse((string) $this->option('from'))->startOfDay();
                $to = Carbon::parse((string) $this->option('to'))->endOfDay();
            } catch (\Throwable) {
                $this->error('Format --from/--to salah');

                return self::FAILURE;
            }

            $rows = BotTransaction::query()
                ->whereBetween('recorded_at', [$from, $to])
                ->get(['id', 'type', 'category', 'nature', 'notes', 'amount', 'is_impulsive']);

            $this->newLine();
            $this->info("Data {$from->toDateString()} s/d {$to->toDateString()}: {$rows->count()} transaksi");

            $legacy = [];
            $byCategory = [];
            foreach ($rows as $row) {
                $cat = trim((string) $row->category);
                $key = mb_strtolower($cat);
                $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
                if ($cat !== '' && ! in_array($key, $officialLower, true)) {
                    $legacy[$cat] = ($legacy[$cat] ?? 0) + 1;
                }
            }

            if ($legacy === []) {
                $this->info('OK: semua kategori di rentang ini ada di closed list.');
            } else {
                $this->error('Masih ada kategori di luar closed list:');
                foreach ($legacy as $cat => $count) {
                    $this->line("  - {$cat}: {$count}");
                    $issues++;
                }
                $this->comment('Jalankan: php artisan bot:reclassify-transactions --from=... --to=...');
            }

            arsort($byCategory);
            $this->newLine();
            $this->table(
                ['Kategori', 'Jumlah'],
                collect($byCategory)->take(20)->map(fn ($n, $c) => [$c, $n])->values()->all()
            );

            // Sample bucket resolve for affiliate-like incomes in range
            $affiliateRows = $rows->filter(
                fn ($r) => mb_strtolower((string) $r->category) === 'affiliate'
                    || str_contains(mb_strtolower((string) $r->notes), 'affiliate')
                    || str_contains(mb_strtolower((string) $r->notes), 'afiliasi')
            );
            $this->line('Affiliate-related rows: '.$affiliateRows->count());
            foreach ($affiliateRows->take(5) as $row) {
                $bucket = $buckets->resolve($row);
                $this->line(sprintf(
                    '  #%d cat=%s nature=%s bucket=%s | %s',
                    $row->id,
                    $row->category,
                    $row->nature,
                    $bucket ?? '— (income excluded)',
                    mb_strimwidth((string) $row->notes, 0, 50, '…'),
                ));
                if ($row->type === TransactionTaxonomy::TYPE_INCOME && $bucket !== null) {
                    $this->warn("  Income #{$row->id} seharusnya bucket null, got {$bucket}");
                    $issues++;
                }
            }
        } else {
            $this->warn('Tabel bot_transactions belum ada — skip audit data.');
        }

        if ($this->option('samples')) {
            $this->newLine();
            $this->info('Expected samples (rules / PDF):');
            $samples = [
                'dapat shopee affiliate 50rb → Pemasukan / Affiliate / Need / bucket —',
                'gaji bulan ini 8jt → Pemasukan / Gaji',
                'beli saham BBCA 1jt → Saving / Investasi & Tabungan / Future Building',
                'bayar membership gym → Lifestyle & Hiburan / Wants / Flexible + Social',
                'bayar BPJS → Proteksi / Need / Protection',
                'makan malam karena capek ≥50rb → Makanan & Minuman / impulsif Yes',
                'tiket konser → Lifestyle & Hiburan / Flexible + Social',
            ];
            foreach ($samples as $line) {
                $this->line('  • '.$line);
            }
        }

        $this->newLine();
        if ($issues === 0) {
            $this->info('Audit lulus: config + data (rentang) sesuai closed taxonomy.');

            return self::SUCCESS;
        }

        $this->error("Audit menemukan {$issues} isu.");

        return self::FAILURE;
    }
}
