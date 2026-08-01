<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliates', 'bank_name')) {
                $table->string('bank_name', 80)->nullable()->after('npwp');
            }
            if (! Schema::hasColumn('affiliates', 'bank_account_number')) {
                $table->string('bank_account_number', 64)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('affiliates', 'bank_account_name')) {
                $table->string('bank_account_name', 120)->nullable()->after('bank_account_number');
            }
        });

        Schema::table('affiliate_claims', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliate_claims', 'bank_name')) {
                $table->string('bank_name', 80)->nullable()->after('npwp_snapshot');
            }
            if (! Schema::hasColumn('affiliate_claims', 'bank_account_number')) {
                $table->string('bank_account_number', 64)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('affiliate_claims', 'bank_account_name')) {
                $table->string('bank_account_name', 120)->nullable()->after('bank_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            foreach (['bank_name', 'bank_account_number', 'bank_account_name'] as $col) {
                if (Schema::hasColumn('affiliates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('affiliate_claims', function (Blueprint $table) {
            foreach (['bank_name', 'bank_account_number', 'bank_account_name'] as $col) {
                if (Schema::hasColumn('affiliate_claims', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
