<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunDashboardSyncJob;
use App\Services\DashboardSyncRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardSyncWebhookController extends Controller
{
    public function trigger(Request $request): JsonResponse
    {
        $expected = (string) config('services.dashboard_sync.webhook_token', '');
        if ($expected === '') {
            return response()->json([
                'ok' => false,
                'error' => 'DASHBOARD_SYNC_WEBHOOK_TOKEN belum di-set di Laravel .env',
            ], 503);
        }

        $token = $request->bearerToken() ?? (string) $request->header('X-Dashboard-Sync-Token', '');
        if ($token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $validated = $request->validate([
            'version' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'dry_run' => ['sometimes', 'boolean'],
            'sync' => ['sometimes', 'boolean'],
        ]);

        $version = $validated['version'];
        $dryRun = $request->boolean('dry_run');
        $runSync = $request->boolean('sync', true);

        $debounceSeconds = (int) config('services.dashboard_sync.webhook_debounce_seconds', 300);
        $lockKey = 'dashboard_sync_webhook:'.$version.':'.($dryRun ? 'dry' : 'live');

        if ($debounceSeconds > 0 && ! Cache::add($lockKey, now()->toIso8601String(), $debounceSeconds)) {
            return response()->json([
                'ok' => true,
                'queued' => false,
                'skipped' => true,
                'message' => 'Sync untuk versi ini baru saja dijadwalkan. Coba lagi nanti.',
            ]);
        }

        if (! $runSync) {
            return response()->json([
                'ok' => true,
                'queued' => false,
                'message' => 'Webhook diterima (sync=false).',
            ]);
        }

        $connection = (string) config('queue.default', 'sync');
        if ($connection === 'sync') {
            $result = app(DashboardSyncRunner::class)->run($version, $dryRun);

            return response()->json([
                'ok' => $result['ok'],
                'queued' => false,
                'exit_code' => $result['exit_code'],
                'output' => $result['output'],
            ], $result['ok'] ? 200 : 500);
        }

        RunDashboardSyncJob::dispatch($version, $dryRun);

        return response()->json([
            'ok' => true,
            'queued' => true,
            'version' => $version,
            'dry_run' => $dryRun,
            'message' => 'Dashboard sync dijadwalkan ke antrian.',
        ], 202);
    }
}
