<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiHealthService;
use App\Services\ClaudeJsonService;
use App\Services\MidtransService;
use App\Services\ServerHealthService;
use Illuminate\View\View;

class ServerHealthController extends Controller
{
    public function index(
        ServerHealthService $serverHealth,
        AiHealthService $aiHealth,
        MidtransService $midtrans,
        ClaudeJsonService $claude,
    ): View {
        return view('admin.server-health.index', [
            'snapshot' => $serverHealth->snapshot(),
            'costs' => $serverHealth->costProjection(),
            'aiHealth' => $aiHealth->summary(),
            'integrations' => [
                'midtrans' => $midtrans->isSnapReady(),
                'claude' => $claude->isConfigured(),
            ],
        ]);
    }
}
