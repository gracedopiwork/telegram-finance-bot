<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateClaim;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Services\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AffiliatesController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $affiliates = Affiliate::query()
            ->withCount(['commissions'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('email', 'like', "%{$q}%")
                        ->orWhere('referral_code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $affiliates->getCollection()->transform(function (Affiliate $a) {
            $a->setAttribute('available_balance', $a->availableBalance());

            return $a;
        });

        return view('admin.affiliates.index', [
            'affiliates' => $affiliates,
            'q' => $q,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $affiliates = Affiliate::query()
            ->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('referral_code', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'referral_code']);

        $results = $affiliates->map(function (Affiliate $a) {
            $label = trim(($a->name ?: '—').' · '.$a->email.' · '.$a->referral_code);

            return [
                'id' => $a->referral_code,
                'text' => $label,
                'referral_code' => $a->referral_code,
                'name' => $a->name,
                'email' => $a->email,
            ];
        })->values();

        // Juga cari nama dari order (belum tentu punya affiliate) untuk saran kode.
        // Mode referrer: hanya affiliate existing (untuk input kode pemberi referral).
        if (! $request->boolean('existing_only')) {
            $orderNames = Order::query()
                ->where(function ($inner) use ($q) {
                    $inner->where('full_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->latest('id')
                ->limit(15)
                ->get(['full_name', 'email']);

            $seenEmails = $affiliates->pluck('email')->map(fn ($e) => strtolower((string) $e))->all();
            $service = app(AffiliateService::class);

            foreach ($orderNames as $order) {
                $email = strtolower(trim((string) $order->email));
                if ($email === '' || in_array($email, $seenEmails, true)) {
                    continue;
                }
                $seenEmails[] = $email;
                $suggested = $service->suggestCodeFromName((string) $order->full_name);
                $results->push([
                    'id' => $suggested,
                    'text' => trim(($order->full_name ?: '—').' · '.$order->email.' · saran: '.$suggested),
                    'referral_code' => $suggested,
                    'name' => $order->full_name,
                    'email' => $order->email,
                    'suggested' => true,
                ]);
            }
        }

        return response()->json(['results' => $results->take(25)->values()]);
    }

    public function suggestCode(Request $request, AffiliateService $affiliates): JsonResponse
    {
        $name = trim((string) $request->query('name', ''));
        if ($name === '') {
            return response()->json(['code' => null], 422);
        }

        return response()->json([
            'code' => $affiliates->suggestCodeFromName($name),
        ]);
    }

    public function create(): View
    {
        $existingEmails = Affiliate::query()
            ->pluck('email')
            ->map(fn ($e) => strtolower((string) $e))
            ->all();

        $candidates = Order::query()
            ->where('status', 'paid')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->latest('paid_at')
            ->latest('id')
            ->limit(400)
            ->get(['email', 'full_name', 'license_id', 'paid_at'])
            ->unique(fn (Order $o) => strtolower((string) $o->email))
            ->reject(fn (Order $o) => in_array(strtolower((string) $o->email), $existingEmails, true))
            ->values()
            ->take(100);

        return view('admin.affiliates.create', [
            'candidates' => $candidates,
        ]);
    }

    public function store(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:190',
            'name' => 'nullable|string|max:120',
            'referral_code' => 'nullable|string|max:32',
        ]);

        $email = strtolower(trim($data['email']));
        $order = Order::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderByRaw("CASE WHEN status = 'paid' THEN 0 ELSE 1 END")
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak ditemukan di order yang sudah ada.',
            ]);
        }

        $preferredCode = $affiliates->normalizeReferralCode($data['referral_code'] ?? null);
        $existing = Affiliate::query()->where('email', $email)->first();
        if ($preferredCode !== null) {
            $affiliates->assertReferralCodeAvailable($preferredCode, $existing?->id);
        }

        $affiliate = $affiliates->ensureForPortalUser(
            $email,
            trim((string) ($data['name'] ?? '')) ?: $order->full_name,
            $order->license_id,
            $preferredCode,
        );

        $wasExisting = $existing !== null;

        return redirect()
            ->route('admin.affiliates.show', $affiliate)
            ->with(
                'success',
                ($wasExisting ? 'Affiliate diperbarui.' : 'Affiliate ditambahkan.')
                .' Kode: '.$affiliate->referral_code
            );
    }

    public function show(Affiliate $affiliate): View
    {
        $affiliate->load([
            'license',
            'commissions' => fn ($q) => $q->with('order')->latest()->limit(100),
            'claims' => fn ($q) => $q->latest()->limit(50),
        ]);

        return view('admin.affiliates.show', [
            'affiliate' => $affiliate,
            'balance' => $affiliate->availableBalance(),
        ]);
    }

    public function update(Request $request, Affiliate $affiliate, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:120',
            'referral_code' => 'required|string|max:32',
        ]);

        $preferredCode = $affiliates->normalizeReferralCode($data['referral_code'] ?? null);
        if ($preferredCode === null) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode affiliate tidak boleh kosong.',
            ]);
        }
        $affiliates->assertReferralCodeAvailable($preferredCode, $affiliate->id);

        $affiliate->referral_code = $preferredCode;
        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $affiliate->name = trim((string) $data['name']);
        }
        $affiliate->save();

        return back()->with('success', 'Kode affiliate diperbarui menjadi '.$affiliate->referral_code.'.');
    }

    public function toggle(Affiliate $affiliate): RedirectResponse
    {
        $affiliate->is_active = ! $affiliate->is_active;
        $affiliate->save();

        return back()->with('success', 'Status affiliate diperbarui.');
    }

    public function claims(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $claims = AffiliateClaim::query()
            ->with(['affiliate', 'processor'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.affiliates.claims', [
            'claims' => $claims,
            'status' => $status,
        ]);
    }

    public function processClaim(Request $request, AffiliateClaim $claim, AffiliateService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:approved,paid,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $service->processClaim(
            $claim,
            $data['status'],
            $request->user()?->id,
            $data['admin_note'] ?? null,
        );

        return back()->with('success', "Klaim #{$claim->id} diubah menjadi {$data['status']}.");
    }

    public function commissions(): View
    {
        $commissions = AffiliateCommission::query()
            ->with(['affiliate', 'order'])
            ->latest()
            ->paginate(40);

        return view('admin.affiliates.commissions', [
            'commissions' => $commissions,
        ]);
    }
}
