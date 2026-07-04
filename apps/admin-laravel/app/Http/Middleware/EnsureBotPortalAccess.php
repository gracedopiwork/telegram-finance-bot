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
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $access = app(PortalAccessService::class);

        if ($access->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            return redirect()
                ->route('portal.emotional')
                ->with('info', 'Paket FTSA Premium hanya mencakup kuesioner FTSA dan hasil behavioral. Dashboard transaksi tersedia setelah membeli YFD First Aid.');
        }

        if ($access->hasBotPortalAccess($email, $telegramUserId)) {
            return $next($request);
        }

        return redirect()
            ->route('portal.emotional')
            ->with('info', 'Paket FTSA Anda mencakup dashboard behavioral & diagnostik FTSA. Dashboard transaksi tersedia setelah membeli YFD First Aid.');
    }
}
