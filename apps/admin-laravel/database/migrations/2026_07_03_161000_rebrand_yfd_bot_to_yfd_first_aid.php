<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        $description = 'YFD First Aid adalah asisten keuangan pribadi berbasis chat Telegram. '
            .'Tinggal kirim pesan biasa seperti "makan malam 50rb" atau "beli kopi 18000 karena ngantuk", '
            .'AI YFD otomatis mengekstrak nominal, kategori, jenis transaksi, sifat (Need/Wants/Saving/Donation), mood, '
            .'dan menandai pembelian impulsif. Semua tersimpan di dashboard web pribadi. '
            .'Sekali bayar — akses bot & dashboard berlaku selamanya.';

        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'name' => 'YFD First Aid',
            'tagline' => 'Catat keuangan harian via chat — lisensi YFD First Aid & dashboard selamanya.',
            'description' => $description,
            'meta_title' => 'YFD First Aid — Catat Keuangan via Chat',
            'meta_description' => 'Catat keuangan harian via chat di Telegram. Lisensi YFD First Aid & dashboard berlaku selamanya — sekali bayar.',
        ]);

        $mobile = DB::table('cp_digital_products')->where('code', 'yfd-mobile-app')->first();
        if ($mobile && is_string($mobile->features)) {
            $features = json_decode($mobile->features, true);
            if (is_array($features)) {
                $features = array_map(
                    fn (string $f) => $f === 'Sync data dari YFD Bot' ? 'Sync data dari YFD First Aid' : $f,
                    $features
                );
                DB::table('cp_digital_products')->where('code', 'yfd-mobile-app')->update([
                    'features' => json_encode($features),
                ]);
            }
        }

        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->update([
            'description' => 'Add-on untuk membuka dashboard FTSA di portal YFD: kuesioner FTSA 1–32, behavioral insight, dan indeks kesehatan finansial selama 12 bulan evaluasi. Tidak termasuk YFD First Aid (bot Telegram) atau dashboard transaksi harian. Jika sudah punya YFD First Aid, upgrade memakai lisensi yang sama.',
        ]);
    }

    public function down(): void
    {
        // Branding rollback not required
    }
};
