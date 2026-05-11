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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class AdminController extends Controller
{
    private function assertToken(Request $request): void
    {
        $expected = (string) env('ADMIN_DASHBOARD_TOKEN', '');
        // Token requirement only enforced for legacy actions, not CRUD dashboard.
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

        return redirect()->route('admin.index', ['token' => $request->query('token')])
            ->with('success', 'User sheet berhasil disimpan.');
    }

    public function runDashboardSync(Request $request)
    {
        $this->assertToken($request);
        $version = (string) $request->input('version', '');
        if ($version === '') {
            return redirect()->route('admin.index', ['token' => $request->query('token')])
                ->with('success', 'Versi dashboard wajib diisi.');
        }

        $scriptPath = base_path('../bot-python/sync_dashboard.py');
        if (!file_exists($scriptPath)) {
            return redirect()->route('admin.index', ['token' => $request->query('token')])
                ->with('success', 'Script sync_dashboard.py tidak ditemukan.');
        }

        $result = Process::path(base_path('../bot-python'))
            ->run("python sync_dashboard.py --version {$version}");

        $message = $result->successful()
            ? "Sync dashboard sukses: {$result->output()}"
            : "Sync dashboard gagal: {$result->errorOutput()}";

        return redirect()->route('admin.index', ['token' => $request->query('token')])
            ->with('success', $message);
    }
}
