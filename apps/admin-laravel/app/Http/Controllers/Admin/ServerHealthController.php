<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiHealthService;
use App\Services\ClaudeJsonService;
use App\Services\PivotService;
use App\Services\ServerHealthService;
use Illuminate\View\View;

class ServerHealthController extends Controller
{
    public function index(
        ServerHealthService $serverHealth,
        AiHealthService $aiHealth,
        PivotService $pivot,
        ClaudeJsonService $claude,
    ): View {
        return view('admin.server-health.index', [
            'snapshot' => $serverHealth->snapshot(),
            'costs' => $serverHealth->costProjection(),
            'aiHealth' => $aiHealth->summary(),
            'integrations' => [
                'pivot' => $pivot->isReady(),
                'midtrans' => $pivot->isReady(),
                'claude' => $claude->isConfigured(),
            ],
        ]);
    }
}
