<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Order;
use App\Services\BaselineClaimService;
use App\Services\PortalAccessService;
use App\Services\PortalAutoLoginService;
use App\Services\PortalOnboardingService;
use App\Support\FinancialBaselineSchema;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (PortalSession::isAuthenticated($request)) {
            return $this->redirectAfterLogin($request);
        }

        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'license_key' => ['required', 'string', 'max:64'],
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));
        $email = strtolower(trim($validated['email']));

        $license = License::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return back()->withInput()->withErrors(['license_key' => 'Kode lisensi tidak ditemukan.']);
        }

        if ($license->status !== 'active') {
            return back()->withInput()->withErrors(['license_key' => 'Lisensi tidak aktif.']);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return back()->withInput()->withErrors(['license_key' => 'Lisensi sudah expired.']);
        }

        $order = Order::query()
            ->where('license_id', $license->id)
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderByDesc('id')
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors([
                'email' => 'Email tidak cocok dengan order lisensi ini.',
            ]);
        }

        $access = app(PortalAccessService::class);
        $isFtsaOnlyOrder = $access->isFtsaOnlyOrder($order);

        if (! $license->assigned_user_id) {
            if ($isFtsaOnlyOrder) {
                $portalUserId = $access->ensureLicensePortalActivation($license->fresh());
            } else {
                return back()->withInput()->withErrors([
                    'license_key' => 'Lisensi belum diaktifkan di Telegram. Jalankan /activate di bot dulu.',
                ]);
            }
        } else {
            $portalUserId = (int) $license->assigned_user_id;
        }

        return $this->establishSession(
            $request,
            $portalUserId,
            $license->assigned_username ?: $order->full_name,
            $email,
            $isFtsaOnlyOrder ? 'ftsa_only' : 'licensed',
        );
    }

    /**
     * Auto-login dari link bertanda tangan (dikirim bot lewat /web).
     */
    public function autoLogin(Request $request): RedirectResponse
    {
        if (PortalSession::isAuthenticated($request)) {
            return $this->redirectAfterLogin($request);
        }

        $telegramUserId = (int) $request->query('uid');
        $profile = app(PortalAutoLoginService::class)->resolvePortalUser($telegramUserId);

        if ($profile === null) {
            return redirect()
                ->route('portal.login')
                ->with('warning', 'Link tidak valid atau lisensi belum aktif. Coba /web lagi di bot Telegram.');
        }

        return $this->establishSession(
            $request,
            $profile['telegram_user_id'],
            $profile['display_name'],
            $profile['email'],
            app(PortalAccessService::class)->isFtsaOnlyPortalUser($profile['email']) ? 'ftsa_only' : 'licensed',
        );
    }

    private function establishSession(
        Request $request,
        int $telegramUserId,
        string $displayName,
        string $email,
        string $userType = 'licensed',
    ): RedirectResponse {
        PortalSession::login($request, $telegramUserId, $displayName, $email, $userType);
        $request->session()->regenerate();

        if (FinancialBaselineSchema::isReady()) {
            app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);
        }

        return $this->redirectAfterLogin($request);
    }

    private function redirectAfterLogin(Request $request): RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $email = (string) (PortalSession::email($request) ?? '');
        $onboarding = app(PortalOnboardingService::class);

        $access = app(PortalAccessService::class);

        if ($onboarding->userNeedsBaseline($telegramUserId)) {
            if ($onboarding->isBotOnlyBuyer($email, $telegramUserId)) {
                return redirect()->route('portal.baseline.create')
                    ->with(
                        'info',
                        'Lengkapi Baseline Data di portal untuk mengaktifkan prescription bucket dan dashboard bot.'
                    );
            }

            $checkupMsg = $onboarding->isFtsaOnlyBuyer($email)
                ? 'Silakan lengkapi Financial Health Check-Up. Hasil diagnostik akan tersimpan dan terhubung ke dashboard FTSA Anda.'
                : 'Silakan lengkapi Financial Health Check-Up terlebih dahulu. Gunakan email yang sama dengan pembelian Anda.';

            return redirect()->route('checkup.show')
                ->with('warning', $checkupMsg)
                ->withInput(['email' => $email]);
        }

        return redirect()->route($access->defaultPortalHomeRoute($email));
    }

    public function logout(Request $request): RedirectResponse
    {
        PortalSession::logout($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
