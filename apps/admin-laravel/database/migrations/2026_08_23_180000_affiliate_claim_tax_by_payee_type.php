<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pajak klaim referral: Individu (NIK) 2.5%, Entitas/Badan Usaha 2%
 * (tidak lagi tergantung ada/tidak NPWP).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliates') && ! Schema::hasColumn('affiliates', 'payee_type')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->string('payee_type', 16)->default('individual')->after('npwp');
            });
        }

        if (Schema::hasTable('affiliate_claims') && ! Schema::hasColumn('affiliate_claims', 'payee_type')) {
            Schema::table('affiliate_claims', function (Blueprint $table) {
                $table->string('payee_type', 16)->nullable()->after('npwp_snapshot');
            });
        }

        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $now = now();
        $rows = [
            [
                'key' => 'affiliate.tax_individual_percent',
                'value' => '2.5',
                'type' => 'text',
                'group' => 'affiliate',
                'label' => 'Pajak klaim Individu / NIK (%)',
                'sort' => 5,
            ],
            [
                'key' => 'affiliate.tax_corporate_percent',
                'value' => '2',
                'type' => 'text',
                'group' => 'affiliate',
                'label' => 'Pajak klaim Entitas / Badan Usaha (%)',
                'sort' => 6,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('site_settings')->where('key', $row['key'])->exists();
            if ($exists) {
                DB::table('site_settings')->where('key', $row['key'])->update([
                    'value' => $row['value'],
                    'label' => $row['label'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('site_settings')->insert(array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // Legacy keys: set ke nilai individu agar admin lama tidak bingung
        foreach (['affiliate.tax_with_npwp_percent', 'affiliate.tax_without_npwp_percent'] as $legacyKey) {
            if (DB::table('site_settings')->where('key', $legacyKey)->exists()) {
                DB::table('site_settings')->where('key', $legacyKey)->update([
                    'value' => '2.5',
                    'label' => 'DEPRECATED — pakai tax_individual / tax_corporate',
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('affiliate_claims') && Schema::hasColumn('affiliate_claims', 'payee_type')) {
            Schema::table('affiliate_claims', function (Blueprint $table) {
                $table->dropColumn('payee_type');
            });
        }
        if (Schema::hasTable('affiliates') && Schema::hasColumn('affiliates', 'payee_type')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->dropColumn('payee_type');
            });
        }
    }
};
