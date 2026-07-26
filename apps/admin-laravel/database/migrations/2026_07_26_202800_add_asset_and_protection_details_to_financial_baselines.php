<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_baselines', 'asset_details')) {
                $table->json('asset_details')->nullable()->after('total_asset');
            }
            if (! Schema::hasColumn('financial_baselines', 'protection_policies')) {
                $table->json('protection_policies')->nullable()->after('has_life_insurance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_baselines', 'asset_details')) {
                $table->dropColumn('asset_details');
            }
            if (Schema::hasColumn('financial_baselines', 'protection_policies')) {
                $table->dropColumn('protection_policies');
            }
        });
    }
};
