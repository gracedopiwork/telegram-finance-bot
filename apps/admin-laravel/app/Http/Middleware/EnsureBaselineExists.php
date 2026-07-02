<?php

namespace App\Http\Middleware;

use App\Services\BaselineClaimService;
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

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        return $next($request);
    }
}
