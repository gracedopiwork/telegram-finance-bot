<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ImpulsivityAssessmentService;
use App\Services\TransactionDashboardService;
use App\Support\PortalSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function transactions(Request $request): View
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $month = $request->query('month');
        $summary = app(TransactionDashboardService::class)->summary($telegramUserId, is_string($month) ? $month : null);

        return view('portal.transactions', [
            'active' => 'transactions',
            'summary' => $summary,
            'months' => $this->monthOptions(),
        ]);
    }

    public function index(Request $request): View
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $month = $request->query('month');
        $monthStr = is_string($month) ? $month : null;
        $summary = app(TransactionDashboardService::class)->summary($telegramUserId, $monthStr);
        $impulsivity = app(ImpulsivityAssessmentService::class)->assess($telegramUserId, $monthStr);

        return view('portal.dashboard', [
            'active' => 'dashboard',
            'summary' => $summary,
            'impulsivity' => [
                'score' => $impulsivity['score'],
                'grade' => $impulsivity['grade'],
                'impulsive_rate' => $impulsivity['impulsive_rate'],
            ],
            'months' => $this->monthOptions(),
        ]);
    }

    public function emotional(Request $request): View
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $month = $request->query('month');
        $assessment = app(ImpulsivityAssessmentService::class)->assess(
            $telegramUserId,
            is_string($month) ? $month : null,
        );

        return view('portal.emotional', [
            'active' => 'emotional',
            'assessment' => $assessment,
            'months' => $this->monthOptions(),
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function monthOptions(): array
    {
        $options = [];
        $cursor = Carbon::now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $options[] = [
                'value' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('F Y'),
            ];
            $cursor->subMonth();
        }

        return $options;
    }
}
