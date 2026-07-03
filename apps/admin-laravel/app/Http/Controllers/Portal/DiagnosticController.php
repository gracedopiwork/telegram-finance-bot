<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\BaselineClaimService;
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

class DiagnosticController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! FinancialBaselineSchema::isReady()) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'Diagnostik sementara tidak tersedia.');
        }

        $email = (string) (PortalSession::email($request) ?? '');
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $onboarding = app(PortalOnboardingService::class);

        if (! $onboarding->userNeedsFinancialDiagnostic($email, $telegramUserId)
            && ! $onboarding->userNeedsSnapshotBaseline($email, $telegramUserId)) {
            return redirect($onboarding->portalHomeRouteName($email))
                ->with('info', 'Baseline data Anda sudah tercatat.');
        }

        $wizardSteps = app(DiagnosticConfigService::class)->wizardSteps();
        if ($wizardSteps === []) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'Konfigurasi diagnostik belum siap.');
        }

        return view('portal.diagnostic.form', [
            'active' => 'baseline',
            'wizardSteps' => $wizardSteps,
            'totalSteps' => count($wizardSteps),
            'email' => $email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! FinancialBaselineSchema::isReady()) {
            return back()->withInput()->with('error', 'Diagnostik sementara tidak tersedia.');
        }

        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $email = strtolower(trim((string) (PortalSession::email($request) ?? '')));
        if ($telegramUserId <= 0 || $email === '') {
            return redirect()->route('portal.login')
                ->with('warning', 'Sesi portal habis. Silakan login ulang.');
        }

        $service = app(BaselineAssessmentService::class);
        $validated = $request->validate($service->validationRulesFinancialStageOnly());
        $validated['email'] = $email;

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $existing = FinancialBaseline::latestForUser($telegramUserId)
            ?? FinancialBaseline::latestForEmail($email);
        $ftsaService = app(FtsaAnswerSummaryService::class);
        $includeFtsa = $existing !== null && $ftsaService->hasFtsaAnswers($existing);

        if ($includeFtsa) {
            $validated['ftsa'] = $existing->answers_json['ftsa'] ?? [];
        }

        try {
            $result = $service->assess($validated, $includeFtsa);

            if ($existing !== null) {
                $existing->update($this->buildUpdatePayload($email, $telegramUserId, $result, $existing));
            } else {
                $payload = $this->buildGuestPayload($email, $result);
                $payload['telegram_user_id'] = $telegramUserId;
                FinancialBaseline::query()->create($payload);
            }
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan diagnostik. Silakan coba lagi.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                config('app.debug') ? $e->getMessage() : 'Gagal memproses diagnostik.'
            );
        }

        app(BaselineClaimService::class)->claimForUser($email, $telegramUserId);

        $onboarding = app(PortalOnboardingService::class);
        $access = app(PortalAccessService::class);

        if ($access->isFtsaOnlyPortalUser($email) && $onboarding->userNeedsFtsa($email, $telegramUserId)) {
            return redirect()->route('portal.ftsa.create')
                ->with('success', 'Diagnostik tersimpan. Lanjutkan kuesioner FTSA 1–32.');
        }

        if ($onboarding->userNeedsSnapshotBaseline($email, $telegramUserId)) {
            return redirect()->to($onboarding->portalDashboardSnapshotUrl())
                ->with('success', 'Diagnostik tersimpan. Lengkapi snapshot keuangan di dashboard.');
        }

        return redirect()->route($onboarding->portalHomeRouteName($email))
            ->with('success', 'Diagnostik keuangan berhasil disimpan.');
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
        if ($telegramUserId > 0) {
            $payload['telegram_user_id'] = $telegramUserId;
        }

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
            if ($existing->{$field} !== null && $existing->{$field} !== false && $existing->{$field} !== '') {
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
            'ftsa_chd' => $result['ftsa_chd'],
            'ftsa_rvd' => $result['ftsa_rvd'],
            'ftsa_ssd' => $result['ftsa_ssd'],
            'ftsa_esd' => $result['ftsa_esd'],
            'dominant_archetype' => $result['dominant_archetype'],
            'dominant_archetype_label' => $result['dominant_archetype_label'],
            'chd_level' => $result['chd_level'],
            'rvd_level' => $result['rvd_level'],
            'ssd_level' => $result['ssd_level'],
            'esd_level' => $result['esd_level'],
            'answers_json' => $result['answers'],
        ];
    }
}
