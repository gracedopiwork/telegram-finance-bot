<?php

namespace App\Http\Controllers;

use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\CheckupResultMailer;
use App\Services\DiagnosticConfigService;
use App\Services\FtsaAnswerSummaryService;
use App\Services\PortalAccessService;
use App\Services\PortalOnboardingService;
use App\Support\ConsultationPricing;
use App\Support\FinancialBaselineSchema;
use App\Support\PortalSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCheckupController extends Controller
{
    /** Pilihan persetujuan penelitian dari halaman pengantar (bool). */
    private const SESSION_RESEARCH_CONSENT = 'checkup.research_consent';

    /** Halaman pengantar FHCU + pilihan persetujuan penelitian. */
    public function intro(): View|RedirectResponse
    {
        if (! FinancialBaselineSchema::isReady()) {
            return redirect()->route('company.home')
                ->with('error', 'Check-up sementara tidak tersedia. Coba lagi nanti.');
        }

        return view('checkup.intro');
    }

    public function start(Request $request): RedirectResponse
    {
        $request->validate(
            ['research_consent' => 'required|in:0,1'],
            ['research_consent.required' => 'Pilih salah satu: Saya bersedia atau Saya tidak bersedia.']
        );

        $request->session()->put(self::SESSION_RESEARCH_CONSENT, $request->boolean('research_consent'));

        return redirect()->route('checkup.form');
    }

    public function form(Request $request): View|RedirectResponse
    {
        if (! FinancialBaselineSchema::isReady()) {
            return redirect()->route('company.home')
                ->with('error', 'Check-up sementara tidak tersedia. Coba lagi nanti.');
        }

        if (PortalSession::isAuthenticated($request)) {
            return redirect()->route('portal.diagnostic');
        }

        // Wizard hanya bisa diisi setelah memilih di halaman pengantar.
        if (! is_bool($request->session()->get(self::SESSION_RESEARCH_CONSENT))) {
            return redirect()->route('checkup.show');
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
        if ($existing === null && $email !== '') {
            $existing = FinancialBaseline::latestForEmail($email);
        }

        if ($existing !== null && app(PortalOnboardingService::class)->hasFinancialDiagnostic($existing)
            && ! $existing->isReviewDue()) {
            $available = $existing->reviewAvailableAt()?->format('d M Y') ?? $existing->formatNextReview('d M Y');
            $months = max(1, (int) config('baseline_assessment.review_months', 3));

            return back()->withInput()->with(
                'error',
                "Hasil check-up untuk email ini sudah tersimpan. Evaluasi ulang tersedia mulai {$available} (setiap {$months} bulan). Cek email Anda untuk salinan hasil."
            );
        }

        $ftsaService = app(FtsaAnswerSummaryService::class);
        $includeFtsa = $existing !== null && $ftsaService->hasFtsaAnswers($existing);

        if ($includeFtsa) {
            $validated['ftsa'] = $existing->answers_json['ftsa'] ?? [];
        }

        $consent = $this->researchConsentPayload($request);

        try {
            $result = $service->assess($validated, $includeFtsa);

            if ($existing !== null) {
                $existing->update($this->buildUpdatePayload($email, $telegramUserId, $result, $existing) + $consent);
                $baseline = $existing->fresh();
            } else {
                $payload = $this->buildGuestPayload($email, $result) + $consent;
                if ($telegramUserId > 0) {
                    $payload['telegram_user_id'] = $telegramUserId;
                }
                $baseline = FinancialBaseline::query()->create($payload);
            }

            if ($telegramUserId > 0) {
                app(\App\Services\BaselineClaimService::class)->claimForUser($email, $telegramUserId);
                $baseline = FinancialBaseline::latestForUser($telegramUserId) ?? $baseline;
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

        try {
            app(CheckupResultMailer::class)->send($baseline);
            $mailNote = ' Salinan hasil juga dikirim ke '.$baseline->email.'.';
        } catch (\Throwable $e) {
            report($e);
            $mailNote = ' Hasil tersimpan, tetapi email gagal dikirim — simpan / screenshot halaman ini.';
        }

        $request->session()->put('checkup.result_id', $baseline->id);

        return redirect()->route('checkup.result')->with('success', 'Check-up berhasil.'.$mailNote);
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

            if ($onboarding->userNeedsFinancialDiagnostic($portalEmail, $portalUserId)) {
                $portalNextLabel = 'Lengkapi Diagnostik Tahap Keuangan';
            } elseif ($onboarding->userNeedsFtsa($portalEmail, $portalUserId)) {
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
            'consultationTier' => ConsultationPricing::forStage((string) $baseline->financial_stage),
            'consultationMeta' => config('consultation_pricing'),
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
            if (($payload[$field] === null || $payload[$field] === false) && $existing->{$field} !== null) {
                $payload[$field] = $existing->{$field};
            }
        }

        return $payload;
    }

    /**
     * Kolom persetujuan penelitian untuk disimpan bersama submit FHCU ini.
     * Tanpa pilihan di session → kosong, sehingga nilai lama di baris tidak tersentuh.
     *
     * @return array<string, mixed>
     */
    private function researchConsentPayload(Request $request): array
    {
        $choice = $request->session()->get(self::SESSION_RESEARCH_CONSENT);
        if (! is_bool($choice)) {
            return [];
        }

        return [
            'research_consent' => $choice,
            'research_consent_at' => now(),
            'research_consent_version' => (string) config('baseline_assessment.research_consent_version'),
        ];
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
