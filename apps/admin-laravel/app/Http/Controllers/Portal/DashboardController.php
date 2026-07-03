<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\BaselineClaimService;
use App\Services\FtsaAiGuidanceService;
use App\Services\FtsaAnswerSummaryService;
use App\Services\ImpulsivityAssessmentService;
use App\Services\PortalFeatureService;
use App\Services\PortalOnboardingService;
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
    $email = (string) (PortalSession::email($request) ?? '');
    app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

    [$month, $period] = $this->filters($request);
    $summary = app(TransactionDashboardService::class)->summary($telegramUserId, $month, $period, $email);

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
    $email = (string) (PortalSession::email($request) ?? '');
    app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

    [$month, $period] = $this->filters($request);
    $summary = app(TransactionDashboardService::class)->summary($telegramUserId, $month, $period, $email);
    $impulsivity = app(ImpulsivityAssessmentService::class)->assess($telegramUserId, $month, $period, $email);
    $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId, $email);
    $baseline = app(PortalOnboardingService::class)->resolveBaseline($email, $telegramUserId);
    $ftsaSummary = $baseline ? app(FtsaAnswerSummaryService::class)->scoreSummary($baseline) : null;
    $ftsaAiGuidance = app(FtsaAiGuidanceService::class)->forBaseline($baseline);

    return view('portal.dashboard', [
      'active' => 'dashboard',
      'summary' => $summary,
      'ftsaUnlocked' => $ftsaUnlocked,
      'baselineRecord' => $baseline,
      'ftsaSummary' => $ftsaSummary,
      'ftsaAiGuidance' => $ftsaAiGuidance,
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
    $email = (string) (PortalSession::email($request) ?? '');
    app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

    [$month, $period] = $this->filters($request);
    $assessment = app(ImpulsivityAssessmentService::class)->assess($telegramUserId, $month, $period, $email);
    $featureService = app(PortalFeatureService::class);
    $ftsaUnlocked = $featureService->canAccessFtsa($telegramUserId, $email);
    $ftsaStatus = $featureService->ftsaEntitlementStatus($telegramUserId, $email);
    $baseline = app(PortalOnboardingService::class)->resolveBaseline($email, $telegramUserId);
    $ftsaAiGuidance = app(FtsaAiGuidanceService::class)->forBaseline($baseline);

    return view('portal.emotional', [
      'active' => 'emotional',
      'assessment' => $assessment,
      'ftsaUnlocked' => $ftsaUnlocked,
      'ftsaEndsAt' => $ftsaStatus['ends_at'],
      'ftsaRetakeLocked' => app(\App\Services\FtsaEvaluationService::class)->isRetakeLocked($telegramUserId),
      'ftsaAiGuidance' => $ftsaAiGuidance,
      'months' => $this->monthOptions(),
      'periods' => $this->periodOptions(),
      'currentPeriod' => $period,
    ]);
  }

  public function premium(): View
  {
    $request = request();
    $telegramUserId = (int) PortalSession::telegramUserId($request);
    $email = (string) (PortalSession::email($request) ?? '');
    $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId, $email);
    $ftsaStatus = app(PortalFeatureService::class)->ftsaEntitlementStatus($telegramUserId, $email);

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
