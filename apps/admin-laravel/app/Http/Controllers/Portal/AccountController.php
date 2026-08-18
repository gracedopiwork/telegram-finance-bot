<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PortalPasswordService;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request, PortalPasswordService $passwords): View
    {
        $email = (string) (PortalSession::email($request) ?? '');

        return view('portal.account', [
            'active' => 'account',
            'email' => $email,
            'hasPassword' => $passwords->hasPassword($email),
            'privacy' => config('portal_privacy'),
            'guide' => config('portal_guide'),
        ]);
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
