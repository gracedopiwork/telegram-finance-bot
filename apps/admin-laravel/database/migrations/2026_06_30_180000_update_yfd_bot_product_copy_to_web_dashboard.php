<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->first();
        if (! $row) {
            return;
        }

        $features = json_decode((string) ($row->features ?? '[]'), true);
        if (! is_array($features)) {
            $features = [];
        }

        $features = array_map(static function (string $item): string {
            return match (true) {
                str_contains(strtolower($item), 'google sheet') => 'Dashboard web real-time (portal YFD)',
                str_contains(strtolower($item), 'sheet terisolasi') => 'Sistem lisensi & akun terisolasi (privat)',
                default => $item,
            };
        }, $features);

        DB::table('cp_digital_products')
            ->where('code', 'yfd-bot-telegram')
            ->update([
                'tagline' => 'Catat keuangan harian via chat — AI auto-parse ke dashboard web YFD.',
                'description' => 'YFD Bot Telegram adalah asisten keuangan pribadi berbasis chat. Tinggal kirim pesan biasa seperti "makan malam 50rb" atau "beli kopi 18000 karena ngantuk", AI YFD otomatis mengekstrak nominal, kategori, jenis transaksi, sifat (Need/Wants/Saving/Donation), mood, dan bahkan menandai pembelian impulsif. Semua tersimpan di dashboard web pribadi yang siap dianalisis dokter finansial Anda.',
                'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                'meta_description' => 'Catat keuangan harian via chat di Telegram. AI YFD otomatis klasifikasikan & simpan ke dashboard web YFD Anda.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Copy lama tidak di-restore — data produk sudah pindah ke dashboard web.
    }
};
