<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Order;
use App\Services\BaselineClaimService;
use App\Services\PortalAccessService;
use App\Services\PortalAutoLoginService;
use App\Services\PortalOnboardingService;
use App\Services\PortalPasswordService;
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
        $method = (string) $request->input('login_method', 'password');
        if (! in_array($method, ['license', 'password'], true)) {
            $method = filled($request->input('password')) && ! filled($request->input('license_key'))
                ? 'password'
                : (filled($request->input('license_key')) ? 'license' : 'password');
        }

        if ($method === 'password') {
            return $this->loginWithPassword($request);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'license_key' => ['required', 'string', 'max:64'],
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));
        $email = strtolower(trim($validated['email']));

        $passwords = app(PortalPasswordService::class);
        if ($passwords->isReady() && $passwords->hasPassword($email)) {
            return back()->withInput()->withErrors([
                'license_key' => 'Akun ini sudah punya password. Masuk dengan email + password (bukan kode lisensi) agar lebih aman.',
            ]);
        }

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
            return back()->withInput()->withErrors([
                'license_key' => 'Lisensi sudah expired. Perpanjang biaya admin dulu (Rp10.000/bulan atau Rp99.000/tahun).',
            ]);
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

        return $this->completeLicenseLogin($request, $license, $order, $email);
    }

    private function loginWithPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $email = strtolower(trim($validated['email']));
        $passwords = app(PortalPasswordService::class);

        if (! $passwords->hasPassword($email)) {
            return back()->withInput()->withErrors([
                'password' => 'Akun ini belum punya password. Pilih “Pertama kali” dan masuk dengan kode lisensi, lalu buat password.',
            ]);
        }

        if (! $passwords->verify($email, $validated['password'])) {
            return back()->withInput()->withErrors([
                'password' => 'Password salah.',
            ]);
        }

        $license = $passwords->resolveLicenseForEmail($email);
        if ($license === null) {
            return back()->withInput()->withErrors([
                'email' => 'Tidak ada lisensi aktif untuk email ini.',
            ]);
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

        return $this->completeLicenseLogin($request, $license, $order, $email);
    }

    private function completeLicenseLogin(Request $request, License $license, Order $order, string $email): RedirectResponse
    {
        $access = app(PortalAccessService::class);
        $entitlements = app(\App\Services\LicenseEntitlementService::class);

        $hasFtsaOnLicense = $entitlements->hasPaidFtsaOrderOnLicense($license);
        $hasBotOnLicense = $entitlements->hasPaidBotOrderOnLicense($license);

        if ($hasFtsaOnLicense && ! $hasBotOnLicense) {
            if (! $license->assigned_user_id) {
                $portalUserId = $access->ensureLicensePortalActivation($license->fresh());
            } else {
                $portalUserId = (int) $license->assigned_user_id;
            }

            return $this->establishSession(
                $request,
                $portalUserId,
                $license->assigned_username ?: $order->full_name,
                $email,
                'ftsa_only',
                (int) $license->id,
            );
        }

        if (! $license->assigned_user_id) {
            return back()->withInput()->withErrors([
                'license_key' => 'Lisensi belum diaktifkan di Telegram. Jalankan /activate '.$license->license_key.' di bot YFD First Aid.',
            ]);
        }

        return $this->establishSession(
            $request,
            (int) $license->assigned_user_id,
            $license->assigned_username ?: $order->full_name,
            $email,
            'licensed',
            (int) $license->id,
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
                ->with('warning', 'Link tidak valid atau lisensi belum aktif. Coba /web lagi di YFD First Aid.');
        }

        $access = app(PortalAccessService::class);
        $email = $profile['email'];
        $ftsaOnlyLicense = $access->resolvePortalLicense($email, $telegramUserId);
        $licenseId = $ftsaOnlyLicense?->id;

        if ($access->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            return redirect()
                ->route('portal.login')
                ->with(
                    'info',
                    'Paket FTSA Premium diakses lewat portal web dengan email + kode lisensi (bukan link /web dari bot Telegram).'
                );
        }

        return $this->establishSession(
            $request,
            $profile['telegram_user_id'],
            $profile['display_name'],
            $email,
            'licensed',
            $licenseId,
        );
    }

    private function establishSession(
        Request $request,
        int $telegramUserId,
        string $displayName,
        string $email,
        string $userType = 'licensed',
        ?int $licenseId = null,
    ): RedirectResponse {
        PortalSession::login($request, $telegramUserId, $displayName, $email, $userType, $licenseId);
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
        $passwords = app(PortalPasswordService::class);

        if (FinancialBaselineSchema::isReady()) {
            app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);
        }

        if ($passwords->isReady() && $email !== '' && ! $passwords->hasPassword($email)) {
            return redirect()->route('portal.account')
                ->with('warning', 'Login pertama berhasil. Buat password sekarang — login berikutnya cukup email + password (tanpa kode lisensi).');
        }

        return redirect()->route($onboarding->portalHomeRouteName($email, $telegramUserId))
            ->with('success', $this->loginSuccessMessage($email, $telegramUserId));
    }

    private function loginSuccessMessage(string $email, int $telegramUserId): string
    {
        $onboarding = app(PortalOnboardingService::class);
        $access = app(PortalAccessService::class);

        if ($access->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            return 'Selamat datang di portal FTSA Premium. Isi diagnostik tahap keuangan lalu kuesioner FTSA 1–32.';
        }

        if ($access->hasBotPortalAccess($email, $telegramUserId) && $onboarding->userNeedsFinancialDiagnostic($email, $telegramUserId)) {
            return 'Selamat datang! Langkah pertama: lengkapi Diagnostik Keuangan agar prescription bucket & insight personal aktif.';
        }

        return 'Selamat datang di portal YFD.';
    }

    public function logout(Request $request): RedirectResponse
    {
        PortalSession::logout($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
