<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ImpulsivityAssessmentService;
use App\Services\PortalFeatureService;
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
    [$month, $period] = $this->filters($request);
    $summary = app(TransactionDashboardService::class)->summary($telegramUserId, $month, $period);

    return view('portal.transactions', [
      'active' => 'transactions',
      'summary' => $summary,
      'months' => $this->monthOptions(),
      'periods' => $this->periodOptions(),
      'currentPeriod' => $period,
    ]);
  }

  public function index(Request $request): View
  {
    $telegramUserId = (int) PortalSession::telegramUserId($request);
    [$month, $period] = $this->filters($request);
    $summary = app(TransactionDashboardService::class)->summary($telegramUserId, $month, $period);
    $impulsivity = app(ImpulsivityAssessmentService::class)->assess($telegramUserId, $month, $period);
    $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId);

    return view('portal.dashboard', [
      'active' => 'dashboard',
      'summary' => $summary,
      'ftsaUnlocked' => $ftsaUnlocked,
      'impulsivity' => [
        'score' => $impulsivity['score'],
        'grade' => $impulsivity['grade'],
        'impulsive_rate' => $impulsivity['impulsive_rate'],
      ],
      'months' => $this->monthOptions(),
      'periods' => $this->periodOptions(),
      'currentPeriod' => $period,
    ]);
  }

  public function emotional(Request $request): View
  {
    $telegramUserId = (int) PortalSession::telegramUserId($request);
    [$month, $period] = $this->filters($request);
    $assessment = app(ImpulsivityAssessmentService::class)->assess($telegramUserId, $month, $period);
    $featureService = app(PortalFeatureService::class);
    $ftsaUnlocked = $featureService->canAccessFtsa($telegramUserId);
    $ftsaStatus = $featureService->ftsaEntitlementStatus($telegramUserId);

    return view('portal.emotional', [
      'active' => 'emotional',
      'assessment' => $assessment,
      'ftsaUnlocked' => $ftsaUnlocked,
      'ftsaEndsAt' => $ftsaStatus['ends_at'],
      'months' => $this->monthOptions(),
      'periods' => $this->periodOptions(),
      'currentPeriod' => $period,
    ]);
  }

  public function premium(): View
  {
    $telegramUserId = (int) PortalSession::telegramUserId(request());
    $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId);
    $ftsaStatus = app(PortalFeatureService::class)->ftsaEntitlementStatus($telegramUserId);

    return view('portal.premium', [
      'active' => 'premium',
      'ftsaUnlocked' => $ftsaUnlocked,
      'ftsaEndsAt' => $ftsaStatus['ends_at'],
      'months' => $this->monthOptions(),
      'periods' => $this->periodOptions(),
      'currentPeriod' => 1,
    ]);
  }

  /**
   * @return array{0: string|null, 1: int}
   */
  private function filters(Request $request): array
  {
    $month = $request->query('month');
    $period = (int) $request->query('period', 1);

    return [
      is_string($month) ? $month : null,
      $period,
    ];
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

  /**
   * @return list<array{value: int, label: string}>
   */
  private function periodOptions(): array
  {
    return [
      ['value' => 1, 'label' => '1 bulan'],
      ['value' => 3, 'label' => '3 bulan'],
      ['value' => 6, 'label' => '6 bulan'],
      ['value' => 12, 'label' => '12 bulan'],
    ];
  }
}
