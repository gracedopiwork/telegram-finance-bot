<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Migrasi data existing (Section 7): nilai lama "Total utang" tunggal jadi
 * satu baris Debt Register "Utang (belum dirinci)".
 *
 * Dipisah dari file migration supaya bisa diuji langsung — migration hanya
 * memanggil kelas ini.
 */
class DebtRegisterBackfill
{
    public const PLACEHOLDER_NAME = 'Utang (belum dirinci)';

    /**
     * @return int jumlah baris baseline yang diisi
     */
    public function __invoke(): int
    {
        if (! Schema::hasTable('financial_baselines')
            || ! Schema::hasColumn('financial_baselines', 'debt_register')
            || ! Schema::hasColumn('financial_baselines', 'total_debt')) {
            return 0;
        }

        // last_updated sengaja memakai tanggal migrasi, bukan tanggal user
        // mengisi total_debt dulu — staleness harus dihitung dari titik data
        // ini benar-benar terakhir diverifikasi.
        $migratedOn = now()->toDateString();
        $filled = 0;

        DB::table('financial_baselines')
            ->select('id', 'total_debt')
            ->whereNotNull('total_debt')
            ->where('total_debt', '>', 0)
            ->whereNull('debt_register')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($migratedOn, &$filled) {
                foreach ($rows as $row) {
                    DB::table('financial_baselines')
                        ->where('id', $row->id)
                        ->update(['debt_register' => json_encode([[
                            'debt_id' => (string) Str::ulid(),
                            'debt_name' => self::PLACEHOLDER_NAME,
                            'debt_type' => 'lainnya',
                            'principal_outstanding' => (int) $row->total_debt,
                            'monthly_installment_reference' => null,
                            'remaining_tenor_months' => null,
                            'linked_expense_category' => DebtRegister::LINKED_EXPENSE_CATEGORY,
                            'last_updated' => $migratedOn,
                        ]])]);
                    $filled++;
                }
            });

        return $filled;
    }
}
