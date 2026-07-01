<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\BucketPrescriptionService;
use App\Services\PortalFeatureService;
use App\Support\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BaselineController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $baseline = FinancialBaseline::latestForUser($telegramUserId);

        if ($baseline === null) {
            return redirect()->route('portal.baseline.create');
        }

        $prescription = app(BucketPrescriptionService::class)->idealsForStage($baseline->financial_stage);
        $stageMeta = app(BucketPrescriptionService::class)->stageMeta($baseline->financial_stage);
        $domains = config('baseline_assessment.ftsa_domains', []);

        return view('portal.baseline.result', [
            'active' => 'baseline',
            'baseline' => $baseline,
            'stageMeta' => $stageMeta,
            'prescription' => $prescription,
            'domains' => $domains,
            'reviewDue' => $baseline->isReviewDue(),
            'months' => $this->monthOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId);

        return view('portal.baseline.form', [
            'active' => 'baseline',
            'config' => config('baseline_assessment'),
            'hasBaseline' => ! FinancialBaseline::userNeedsBaseline($telegramUserId),
            'ftsaUnlocked' => $ftsaUnlocked,
            'months' => $this->monthOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $service = app(BaselineAssessmentService::class);
        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $ftsaUnlocked = app(PortalFeatureService::class)->canAccessFtsa($telegramUserId);
        $rules = $service->validationRules();
        if (! $ftsaUnlocked) {
            foreach (range(1, 32) as $i) {
                unset($rules["ftsa.{$i}"]);
            }
        }
        $validated = $request->validate($rules);
        $result = $service->assess($validated);
        $snapshot = $validated['snapshot'] ?? [];

        if (! $ftsaUnlocked) {
            $result['ftsa_chd'] = 0;
            $result['ftsa_rvd'] = 0;
            $result['ftsa_ssd'] = 0;
            $result['ftsa_esd'] = 0;
            $result['dominant_archetype'] = 'locked';
            $result['dominant_archetype_label'] = 'FTSA Premium Locked';
            $result['chd_level'] = null;
            $result['rvd_level'] = null;
            $result['ssd_level'] = null;
            $result['esd_level'] = null;
        }

        FinancialBaseline::query()->create([
            'telegram_user_id' => $telegramUserId,
            'assessed_at' => $result['assessed_at'],
            'next_review_at' => $result['next_review_at'],
            'financial_stage_score' => $result['financial_stage_score'],
            'financial_stage' => $result['financial_stage'],
            'stage_label' => $result['stage_label'],
            'current_goal' => $snapshot['current_goal'] ?? null,
            'avg_monthly_income' => isset($snapshot['avg_monthly_income']) ? (int) $snapshot['avg_monthly_income'] : null,
            'emergency_fund' => isset($snapshot['emergency_fund']) ? (int) $snapshot['emergency_fund'] : null,
            'cash_savings' => isset($snapshot['cash_savings']) ? (int) $snapshot['cash_savings'] : null,
            'total_investment' => isset($snapshot['total_investment']) ? (int) $snapshot['total_investment'] : null,
            'total_asset' => isset($snapshot['total_asset']) ? (int) $snapshot['total_asset'] : null,
            'total_debt' => isset($snapshot['total_debt']) ? (int) $snapshot['total_debt'] : null,
            'has_bpjs' => (bool) ($snapshot['has_bpjs'] ?? false),
            'has_health_insurance' => (bool) ($snapshot['has_health_insurance'] ?? false),
            'has_income_protection' => (bool) ($snapshot['has_income_protection'] ?? false),
            'has_life_insurance' => (bool) ($snapshot['has_life_insurance'] ?? false),
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
        ]);

        return redirect()
            ->route('portal.baseline')
            ->with('success', 'Baseline berhasil disimpan. Prescription bucket dashboard disesuaikan dengan tahap keuangan Anda.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function monthOptions(): array
    {
        $options = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $options[] = [
                'value' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('F Y'),
            ];
            $cursor = $cursor->copy()->subMonth();
        }

        return $options;
    }
}
