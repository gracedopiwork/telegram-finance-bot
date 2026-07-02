<?php

namespace App\Http\Controllers;

use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\DiagnosticConfigService;
use App\Services\FtsaAnswerSummaryService;
use App\Services\PortalAccessService;
use App\Services\PortalOnboardingService;
use App\Support\FinancialBaselineSchema;
use App\Support\PortalSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCheckupController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! FinancialBaselineSchema::isReady()) {
            return redirect()->route('company.home')
                ->with('error', 'Check-up sementara tidak tersedia. Coba lagi nanti.');
        }

        $diagnostic = app(DiagnosticConfigService::class);
        $wizardSteps = $diagnostic->wizardSteps();
        if ($wizardSteps === []) {
            return redirect()->route('company.home')
                ->with('error', 'Konfigurasi check-up belum siap.');
        }

        $prefillEmail = old('email');
        if (! $prefillEmail && PortalSession::isAuthenticated($request)) {
            $prefillEmail = PortalSession::email($request);
        }

        return view('checkup.form', [
            'wizardSteps' => $wizardSteps,
            'totalSteps' => count($wizardSteps),
            'prefillEmail' => $prefillEmail,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! FinancialBaselineSchema::isReady()) {
            return back()->withInput()->with('error', 'Check-up sementara tidak tersedia.');
        }

        $service = app(BaselineAssessmentService::class);
        $validated = $request->validate($service->validationRulesFinancialStageOnly());
        $email = strtolower(trim($validated['email']));

        $telegramUserId = PortalSession::isAuthenticated($request)
            ? (int) PortalSession::telegramUserId($request)
            : 0;
        $existing = $telegramUserId > 0 ? FinancialBaseline::latestForUser($telegramUserId) : null;
        $ftsaService = app(FtsaAnswerSummaryService::class);
        $includeFtsa = $existing !== null && $ftsaService->hasFtsaAnswers($existing);

        if ($includeFtsa) {
            $validated['ftsa'] = $existing->answers_json['ftsa'] ?? [];
        }

        try {
            $result = $service->assess($validated, $includeFtsa);

            if ($existing !== null && $telegramUserId > 0) {
                $existing->update($this->buildUpdatePayload($email, $telegramUserId, $result, $existing));
                $baseline = $existing->fresh();
            } else {
                $payload = $this->buildGuestPayload($email, $result);
                if ($telegramUserId > 0) {
                    $payload['telegram_user_id'] = $telegramUserId;
                }
                $baseline = FinancialBaseline::query()->create($payload);
            }
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan hasil check-up. Silakan coba lagi.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                config('app.debug') ? $e->getMessage() : 'Gagal memproses check-up.'
            );
        }

        $request->session()->put('checkup.result_id', $baseline->id);

        return redirect()->route('checkup.result');
    }

    public function result(Request $request): View|RedirectResponse
    {
        $baselineId = (int) $request->session()->get('checkup.result_id', 0);
        if ($baselineId <= 0) {
            return redirect()->route('checkup.show');
        }

        $baseline = FinancialBaseline::query()->find($baselineId);
        if ($baseline === null) {
            return redirect()->route('checkup.show');
        }

        $stageDisplay = app(DiagnosticConfigService::class)->stageDisplay(
            (string) $baseline->financial_stage,
            (int) $baseline->financial_stage_score,
        );
        $fromPortal = PortalSession::isAuthenticated($request);
        $portalEmail = (string) (PortalSession::email($request) ?? $baseline->email ?? '');
        $access = app(PortalAccessService::class);
        $isFtsaOnlyPortal = $fromPortal && $access->isFtsaOnlyPortalUser($portalEmail);
        $portalHomeRoute = null;
        $portalNextUrl = null;
        $portalNextLabel = null;

        if ($fromPortal && $isFtsaOnlyPortal) {
            $onboarding = app(PortalOnboardingService::class);
            $portalUserId = (int) PortalSession::telegramUserId($request);
            $portalNextUrl = $onboarding->nextFtsaOnlyOnboardingUrl($portalEmail, $portalUserId);

            if ($onboarding->userNeedsFtsa($portalEmail, $portalUserId)) {
                $portalNextLabel = 'Lengkapi FTSA 1–32';
            } else {
                $portalNextLabel = 'Buka Dashboard FTSA';
                $portalHomeRoute = 'portal.emotional';
            }
        } elseif ($fromPortal && $access->hasBotPortalAccess($portalEmail)) {
            $portalHomeRoute = 'portal.dashboard';
        }

        return view('checkup.result', [
            'baseline' => $baseline,
            'stageDisplay' => $stageDisplay,
            'fromPortal' => $fromPortal,
            'isFtsaOnlyPortal' => $isFtsaOnlyPortal,
            'portalHomeRoute' => $portalHomeRoute,
            'portalNextUrl' => $portalNextUrl,
            'portalNextLabel' => $portalNextLabel,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(
        string $email,
        int $telegramUserId,
        array $result,
        FinancialBaseline $existing,
    ): array {
        $payload = $this->buildGuestPayload($email, $result);
        $payload['telegram_user_id'] = $telegramUserId;

        if (app(FtsaAnswerSummaryService::class)->hasFtsaAnswers($existing)) {
            $payload['ftsa_chd'] = $result['ftsa_chd'];
            $payload['ftsa_rvd'] = $result['ftsa_rvd'];
            $payload['ftsa_ssd'] = $result['ftsa_ssd'];
            $payload['ftsa_esd'] = $result['ftsa_esd'];
            $payload['dominant_archetype'] = $result['dominant_archetype'];
            $payload['dominant_archetype_label'] = $result['dominant_archetype_label'];
            $payload['chd_level'] = $result['chd_level'];
            $payload['rvd_level'] = $result['rvd_level'];
            $payload['ssd_level'] = $result['ssd_level'];
            $payload['esd_level'] = $result['esd_level'];
        }

        foreach ([
            'current_goal',
            'avg_monthly_income',
            'emergency_fund',
            'cash_savings',
            'total_investment',
            'total_asset',
            'total_debt',
            'has_bpjs',
            'has_health_insurance',
            'has_income_protection',
            'has_life_insurance',
        ] as $field) {
            if (($payload[$field] === null || $payload[$field] === false) && $existing->{$field} !== null) {
                $payload[$field] = $existing->{$field};
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function buildGuestPayload(string $email, array $result): array
    {
        return [
            'email' => $email,
            'telegram_user_id' => null,
            'assessed_at' => $result['assessed_at'],
            'next_review_at' => $result['next_review_at'],
            'financial_stage_score' => $result['financial_stage_score'],
            'financial_stage' => $result['financial_stage'],
            'stage_label' => $result['stage_label'],
            'current_goal' => null,
            'avg_monthly_income' => null,
            'emergency_fund' => null,
            'cash_savings' => null,
            'total_investment' => null,
            'total_asset' => null,
            'total_debt' => null,
            'has_bpjs' => false,
            'has_health_insurance' => false,
            'has_income_protection' => false,
            'has_life_insurance' => false,
            'ftsa_chd' => 0,
            'ftsa_rvd' => 0,
            'ftsa_ssd' => 0,
            'ftsa_esd' => 0,
            'dominant_archetype' => 'guest',
            'dominant_archetype_label' => 'Financial Health Check-Up',
            'chd_level' => null,
            'rvd_level' => null,
            'ssd_level' => null,
            'esd_level' => null,
            'answers_json' => $result['answers'],
        ];
    }
}
