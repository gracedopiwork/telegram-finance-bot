<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\DiagnosticAnswerSummaryService;
use App\Services\DiagnosticConfigService;
use App\Support\FinancialBaselineSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticResultsController extends Controller
{
    public function index(Request $request): View
    {
        $query = FinancialBaseline::query()->orderByDesc('assessed_at');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $needle = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($search, $needle) {
                $q->whereRaw('LOWER(email) LIKE ?', [$needle])
                    ->orWhere('stage_label', 'like', '%'.$search.'%')
                    ->orWhere('financial_stage', 'like', '%'.$search.'%');
                if (ctype_digit($search)) {
                    $q->orWhere('telegram_user_id', (int) $search);
                }
            });
        }

        if ($stage = $request->input('stage')) {
            $query->where('financial_stage', $stage);
        }

        if ($source = $request->input('source')) {
            if ($source === 'landing') {
                $query->whereNull('telegram_user_id');
            } elseif ($source === 'portal') {
                $query->whereNotNull('telegram_user_id');
            }
        }

        $results = $query->paginate(25)->withQueryString();
        $stages = app(DiagnosticConfigService::class)->stageLabels();
        $summaryService = app(DiagnosticAnswerSummaryService::class);

        return view('admin.diagnostic_results.index', [
            'results' => $results,
            'stages' => $stages,
            'summaryService' => $summaryService,
            'schemaReady' => FinancialBaselineSchema::isReady(),
        ]);
    }

    public function show(FinancialBaseline $financial_baseline): View
    {
        $summaryService = app(DiagnosticAnswerSummaryService::class);
        $stageDisplay = app(DiagnosticConfigService::class)->stageDisplay(
            (string) $financial_baseline->financial_stage,
            (int) $financial_baseline->financial_stage_score,
        );

        return view('admin.diagnostic_results.show', [
            'baseline' => $financial_baseline,
            'email' => $summaryService->resolvedEmail($financial_baseline),
            'summary' => $summaryService->summarize($financial_baseline),
            'stageDisplay' => $stageDisplay,
        ]);
    }

    public function destroy(FinancialBaseline $financial_baseline): RedirectResponse
    {
        $email = app(DiagnosticAnswerSummaryService::class)->resolvedEmail($financial_baseline);
        $financial_baseline->delete();

        return redirect()->route('admin.diagnostic-results.index')
            ->with('success', 'Hasil diagnostik'.($email ? " untuk {$email}" : '').' berhasil dihapus.');
    }
}
