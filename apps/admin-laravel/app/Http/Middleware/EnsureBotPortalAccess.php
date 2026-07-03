<?php

namespace App\Http\Middleware;

use App\Services\PortalAccessService;
use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBotPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = (string) (PortalSession::email($request) ?? '');

        if (app(PortalAccessService::class)->hasBotPortalAccess($email)) {
            return $next($request);
        }

        return redirect()
            ->route('portal.emotional')
            ->with('info', 'Paket FTSA Anda mencakup dashboard behavioral & diagnostik FTSA. Dashboard transaksi tersedia setelah membeli YFD First Aid.');
    }
}
