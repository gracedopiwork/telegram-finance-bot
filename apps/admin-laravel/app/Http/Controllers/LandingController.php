<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSlot;
use App\Models\CpAdvisor;
use App\Models\CpArticle;
use App\Models\CpDigitalProduct;
use App\Models\CpFaq;
use App\Models\CpPartner;
use App\Models\CpService;
use App\Models\GoogleBusinessConnection;
use App\Models\GoogleBusinessReview;
use App\Models\Setting;
use App\Services\ConsultationCheckoutService;
use App\Services\ConsultationSlotService;
use App\Support\ConsultationPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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
        $reviewsSettings = $this->settingsByGroup('reviews');
        $syncedReviews = GoogleBusinessReview::forHomepage()->get();
        $connection = GoogleBusinessConnection::current();

        if ($syncedReviews->isNotEmpty()) {
            if ($connection?->average_rating !== null) {
                $reviewsSettings['reviews.google_rating'] = (string) $connection->average_rating;
            }
            if ($connection?->total_review_count !== null) {
                $reviewsSettings['reviews.google_count'] = (string) $connection->total_review_count;
            }
        }

        return view('Companyprofile.home', [
            'active'   => 'home',
            'hero'     => $this->settingsByGroup('hero'),
            'homeCopy' => $this->settingsByGroup('home'),
            'stats'    => $this->settingsByGroup('stats'),
            'reviews'  => $reviewsSettings,
            'syncedReviews' => $syncedReviews,
            'services' => CpService::active()->orderBy('sort')->take(7)->get(),
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
            'page'     => $this->settingsByGroup('page_tentang'),
        ]);
    }

    public function layanan()
    {
        return view('Companyprofile.layanan', [
            'active'   => 'layanan',
            'services' => CpService::active()->orderBy('sort')->get(),
            'partners' => CpPartner::active()->orderBy('sort')->orderBy('id')->get(),
            'page'     => $this->settingsByGroup('page_layanan'),
        ]);
    }

    public function paket()
    {
        return view('Companyprofile.paket', [
            'active' => 'paket',
            'consultationTiers' => ConsultationPricing::stages(),
            'consultationMeta' => ConsultationPricing::meta(),
            'page' => $this->settingsByGroup('page_paket'),
        ]);
    }

    public function penasihat()
    {
        return view('Companyprofile.penasihat', [
            'active'   => 'penasihat',
            'advisors' => CpAdvisor::active()->orderBy('sort')->get(),
            'partners' => CpPartner::active()->orderBy('sort')->orderBy('id')->get(),
            'page'     => $this->settingsByGroup('page_penasihat'),
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
            'page'     => $this->settingsByGroup('page_produk'),
        ]);
    }

    public function wealthpedia()
    {
        $categoryFilter = trim((string) request('category', ''));
        $articlesQuery = CpArticle::active()->orderBy('sort')->orderByDesc('id');
        if ($categoryFilter !== '') {
            $articlesQuery->where('category', $categoryFilter);
        }

        $homeCopy = $this->settingsByGroup('home');
        $pageWealthpedia = $this->settingsByGroup('page_wealthpedia');
        $bfDesc = $homeCopy['wealthpedia.cat_bf_desc']
            ?? 'Memahami bagaimana emosi, kebiasaan, bias kognitif, dan proses pengambilan keputusan memengaruhi cara kita menggunakan uang dalam kehidupan sehari-hari.';
        $fhDesc = $homeCopy['wealthpedia.cat_fh_desc']
            ?? 'Pelajari prinsip-prinsip membangun kondisi finansial yang sehat, stabil, dan berkelanjutan.';

        $categoryMeta = [
            'Cashflow' => ['ic' => 'water_drop', 'title' => 'Cashflow & Budgeting', 'desc' => 'Mengatur arus kas, anggaran, dan kebiasaan harian.', 'color' => '#0d2b4e'],
            'Cashflow & Budgeting' => ['ic' => 'water_drop', 'title' => 'Cashflow & Budgeting', 'desc' => 'Mengatur arus kas, anggaran, dan kebiasaan harian.', 'color' => '#0d2b4e'],
            'Hutang' => ['ic' => 'credit_card_off', 'title' => 'Manajemen Hutang', 'desc' => 'Strategi keluar dari hutang dan menghindari hutang destruktif.', 'color' => '#174a7d'],
            'Manajemen Hutang' => ['ic' => 'credit_card_off', 'title' => 'Manajemen Hutang', 'desc' => 'Strategi keluar dari hutang dan menghindari hutang destruktif.', 'color' => '#174a7d'],
            'Dana Darurat' => ['ic' => 'savings', 'title' => 'Dana Darurat & Tabungan', 'desc' => 'Membangun bantalan finansial yang sehat.', 'color' => '#f5a623'],
            'Tabungan' => ['ic' => 'savings', 'title' => 'Dana Darurat & Tabungan', 'desc' => 'Membangun bantalan finansial yang sehat.', 'color' => '#f5a623'],
            'Proteksi' => ['ic' => 'shield', 'title' => 'Proteksi & Asuransi', 'desc' => 'Memahami asuransi sebelum membeli.', 'color' => '#123a63'],
            'Asuransi' => ['ic' => 'shield', 'title' => 'Proteksi & Asuransi', 'desc' => 'Memahami asuransi sebelum membeli.', 'color' => '#123a63'],
            'Investasi' => ['ic' => 'trending_up', 'title' => 'Investasi Dasar', 'desc' => 'Reksadana, saham, emas — untuk pemula.', 'color' => '#1c5a97'],
            'Emotional Finance' => [
                'ic' => 'psychology',
                'title' => 'Behavioural Finance',
                'desc' => $bfDesc,
                'color' => '#2bb3a3',
            ],
            'Behavioural Finance' => [
                'ic' => 'psychology',
                'title' => 'Behavioural Finance',
                'desc' => $bfDesc,
                'color' => '#2bb3a3',
            ],
            'Behavioral Finance' => [
                'ic' => 'psychology',
                'title' => 'Behavioural Finance',
                'desc' => $bfDesc,
                'color' => '#2bb3a3',
            ],
            'Financial Health' => [
                'ic' => 'ecg_heart',
                'title' => 'Financial Health',
                'desc' => $fhDesc,
                'color' => '#1d9e75',
            ],
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
                    'color' => '#0d2b4e',
                ];

                return [
                    'name' => $name,
                    'ic' => $meta['ic'],
                    'title' => $meta['title'],
                    'desc' => $meta['desc'],
                    'color' => $meta['color'] ?? '#0d2b4e',
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
            'homeCopy' => $homeCopy,
            'page' => $pageWealthpedia,
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
        if (! in_array($consultationType, ['standard', 'recovery', 'premarital'], true)) {
            $consultationType = 'standard';
        }
        $isPremarital = $consultationType === 'premarital';
        $isRecovery = $consultationType === 'recovery';

        $openSlots = collect();
        $availableDates = [];
        $slotsByDate = [];
        $advisors = collect();
        $datesByAdvisor = [];
        $slotsByAdvisorDate = [];

        if (Schema::hasTable('consultation_slots')) {
            app(ConsultationSlotService::class)->releaseExpiredHolds();
            $openSlots = ConsultationSlot::query()
                ->bookable()
                ->with('advisor')
                ->orderBy('starts_at')
                ->get();

            if ($isPremarital) {
                $advisors = CpAdvisor::active()->orderBy('sort')->get();
                foreach ($openSlots as $slot) {
                    $aid = (int) $slot->advisor_id;
                    $date = $slot->starts_at->format('Y-m-d');
                    $time = $slot->starts_at->format('H:i');
                    $datesByAdvisor[$aid][$date] = true;
                    if (! isset($slotsByAdvisorDate[$aid][$date][$time])) {
                        $slotsByAdvisorDate[$aid][$date][$time] = [
                            'id' => $slot->id,
                            'label' => $time,
                        ];
                    }
                }
                foreach ($datesByAdvisor as $aid => $dates) {
                    $keys = array_keys($dates);
                    sort($keys);
                    $datesByAdvisor[$aid] = $keys;
                }
                foreach ($slotsByAdvisorDate as $aid => $byDate) {
                    foreach ($byDate as $date => $times) {
                        ksort($times);
                        $slotsByAdvisorDate[$aid][$date] = array_values($times);
                    }
                }
            } else {
                foreach ($openSlots as $slot) {
                    $date = $slot->starts_at->format('Y-m-d');
                    $availableDates[$date] = true;
                    $time = $slot->starts_at->format('H:i');
                    if (! isset($slotsByDate[$date][$time])) {
                        $slotsByDate[$date][$time] = [
                            'id' => $slot->id,
                            'label' => $time,
                        ];
                    }
                }
                $availableDates = array_keys($availableDates);
                sort($availableDates);
                foreach ($slotsByDate as $date => $times) {
                    ksort($times);
                    $slotsByDate[$date] = array_values($times);
                }
            }
        }

        $calendarMonth = $openSlots->isNotEmpty()
            ? $openSlots->first()->starts_at->copy()->startOfMonth()
            : now()->startOfMonth();

        $active = match ($consultationType) {
            'recovery' => 'recovery',
            'premarital' => 'premarital',
            default => 'pertemuan',
        };

        return view('Companyprofile.pertemuan', [
            'active' => $active,
            'consultationTiers' => ConsultationPricing::stages(),
            'consultationMeta' => ConsultationPricing::meta(),
            'selectedStage' => is_string($stageKey) ? $stageKey : null,
            'selectedType' => $consultationType,
            'selectedTier' => ConsultationPricing::forStage(is_string($stageKey) ? $stageKey : null),
            'isRecovery' => $isRecovery,
            'isPremarital' => $isPremarital,
            'advisors' => $advisors,
            'datesByAdvisor' => $datesByAdvisor,
            'slotsByAdvisorDate' => $slotsByAdvisorDate,
            'openSlots' => $openSlots,
            'availableDates' => $availableDates,
            'slotsByDate' => $slotsByDate,
            'calendarMonth' => $calendarMonth->format('Y-m'),
            'holdMinutes' => ConsultationSlot::HOLD_MINUTES,
            'overtimeDisclosure' => ConsultationPricing::overtimeDisclosure(),
            'page' => $this->settingsByGroup('page_pertemuan'),
        ]);
    }

    public function pertemuanSlots(Request $request)
    {
        if (! Schema::hasTable('consultation_slots')) {
            return response()->json(['slots' => []]);
        }

        app(ConsultationSlotService::class)->releaseExpiredHolds();

        $date = (string) $request->query('date', '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['slots' => []]);
        }

        $advisorId = (int) $request->query('advisor_id', 0);

        $query = ConsultationSlot::query()
            ->bookable()
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at');

        if ($advisorId > 0) {
            $query->where('advisor_id', $advisorId);
        }

        $seen = [];
        $slots = [];
        foreach ($query->get() as $s) {
            $time = $s->starts_at->format('H:i');
            if (isset($seen[$time])) {
                continue;
            }
            $seen[$time] = true;
            $slots[] = [
                'id' => $s->id,
                'label' => $time,
                'starts_at' => $s->starts_at->toIso8601String(),
            ];
        }

        return response()->json(['slots' => $slots]);
    }

    public function bookPertemuan(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('consultation_slots')) {
            return back()->with('error', 'Sistem jadwal belum siap. Hubungi admin YFD.');
        }

        $data = $request->validate([
            'slot_id' => 'required|integer|exists:consultation_slots,id',
            'advisor_id' => 'nullable|integer|exists:cp_advisors,id',
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200',
            'phone' => 'nullable|string|max:40',
            'age' => 'nullable|integer|min:15|max:99',
            'stage' => 'nullable|string|max:80',
            'situation' => 'nullable|string|max:200',
            'condition' => 'nullable|string|max:2000',
            'service_type' => 'nullable|in:standard,recovery,premarital',
            'session_role' => 'nullable|in:male,female,couple',
        ]);

        $slot = ConsultationSlot::with('advisor')->findOrFail((int) $data['slot_id']);
        $serviceType = $data['service_type'] ?? 'standard';

        if ($serviceType === 'premarital') {
            $advisorId = (int) ($data['advisor_id'] ?? 0);
            if ($advisorId <= 0 || (int) $slot->advisor_id !== $advisorId) {
                return back()
                    ->withInput()
                    ->with('error', 'Premarital wajib pilih dokter di awal, lalu slot milik dokter yang sama.');
            }
        }

        $sessionRole = $data['session_role'] ?? null;
        $roleNote = match ($sessionRole) {
            'male' => 'Sesi 1-on-1 male',
            'female' => 'Sesi 1-on-1 female',
            'couple' => 'Sesi couple',
            default => null,
        };
        $condition = $data['condition'] ?? null;
        if ($roleNote) {
            $condition = trim($roleNote.($condition ? "\n".$condition : ''));
        }

        try {
            $result = app(ConsultationCheckoutService::class)->holdAndCreateSessionPayment($slot, [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'age' => isset($data['age']) ? (int) $data['age'] : null,
                'stage_key' => $data['stage'] ?? null,
                'situation' => $data['situation'] ?? null,
                'condition' => $condition,
                'service_type' => $serviceType,
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->away($result['payment_url']);
    }

    public function informasi()
    {
        return view('Companyprofile.informasi', [
            'active' => 'informasi',
            'faqs'   => CpFaq::active()->orderBy('sort')->get(),
            'page'   => $this->settingsByGroup('page_informasi'),
        ]);
    }

    public function privacy()
    {
        return view('Companyprofile.privacy', [
            'active' => 'informasi',
            'privacy' => config('portal_privacy'),
        ]);
    }

    public function guide()
    {
        return view('Companyprofile.guide', [
            'active' => 'informasi',
            'guide' => config('portal_guide'),
        ]);
    }

    public function bundle(string $slug)
    {
        $key = match ($slug) {
            'edukasi' => 'education',
            'recovery' => 'recovery',
            'premarital' => 'premarital',
            default => $slug,
        };
        $bundle = \App\Support\YfdBundles::get($key);
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
