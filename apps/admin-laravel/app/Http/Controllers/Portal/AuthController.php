<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Models\License;
use App\Models\Order;
use App\Services\PortalAutoLoginService;
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

        if (! $license->assigned_user_id) {
            return back()->withInput()->withErrors([
                'license_key' => 'Lisensi belum diaktifkan di Telegram. Jalankan /activate di bot dulu.',
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

        return $this->establishSession(
            $request,
            (int) $license->assigned_user_id,
            $license->assigned_username ?: $order->full_name,
            $email,
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
        );
    }

    private function establishSession(
        Request $request,
        int $telegramUserId,
        string $displayName,
        string $email,
    ): RedirectResponse {
        PortalSession::login($request, $telegramUserId, $displayName, $email);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($request);
    }

    private function redirectAfterLogin(Request $request): RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);

        if (FinancialBaseline::userNeedsBaseline($telegramUserId)) {
            return redirect()
                ->route('portal.baseline.create')
                ->with('info', 'Selamat datang! Lengkapi Health Check-Up sebagai langkah pertama.');
        }

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        PortalSession::logout($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
