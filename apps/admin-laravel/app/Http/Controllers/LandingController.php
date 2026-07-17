<?php

namespace App\Http\Controllers;

use App\Models\CpAdvisor;
use App\Models\CpArticle;
use App\Models\CpDigitalProduct;
use App\Models\CpFaq;
use App\Models\CpService;
use App\Models\Setting;
use App\Support\ConsultationPricing;
use Illuminate\Support\Facades\Cache;

class LandingController extends Controller
{
    /**
     * Helper: ambil settings group sebagai array key/value (cached).
     */
    private function settingsByGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return Setting::where('group', $group)->orderBy('sort')->pluck('value', 'key')->toArray();
        });
    }

    public function home()
    {
        return view('Companyprofile.home', [
            'active'   => 'home',
            'hero'     => $this->settingsByGroup('hero'),
            'stats'    => $this->settingsByGroup('stats'),
            'reviews'  => $this->settingsByGroup('reviews'),
            'services' => CpService::active()->orderBy('sort')->take(6)->get(),
        ]);
    }

    public function tentang()
    {
        return view('Companyprofile.tentang', [
            'active'   => 'tentang',
            'about'    => $this->settingsByGroup('about'),
            'vision'   => $this->settingsByGroup('vision'),
            'mission'  => $this->settingsByGroup('mission'),
            'values'   => $this->settingsByGroup('values'),
        ]);
    }

    public function layanan()
    {
        return view('Companyprofile.layanan', [
            'active'   => 'layanan',
            'services' => CpService::active()->orderBy('sort')->get(),
        ]);
    }

    public function paket()
    {
        return view('Companyprofile.paket', [
            'active' => 'paket',
            'consultationTiers' => ConsultationPricing::stages(),
            'consultationMeta' => config('consultation_pricing'),
        ]);
    }

    public function penasihat()
    {
        return view('Companyprofile.penasihat', [
            'active'   => 'penasihat',
            'advisors' => CpAdvisor::active()->orderBy('sort')->get(),
        ]);
    }

    public function produk()
    {
        $products = CpDigitalProduct::active()->orderBy('sort')->orderBy('id')->get();
        $featured = $products->firstWhere('is_featured', true) ?? $products->first();
        $others   = $products->reject(fn ($p) => $featured && $p->id === $featured->id)->values();

        return view('Companyprofile.produk', [
            'active'   => 'produk',
            'featured' => $featured,
            'products' => $products,
            'others'   => $others,
        ]);
    }

    public function wealthpedia()
    {
        $categoryFilter = trim((string) request('category', ''));
        $articlesQuery = CpArticle::active()->orderBy('sort')->orderByDesc('id');
        if ($categoryFilter !== '') {
            $articlesQuery->where('category', $categoryFilter);
        }

        $categoryMeta = [
            'Cashflow' => ['ic' => 'water_drop', 'title' => 'Cashflow & Budgeting', 'desc' => 'Mengatur arus kas, anggaran, dan kebiasaan harian.'],
            'Cashflow & Budgeting' => ['ic' => 'water_drop', 'title' => 'Cashflow & Budgeting', 'desc' => 'Mengatur arus kas, anggaran, dan kebiasaan harian.'],
            'Hutang' => ['ic' => 'credit_card_off', 'title' => 'Manajemen Hutang', 'desc' => 'Strategi keluar dari hutang dan menghindari hutang destruktif.'],
            'Manajemen Hutang' => ['ic' => 'credit_card_off', 'title' => 'Manajemen Hutang', 'desc' => 'Strategi keluar dari hutang dan menghindari hutang destruktif.'],
            'Dana Darurat' => ['ic' => 'savings', 'title' => 'Dana Darurat & Tabungan', 'desc' => 'Membangun bantalan finansial yang sehat.'],
            'Tabungan' => ['ic' => 'savings', 'title' => 'Dana Darurat & Tabungan', 'desc' => 'Membangun bantalan finansial yang sehat.'],
            'Proteksi' => ['ic' => 'shield', 'title' => 'Proteksi & Asuransi', 'desc' => 'Memahami asuransi sebelum membeli.'],
            'Asuransi' => ['ic' => 'shield', 'title' => 'Proteksi & Asuransi', 'desc' => 'Memahami asuransi sebelum membeli.'],
            'Investasi' => ['ic' => 'trending_up', 'title' => 'Investasi Dasar', 'desc' => 'Reksadana, saham, emas — untuk pemula.'],
            'Emotional Finance' => ['ic' => 'psychology', 'title' => 'Emotional Finance', 'desc' => 'Trauma finansial, impulsive spending, regulasi diri.'],
        ];

        $categories = CpArticle::active()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->selectRaw('COUNT(*) as article_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(function ($row) use ($categoryMeta) {
                $name = (string) $row->category;
                $meta = $categoryMeta[$name] ?? [
                    'ic' => 'article',
                    'title' => $name,
                    'desc' => 'Artikel seputar '.$name.'.',
                ];

                return [
                    'name' => $name,
                    'ic' => $meta['ic'],
                    'title' => $meta['title'],
                    'desc' => $meta['desc'],
                    'count' => (int) $row->article_count,
                ];
            })
            ->values()
            ->all();

        return view('Companyprofile.wealthpedia', [
            'active' => 'wealthpedia',
            'articles' => $articlesQuery->get(),
            'categories' => $categories,
            'activeCategory' => $categoryFilter,
        ]);
    }

    public function wealthpediaShow(string $slug)
    {
        $article = CpArticle::active()->where('slug', $slug)->firstOrFail();

        return view('Companyprofile.wealthpedia-show', [
            'active'  => 'wealthpedia',
            'article' => $article,
        ]);
    }

    public function pertemuan()
    {
        $stageKey = request('stage');
        $consultationType = request('type', 'standard');

        return view('Companyprofile.pertemuan', [
            'active' => $consultationType === 'recovery' ? 'recovery' : 'pertemuan',
            'consultationTiers' => ConsultationPricing::stages(),
            'consultationMeta' => config('consultation_pricing'),
            'selectedStage' => is_string($stageKey) ? $stageKey : null,
            'selectedType' => is_string($consultationType) ? $consultationType : 'standard',
            'selectedTier' => ConsultationPricing::forStage(is_string($stageKey) ? $stageKey : null),
        ]);
    }

    public function informasi()
    {
        return view('Companyprofile.informasi', [
            'active' => 'informasi',
            'faqs'   => CpFaq::active()->orderBy('sort')->get(),
        ]);
    }

    public function bundle(string $slug)
    {
        $key = match ($slug) {
            'edukasi' => 'education',
            'recovery' => 'recovery',
            default => $slug,
        };
        $bundle = config("yfd_bundles.{$key}");
        if (! is_array($bundle)) {
            abort(404);
        }

        return view('Companyprofile.bundle', [
            'active' => $bundle['active'] ?? 'layanan',
            'bundle' => $bundle,
        ]);
    }

    public function index()
    {
        return $this->home();
    }
}
