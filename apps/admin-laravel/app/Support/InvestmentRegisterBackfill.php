<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Migrasi data existing: nilai lama "Total investasi" tunggal jadi satu baris
 * Daftar Investasi "Investasi (belum dirinci)". Polanya sama dengan
 * DebtRegisterBackfill (Section 7).
 *
 * Dipisah dari file migration supaya bisa diuji langsung — migration hanya
 * memanggil kelas ini.
 */
class InvestmentRegisterBackfill
{
    public const PLACEHOLDER_PURPOSE = 'Investasi (belum dirinci)';

    /**
     * @return int jumlah baris baseline yang diisi
     */
    public function __invoke(): int
    {
        if (! Schema::hasTable('financial_baselines')
            || ! Schema::hasColumn('financial_baselines', 'investment_register')
            || ! Schema::hasColumn('financial_baselines', 'total_investment')) {
            return 0;
        }

        // last_updated sengaja memakai tanggal migrasi, bukan tanggal user
        // mengisi total_investment dulu — staleness harus dihitung dari titik
        // data ini benar-benar terakhir diverifikasi.
        $migratedOn = now()->toDateString();
        $filled = 0;

        DB::table('financial_baselines')
            ->select('id', 'total_investment')
            ->whereNotNull('total_investment')
            ->where('total_investment', '>', 0)
            ->whereNull('investment_register')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($migratedOn, &$filled) {
                foreach ($rows as $row) {
                    DB::table('financial_baselines')
                        ->where('id', $row->id)
                        ->update(['investment_register' => json_encode([[
                            'investment_id' => (string) Str::ulid(),
                            'investment_type' => 'lainnya',
                            'purpose' => self::PLACEHOLDER_PURPOSE,
                            'start_year' => null,
                            'target_years' => null,
                            'target_amount' => null,
                            // Angka lama adalah nilai yang dipegang sekarang, bukan modal
                            // yang disetor. Pokok dibiarkan null supaya yield tampil "—"
                            // daripada mengarang 0% dari modal yang tidak pernah diketahui.
                            'realized_principal' => null,
                            'realized_actual' => (int) $row->total_investment,
                            'last_updated' => $migratedOn,
                        ]])]);
                    $filled++;
                }
            });

        return $filled;
    }
}
