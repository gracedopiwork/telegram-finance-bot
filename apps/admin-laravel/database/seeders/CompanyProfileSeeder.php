<?php

namespace Database\Seeders;

use App\Models\CpAdvisor;
use App\Models\CpArticle;
use App\Models\CpFaq;
use App\Models\CpPackage;
use App\Models\CpService;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedPackages();
        $this->seedAdvisors();
        $this->seedServices();
        $this->seedFaqs();
        $this->seedArticles();
    }

    private function seedSettings(): void
    {
        $settings = [
            // ===== Brand =====
            ['key' => 'brand.name',          'value' => 'Your Financial Doctor',                                  'type' => 'text',     'group' => 'brand',   'label' => 'Nama Brand',           'sort' => 1],
            ['key' => 'brand.short',         'value' => 'YFD',                                                    'type' => 'text',     'group' => 'brand',   'label' => 'Singkatan',            'sort' => 2],
            ['key' => 'brand.tagline',       'value' => "Indonesia's First Financial Health Center",              'type' => 'text',     'group' => 'brand',   'label' => 'Tagline',              'sort' => 3],
            ['key' => 'brand.motto',         'value' => 'Building Financially Healthy Generations.',              'type' => 'text',     'group' => 'brand',   'label' => 'Motto',                'sort' => 4],
            ['key' => 'brand.logo',          'value' => 'images/yfd-logo.png',                                    'type' => 'image',    'group' => 'brand',   'label' => 'Logo header (navbar)',  'sort' => 5],
            ['key' => 'brand.logo_footer',   'value' => '',                                                         'type' => 'image',    'group' => 'brand',   'label' => 'Logo footer (kosong = sama dengan header)', 'sort' => 6],

            // ===== Contact =====
            ['key' => 'contact.phone',       'value' => '+6285111228911',                                         'type' => 'text',     'group' => 'contact', 'label' => 'Nomor Telepon',        'sort' => 1],
            ['key' => 'contact.wa_number',   'value' => '6285111228911',                                          'type' => 'text',     'group' => 'contact', 'label' => 'WhatsApp Number (intl)', 'sort' => 2],
            ['key' => 'contact.wa_message',  'value' => 'Halo YFD, saya tertarik untuk konsultasi finansial. Mohon info jadwal dan paket yang tersedia. Terima kasih.', 'type' => 'textarea', 'group' => 'contact', 'label' => 'Default Pesan WA',    'sort' => 3],
            ['key' => 'contact.email',       'value' => 'yfinancialdoctor@gmail.com',                              'type' => 'text',     'group' => 'contact', 'label' => 'Email',                'sort' => 4],
            ['key' => 'contact.instagram',   'value' => 'your_financial_doctor',                                   'type' => 'text',     'group' => 'contact', 'label' => 'Instagram: username atau URL lengkap',  'sort' => 5],
            ['key' => 'contact.tiktok',      'value' => 'your_financial_doctor',                                   'type' => 'text',     'group' => 'contact', 'label' => 'TikTok: username atau URL lengkap',     'sort' => 6],
            ['key' => 'contact.threads',     'value' => 'your_financial_doctor',                                   'type' => 'text',     'group' => 'contact', 'label' => 'Threads: username atau URL lengkap',    'sort' => 7],
            ['key' => 'contact.address',     'value' => 'Indonesia',                                              'type' => 'text',     'group' => 'contact', 'label' => 'Alamat',               'sort' => 8],

            // ===== Hero (Home) =====
            ['key' => 'hero.eyebrow',        'value' => "INDONESIA'S FIRST FINANCIAL HEALTH CENTER",              'type' => 'text',     'group' => 'hero',    'label' => 'Hero Eyebrow',         'sort' => 1],
            ['key' => 'hero.title',          'value' => 'Tubuh Bisa Sakit, Begitu Juga Dompet — Saatnya ke Dokter Finansial.', 'type' => 'textarea', 'group' => 'hero', 'label' => 'Hero Headline',  'sort' => 2],
            ['key' => 'hero.subtitle',       'value' => 'Your Financial Doctor (YFD) didirikan oleh dua dokter umum yang melihat bahwa masyarakat tidak hanya butuh kesehatan jasmani, tetapi juga kesehatan finansial. Kami hadir dengan pendekatan personal untuk membantu Anda mencapai Herd Financial Immunity.', 'type' => 'textarea', 'group' => 'hero', 'label' => 'Hero Subtitle', 'sort' => 3],
            ['key' => 'hero.cta_primary',    'value' => 'Mulai Financial Health Check Up',                        'type' => 'text',     'group' => 'hero',    'label' => 'CTA Utama',            'sort' => 4],
            ['key' => 'hero.cta_primary_url', 'value' => '',                                                      'type' => 'text',     'group' => 'hero',    'label' => 'Link CTA utama (diagnosa / eksternal, kosong = halaman Paket)', 'sort' => 5],
            ['key' => 'hero.cta_secondary',  'value' => 'Konsultasi Gratis via WA',                               'type' => 'text',     'group' => 'hero',    'label' => 'CTA Sekunder',         'sort' => 6],

            // ===== Stats (Latar Belakang) =====
            ['key' => 'stats.s1.value',      'value' => '82,2%',                                                  'type' => 'text',     'group' => 'stats',   'label' => 'Stat 1 — Angka',       'sort' => 1],
            ['key' => 'stats.s1.label',      'value' => 'Masyarakat menengah-bawah (BPS 2025)',                   'type' => 'text',     'group' => 'stats',   'label' => 'Stat 1 — Label',       'sort' => 2],
            ['key' => 'stats.s2.value',      'value' => '49%',                                                    'type' => 'text',     'group' => 'stats',   'label' => 'Stat 2 — Angka',       'sort' => 3],
            ['key' => 'stats.s2.label',      'value' => 'Populasi menuju kelas menengah',                         'type' => 'text',     'group' => 'stats',   'label' => 'Stat 2 — Label',       'sort' => 4],
            ['key' => 'stats.s3.value',      'value' => '68,75%',                                                 'type' => 'text',     'group' => 'stats',   'label' => 'Stat 3 — Angka',       'sort' => 5],
            ['key' => 'stats.s3.label',      'value' => 'Penduduk usia produktif',                                'type' => 'text',     'group' => 'stats',   'label' => 'Stat 3 — Label',       'sort' => 6],
            ['key' => 'stats.s4.value',      'value' => '8-9%',                                                   'type' => 'text',     'group' => 'stats',   'label' => 'Stat 4 — Angka',       'sort' => 7],
            ['key' => 'stats.s4.label',      'value' => 'Di bawah garis kemiskinan',                              'type' => 'text',     'group' => 'stats',   'label' => 'Stat 4 — Label',       'sort' => 8],

            // ===== About / Tentang =====
            ['key' => 'about.bg_p1',         'value' => 'Indonesia adalah negara dengan penduduk terbesar ke-4 di dunia dan masyarakat usia produktif terbanyak di Asia Tenggara (68,75% dari total populasi). Bonus demografi ini berpotensi besar — jika dikelola dengan baik.', 'type' => 'textarea', 'group' => 'about', 'label' => 'Latar Belakang — Paragraf 1', 'sort' => 1],
            ['key' => 'about.bg_p2',         'value' => 'Sayangnya berdasarkan data BPS 2025, mayoritas masyarakat Indonesia (82,2%) merupakan kelompok ekonomi menengah ke bawah, dengan 8–9% berada di bawah garis kemiskinan nasional. Indonesia bukan negara paling miskin — namun sebagian besar belum sehat secara finansial.', 'type' => 'textarea', 'group' => 'about', 'label' => 'Latar Belakang — Paragraf 2', 'sort' => 2],
            ['key' => 'about.bg_p3',         'value' => 'Pendidikan finansial adalah kunci naik kelas dan keluar dari kemiskinan. Sebanyak 49% masyarakat menengah-bawah sedang menuju kelas menengah dan menjadi tulang punggung Indonesia. Masalahnya bukan kemalasan — tapi rendahnya literasi keuangan.', 'type' => 'textarea', 'group' => 'about', 'label' => 'Latar Belakang — Paragraf 3', 'sort' => 3],

            // ===== Vision & Mission =====
            ['key' => 'vision.text',         'value' => 'Menjadi pelopor dan penggerak pusat kesehatan finansial pertama di Indonesia yang berfokus pada pelayanan perencanaan keuangan yang sehat, sadar, dan terarah melalui pendekatan komunal dan personal — untuk meningkatkan level keuangan setidaknya satu tingkat lebih maju, dan menurunkan persentase masyarakat rentan di bawah garis kemiskinan di tahun 2035.', 'type' => 'textarea', 'group' => 'vision', 'label' => 'Visi YFD',  'sort' => 1],

            ['key' => 'mission.m1.title',    'value' => 'Memahami Kondisi Finansial',                              'type' => 'text',     'group' => 'mission', 'label' => 'Misi 1 — Judul',       'sort' => 1],
            ['key' => 'mission.m1.icon',     'value' => 'visibility',                                              'type' => 'text',     'group' => 'mission', 'label' => 'Misi 1 — Icon',        'sort' => 2],
            ['key' => 'mission.m1.desc',     'value' => 'Membantu masyarakat memahami kondisi kesehatan finansial mereka secara objektif.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 1 — Deskripsi', 'sort' => 3],

            ['key' => 'mission.m2.title',    'value' => 'Sistem Financial Check Up',                               'type' => 'text',     'group' => 'mission', 'label' => 'Misi 2 — Judul',       'sort' => 4],
            ['key' => 'mission.m2.icon',     'value' => 'monitor_heart',                                           'type' => 'text',     'group' => 'mission', 'label' => 'Misi 2 — Icon',        'sort' => 5],
            ['key' => 'mission.m2.desc',     'value' => 'Membangun sistem financial check up yang mudah diakses dan terukur.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 2 — Deskripsi', 'sort' => 6],

            ['key' => 'mission.m3.title',    'value' => 'Edukasi Berjenjang',                                      'type' => 'text',     'group' => 'mission', 'label' => 'Misi 3 — Judul',       'sort' => 7],
            ['key' => 'mission.m3.icon',     'value' => 'school',                                                  'type' => 'text',     'group' => 'mission', 'label' => 'Misi 3 — Icon',        'sort' => 8],
            ['key' => 'mission.m3.desc',     'value' => 'Memberikan edukasi & pendampingan finansial dari level dasar hingga advance, melalui media massa & secara personal.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 3 — Deskripsi', 'sort' => 9],

            ['key' => 'mission.m4.title',    'value' => 'Ekosistem Terpadu',                                       'type' => 'text',     'group' => 'mission', 'label' => 'Misi 4 — Judul',       'sort' => 10],
            ['key' => 'mission.m4.icon',     'value' => 'eco',                                                     'type' => 'text',     'group' => 'mission', 'label' => 'Misi 4 — Icon',        'sort' => 11],
            ['key' => 'mission.m4.desc',     'value' => 'Mengintegrasikan edukasi, proteksi, pendampingan, dan solusi finansial dalam satu ekosistem.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 4 — Deskripsi', 'sort' => 12],

            ['key' => 'mission.m5.title',    'value' => 'Pelatihan Instrumen Finansial',                           'type' => 'text',     'group' => 'mission', 'label' => 'Misi 5 — Judul',       'sort' => 13],
            ['key' => 'mission.m5.icon',     'value' => 'fitness_center',                                          'type' => 'text',     'group' => 'mission', 'label' => 'Misi 5 — Icon',        'sort' => 14],
            ['key' => 'mission.m5.desc',     'value' => 'Memberikan pelatihan langsung/tidak langsung mengenai cara mengoptimalkan setiap instrumen finansial yang relevan.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 5 — Deskripsi', 'sort' => 15],

            ['key' => 'mission.m6.title',    'value' => 'Kontribusi Kebijakan',                                    'type' => 'text',     'group' => 'mission', 'label' => 'Misi 6 — Judul',       'sort' => 16],
            ['key' => 'mission.m6.icon',     'value' => 'gavel',                                                   'type' => 'text',     'group' => 'mission', 'label' => 'Misi 6 — Icon',        'sort' => 17],
            ['key' => 'mission.m6.desc',     'value' => 'Berkontribusi proaktif terhadap kebijakan makro & mikro ekonomi yang berdampak pada masyarakat luas.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 6 — Deskripsi', 'sort' => 18],

            ['key' => 'mission.m7.title',    'value' => 'Generasi Resilient',                                      'type' => 'text',     'group' => 'mission', 'label' => 'Misi 7 — Judul',       'sort' => 19],
            ['key' => 'mission.m7.icon',     'value' => 'diversity_3',                                             'type' => 'text',     'group' => 'mission', 'label' => 'Misi 7 — Icon',        'sort' => 20],
            ['key' => 'mission.m7.desc',     'value' => 'Membentuk generasi Indonesia yang resilient secara finansial menghadapi bonus demografi.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 7 — Deskripsi', 'sort' => 21],

            ['key' => 'mission.m8.title',    'value' => 'Regulasi Emosi & Anti-Korupsi',                           'type' => 'text',     'group' => 'mission', 'label' => 'Misi 8 — Judul',       'sort' => 22],
            ['key' => 'mission.m8.icon',     'value' => 'shield_with_heart',                                       'type' => 'text',     'group' => 'mission', 'label' => 'Misi 8 — Icon',        'sort' => 23],
            ['key' => 'mission.m8.desc',     'value' => 'Menurunkan angka korupsi Indonesia melalui pendekatan regulasi emosi dan kesehatan manusia.', 'type' => 'textarea', 'group' => 'mission', 'label' => 'Misi 8 — Deskripsi', 'sort' => 24],

            // ===== Core Values =====
            ['key' => 'values.v1.title',     'value' => 'Compassion First',                                        'type' => 'text',     'group' => 'values',  'label' => 'Value 1 — Judul',      'sort' => 1],
            ['key' => 'values.v1.icon',      'value' => 'favorite',                                                'type' => 'text',     'group' => 'values',  'label' => 'Value 1 — Icon',       'sort' => 2],
            ['key' => 'values.v1.desc',      'value' => 'Kami percaya setiap kondisi finansial punya cerita. Kami hadir untuk memahami, bukan menghakimi.', 'type' => 'textarea', 'group' => 'values', 'label' => 'Value 1 — Deskripsi', 'sort' => 3],

            ['key' => 'values.v2.title',     'value' => 'People Over Profit',                                      'type' => 'text',     'group' => 'values',  'label' => 'Value 2 — Judul',      'sort' => 4],
            ['key' => 'values.v2.icon',      'value' => 'groups',                                                  'type' => 'text',     'group' => 'values',  'label' => 'Value 2 — Icon',       'sort' => 5],
            ['key' => 'values.v2.desc',      'value' => 'Manusia selalu lebih penting daripada angka. Keputusan dibuat untuk keberlanjutan hidup klien, bukan sekadar transaksi.', 'type' => 'textarea', 'group' => 'values', 'label' => 'Value 2 — Deskripsi', 'sort' => 6],

            ['key' => 'values.v3.title',     'value' => 'Act with Integrity',                                      'type' => 'text',     'group' => 'values',  'label' => 'Value 3 — Judul',      'sort' => 7],
            ['key' => 'values.v3.icon',      'value' => 'verified',                                                'type' => 'text',     'group' => 'values',  'label' => 'Value 3 — Icon',       'sort' => 8],
            ['key' => 'values.v3.desc',      'value' => 'Transparan. Jujur. Bertanggung jawab. Tanpa janji instan. Tanpa manipulasi.', 'type' => 'textarea', 'group' => 'values', 'label' => 'Value 3 — Deskripsi', 'sort' => 9],

            ['key' => 'values.v4.title',     'value' => 'Education for Empowerment',                               'type' => 'text',     'group' => 'values',  'label' => 'Value 4 — Judul',      'sort' => 10],
            ['key' => 'values.v4.icon',      'value' => 'school',                                                  'type' => 'text',     'group' => 'values',  'label' => 'Value 4 — Icon',       'sort' => 11],
            ['key' => 'values.v4.desc',      'value' => 'Pengetahuan adalah fondasi. Literasi finansial adalah hak, bukan privilese.', 'type' => 'textarea', 'group' => 'values', 'label' => 'Value 4 — Deskripsi', 'sort' => 12],

            ['key' => 'values.v5.title',     'value' => 'Sustainable Financial Growth',                            'type' => 'text',     'group' => 'values',  'label' => 'Value 5 — Judul',      'sort' => 13],
            ['key' => 'values.v5.icon',      'value' => 'eco',                                                     'type' => 'text',     'group' => 'values',  'label' => 'Value 5 — Icon',       'sort' => 14],
            ['key' => 'values.v5.desc',      'value' => 'Kami membangun finansial yang sehat dan tahan krisis, bukan sekadar kenaikan cepat.', 'type' => 'textarea', 'group' => 'values', 'label' => 'Value 5 — Deskripsi', 'sort' => 15],

            ['key' => 'values.v6.title',     'value' => 'Healers of Financial Trauma',                             'type' => 'text',     'group' => 'values',  'label' => 'Value 6 — Judul',      'sort' => 16],
            ['key' => 'values.v6.icon',      'value' => 'healing',                                                 'type' => 'text',     'group' => 'values',  'label' => 'Value 6 — Icon',       'sort' => 17],
            ['key' => 'values.v6.desc',      'value' => 'YFD hadir sebagai pemutus pola dan trauma finansial. Membantu mengenali luka finansial masa lalu, memahami pola perilaku uang, lalu membangun strategi yang lebih sehat.', 'type' => 'textarea', 'group' => 'values', 'label' => 'Value 6 — Deskripsi', 'sort' => 18],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }

    private function seedPackages(): void
    {
        $packages = [
            [
                'code' => 'lite',
                'name' => 'Basic Diagnosis',
                'tier_label' => 'LITE',
                'price' => 250000,
                'period' => '/paket',
                'description' => 'Pemeriksaan dasar kesehatan finansial: cashflow, dana darurat, dan financial health score awal.',
                'features' => [
                    'Cashflow basic analysis',
                    'Dana darurat assessment',
                    'Financial Health Score',
                    'Risk category classification',
                    '1x sesi WhatsApp consultation (30 menit)',
                    'Quick recommendation report',
                ],
                'variant' => 'plain',
                'is_recommended' => false,
                'sort' => 1,
            ],
            [
                'code' => 'pro',
                'name' => 'Comprehensive Exam',
                'tier_label' => 'PRO',
                'price' => 750000,
                'period' => '/paket',
                'description' => 'Diagnosis mendalam mencakup hutang, proteksi, perilaku finansial & rekomendasi personal.',
                'features' => [
                    'Semua dari Basic Diagnosis',
                    'Debt ratio examination',
                    'Proteksi & asuransi audit',
                    'Investasi portfolio review',
                    'Perilaku finansial assessment',
                    'Financial stress diagnostic',
                    '2x sesi consultation (60 menit)',
                    'Personalized written recommendation',
                ],
                'variant' => 'featured',
                'is_recommended' => true,
                'sort' => 2,
            ],
            [
                'code' => 'ecosystem',
                'name' => 'Executive Wealth Check',
                'tier_label' => 'ECOSYSTEM',
                'price' => 2500000,
                'period' => '/paket',
                'description' => 'Diagnosis komprehensif + financial recovery plan, perencanaan pendidikan/pensiun, & monitoring 3 bulan.',
                'features' => [
                    'Semua dari Comprehensive Exam',
                    'Financial recovery plan',
                    'Perencanaan dana pendidikan',
                    'Perencanaan dana pensiun',
                    'Strategi proteksi keluarga',
                    'Pendampingan 3 bulan',
                    'Akses ke Education Platform',
                    '4x sesi consultation premium',
                ],
                'variant' => 'plain',
                'is_recommended' => false,
                'sort' => 3,
            ],
        ];

        foreach ($packages as $p) {
            CpPackage::updateOrCreate(['code' => $p['code']], $p);
        }
    }

    private function seedAdvisors(): void
    {
        $advisors = [
            [
                'name' => 'dr. Ayuti Bulaan',
                'role_label' => 'Founder & Financial Doctor',
                'badges' => ['Dokter Umum', 'QWP', 'Founder'],
                'years_exp' => '2018',
                'spec_short' => 'Founder YFD',
                'spec_icon' => 'favorite',
                'spec_long' => 'Dokter umum dengan ketertarikan mendalam pada dunia finansial sejak 2018. Tumbuh dengan melihat bagaimana masalah finansial dapat menghancurkan ketenangan keluarga, kesehatan mental, hingga masa depan seseorang. YFD lahir dari pengalaman hidup yang penuh tekanan finansial sejak kecil.',
                'tag' => 'Founder',
                'sort' => 1,
            ],
            [
                'name' => 'dr. Catherine',
                'role_label' => 'Co-Founder & Financial Doctor',
                'badges' => ['Dokter Umum', 'QWP', 'Co-Founder'],
                'years_exp' => 'YFD',
                'spec_short' => 'Co-Founder YFD',
                'spec_icon' => 'healing',
                'spec_long' => 'Bersama dr. Ayuti membangun YFD sebagai pusat kesehatan finansial pertama di Indonesia. Berfokus pada pendekatan personal yang memahami bahwa setiap kondisi finansial punya cerita — dan klien butuh dokter, bukan penjual produk.',
                'tag' => 'Co-Founder',
                'sort' => 2,
            ],
        ];

        foreach ($advisors as $a) {
            CpAdvisor::updateOrCreate(['name' => $a['name']], $a);
        }
    }

    private function seedServices(): void
    {
        $services = [
            [
                'section' => 'main',
                'eyebrow' => 'Diagnostic',
                'title' => 'Financial Health Check Up',
                'description' => 'Pemeriksaan kesehatan finansial menyeluruh untuk mengukur fondasi keuangan Anda. Kami melakukan diagnosa objektif terhadap semua aspek vital kondisi finansial Anda.',
                'icon' => 'monitor_heart',
                'features' => [
                    'Cashflow Analysis',
                    'Debt Ratio Examination',
                    'Dana Darurat Scan',
                    'Proteksi & Asuransi Audit',
                    'Investasi Portfolio Review',
                    'Perilaku Finansial Assessment',
                    'Financial Stress Diagnostic',
                    'Tingkat Risiko Finansial',
                ],
                'cta_label' => 'Lihat Paket Health Check Up',
                'cta_route' => 'company.paket',
                'sort' => 1,
            ],
            [
                'section' => 'main',
                'eyebrow' => 'Personal Care',
                'title' => 'Financial Consultation',
                'description' => 'Konsultasi personal one-on-one dengan tim dokter finansial untuk merancang strategi keuangan yang sesuai kondisi Anda.',
                'icon' => 'stethoscope',
                'features' => [
                    'Penyusunan tujuan finansial',
                    'Perbaikan cashflow',
                    'Manajemen hutang',
                    'Persiapan dana pendidikan',
                    'Perencanaan dana pensiun',
                    'Strategi proteksi keluarga',
                ],
                'cta_label' => 'Booking Konsultasi',
                'cta_route' => 'company.pertemuan',
                'sort' => 2,
            ],
            [
                'section' => 'main',
                'eyebrow' => 'Education',
                'title' => 'Financial Education Platform',
                'description' => 'Platform edukasi multi-channel yang fokus pada literasi finansial praktis, emotional finance, dan pembentukan financial resilience.',
                'icon' => 'school',
                'features' => [
                    'Webinar & Workshop',
                    'Kelas Online',
                    'Social Media Education',
                    'E-book',
                    'Community Learning',
                    'Practical financial literacy',
                ],
                'cta_label' => 'Kunjungi Wealthpedia',
                'cta_route' => 'company.wealthpedia',
                'sort' => 3,
            ],
            [
                'section' => 'main',
                'eyebrow' => 'Digital Tool',
                'title' => 'Digital Financial Monitoring',
                'description' => 'Produk digital untuk memantau kesehatan finansial Anda secara real-time. Tracking otomatis, reminder, dan goal monitoring dalam satu dashboard.',
                'icon' => 'monitoring',
                'features' => [
                    'Pencatatan keuangan',
                    'Tracking cashflow',
                    'Monitoring financial health score',
                    'Reminder finansial',
                    'Goal tracking',
                    'Progress monitoring',
                ],
                'cta_label' => 'Coba Bot Monitoring',
                'cta_route' => null,
                'sort' => 4,
            ],
            [
                'section' => 'main',
                'eyebrow' => 'Recovery',
                'title' => 'Financial Recovery Program',
                'description' => 'Program pendampingan intensif untuk individu yang sedang dalam fase kritis finansial — financial trauma, hutang berlebih, atau kehilangan pekerjaan.',
                'icon' => 'healing',
                'features' => [
                    'Financial burnout',
                    'Hutang berlebihan',
                    'Financial trauma',
                    'Krisis keuangan keluarga',
                    'Kehilangan pekerjaan',
                    'Financial instability',
                ],
                'cta_label' => 'Konsultasi Recovery',
                'cta_route' => 'company.pertemuan',
                'sort' => 5,
            ],
            [
                'section' => 'main',
                'eyebrow' => 'Human Capital',
                'title' => 'Education Financing & Human Capital Support',
                'description' => 'Sistem pembiayaan pendidikan berbasis pendampingan finansial — terutama untuk profesi strategis seperti tenaga kesehatan.',
                'icon' => 'volunteer_activism',
                'features' => [
                    'Akses pendidikan yang lebih luas',
                    'Keberlanjutan SDM berkualitas',
                    'Sistem pembiayaan sehat & bertanggung jawab',
                ],
                'cta_label' => 'Hubungi Tim YFD',
                'cta_route' => 'company.informasi',
                'sort' => 6,
            ],
        ];

        foreach ($services as $s) {
            CpService::updateOrCreate(['title' => $s['title']], $s);
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'category' => 'Umum',
                'question' => 'Apa itu Your Financial Doctor (YFD)?',
                'answer'   => 'YFD adalah pusat kesehatan finansial pertama di Indonesia, didirikan oleh dua dokter umum (dr. Ayuti Bulaan QWP & dr. Catherine QWP). Kami menggunakan pendekatan kesehatan untuk membantu masyarakat memahami kondisi finansial mereka secara objektif dan membangun pondasi keuangan yang kokoh.',
                'sort' => 1,
            ],
            [
                'category' => 'Layanan',
                'question' => 'Apakah konsultasi YFD dilakukan secara online atau offline?',
                'answer'   => 'Saat ini konsultasi YFD dilakukan secara online via WhatsApp & video call. Anda cukup mengisi form booking dan tim kami akan menjadwalkan sesi yang sesuai.',
                'sort' => 2,
            ],
            [
                'category' => 'Umum',
                'question' => 'Saya bukan orang yang pintar finansial — apakah cocok untuk saya?',
                'answer'   => 'Sangat cocok. YFD justru hadir untuk masyarakat yang merasa "buta finansial". Kami percaya literasi finansial adalah hak, bukan privilese. Kami bantu dari level dasar hingga advance, sesuai kondisi Anda.',
                'sort' => 3,
            ],
            [
                'category' => 'Layanan',
                'question' => 'Saya sedang krisis keuangan / banyak hutang — bisakah dibantu?',
                'answer'   => 'Bisa. Kami punya layanan khusus bernama Financial Recovery Program untuk individu yang mengalami financial burnout, hutang berlebih, atau financial trauma. Hubungi kami via WhatsApp untuk diskusi awal gratis.',
                'sort' => 4,
            ],
            [
                'category' => 'Umum',
                'question' => 'Apakah YFD menjual produk asuransi atau investasi?',
                'answer'   => 'YFD bukan agen produk. Kami adalah konsultan independen yang membantu Anda memahami kondisi finansial dan memberikan rekomendasi objektif. Keputusan akhir tetap di tangan Anda.',
                'sort' => 5,
            ],
            [
                'category' => 'Pricing',
                'question' => 'Berapa biaya konsultasi YFD?',
                'answer'   => 'Konsultasi awal via WhatsApp gratis. Untuk Financial Health Check Up dan layanan lain, silakan lihat halaman Paket Health Check Up untuk detail harga.',
                'sort' => 6,
            ],
            [
                'category' => 'Booking',
                'question' => 'Bagaimana cara saya melakukan booking?',
                'answer'   => 'Klik tombol "Booking Pertemuan" atau langsung "Konsultasi via WA" di header. Isi form, lalu Anda akan diarahkan ke chat WhatsApp dengan pesan otomatis terkirim.',
                'sort' => 7,
            ],
            [
                'category' => 'Karier',
                'question' => 'Saya tertarik bergabung sebagai konsultan / educator. Caranya?',
                'answer'   => 'Kirim CV dan portofolio Anda ke yfinancialdoctor@gmail.com dengan subject "Recruitment YFD". Lihat halaman Tim Dokter untuk role yang tersedia.',
                'sort' => 8,
            ],
        ];

        foreach ($faqs as $f) {
            CpFaq::updateOrCreate(['question' => $f['question']], $f);
        }
    }

    private function seedArticles(): void
    {
        $articles = [
            [
                'slug' => 'mengenali-trauma-finansial',
                'title' => 'Mengenali Trauma Finansial: Mengapa Kita Sulit Mengatur Uang?',
                'category' => 'Emotional Finance',
                'read_time' => '5 menit baca',
                'description' => 'Banyak keputusan destruktif manusia bukan karena kurang pintar, tapi karena trauma yang belum selesai...',
                'content_html' => '<p>Trauma finansial adalah pola psikologis yang terbentuk dari pengalaman finansial yang tidak menyenangkan di masa lalu. Banyak keputusan destruktif manusia sebenarnya bukan karena kurang pintar — tetapi karena manusia mengambil keputusan saat tubuh dan emosinya tidak sehat.</p><p>YFD percaya bahwa akar masalah finansial sering berada pada emosi, perilaku, dan ketidakmampuan meregulasi diri. Itulah mengapa kami menggunakan pendekatan kesehatan untuk membantu klien menyembuhkan luka finansial.</p>',
                'sort' => 1,
            ],
            [
                'slug' => 'metode-50-30-20-untuk-indonesia',
                'title' => 'Metode 50/30/20: Apakah Masih Relevan untuk Indonesia 2026?',
                'category' => 'Cashflow',
                'read_time' => '7 menit baca',
                'description' => 'Kerangka klasik 50/30/20 perlu disesuaikan dengan realitas inflasi dan struktur pengeluaran masyarakat Indonesia...',
                'content_html' => '<p>Metode 50/30/20 (50% kebutuhan, 30% keinginan, 20% tabungan/investasi) adalah kerangka klasik yang populer secara global. Tapi apakah kerangka ini masih relevan untuk konteks Indonesia 2026?</p><p>Dengan inflasi yang terus naik dan struktur pengeluaran rumah tangga yang berbeda dengan negara maju, banyak masyarakat Indonesia kesulitan mengikuti rasio ini. YFD merekomendasikan adaptasi yang lebih realistis sesuai level pendapatan.</p>',
                'sort' => 2,
            ],
            [
                'slug' => 'investasi-modal-100-ribu',
                'title' => 'Mulai Investasi dengan Modal Rp 100.000: Panduan Pemula',
                'category' => 'Investasi',
                'read_time' => '6 menit baca',
                'description' => 'Tidak perlu menunggu kaya untuk mulai investasi. Berikut panduan praktis untuk memulai dari nominal kecil...',
                'content_html' => '<p>Banyak masyarakat Indonesia menunda investasi karena merasa modal mereka terlalu kecil. Padahal, di era digital sekarang, Anda bisa mulai investasi reksadana atau emas dengan modal hanya Rp 100.000.</p><p>Kunci dari investasi bukan jumlahnya, tapi konsistensi dan pemahaman terhadap profil risiko Anda. YFD bantu Anda menemukan instrumen investasi yang sesuai kondisi.</p>',
                'sort' => 3,
            ],
        ];

        foreach ($articles as $a) {
            CpArticle::updateOrCreate(['slug' => $a['slug']], $a);
        }
    }
}
