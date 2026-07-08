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
        return view('Companyprofile.wealthpedia', [
            'active'   => 'wealthpedia',
            'articles' => CpArticle::active()->orderBy('sort')->orderByDesc('id')->get(),
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
