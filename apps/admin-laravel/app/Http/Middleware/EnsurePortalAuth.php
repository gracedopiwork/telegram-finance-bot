<?php

namespace App\Http\Middleware;

use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PortalSession::isAuthenticated($request)) {
            return redirect()->route('portal.login')
                ->with('warning', 'Silakan login untuk membuka dashboard keuangan Anda.');
        }

        return $next($request);
    }
}
