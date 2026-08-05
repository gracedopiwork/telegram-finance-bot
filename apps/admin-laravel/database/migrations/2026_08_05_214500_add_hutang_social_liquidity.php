<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Likuiditas Sosial — Utang Masuk/Keluar (mirror Piutang).
 * Utang Masuk = terima pinjaman sosial (bukan Pemasukan).
 * Utang Keluar = bayar balik pinjaman sosial (bukan Pengeluaran 4-bucket).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_transactions') && DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
                ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
                ."'Piutang Keluar', 'Piutang Masuk', 'Utang Masuk', 'Utang Keluar'"
                .") NOT NULL"
            );
        }

        if (! Schema::hasTable('bot_social_payables')) {
            Schema::create('bot_social_payables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('telegram_user_id')->index();
                $table->unsignedBigInteger('inbound_transaction_id')->nullable()->index();
                $table->unsignedBigInteger('settled_transaction_id')->nullable()->index();
                $table->string('counterparty_name', 120)->default('');
                $table->unsignedBigInteger('amount');
                $table->string('status', 32)->default('active')->index(); // active|settled
                $table->string('mood_at_borrow', 32)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('category_bucket_mappings')) {
            $now = now();
            foreach ([
                [
                    'transaction_type' => 'payable_in',
                    'reason' => 'Utang Masuk dikecualikan dari pendapatan & 4 bucket (Likuiditas Sosial)',
                ],
                [
                    'transaction_type' => 'payable_out',
                    'reason' => 'Utang Keluar dikecualikan dari 4 bucket (Likuiditas Sosial)',
                ],
            ] as $row) {
                $exists = DB::table('category_bucket_mappings')
                    ->where('transaction_type', $row['transaction_type'])
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('category_bucket_mappings')->insert([
                    'category' => '*',
                    'sub_category' => '-',
                    'bucket' => 'Transfer (Excluded)',
                    'transaction_type' => $row['transaction_type'],
                    'nature' => '',
                    'match_keywords' => null,
                    'reason' => $row['reason'],
                    'sort_order' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('settings')) {
            $body = DB::table('settings')->where('key', 'portal.social_liquidity_body')->first();
            if ($body) {
                DB::table('settings')->where('key', 'portal.social_liquidity_body')->update([
                    'value' => 'Arus kas karena hubungan sosial: piutang (kamu meminjamkan) dan hutang (kamu menerima pinjaman). Tidak masuk 4 bucket prescription — mengukur dampak jaringan sosial ke likuiditas tanpa menghakimi.',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_social_payables');

        if (Schema::hasTable('bot_transactions') && DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
                ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
                ."'Piutang Keluar', 'Piutang Masuk'"
                .") NOT NULL"
            );
        }

        if (Schema::hasTable('category_bucket_mappings')) {
            DB::table('category_bucket_mappings')
                ->whereIn('transaction_type', ['payable_in', 'payable_out'])
                ->delete();
        }
    }
};
