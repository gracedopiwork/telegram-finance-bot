<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalGuidanceSnapshot;
use App\Services\BaselineClaimService;
use App\Services\FtsaAnswerSummaryService;
use App\Services\FtsaAiGuidanceService;
use App\Services\ImpulsivityAssessmentService;
use App\Services\PortalAccessService;
use App\Services\PortalFeatureService;
use App\Services\PortalGuidanceSnapshotService;
use App\Services\PortalOnboardingService;
use App\Services\PortalAiGuidanceService;
use App\Services\TransactionDashboardService;
use App\Support\PortalSession;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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

    return view('portal.dashboard', [
      'active' => 'dashboard',
      'summary' => $summary,
      'ftsaUnlocked' => $ftsaUnlocked,
      'baselineRecord' => $baseline,
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

  public function generateManualFinancialGuidance(Request $request): RedirectResponse
  {
    [$month, $period] = $this->filters($request);

    return redirect()
      ->route('portal.dashboard', ['month' => $month, 'period' => $period])
      ->with('warning', 'Generate manual dinonaktifkan. Doctor\'s Note keluar otomatis akhir bulan pukul 22.00 WIB.');
  }

  public function generateManualBehavioralGuidance(Request $request): RedirectResponse
  {
    [$month, $period] = $this->filters($request);

    return redirect()
      ->route('portal.emotional', ['month' => $month, 'period' => $period])
      ->with('warning', 'Generate manual dinonaktifkan. Behavioral Recommendation keluar otomatis akhir bulan pukul 22.00 WIB.');
  }

  public function emotional(Request $request): View
  {
    $telegramUserId = (int) PortalSession::telegramUserId($request);
    $email = (string) (PortalSession::email($request) ?? '');
    app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

    [$month, $period] = $this->filters($request);
    $access = app(PortalAccessService::class);
    $isFtsaOnly = $access->isFtsaOnlyPortalUser($email, $telegramUserId);

    if ($isFtsaOnly) {
      $assessment = $this->ftsaOnlyAssessment($telegramUserId, $email, $month, $period);
    } else {
      $assessment = app(ImpulsivityAssessmentService::class)->assess($telegramUserId, $month, $period, $email);
    }

    $featureService = app(PortalFeatureService::class);
    $ftsaUnlocked = $featureService->canAccessFtsa($telegramUserId, $email);
    $ftsaStatus = $featureService->ftsaEntitlementStatus($telegramUserId, $email);
    $baseline = app(PortalOnboardingService::class)->resolveBaseline($email, $telegramUserId);
    $stageMeta = [];
    $stageGuidance = [];
    if ($baseline !== null && ($baseline->financial_stage || $baseline->stage_label)) {
      try {
        $stageMeta = app(\App\Services\BucketPrescriptionService::class)->stageMeta($baseline->financial_stage);
        $stageGuidance = app(\App\Services\FinancialStageGuidanceService::class)->forBaseline($baseline);
      } catch (\Throwable $e) {
        report($e);
      }
    }

    try {
      $ftsaAiGuidance = app(FtsaAiGuidanceService::class)->forBaseline($baseline);
    } catch (\Throwable $e) {
      report($e);
      $ftsaAiGuidance = ['insights' => [], 'recommendations' => [], 'source' => 'none', 'generated_at' => null];
    }

    return view('portal.emotional', [
      'active' => 'emotional',
      'assessment' => $assessment,
      'baseline' => $baseline,
      'stageMeta' => $stageMeta,
      'stageGuidance' => $stageGuidance,
      'ftsaUnlocked' => $ftsaUnlocked,
      'ftsaEndsAt' => $ftsaStatus['ends_at'],
      'ftsaRetakeLocked' => app(\App\Services\FtsaEvaluationService::class)->isRetakeLocked($telegramUserId),
      'ftsaAiGuidance' => $ftsaAiGuidance,
      'months' => $this->monthOptions(),
      'periods' => $this->periodOptions(),
      'currentPeriod' => $period,
    ]);
  }

  /**
   * Ringkasan behavioral minimal untuk pembeli FTSA-only (tanpa data transaksi bot).
   *
   * @return array<string, mixed>
   */
  private function ftsaOnlyAssessment(int $telegramUserId, string $email, string $month, int $period): array
  {
    $baseline = app(PortalOnboardingService::class)->resolveBaseline($email, $telegramUserId);
    $ftsaSummary = app(FtsaAnswerSummaryService::class);
    $ftsaProfile = null;

    if ($baseline !== null && $ftsaSummary->hasCompletedFtsa($baseline)) {
      $ftsaProfile = [
        'archetype' => $baseline->dominant_archetype_label ?? $baseline->dominant_archetype,
        'domains' => [
          ['key' => 'chd', 'label' => 'CHD', 'score' => (int) $baseline->ftsa_chd, 'level' => $baseline->chd_level],
          ['key' => 'rvd', 'label' => 'RVD', 'score' => (int) $baseline->ftsa_rvd, 'level' => $baseline->rvd_level],
          ['key' => 'ssd', 'label' => 'SSD', 'score' => (int) $baseline->ftsa_ssd, 'level' => $baseline->ssd_level],
          ['key' => 'esd', 'label' => 'ESD', 'score' => (int) $baseline->ftsa_esd, 'level' => $baseline->esd_level],
        ],
      ];
    }

    return [
      'month' => $month,
      'period_months' => $period,
      'period_label' => Carbon::createFromFormat('Y-m', $month)->startOfMonth()->translatedFormat('F Y'),
      'expense_count' => 0,
      'ftsa_profile' => $ftsaProfile,
      'doctors_note' => '',
      'score' => 0,
      'grade' => '—',
      'impulsive_rate' => 0,
      'impulsive_amount_share' => 0,
      'impulsive_amount' => 0,
      'risk_label' => '—',
      'emotional_balance' => ['score' => 0, 'label' => '—'],
      'mood_groups' => [
        'positive' => ['share' => 0],
        'neutral' => ['share' => 0],
        'negative' => ['share' => 0],
      ],
      'insights' => [],
      'recommendations' => ['personalized' => [], 'general' => []],
      'ai_source' => 'none',
    ];
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
   * @return array{0: string, 1: int}
   */
  private function filters(Request $request): array
  {
    $month = $request->query('month');
    $period = max(1, (int) $request->query('period', 1));

    if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
      $month = Carbon::now()->format('Y-m');
    } else {
      // Cegah month lama (mis. 2024-05) nyangkut di URL sementara dropdown hanya 12 bulan terakhir.
      $allowed = collect($this->monthOptions())->pluck('value')->all();
      if (! in_array($month, $allowed, true)) {
        $month = Carbon::now()->format('Y-m');
      }
    }

    return [$month, $period];
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
