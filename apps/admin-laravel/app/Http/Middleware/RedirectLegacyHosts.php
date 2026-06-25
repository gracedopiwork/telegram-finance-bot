<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacyHosts
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = rtrim((string) config('app.url'), '/');
        if ($canonical === '' || $canonical === 'http://localhost') {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $legacyHosts = config('app.legacy_redirect_hosts', []);

        if (! in_array($host, $legacyHosts, true)) {
            return $next($request);
        }

        $canonicalHost = strtolower((string) parse_url($canonical, PHP_URL_HOST));
        if ($canonicalHost !== '' && $host === $canonicalHost) {
            return $next($request);
        }

        $target = $canonical.$request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
