<?php

namespace App\Http\Middleware;

use App\Models\FinancialBaseline;
use App\Services\PortalAccessService;
use App\Services\PortalOnboardingService;
use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBaselineExists
{
    public function handle(Request $request, Closure $next): Response
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $email = (string) (PortalSession::email($request) ?? '');
        $access = app(PortalAccessService::class);
        $onboarding = app(PortalOnboardingService::class);

        if ($access->isFtsaOnlyPortalUser($email)) {
            return $next($request);
        }

        if (! FinancialBaseline::userNeedsBaseline($telegramUserId)) {
            return $next($request);
        }

        return redirect($onboarding->firstBaselineUrl($email, $telegramUserId))
            ->with(
                'info',
                'Lengkapi Baseline Data (diagnostik) terlebih dahulu untuk mengaktifkan Financial Health Dashboard.'
            );
    }
}
