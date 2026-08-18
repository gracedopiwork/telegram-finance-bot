<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Taxonomy 5B.7: jenis pekerjaan di baseline.
 * Gym PT/atlet → Future Building (onboarding revisi 15 Agustus 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_baselines') && ! Schema::hasColumn('financial_baselines', 'job_type')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->string('job_type', 32)->nullable()->after('current_goal');
                $table->string('tax_scheme', 32)->nullable()->after('job_type');
            });
        }

        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        foreach ((array) config('category_bucket_mappings_defaults', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $sort = (int) ($row['sort_order'] ?? $index);
            if ($sort !== 204) {
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

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        if (Schema::hasTable('financial_baselines') && Schema::hasColumn('financial_baselines', 'job_type')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->dropColumn(['job_type', 'tax_scheme']);
            });
        }

        if (Schema::hasTable('category_bucket_mappings')) {
            DB::table('category_bucket_mappings')->where('sort_order', 204)->delete();
            Cache::forget('category_bucket_mappings:active');
        }
    }
};
