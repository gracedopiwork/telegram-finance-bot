<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PortalPasswordService;
use App\Services\UserDataConsentService;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request, PortalPasswordService $passwords, UserDataConsentService $consents): View
    {
        $email = (string) (PortalSession::email($request) ?? '');
        $telegramUserId = (int) (PortalSession::telegramUserId($request) ?? 0);

        return view('portal.account', [
            'active' => 'account',
            'email' => $email,
            'hasPassword' => $passwords->hasPassword($email),
            'privacy' => config('portal_privacy'),
            'guide' => config('portal_guide'),
            'consentAccepted' => $telegramUserId > 0 && $consents->hasAcceptedCurrent($telegramUserId),
            'consentLatest' => $telegramUserId > 0 ? $consents->latestForUser($telegramUserId) : null,
        ]);
    }

    public function storeConsent(Request $request, UserDataConsentService $consents): RedirectResponse
    {
        $telegramUserId = (int) (PortalSession::telegramUserId($request) ?? 0);
        if ($telegramUserId < 1) {
            return back()->withErrors([
                'consent' => 'Akun Telegram belum terhubung. Aktifkan lisensi di bot dulu.',
            ]);
        }

        $validated = $request->validate([
            'checkbox_ids' => ['required', 'array', 'min:1'],
            'checkbox_ids.*' => ['string', 'max:64'],
        ]);

        try {
            $consents->accept(
                $telegramUserId,
                'web',
                array_values($validated['checkbox_ids']),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Persetujuan privasi tersimpan (metode Web).');
    }

    public function updatePassword(Request $request, PortalPasswordService $passwords): RedirectResponse
    {
        $email = (string) (PortalSession::email($request) ?? '');
        if ($email === '') {
            return redirect()->route('portal.login');
        }

        $hasPassword = $passwords->hasPassword($email);
        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
        if ($hasPassword) {
            $rules['current_password'] = ['required', 'string'];
        }

        $validated = $request->validate($rules);

        if ($hasPassword && ! $passwords->verify($email, (string) $validated['current_password'])) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        $passwords->setPassword($email, (string) $validated['password']);

        return back()->with('success', $hasPassword
            ? 'Password berhasil diganti. Login berikutnya bisa pakai email + password.'
            : 'Password berhasil dibuat. Login berikutnya bisa pilih lisensi atau password.');
    }
}
