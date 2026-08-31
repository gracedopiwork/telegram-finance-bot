<?php

namespace App\Http\Middleware;

use App\Services\PortalPasswordService;
use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setelah login pertama (lisensi), wajib buat password sebelum akses dashboard.
 * Login berikutnya cukup email + password — lisensi tidak dipakai ulang di portal.
 */
class EnsurePortalPasswordSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PortalSession::isAuthenticated($request)) {
            return $next($request);
        }

        $email = (string) (PortalSession::email($request) ?? '');
        if ($email === '') {
            return $next($request);
        }

        $passwords = app(PortalPasswordService::class);
        if (! $passwords->isReady() || $passwords->hasPassword($email)) {
            return $next($request);
        }

        $allowed = $request->routeIs(
            'portal.account',
            'portal.account.password',
            'portal.logout',
        );

        if ($allowed) {
            return $next($request);
        }

        return redirect()
            ->route('portal.account')
            ->with('warning', 'Demi keamanan, buat password portal dulu. Kode lisensi hanya untuk login pertama — login berikutnya pakai password.');
    }
}
