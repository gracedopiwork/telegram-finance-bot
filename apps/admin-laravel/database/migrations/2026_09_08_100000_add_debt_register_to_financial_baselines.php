<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Debt Register — menggantikan input tunggal "Total utang (Rp)" dengan tabel per item.
 * Polanya mengikuti protection_policies: satu kolom json di financial_baselines.
 *
 * @see docs/Baseline_Data_IT_Guidance_v1.0.pdf Section 3
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_baselines', 'debt_register')) {
                $table->json('debt_register')->nullable()->after('total_debt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_baselines', 'debt_register')) {
                $table->dropColumn('debt_register');
            }
        });
    }
};
