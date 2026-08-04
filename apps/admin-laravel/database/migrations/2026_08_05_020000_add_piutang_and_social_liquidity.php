<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * YFD AI Taxonomy §5 / §5A — Piutang Keluar/Masuk + Likuiditas Sosial.
 * Tidak masuk 4 bucket prescription; panel dashboard terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_transactions') && DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM("
                ."'Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak', "
                ."'Piutang Keluar', 'Piutang Masuk'"
                .") NOT NULL"
            );
        }

        if (! Schema::hasTable('bot_social_receivables')) {
            Schema::create('bot_social_receivables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('telegram_user_id')->index();
                $table->unsignedBigInteger('outbound_transaction_id')->nullable()->index();
                $table->unsignedBigInteger('settled_transaction_id')->nullable()->index();
                $table->string('counterparty_name', 120)->default('');
                $table->unsignedBigInteger('amount');
                $table->timestamp('expected_back_at')->nullable();
                $table->string('status', 32)->default('active')->index(); // active|settled|written_off|disputed
                $table->string('mood_at_lend', 32)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('category_bucket_mappings')) {
            $exists = DB::table('category_bucket_mappings')
                ->where('transaction_type', 'receivable_out')
                ->exists();
            if (! $exists) {
                $now = now();
                DB::table('category_bucket_mappings')->insert([
                    [
                        'category' => '*',
                        'sub_category' => '-',
                        'bucket' => 'Transfer (Excluded)',
                        'transaction_type' => 'receivable_out',
                        'nature' => '',
                        'match_keywords' => null,
                        'reason' => 'Piutang Keluar dikecualikan dari 4 bucket (Likuiditas Sosial)',
                        'sort_order' => 0,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'category' => '*',
                        'sub_category' => '-',
                        'bucket' => 'Transfer (Excluded)',
                        'transaction_type' => 'receivable_in',
                        'nature' => '',
                        'match_keywords' => null,
                        'reason' => 'Piutang Masuk dikecualikan dari pendapatan & 4 bucket',
                        'sort_order' => 0,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            }
        }

        if (Schema::hasTable('settings')) {
            $now = now();
            $rows = [
                [
                    'key' => 'portal.social_liquidity_title',
                    'value' => 'Likuiditas Sosial',
                    'type' => 'text',
                    'group' => 'bot',
                    'label' => 'Portal — Judul panel Likuiditas Sosial',
                    'sort' => 90,
                ],
                [
                    'key' => 'portal.social_liquidity_body',
                    'value' => 'Pinjaman ke keluarga/teman (piutang) tidak masuk 4 bucket. Panel ini mengukur dampak jaringan sosial ke cashflow — tanpa menghakimi keputusan meminjamkan.',
                    'type' => 'textarea',
                    'group' => 'bot',
                    'label' => 'Portal — Teks panel Likuiditas Sosial',
                    'sort' => 91,
                ],
            ];
            foreach ($rows as $row) {
                $existing = DB::table('settings')->where('key', $row['key'])->first();
                if ($existing) {
                    continue;
                }
                DB::table('settings')->insert(array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_social_receivables');

        if (Schema::hasTable('bot_transactions') && DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM('Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak') NOT NULL"
            );
        }

        if (Schema::hasTable('category_bucket_mappings')) {
            DB::table('category_bucket_mappings')
                ->whereIn('transaction_type', ['receivable_out', 'receivable_in'])
                ->delete();
        }
    }
};
