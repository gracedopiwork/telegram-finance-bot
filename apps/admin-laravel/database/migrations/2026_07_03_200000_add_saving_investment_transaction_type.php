<?php

use App\Support\TransactionTaxonomy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_transactions')) {
            return;
        }

        DB::statement(
            "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM('Pemasukan', 'Pengeluaran', 'Saving/Investment') NOT NULL"
        );

        DB::table('bot_transactions')
            ->whereIn('nature', ['Saving/Investement', 'Saving/Investment'])
            ->update([
                'type' => TransactionTaxonomy::TYPE_SAVING,
                'nature' => TransactionTaxonomy::NATURE_NEED,
            ]);

        DB::table('bot_transactions')
            ->where('nature', 'Donation')
            ->whereRaw('LOWER(category) IN (?, ?)', ['jajan', ''])
            ->update([
                'type' => TransactionTaxonomy::TYPE_EXPENSE,
                'category' => 'Social',
                'nature' => TransactionTaxonomy::NATURE_NEED,
            ]);

        DB::table('bot_transactions')
            ->where('nature', 'Donation')
            ->update([
                'type' => TransactionTaxonomy::TYPE_EXPENSE,
                'nature' => TransactionTaxonomy::NATURE_NEED,
            ]);

        DB::table('bot_transactions')
            ->whereNotIn('nature', TransactionTaxonomy::NATURES)
            ->update(['nature' => TransactionTaxonomy::NATURE_NEED]);

        if (Schema::hasTable('category_bucket_mappings')) {
            DB::table('category_bucket_mappings')
                ->whereIn('nature', ['Saving/Investement', 'Saving/Investment', 'Donation'])
                ->update(['nature' => null]);

            DB::table('category_bucket_mappings')
                ->where('reason', 'like', '%nabung/investasi%')
                ->orWhere('reason', 'like', '%Semua nabung%')
                ->update([
                    'transaction_type' => 'saving',
                    'nature' => null,
                    'category' => '*',
                ]);

            DB::table('category_bucket_mappings')
                ->where('reason', 'like', '%donasi%')
                ->update([
                    'transaction_type' => 'expense',
                    'nature' => null,
                    'category' => 'Social',
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_transactions')) {
            return;
        }

        DB::table('bot_transactions')
            ->where('type', TransactionTaxonomy::TYPE_SAVING)
            ->update([
                'type' => TransactionTaxonomy::TYPE_EXPENSE,
                'nature' => 'Saving/Investement',
            ]);

        DB::statement(
            "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM('Pemasukan', 'Pengeluaran') NOT NULL"
        );
    }
};
