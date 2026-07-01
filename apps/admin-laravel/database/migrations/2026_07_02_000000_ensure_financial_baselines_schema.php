<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_baselines')) {
            Schema::create('financial_baselines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('telegram_user_id');
                $table->timestamp('assessed_at');
                $table->timestamp('next_review_at');
                $table->unsignedTinyInteger('financial_stage_score')->default(0);
                $table->string('financial_stage', 32);
                $table->string('stage_label', 64);
                $table->string('current_goal', 512)->nullable();
                $table->unsignedBigInteger('avg_monthly_income')->nullable();
                $table->unsignedBigInteger('emergency_fund')->nullable();
                $table->unsignedBigInteger('cash_savings')->nullable();
                $table->unsignedBigInteger('total_investment')->nullable();
                $table->unsignedBigInteger('total_asset')->nullable();
                $table->unsignedBigInteger('total_debt')->nullable();
                $table->boolean('has_bpjs')->default(false);
                $table->boolean('has_health_insurance')->default(false);
                $table->boolean('has_income_protection')->default(false);
                $table->boolean('has_life_insurance')->default(false);
                $table->unsignedTinyInteger('ftsa_chd')->default(0);
                $table->unsignedTinyInteger('ftsa_rvd')->default(0);
                $table->unsignedTinyInteger('ftsa_ssd')->default(0);
                $table->unsignedTinyInteger('ftsa_esd')->default(0);
                $table->string('dominant_archetype', 16);
                $table->string('dominant_archetype_label', 64);
                $table->string('chd_level', 32)->nullable();
                $table->string('rvd_level', 32)->nullable();
                $table->string('ssd_level', 32)->nullable();
                $table->string('esd_level', 32)->nullable();
                $table->json('answers_json');
                $table->timestamps();

                $table->index(['telegram_user_id', 'assessed_at']);
            });

            return;
        }

        if (! Schema::hasColumn('financial_baselines', 'current_goal')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->string('current_goal', 512)->nullable()->after('stage_label');
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'avg_monthly_income')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('avg_monthly_income')->nullable()->after('stage_label');
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'emergency_fund')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('emergency_fund')->nullable();
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'cash_savings')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('cash_savings')->nullable();
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'total_investment')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('total_investment')->nullable();
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'total_asset')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('total_asset')->nullable();
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'total_debt')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->unsignedBigInteger('total_debt')->nullable();
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'has_bpjs')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->boolean('has_bpjs')->default(false);
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'has_health_insurance')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->boolean('has_health_insurance')->default(false);
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'has_income_protection')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->boolean('has_income_protection')->default(false);
            });
        }
        if (! Schema::hasColumn('financial_baselines', 'has_life_insurance')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->boolean('has_life_insurance')->default(false);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
