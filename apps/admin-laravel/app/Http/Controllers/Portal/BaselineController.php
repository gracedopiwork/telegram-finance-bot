<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\BaselineAssessmentService;
use App\Services\BucketPrescriptionService;
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

        return view('portal.baseline.form', [
            'active' => 'baseline',
            'config' => config('baseline_assessment'),
            'hasBaseline' => ! FinancialBaseline::userNeedsBaseline($telegramUserId),
            'months' => $this->monthOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $service = app(BaselineAssessmentService::class);
        $validated = $request->validate($service->validationRules());

        $telegramUserId = (int) PortalSession::telegramUserId($request);
        $result = $service->assess($validated);

        FinancialBaseline::query()->create([
            'telegram_user_id' => $telegramUserId,
            'assessed_at' => $result['assessed_at'],
            'next_review_at' => $result['next_review_at'],
            'financial_stage_score' => $result['financial_stage_score'],
            'financial_stage' => $result['financial_stage'],
            'stage_label' => $result['stage_label'],
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
