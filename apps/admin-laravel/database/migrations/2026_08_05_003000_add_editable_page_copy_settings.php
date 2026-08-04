<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teks halaman marketing → Site Settings (editable di Admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $now = now();
        $rows = [];

        $add = function (string $key, string $value, string $type, string $group, string $label, int $sort) use (&$rows, $now): void {
            $rows[] = compact('key', 'value', 'type', 'group', 'label', 'sort') + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        // —— Halaman Tentang ——
        $add('page.tentang.hero_eyebrow', 'TENTANG YFD', 'text', 'page_tentang', 'Tentang — Hero eyebrow', 1);
        $add('page.tentang.hero_title', 'Pusat Kesehatan Finansial Pertama di Indonesia.', 'textarea', 'page_tentang', 'Tentang — Hero judul', 2);
        $add('page.tentang.hero_intro', "YFD didirikan oleh dua dokter umum yang melihat bahwa masyarakat tidak hanya butuh\nkesehatan jasmani, tetapi juga kesehatan finansial yang krusial dalam mendukung\nstabilitas dan kelangsungan hidup bernegara.", 'textarea', 'page_tentang', 'Tentang — Hero intro', 3);
        $add('page.tentang.hero_quote', '"Tidak hanya tubuh yang bisa diserang penyakit, namun dompet yang sakit juga memerlukan dokter."', 'textarea', 'page_tentang', 'Tentang — Hero kutipan', 4);
        $add('page.tentang.hero_card_title', 'Herd Financial Immunity', 'text', 'page_tentang', 'Tentang — Kartu hero judul', 5);
        $add('page.tentang.hero_card_desc', 'Membangun kekebalan komunitas finansial untuk masyarakat mayoritas Indonesia.', 'textarea', 'page_tentang', 'Tentang — Kartu hero deskripsi', 6);
        $add('page.tentang.bg_section_title', 'Latar Belakang Berdirinya YFD Indonesia', 'text', 'page_tentang', 'Tentang — Judul section latar', 10);
        $add('page.tentang.stat_1_value', '2 Dokter', 'text', 'page_tentang', 'Tentang — Stat 1 nilai', 11);
        $add('page.tentang.stat_1_label', 'Founder Dokter Umum', 'text', 'page_tentang', 'Tentang — Stat 1 label', 12);
        $add('page.tentang.stat_2_value', '2035', 'text', 'page_tentang', 'Tentang — Stat 2 nilai', 13);
        $add('page.tentang.stat_2_label', 'Target Visi YFD', 'text', 'page_tentang', 'Tentang — Stat 2 label', 14);
        $add('page.tentang.vision_section_title', 'Visi Kami', 'text', 'page_tentang', 'Tentang — Judul visi', 20);
        $add('page.tentang.mission_section_title', 'Delapan Misi YFD', 'text', 'page_tentang', 'Tentang — Judul misi', 21);
        $add('page.tentang.values_section_title', 'Enam Nilai Inti YFD', 'text', 'page_tentang', 'Tentang — Judul nilai', 22);
        $add('page.tentang.cta_title', 'Siap membangun kesehatan finansial bersama YFD?', 'textarea', 'page_tentang', 'Tentang — CTA judul', 30);
        $add('page.tentang.cta_body', 'Mulai dari screening gratis, atau kenali lebih dekat tim dokter finansial kami.', 'textarea', 'page_tentang', 'Tentang — CTA isi', 31);
        $add('page.tentang.cta_primary', 'Mulai Check Up Gratis', 'text', 'page_tentang', 'Tentang — CTA tombol utama', 32);
        $add('page.tentang.cta_secondary', 'Kenali Tim Dokter', 'text', 'page_tentang', 'Tentang — CTA tombol sekunder', 33);

        // —— Halaman Layanan ——
        $add('page.layanan.hero_eyebrow', 'EKOSISTEM LAYANAN YFD', 'text', 'page_layanan', 'Layanan — Hero eyebrow', 1);
        $add('page.layanan.hero_title', 'Tujuh Pilar Layanan Kesehatan Finansial', 'textarea', 'page_layanan', 'Layanan — Hero judul', 2);
        $add('page.layanan.hero_subtitle', 'Mengintegrasikan edukasi, proteksi, pendampingan, dan solusi finansial dalam satu ekosistem untuk membangun Herd Financial Immunity.', 'textarea', 'page_layanan', 'Layanan — Hero subjudul', 3);
        $add('page.layanan.cta_primary', 'Check up sekarang', 'text', 'page_layanan', 'Layanan — CTA utama', 4);
        $add('page.layanan.cta_secondary', 'Konsultasi WA', 'text', 'page_layanan', 'Layanan — CTA sekunder', 5);
        $add('page.layanan.pulse_1_title', 'Pulse Check', 'text', 'page_layanan', 'Layanan — Pulse 1 judul', 10);
        $add('page.layanan.pulse_1_desc', 'Setiap layanan dimulai dari pemeriksaan kesehatan finansial menyeluruh.', 'textarea', 'page_layanan', 'Layanan — Pulse 1 deskripsi', 11);
        $add('page.layanan.pulse_2_title', 'Personalized Plan', 'text', 'page_layanan', 'Layanan — Pulse 2 judul', 12);
        $add('page.layanan.pulse_2_desc', 'Rekomendasi disusun personal sesuai level dan tujuan keuangan masing-masing klien.', 'textarea', 'page_layanan', 'Layanan — Pulse 2 deskripsi', 13);
        $add('page.layanan.pulse_3_title', 'Pendampingan', 'text', 'page_layanan', 'Layanan — Pulse 3 judul', 14);
        $add('page.layanan.pulse_3_desc', 'Bukan sekadar transaksi — tim YFD mendampingi sampai tujuan keuangan tercapai.', 'textarea', 'page_layanan', 'Layanan — Pulse 3 deskripsi', 15);
        $add('page.layanan.partners_title', 'Partner for Financial Support', 'text', 'page_layanan', 'Layanan — Judul partner', 20);
        $add('page.layanan.partners_subtitle', 'Jaringan mitra profesional YFD untuk mendukung kebutuhan finansial & legal Anda.', 'textarea', 'page_layanan', 'Layanan — Subjudul partner', 21);
        $add('page.layanan.final_cta_title', 'Mulai dari Mana?', 'text', 'page_layanan', 'Layanan — CTA akhir judul', 30);
        $add('page.layanan.final_cta_body', 'Belum tahu layanan mana yang cocok? Mulai dari screening gratis — tim YFD akan mengarahkan langkah berikutnya.', 'textarea', 'page_layanan', 'Layanan — CTA akhir isi', 31);

        // —— Halaman Paket ——
        $add('page.paket.hero_eyebrow', 'TARIF & PAKET', 'text', 'page_paket', 'Paket — Hero eyebrow', 1);
        $add('page.paket.hero_title', 'Screening Gratis. Konsultasi Berbayar Sesuai Tahap.', 'textarea', 'page_paket', 'Paket — Hero judul', 2);
        $add('page.paket.hero_subtitle', 'Financial Health Check Up gratis. Tarif konsultasi mengikuti tahap finansial hasil screening Anda.', 'textarea', 'page_paket', 'Paket — Hero subjudul', 3);
        $add('page.paket.cta_checkup', 'Mulai Screening Gratis', 'text', 'page_paket', 'Paket — Tombol screening', 4);
        $add('page.paket.section_gratis_title', 'Yang Gratis vs Yang Berbayar', 'text', 'page_paket', 'Paket — Judul section gratis/berbayar', 10);
        $add('page.paket.section_tiers_title', 'Tarif Konsultasi per Tahap', 'text', 'page_paket', 'Paket — Judul tabel tarif', 11);
        $add('page.paket.final_cta_title', 'Siap konsultasi dengan dokter finansial?', 'textarea', 'page_paket', 'Paket — CTA akhir judul', 20);
        $add('page.paket.final_cta_body', 'Selesaikan screening terlebih dahulu agar rekomendasi tahap & tarif lebih akurat.', 'textarea', 'page_paket', 'Paket — CTA akhir isi', 21);

        // —— Halaman Produk ——
        $add('page.produk.hero_eyebrow', 'PRODUK DIGITAL', 'text', 'page_produk', 'Produk — Hero eyebrow', 1);
        $add('page.produk.hero_title', 'Alat Digital untuk Kesehatan Finansial Harian', 'textarea', 'page_produk', 'Produk — Hero judul', 2);
        $add('page.produk.hero_subtitle', 'Catat, pantau, dan evaluasi keputusan finansial Anda — didampingi pendekatan dokter finansial YFD.', 'textarea', 'page_produk', 'Produk — Hero subjudul', 3);

        // —— Halaman Penasihat ——
        $add('page.penasihat.hero_eyebrow', 'TIM DOKTER', 'text', 'page_penasihat', 'Penasihat — Hero eyebrow', 1);
        $add('page.penasihat.hero_title', 'Dokter Finansial YFD', 'textarea', 'page_penasihat', 'Penasihat — Hero judul', 2);
        $add('page.penasihat.hero_subtitle', 'Dua dokter umum yang membangun YFD agar masyarakat punya tempat berkonsultasi untuk kesehatan finansial — bukan sekadar produk keuangan.', 'textarea', 'page_penasihat', 'Penasihat — Hero subjudul', 3);
        $add('page.penasihat.cta_wa', 'Konsultasi via WhatsApp', 'text', 'page_penasihat', 'Penasihat — CTA WA di kartu', 4);
        $add('page.penasihat.final_cta_title', 'Siap Bertemu Dokter Finansial?', 'textarea', 'page_penasihat', 'Penasihat — CTA akhir judul', 10);
        $add('page.penasihat.final_cta_body', 'Booking jadwal konsultasi online via WhatsApp, atau mulai dari screening gratis.', 'textarea', 'page_penasihat', 'Penasihat — CTA akhir isi', 11);

        // —— Halaman Informasi ——
        $add('page.informasi.hero_eyebrow', 'INFORMASI & FAQ', 'text', 'page_informasi', 'Informasi — Hero eyebrow', 1);
        $add('page.informasi.hero_title', 'Ada pertanyaan tentang YFD?', 'textarea', 'page_informasi', 'Informasi — Hero judul', 2);
        $add('page.informasi.hero_subtitle', 'Temukan jawaban cepat di FAQ, atau hubungi tim YFD melalui kanal resmi kami.', 'textarea', 'page_informasi', 'Informasi — Hero subjudul', 3);
        $add('page.informasi.contact_title', 'Hubungi Kami', 'text', 'page_informasi', 'Informasi — Judul kontak', 10);
        $add('page.informasi.faq_title', 'Pertanyaan yang Sering Diajukan', 'text', 'page_informasi', 'Informasi — Judul FAQ', 11);
        $add('page.informasi.final_cta_title', 'Belum menemukan jawaban?', 'textarea', 'page_informasi', 'Informasi — CTA judul', 20);
        $add('page.informasi.final_cta_body', 'Chat WhatsApp YFD — tim kami siap membantu.', 'textarea', 'page_informasi', 'Informasi — CTA isi', 21);
        $add('page.informasi.final_cta_button', 'Chat WhatsApp', 'text', 'page_informasi', 'Informasi — CTA tombol', 22);

        // —— Halaman Wealthpedia ——
        $add('page.wealthpedia.hero_title', 'Wealthpedia', 'text', 'page_wealthpedia', 'Wealthpedia — Hero judul', 1);
        $add('page.wealthpedia.hero_subtitle', 'Pustaka edukasi kesehatan finansial dari YFD — artikel, insight perilaku, dan panduan praktis.', 'textarea', 'page_wealthpedia', 'Wealthpedia — Hero subjudul', 2);
        $add('page.wealthpedia.featured_title', 'Artikel Pilihan', 'text', 'page_wealthpedia', 'Wealthpedia — Judul artikel pilihan', 3);

        // —— Halaman Pertemuan ——
        $add('page.pertemuan.hero_badge', 'ONLINE BOOKING', 'text', 'page_pertemuan', 'Pertemuan — Badge', 1);
        $add('page.pertemuan.hero_title_standard', 'Booking Financial Consultation', 'text', 'page_pertemuan', 'Pertemuan — Judul konsultasi', 2);
        $add('page.pertemuan.hero_title_recovery', 'Booking Financial Recovery Program', 'text', 'page_pertemuan', 'Pertemuan — Judul recovery', 3);
        $add('page.pertemuan.hero_title_premarital', 'Booking Premarital Check Up', 'text', 'page_pertemuan', 'Pertemuan — Judul premarital', 4);
        $add('page.pertemuan.hero_subtitle_standard', 'Konsultasi 1-on-1 dengan dokter YFD dilakukan secara online via WhatsApp. Pilih tanggal & jam yang tersedia, lalu lanjut ke WhatsApp.', 'textarea', 'page_pertemuan', 'Pertemuan — Subjudul konsultasi', 5);
        $add('page.pertemuan.hero_subtitle_recovery', 'Pendampingan intensif untuk kondisi finansial darurat. Pilih tanggal & jam available, lanjut WhatsApp — admin verifikasi pembayaran untuk mengunci slot.', 'textarea', 'page_pertemuan', 'Pertemuan — Subjudul recovery', 6);
        $add('page.pertemuan.hero_subtitle_premarital', 'Karena 2 orang yang konsultasi, pilih dokter di awal agar sesi male, female, dan couple ditangani dokter yang sama. Lalu pilih tanggal & jam available.', 'textarea', 'page_pertemuan', 'Pertemuan — Subjudul premarital', 7);

        // —— Nav & Footer ——
        $add('page.nav.cta_wa', 'Konsultasi WA', 'text', 'page_nav', 'Nav — Tombol WA header', 1);
        $add('page.nav.float_wa', 'Chat WhatsApp', 'text', 'page_nav', 'Nav — Tombol WA mengambang', 2);
        $add('page.footer.blurb', 'Pusat kesehatan finansial pertama di Indonesia — pendekatan dokter untuk dompet yang lebih sehat.', 'textarea', 'page_nav', 'Footer — Blurb', 10);
        $add('page.footer.col_layanan', 'Layanan', 'text', 'page_nav', 'Footer — Judul kolom layanan', 11);
        $add('page.footer.col_perusahaan', 'Perusahaan', 'text', 'page_nav', 'Footer — Judul kolom perusahaan', 12);
        $add('page.footer.copyright_extra', 'Founded by dr. Ayuti Bulaan QWP & dr. Catherine QWP.', 'text', 'page_nav', 'Footer — Baris founder', 13);

        // —— Tarif konsultasi (override config) ——
        $add('pricing.period', '/sesi', 'text', 'page_pricing', 'Tarif — Satuan periode', 1);
        $add('pricing.standard_from', '100000', 'text', 'page_pricing', 'Tarif — Mulai konsultasi reguler (angka)', 2);
        $add('pricing.recovery_from', '150000', 'text', 'page_pricing', 'Tarif — Mulai recovery (angka)', 3);
        $add('pricing.multi_session_note', 'Satu kasus bisa membutuhkan lebih dari satu pertemuan — tim YFD akan menjelaskan rencana sesi setelah screening.', 'textarea', 'page_pricing', 'Tarif — Catatan multi-sesi', 4);

        foreach (['surviving', 'growing', 'steady', 'comfortable'] as $i => $stage) {
            $cfg = config("consultation_pricing.stages.{$stage}", []);
            $base = 10 + ($i * 10);
            $add("pricing.stage.{$stage}.label", (string) ($cfg['label'] ?? $stage), 'text', 'page_pricing', "Tarif — {$stage} label", $base);
            $add("pricing.stage.{$stage}.phase", (string) ($cfg['phase'] ?? ''), 'text', 'page_pricing', "Tarif — {$stage} fase", $base + 1);
            $add("pricing.stage.{$stage}.description", (string) ($cfg['description'] ?? ''), 'textarea', 'page_pricing', "Tarif — {$stage} deskripsi", $base + 2);
            $add("pricing.stage.{$stage}.price_min", (string) ($cfg['price_min'] ?? ''), 'text', 'page_pricing', "Tarif — {$stage} harga min", $base + 3);
            $add("pricing.stage.{$stage}.price_max", (string) ($cfg['price_max'] ?? ''), 'text', 'page_pricing', "Tarif — {$stage} harga max", $base + 4);
        }

        // —— Bundle pages ——
        foreach (['recovery', 'education', 'premarital'] as $bKey) {
            $b = config("yfd_bundles.{$bKey}", []);
            if (! is_array($b)) {
                continue;
            }
            $label = $b['title'] ?? $bKey;
            $add("bundle.{$bKey}.eyebrow", (string) ($b['eyebrow'] ?? ''), 'text', 'page_bundles', "{$label} — Eyebrow", 1);
            $add("bundle.{$bKey}.title", (string) ($b['title'] ?? ''), 'text', 'page_bundles', "{$label} — Judul", 2);
            $add("bundle.{$bKey}.description", (string) ($b['description'] ?? ''), 'textarea', 'page_bundles', "{$label} — Deskripsi", 3);
            $add("bundle.{$bKey}.features_label", (string) ($b['features_label'] ?? 'Cakupan'), 'text', 'page_bundles', "{$label} — Label fitur", 4);
            $add("bundle.{$bKey}.features", implode("\n", $b['features'] ?? []), 'textarea', 'page_bundles', "{$label} — Fitur (1 baris = 1 item)", 5);
            $add("bundle.{$bKey}.footnote", (string) ($b['footnote'] ?? ''), 'textarea', 'page_bundles', "{$label} — Catatan kaki", 6);
            $add("bundle.{$bKey}.cta_primary_label", (string) ($b['cta_primary']['label'] ?? ''), 'text', 'page_bundles', "{$label} — CTA utama", 7);
            $add("bundle.{$bKey}.cta_secondary_label", (string) ($b['cta_secondary']['label'] ?? ''), 'text', 'page_bundles', "{$label} — CTA sekunder", 8);

            $pricingLines = [];
            foreach ($b['pricing'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $pricingLines[] = ($row['label'] ?? '').'|'.($row['amount'] ?? '').'|'.($row['note'] ?? '');
            }
            if ($pricingLines !== []) {
                $add("bundle.{$bKey}.pricing_rows", implode("\n", $pricingLines), 'textarea', 'page_bundles', "{$label} — Tarif (Label|angka|catatan per baris)", 9);
            }
        }

        foreach ($rows as $row) {
            $exists = DB::table('site_settings')->where('key', $row['key'])->exists();
            if ($exists) {
                DB::table('site_settings')->where('key', $row['key'])->update([
                    'type' => $row['type'],
                    'group' => $row['group'],
                    'label' => $row['label'],
                    'sort' => $row['sort'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('site_settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->where(function ($q) {
            $q->where('key', 'like', 'page.%')
                ->orWhere('key', 'like', 'pricing.%')
                ->orWhere('key', 'like', 'bundle.%');
        })->delete();
    }
};
