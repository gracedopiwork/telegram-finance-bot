<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\AffiliateService;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request, AffiliateService $affiliates): View|RedirectResponse
    {
        if (! $affiliates->enabled()) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'Program referral belum aktif.');
        }

        $email = PortalSession::email($request);
        if (! $email) {
            return redirect()->route('portal.login');
        }

        $affiliate = $affiliates->ensureForPortalUser(
            $email,
            (string) $request->session()->get(PortalSession::DISPLAY_NAME),
            PortalSession::licenseId($request),
        );

        $commissions = $affiliate->commissions()
            ->with('order:id,order_code,full_name,email,amount,paid_at,product_name')
            ->latest()
            ->limit(50)
            ->get();

        $claims = $affiliate->claims()->latest()->limit(20)->get();

        return view('portal.affiliate', [
            'active' => 'affiliate',
            'affiliate' => $affiliate,
            'balance' => $affiliate->availableBalance(),
            'commissions' => $commissions,
            'claims' => $claims,
            'shareUrl' => $affiliates->shareUrl($affiliate),
            'commissionAmount' => $affiliates->commissionAmount(),
            'discountAmount' => $affiliates->discountAmount(),
            'minClaim' => $affiliates->minClaimAmount(),
            'taxWithNpwp' => $affiliates->taxPercent('123'),
            'taxWithoutNpwp' => $affiliates->taxPercent(null),
        ]);
    }

    public function claim(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $email = PortalSession::email($request);
        if (! $email) {
            return redirect()->route('portal.login');
        }

        $data = $request->validate([
            'npwp' => ['nullable', 'string', 'max:32'],
        ]);

        $affiliate = $affiliates->ensureForPortalUser(
            $email,
            (string) $request->session()->get(PortalSession::DISPLAY_NAME),
            PortalSession::licenseId($request),
        );

        $claim = $affiliates->submitClaim($affiliate, $data['npwp'] ?? null);

        return redirect()->route('portal.affiliate')
            ->with('success', 'Klaim Rp '.number_format($claim->net_amount, 0, ',', '.').' diajukan. Tim admin akan proses transfer manual.');
    }
}
