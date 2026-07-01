<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class FinancialBaselineSchema
{
    public static function isReady(): bool
    {
        if (! Schema::hasTable('financial_baselines')) {
            return false;
        }

        foreach ([
            'current_goal',
            'avg_monthly_income',
            'answers_json',
        ] as $column) {
            if (! Schema::hasColumn('financial_baselines', $column)) {
                return false;
            }
        }

        return true;
    }
}
