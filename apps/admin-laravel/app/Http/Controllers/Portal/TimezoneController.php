<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\PortalUserTimezoneService;
use App\Support\PortalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimezoneController extends Controller
{
    public function updateManual(Request $request): RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $zoneKey = (string) $request->input('zone', '');

        $options = app(PortalUserTimezoneService::class)->options();
        if ($telegramUserId > 0 && isset($options[$zoneKey])) {
            app(PortalUserTimezoneService::class)->setManual($telegramUserId, $zoneKey);
        }

        return back()->with('success', 'Zona waktu disimpan.');
    }

    public function updateAuto(Request $request): JsonResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        if ($telegramUserId <= 0) {
            return response()->json(['ok' => false], 401);
        }

        $browserTz = (string) $request->input('timezone', '');
        $service = app(PortalUserTimezoneService::class);
        $service->setFromBrowser($telegramUserId, $browserTz);

        $meta = $service->meta($telegramUserId);

        return response()->json([
            'ok' => true,
            'timezone' => $meta['timezone'],
            'label' => $meta['label'],
            'source' => $meta['source'],
        ]);
    }
}
