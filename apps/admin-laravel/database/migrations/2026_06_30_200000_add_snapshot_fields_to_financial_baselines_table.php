<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            $table->string('current_goal', 512)->nullable()->after('stage_label');
            $table->unsignedBigInteger('avg_monthly_income')->nullable()->after('current_goal');
            $table->unsignedBigInteger('emergency_fund')->nullable()->after('avg_monthly_income');
            $table->unsignedBigInteger('cash_savings')->nullable()->after('emergency_fund');
            $table->unsignedBigInteger('total_investment')->nullable()->after('cash_savings');
            $table->unsignedBigInteger('total_asset')->nullable()->after('total_investment');
            $table->unsignedBigInteger('total_debt')->nullable()->after('total_asset');
            $table->boolean('has_bpjs')->default(false)->after('total_debt');
            $table->boolean('has_health_insurance')->default(false)->after('has_bpjs');
            $table->boolean('has_income_protection')->default(false)->after('has_health_insurance');
            $table->boolean('has_life_insurance')->default(false)->after('has_income_protection');
        });
    }

    public function down(): void
    {
        Schema::table('financial_baselines', function (Blueprint $table): void {
            $table->dropColumn([
                'current_goal',
                'avg_monthly_income',
                'emergency_fund',
                'cash_savings',
                'total_investment',
                'total_asset',
                'total_debt',
                'has_bpjs',
                'has_health_insurance',
                'has_income_protection',
                'has_life_insurance',
            ]);
        });
    }
};
