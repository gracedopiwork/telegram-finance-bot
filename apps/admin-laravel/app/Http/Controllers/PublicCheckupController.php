<?php

namespace App\Http\Controllers;

use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\DiagnosticConfigService;
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

        try {
            $result = $service->assess($validated, false);
            $baseline = FinancialBaseline::query()->create($this->buildGuestPayload($email, $result));

            if (PortalSession::isAuthenticated($request)) {
                $baseline->update([
                    'telegram_user_id' => (int) PortalSession::telegramUserId($request),
                ]);
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

        return view('checkup.result', [
            'baseline' => $baseline,
            'stageDisplay' => $stageDisplay,
            'fromPortal' => $fromPortal,
        ]);
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
