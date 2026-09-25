<?php

use App\Models\CpAdvisor;
use App\Models\CpDigitalProduct;
use App\Models\CpService;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Revisi Website YFD (PDF 25 Sep 2026):
 * - Label Layanan baru (editable di Site Settings → Nav & Footer)
 * - Wealthpedia SEO meta
 * - Rename produk First Aid / FTSA
 * - Co-Founder spelling Catherina
 */
return new class extends Migration
{
    public function up(): void
    {
        $add = function (string $key, string $value, string $type, string $group, string $label, int $sort): void {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group' => $group,
                    'label' => $label,
                    'sort' => $sort,
                ]
            );
        };

        // —— Layanan mega-menu / footer (admin-editable) ——
        $add('page.nav.svc_checkup', 'Financial Health Check Up (FHCU)', 'text', 'page_nav', 'Layanan — FHCU', 10);
        $add('page.nav.svc_consult', 'Consultation with Financial Planner', 'text', 'page_nav', 'Layanan — Consultation', 11);
        $add('page.nav.svc_premarital', 'Pre Marital Financial Health Check Up', 'text', 'page_nav', 'Layanan — Premarital', 12);
        $add('page.nav.svc_recovery', 'Financial Recovery Program', 'text', 'page_nav', 'Layanan — Recovery', 13);
        $add('page.nav.svc_education', 'Financial Health Education Platform', 'text', 'page_nav', 'Layanan — Education', 14);
        $add('page.nav.svc_first_aid', 'First Aid by YFD', 'text', 'page_nav', 'Layanan — First Aid', 15);
        $add('page.nav.svc_ftsa', 'Financial Trauma Self-Assessment (FTSA)', 'text', 'page_nav', 'Layanan — FTSA', 16);

        // —— Wealthpedia SEO ——
        $add('page.wealthpedia.meta_title', 'Wealthpedia — Literasi Finansial YFD', 'text', 'page_wealthpedia', 'Wealthpedia — Meta title', 4);
        $add('page.wealthpedia.meta_description', 'Pustaka edukasi kesehatan finansial dari YFD — artikel, insight perilaku, dan panduan praktis.', 'textarea', 'page_wealthpedia', 'Wealthpedia — Meta description', 5);
        $add('page.wealthpedia.h2_categories', 'Kategori Edukasi', 'text', 'page_wealthpedia', 'Wealthpedia — H2 kategori', 6);
        $add('page.wealthpedia.h2_featured', 'Artikel Pilihan', 'text', 'page_wealthpedia', 'Wealthpedia — H2 artikel pilihan', 7);
        $add('page.wealthpedia.h2_social', 'Mini-Edukasi Harian di Social Media', 'text', 'page_wealthpedia', 'Wealthpedia — H2 social', 8);
        $add('page.wealthpedia.hero_eyebrow', 'FINANCIAL HEALTH EDUCATION PLATFORM', 'text', 'page_wealthpedia', 'Wealthpedia — Hero eyebrow', 0);

        // —— Produk disclaimer ——
        $add(
            'page.produk.disclaimer_body',
            'Kami tidak menjual asuransi, produk investasi, atau menjadi manajer investasi. Tools digital kami berfokus pada edukasi & pendampingan — Anda tetap memegang kendali penuh atas keputusan finansial.',
            'textarea',
            'page_produk',
            'Produk — Disclaimer body',
            10
        );

        $add('page.footer.copyright_extra', 'Founded by dr. Ayuti Bulaan QWP & dr. Catherina QWP.', 'text', 'page_nav', 'Footer — Baris founder', 13);

        // —— Digital product display names ——
        CpDigitalProduct::query()->where('code', 'yfd-bot-telegram')->update([
            'name' => 'First Aid by YFD',
            'meta_title' => 'First Aid by YFD — Catat Keuangan via Chat',
        ]);
        CpDigitalProduct::query()->where('code', 'yfd-ftsa-premium')->update([
            'name' => 'Financial Trauma Self-Assessment (FTSA)',
            'meta_title' => 'Financial Trauma Self-Assessment (FTSA) | YFD',
        ]);

        // —— Co-Founder spelling (Catherine → Catherina) ——
        CpAdvisor::query()
            ->where('name', 'dr. Catherine')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%Catherine%')
                    ->where('name', 'not like', '%Catherina%');
            })
            ->update(['name' => 'dr. Catherina']);

        // —— Pillar layanan titles (Admin → Layanan) ——
        $map = [
            'Financial Health Check Up / Screening' => 'Financial Health Check Up (FHCU)',
            'YFD First Aid' => 'First Aid by YFD',
            'Financial Consultation' => 'Consultation with Financial Planner',
            'Premarital Financial Health Check Up' => 'Pre Marital Financial Health Check Up',
            'Financial Education Platform' => 'Financial Health Education Platform',
            'Financial Recovery Program' => 'Financial Recovery Program',
        ];
        foreach ($map as $from => $to) {
            CpService::query()->where('title', $from)->update(['title' => $to]);
        }
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', [
            'page.nav.svc_checkup',
            'page.nav.svc_consult',
            'page.nav.svc_premarital',
            'page.nav.svc_recovery',
            'page.nav.svc_education',
            'page.nav.svc_first_aid',
            'page.nav.svc_ftsa',
            'page.wealthpedia.meta_title',
            'page.wealthpedia.meta_description',
            'page.wealthpedia.h2_categories',
            'page.wealthpedia.h2_featured',
            'page.wealthpedia.h2_social',
            'page.wealthpedia.hero_eyebrow',
            'page.produk.disclaimer_body',
        ])->delete();
    }
};
