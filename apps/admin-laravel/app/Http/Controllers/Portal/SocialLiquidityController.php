<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BotSocialPayable;
use App\Models\BotSocialReceivable;
use App\Services\SocialLiquidityService;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialLiquidityController extends Controller
{
    public function writeOffReceivable(Request $request, BotSocialReceivable $receivable): RedirectResponse
    {
        $userId = (int) PortalSession::telegramUserId($request);
        if ($userId <= 0 || (int) $receivable->telegram_user_id !== $userId) {
            abort(403);
        }

        app(SocialLiquidityService::class)->writeOff($receivable);

        return back()->with('success', 'Piutang direlakan dan dicatat sebagai Pengeluaran Sosial & Keluarga.');
    }

    public function disputeReceivable(Request $request, BotSocialReceivable $receivable): RedirectResponse
    {
        $userId = (int) PortalSession::telegramUserId($request);
        if ($userId <= 0 || (int) $receivable->telegram_user_id !== $userId) {
            abort(403);
        }

        app(SocialLiquidityService::class)->markReceivableDisputed($receivable);

        return back()->with('success', 'Piutang ditandai sengketa (di luar perhitungan).');
    }

    public function disputePayable(Request $request, BotSocialPayable $payable): RedirectResponse
    {
        $userId = (int) PortalSession::telegramUserId($request);
        if ($userId <= 0 || (int) $payable->telegram_user_id !== $userId) {
            abort(403);
        }

        app(SocialLiquidityService::class)->markPayableDisputed($payable);

        return back()->with('success', 'Utang ditandai sengketa (di luar perhitungan).');
    }
}
