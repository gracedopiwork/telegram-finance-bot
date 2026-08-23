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

        $referrals = $affiliate->referredOrders()
            ->with(['digitalProduct:id,name,code'])
            ->latest('id')
            ->limit(100)
            ->get([
                'id',
                'order_code',
                'full_name',
                'email',
                'phone',
                'status',
                'product_name',
                'plan',
                'digital_product_id',
                'paid_at',
                'created_at',
                'payment_gateway',
                'amount',
            ]);

        return view('portal.affiliate', [
            'active' => 'affiliate',
            'affiliate' => $affiliate,
            'balance' => $affiliate->availableBalance(),
            'commissions' => $commissions,
            'claims' => $claims,
            'referrals' => $referrals,
            'referralCount' => $affiliate->referredOrders()->count(),
            'shareUrl' => $affiliates->shareUrl($affiliate),
            'commissionAmount' => $affiliates->commissionAmount(),
            'discountAmount' => $affiliates->discountAmount(),
            'minClaim' => $affiliates->minClaimAmount(),
            'taxIndividual' => $affiliates->taxPercentForPayeeType(\App\Models\Affiliate::PAYEE_INDIVIDUAL),
            'taxCorporate' => $affiliates->taxPercentForPayeeType(\App\Models\Affiliate::PAYEE_CORPORATE),
        ]);
    }

    public function claim(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $email = PortalSession::email($request);
        if (! $email) {
            return redirect()->route('portal.login');
        }

        $data = $request->validate([
            'payee_type' => ['required', 'string', 'in:individual,corporate'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'bank_name' => ['required', 'string', 'max:80'],
            'bank_account_number' => ['required', 'string', 'max:64'],
            'bank_account_name' => ['required', 'string', 'max:120'],
        ]);

        $affiliate = $affiliates->ensureForPortalUser(
            $email,
            (string) $request->session()->get(PortalSession::DISPLAY_NAME),
            PortalSession::licenseId($request),
        );

        $claim = $affiliates->submitClaim(
            $affiliate,
            $data['npwp'] ?? null,
            $data['bank_name'],
            $data['bank_account_number'],
            $data['bank_account_name'],
            $data['payee_type'],
        );

        return redirect()->route('portal.affiliate')
            ->with('success', 'Klaim Rp '.number_format($claim->net_amount, 0, ',', '.').' diajukan ke rekening '.$claim->bank_name.' '.$claim->bank_account_number.'. Tim admin akan proses transfer.');
    }
}
