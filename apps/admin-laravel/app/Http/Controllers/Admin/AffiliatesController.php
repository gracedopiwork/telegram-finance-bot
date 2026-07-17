<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateClaim;
use App\Models\AffiliateCommission;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Affiliate $affiliate): View
    {
        $affiliate->load([
            'commissions' => fn ($q) => $q->with('order')->latest()->limit(100),
            'claims' => fn ($q) => $q->latest()->limit(50),
        ]);

        return view('admin.affiliates.show', [
            'affiliate' => $affiliate,
            'balance' => $affiliate->availableBalance(),
        ]);
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
