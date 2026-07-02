<?php

namespace App\Http\Middleware;

use App\Services\BaselineClaimService;
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

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        if ($access->isFtsaOnlyPortalUser($email)) {
            return $next($request);
        }

        if (! $onboarding->userNeedsBotOnboardingBaseline($email, $telegramUserId)) {
            return $next($request);
        }

        return redirect($onboarding->firstBaselineUrl($email, $telegramUserId))
            ->with(
                'info',
                'Lengkapi Baseline Data (diagnostik) terlebih dahulu untuk mengaktifkan Financial Health Dashboard.'
            );
    }
}
