<?php

namespace App\Http\Middleware;

use App\Services\PortalAccessService;
use App\Services\PortalPasswordService;
use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setelah login pertama (lisensi) untuk user First Aid, wajib buat password
 * sebelum akses dashboard. FTSA-only / course korporat boleh tetap pakai
 * email + lisensi saja — password opsional sampai lanjut beli First Aid.
 */
class EnsurePortalPasswordSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PortalSession::isAuthenticated($request)) {
            return $next($request);
        }

        $email = (string) (PortalSession::email($request) ?? '');
        $telegramUserId = (int) (PortalSession::telegramUserId($request) ?? 0);

        // Course / FTSA Premium standalone: tidak dipaksa buat password.
        if (
            PortalSession::userType($request) === 'ftsa_only'
            || ($email !== '' && app(PortalAccessService::class)->isFtsaOnlyPortalUser($email, $telegramUserId))
        ) {
            return $next($request);
        }

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
