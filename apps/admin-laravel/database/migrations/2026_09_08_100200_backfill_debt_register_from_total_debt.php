<?php

use App\Support\DebtRegisterBackfill;
use Illuminate\Database\Migrations\Migration;

/**
 * @see docs/Baseline_Data_IT_Guidance_v1.0.pdf Section 7
 */
return new class extends Migration
{
    public function up(): void
    {
        (new DebtRegisterBackfill)();
    }

    public function down(): void
    {
        // Hanya menghapus baris placeholder yang dibuat up(); total_debt lama
        // tetap utuh di kolomnya sendiri, dan register yang sudah dirinci user
        // tidak tersentuh.
        (new DebtRegisterBackfill)->rollback();
    }
};
