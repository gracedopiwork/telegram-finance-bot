<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rapikan mapping agar tidak tabrakan: keyword pendek, Proteksi ≠ tumbler,
 * makeup ≠ Essential, website/networking/premi tidak menelan konteks lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $syncSorts = [19, 20, 21, 22, 24, 25, 34, 40, 59, 274];
        foreach ((array) config('category_bucket_mappings_defaults', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $sort = (int) ($row['sort_order'] ?? $index);
            if (! in_array($sort, $syncSorts, true)) {
                continue;
            }
            DB::table('category_bucket_mappings')->updateOrInsert(
                [
                    'category' => $row['category'],
                    'sub_category' => $row['sub_category'] ?? null,
                    'transaction_type' => $row['transaction_type'] ?? 'expense',
                    'sort_order' => $sort,
                ],
                [
                    'bucket' => $row['bucket'],
                    'nature' => $row['nature'] ?? null,
                    'match_keywords' => $row['match_keywords'] ?? null,
                    'reason' => $row['reason'] ?? null,
                    'is_active' => true,
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('category_bucket_mappings')
            ->where('category', '*')
            ->where('transaction_type', 'expense')
            ->whereIn('sort_order', [20, 22])
            ->where(function ($q) {
                $q->where('match_keywords', 'like', '%,les,%')
                    ->orWhere('match_keywords', 'like', 'les,%')
                    ->orWhere('reason', 'like', '%self-development selain olahraga%');
            })
            ->delete();

        $pollute = [
            'tumbler', 'termos', 'kulkas', 'perabot', 'gorden', 'sprei', 'piring',
            'rusak', 'networking', 'website', 'premi', 'jiwa', 'sunscreen',
        ];
        $rows = DB::table('category_bucket_mappings')
            ->whereRaw('LOWER(TRIM(category)) = ?', ['proteksi'])
            ->get();
        foreach ($rows as $row) {
            $keywords = array_filter(array_map('trim', preg_split('/[\n,;]+/', (string) $row->match_keywords) ?: []));
            $clean = array_values(array_filter(
                $keywords,
                fn (string $kw) => ! in_array(mb_strtolower($kw), $pollute, true),
            ));
            if ($clean !== array_values($keywords)) {
                DB::table('category_bucket_mappings')->where('id', $row->id)->update([
                    'match_keywords' => implode(',', $clean),
                    'updated_at' => now(),
                ]);
            }
        }

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        Cache::forget('category_bucket_mappings:active');
    }
};
