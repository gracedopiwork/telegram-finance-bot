<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar Investasi — menggantikan input tunggal "Total investasi (Rp)" dengan
 * tabel per item. Polanya mengikuti debt_register: satu kolom json di
 * financial_baselines.
 *
 * @see docs/Baseline_Data_IT_Guidance_v1.0.pdf Section 2 (breakdown per item
 *      disusun mengikuti pola Debt Register di Section 3)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_baselines', 'investment_register')) {
                $table->json('investment_register')->nullable()->after('total_investment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_baselines', 'investment_register')) {
                $table->dropColumn('investment_register');
            }
        });
    }
};
