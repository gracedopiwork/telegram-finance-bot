<?php

namespace App\Http\Middleware;

use App\Models\FinancialBaseline;
use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBaselineExists
{
    public function handle(Request $request, Closure $next): Response
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);

        if (FinancialBaseline::userNeedsBaseline($telegramUserId)) {
            return redirect()
                ->route('portal.baseline.create')
                ->with('info', 'Lengkapi Financial Health Check-Up & FTSA-32 terlebih dahulu.');
        }

        return $next($request);
    }
}
