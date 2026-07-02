<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\DiagnosticAnswerSummaryService;
use App\Services\FtsaAnswerSummaryService;
use App\Support\FinancialBaselineSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FtsaResultsController extends Controller
{
    public function index(Request $request): View
    {
        $ftsaService = app(FtsaAnswerSummaryService::class);
        $summaryService = app(DiagnosticAnswerSummaryService::class);

        $query = FinancialBaseline::query()
            ->where(function ($q) {
                $q->whereNotIn('dominant_archetype', ['locked', 'guest', ''])
                    ->orWhere('ftsa_chd', '>', 0)
                    ->orWhere('ftsa_rvd', '>', 0)
                    ->orWhere('ftsa_ssd', '>', 0)
                    ->orWhere('ftsa_esd', '>', 0);
            })
            ->orderByDesc('assessed_at');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $needle = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($search, $needle) {
                $q->whereRaw('LOWER(email) LIKE ?', [$needle])
                    ->orWhere('dominant_archetype_label', 'like', '%'.$search.'%')
                    ->orWhere('dominant_archetype', 'like', '%'.$search.'%');
                if (ctype_digit($search)) {
                    $q->orWhere('telegram_user_id', (int) $search);
                }
            });
        }

        if ($archetype = $request->input('archetype')) {
            $query->where('dominant_archetype', $archetype);
        }

        if ($request->input('complete') === '1') {
            $query->whereNotIn('dominant_archetype', ['locked', 'guest', '']);
        } elseif ($request->input('complete') === '0') {
            $query->where('dominant_archetype', 'locked');
        }

        $results = $query->paginate(25)->withQueryString();

        return view('admin.ftsa_results.index', [
            'results' => $results,
            'ftsaService' => $ftsaService,
            'summaryService' => $summaryService,
            'schemaReady' => FinancialBaselineSchema::isReady(),
            'archetypes' => $this->archetypeOptions(),
        ]);
    }

    public function show(FinancialBaseline $financial_baseline): View
    {
        $ftsaService = app(FtsaAnswerSummaryService::class);
        $summaryService = app(DiagnosticAnswerSummaryService::class);

        return view('admin.ftsa_results.show', [
            'baseline' => $financial_baseline,
            'email' => $summaryService->resolvedEmail($financial_baseline),
            'ftsaAnswers' => $ftsaService->summarizeAnswers($financial_baseline),
            'ftsaSummary' => $ftsaService->scoreSummary($financial_baseline),
            'hasFtsa' => $ftsaService->hasFtsaAnswers($financial_baseline),
            'isLocked' => $ftsaService->isFtsaLocked($financial_baseline),
        ]);
    }

    public function destroy(FinancialBaseline $financial_baseline): RedirectResponse
    {
        $email = app(DiagnosticAnswerSummaryService::class)->resolvedEmail($financial_baseline);
        $financial_baseline->delete();

        return redirect()->route('admin.ftsa-results.index')
            ->with('success', 'Hasil FTSA'.($email ? " untuk {$email}" : '').' berhasil dihapus.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function archetypeOptions(): array
    {
        $options = [];
        foreach ((array) config('baseline_assessment.ftsa_domains', []) as $domain) {
            if (! is_array($domain)) {
                continue;
            }
            $options[] = [
                'value' => (string) ($domain['archetype'] ?? ''),
                'label' => (string) ($domain['archetype_label'] ?? ''),
            ];
        }

        return array_values(array_filter($options, fn (array $o) => $o['value'] !== ''));
    }
}
