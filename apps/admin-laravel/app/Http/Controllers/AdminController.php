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
use Illuminate\Support\Str;

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

        $scriptPath = base_path('../bot-python/sync_dashboard.py');
        if (! file_exists($scriptPath)) {
            return $this->redirectAdminIndex($request)
                ->with('error', 'Script sync_dashboard.py tidak ditemukan di apps/bot-python.');
        }

        $python = (string) config('services.sync_dashboard.python_binary', 'python');
        $command = array_filter([
            $python,
            'sync_dashboard.py',
            '--version',
            $validated['version'],
            $request->boolean('dry_run') ? '--dry-run' : null,
        ]);

        $result = Process::path(base_path('../bot-python'))
            ->timeout((int) config('services.sync_dashboard.timeout_seconds', 3600))
            ->run($command);

        $out = trim($result->output().$result->errorOutput());
        $out = Str::limit($out !== '' ? $out : '(tanpa output)', 4000);

        if ($result->successful()) {
            return $this->redirectAdminIndex($request)
                ->with('success', 'Sync dashboard selesai. '.$out);
        }

        return $this->redirectAdminIndex($request)
            ->with('error', 'Sync dashboard gagal (exit '.$result->exitCode().'). '.$out);
    }

    private function redirectAdminIndex(Request $request)
    {
        $token = $request->query('token');

        return $token
            ? redirect()->route('admin.index', ['token' => $token])
            : redirect()->route('admin.index');
    }
}
