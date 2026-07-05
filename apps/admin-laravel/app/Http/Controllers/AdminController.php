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
use App\Services\AiHealthService;
use App\Services\ServerHealthService;
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
            'serverHealth' => app(ServerHealthService::class)->snapshot(),
            'serverCosts' => app(ServerHealthService::class)->costProjection(),
            'recentArticles' => CpArticle::latest()->limit(5)->get(),
            'recentOrders'   => Order::latest()->limit(5)->get(),
        ]);
    }

    private function redirectAdminIndex(Request $request)
    {
        $token = $request->query('token');

        return $token
            ? redirect()->route('admin.index', ['token' => $token])
            : redirect()->route('admin.index');
    }
}
