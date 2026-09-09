<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * debt_id menghubungkan transaksi cicilan ke satu baris Debt Register.
 * Dipakai MURNI untuk narasi konsistensi pembayaran — tidak pernah untuk
 * mengurangi principal_outstanding (lihat Section 3.3 & 5).
 *
 * Tanpa foreign key: bot_transactions memang tidak punya FK sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('bot_transactions', 'debt_id')) {
                $table->string('debt_id', 32)->nullable()->index()->after('taxonomy_flags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bot_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('bot_transactions', 'debt_id')) {
                $table->dropColumn('debt_id');
            }
        });
    }
};
