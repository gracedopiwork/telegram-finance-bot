<?php

namespace App\Http\Controllers;

use App\Models\CpAdvisor;
use App\Models\CpArticle;
use App\Models\CpFaq;
use App\Models\CpPackage;
use App\Models\CpService;
use App\Models\License;
use App\Models\Order;
use App\Models\Setting;
use App\Models\UserSheet;
use App\Services\AiHealthService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function assertToken(Request $request): void
    {
        if ($request->user()) {
            return;
        }
        $expected = (string) env('ADMIN_DASHBOARD_TOKEN', '');
        if ($expected !== '' && $request->query('token') !== $expected) {
            abort(403, 'Invalid admin token');
        }
    }

    public function index(Request $request)
    {
        return view('admin.index', [
            'stats' => [
                'settings' => Setting::count(),
                'packages' => CpPackage::count(),
                'services' => CpService::count(),
                'advisors' => CpAdvisor::count(),
                'faqs'     => CpFaq::count(),
                'articles' => CpArticle::count(),
            ],
            'aiHealth' => app(AiHealthService::class)->summary(),
            'recentArticles' => CpArticle::latest()->limit(5)->get(),
            'recentOrders'   => Order::latest()->limit(5)->get(),
        ]);
    }

    public function storeUserSheet(Request $request)
    {
        $this->assertToken($request);
        $validated = $request->validate([
            'telegram_user_id' => ['required', 'numeric'],
            'spreadsheet_id' => ['required', 'string', 'max:128'],
            'spreadsheet_url' => ['nullable', 'string', 'max:512'],
        ]);

        UserSheet::updateOrCreate(
            ['telegram_user_id' => $validated['telegram_user_id']],
            [
                'spreadsheet_id' => $validated['spreadsheet_id'],
                'spreadsheet_url' => $validated['spreadsheet_url'] ?? null,
                'status' => 'active',
            ]
        );

        return $this->redirectAdminIndex($request)->with('success', 'User sheet berhasil disimpan.');
    }

    public function runDashboardSync(Request $request)
    {
        $this->assertToken($request);

        $validated = $request->validate([
            'version' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        $result = app(\App\Services\DashboardSyncRunner::class)->run(
            $validated['version'],
            $request->boolean('dry_run'),
        );

        if ($result['ok']) {
            return $this->redirectAdminIndex($request)
                ->with('success', 'Sync dashboard selesai. '.$result['output']);
        }

        return $this->redirectAdminIndex($request)
            ->with('error', 'Sync dashboard gagal (exit '.$result['exit_code'].'). '.$result['output']);
    }

    private function redirectAdminIndex(Request $request)
    {
        $token = $request->query('token');

        return $token
            ? redirect()->route('admin.index', ['token' => $token])
            : redirect()->route('admin.index');
    }
}
