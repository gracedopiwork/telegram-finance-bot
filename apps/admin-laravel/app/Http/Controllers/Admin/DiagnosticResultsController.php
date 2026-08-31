<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\DiagnosticAnswerSummaryService;
use App\Services\DiagnosticConfigService;
use App\Services\DiagnosticResultsExportService;
use App\Services\FtsaAnswerSummaryService;
use App\Support\FinancialBaselineSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DiagnosticResultsController extends Controller
{
    public function index(Request $request): View
    {
        $query = app(DiagnosticResultsExportService::class)->filteredQuery($request);

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

    public function export(Request $request): Response
    {
        $format = strtolower((string) $request->input('format', 'xlsx'));
        $layout = strtolower((string) $request->input('layout', 'wide'));
        if (! in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }
        if (! in_array($layout, ['wide', 'long'], true)) {
            $layout = 'wide';
        }

        $exporter = app(DiagnosticResultsExportService::class);
        $table = $layout === 'long'
            ? $exporter->buildLongTable($request)
            : $exporter->buildWideTable($request);

        $stamp = now()->format('Ymd_His');
        $baseName = $layout === 'long'
            ? "hasil-diagnostik-per-jawaban-{$stamp}"
            : "hasil-diagnostik-lengkap-{$stamp}";

        if ($format === 'csv') {
            $csv = $exporter->toCsv($table['headers'], $table['rows']);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$baseName}.csv\"",
            ]);
        }

        $xlsx = $exporter->toXlsx(
            $table['headers'],
            $table['rows'],
            $layout === 'long' ? 'Per Jawaban' : 'Diagnostik'
        );

        return response($xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$baseName}.xlsx\"",
        ]);
    }

    public function show(FinancialBaseline $financial_baseline): View
    {
        $summaryService = app(DiagnosticAnswerSummaryService::class);
        $ftsaService = app(FtsaAnswerSummaryService::class);
        $stageDisplay = app(DiagnosticConfigService::class)->stageDisplay(
            (string) $financial_baseline->financial_stage,
            (int) $financial_baseline->financial_stage_score,
        );

        return view('admin.diagnostic_results.show', [
            'baseline' => $financial_baseline,
            'email' => $summaryService->resolvedEmail($financial_baseline),
            'summary' => $summaryService->summarize($financial_baseline),
            'stageDisplay' => $stageDisplay,
            'ftsaAnswers' => $ftsaService->summarizeAnswers($financial_baseline),
            'ftsaSummary' => $ftsaService->scoreSummary($financial_baseline),
            'hasFtsa' => $ftsaService->hasFtsaAnswers($financial_baseline),
            'isFtsaLocked' => $ftsaService->isFtsaLocked($financial_baseline),
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
