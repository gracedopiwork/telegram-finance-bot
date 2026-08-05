<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KBBI: jenis sosial "Hutang Masuk/Keluar" → "Utang Masuk/Keluar".
 * Alias "hutang" tetap diterima di normalizer untuk input lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_transactions') || DB::getDriverName() !== 'mysql') {
            return;
        }

        // Expand ENUM to include both labels, migrate rows, then shrink.
        DB::statement(
            "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
            ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
            ."'Piutang Keluar', 'Piutang Masuk', "
            ."'Hutang Masuk', 'Hutang Keluar', 'Utang Masuk', 'Utang Keluar'"
            .") NOT NULL"
        );

        DB::table('bot_transactions')->where('type', 'Hutang Masuk')->update(['type' => 'Utang Masuk']);
        DB::table('bot_transactions')->where('type', 'Hutang Keluar')->update(['type' => 'Utang Keluar']);

        DB::statement(
            "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
            ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
            ."'Piutang Keluar', 'Piutang Masuk', 'Utang Masuk', 'Utang Keluar'"
            .") NOT NULL"
        );

        if (Schema::hasTable('category_bucket_mappings')) {
            DB::table('category_bucket_mappings')
                ->where('transaction_type', 'payable_in')
                ->update([
                    'reason' => 'Utang Masuk dikecualikan dari pendapatan & 4 bucket (Likuiditas Sosial)',
                    'updated_at' => now(),
                ]);
            DB::table('category_bucket_mappings')
                ->where('transaction_type', 'payable_out')
                ->update([
                    'reason' => 'Utang Keluar dikecualikan dari 4 bucket (Likuiditas Sosial)',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_transactions') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
            ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
            ."'Piutang Keluar', 'Piutang Masuk', "
            ."'Hutang Masuk', 'Hutang Keluar', 'Utang Masuk', 'Utang Keluar'"
            .") NOT NULL"
        );

        DB::table('bot_transactions')->where('type', 'Utang Masuk')->update(['type' => 'Hutang Masuk']);
        DB::table('bot_transactions')->where('type', 'Utang Keluar')->update(['type' => 'Hutang Keluar']);

        DB::statement(
            "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
            ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
            ."'Piutang Keluar', 'Piutang Masuk', 'Hutang Masuk', 'Hutang Keluar'"
            .") NOT NULL"
        );
    }
};
