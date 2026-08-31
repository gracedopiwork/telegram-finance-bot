<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialBaseline;
use App\Services\DiagnosticAnswerSummaryService;
use App\Services\FtsaAnswerSummaryService;
use App\Services\FtsaResultsExportService;
use App\Support\FinancialBaselineSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FtsaResultsController extends Controller
{
    public function index(Request $request): View
    {
        $ftsaService = app(FtsaAnswerSummaryService::class);
        $summaryService = app(DiagnosticAnswerSummaryService::class);

        $results = app(FtsaResultsExportService::class)
            ->filteredQuery($request)
            ->paginate(25)
            ->withQueryString();

        return view('admin.ftsa_results.index', [
            'results' => $results,
            'ftsaService' => $ftsaService,
            'summaryService' => $summaryService,
            'schemaReady' => FinancialBaselineSchema::isReady(),
            'archetypes' => $this->archetypeOptions(),
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

        $exporter = app(FtsaResultsExportService::class);
        $table = $layout === 'long'
            ? $exporter->buildLongTable($request)
            : $exporter->buildWideTable($request);

        $stamp = now()->format('Ymd_His');
        $baseName = $layout === 'long'
            ? "hasil-ftsa-per-jawaban-{$stamp}"
            : "hasil-ftsa-lengkap-{$stamp}";

        return $this->downloadTable($exporter, $table, $format, $baseName, $layout === 'long' ? 'Per Jawaban' : 'FTSA');
    }

    public function exportOne(Request $request, FinancialBaseline $financial_baseline): Response
    {
        $format = strtolower((string) $request->input('format', 'xlsx'));
        $layout = strtolower((string) $request->input('layout', 'wide'));
        if (! in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }
        if (! in_array($layout, ['wide', 'long'], true)) {
            $layout = 'wide';
        }

        $exporter = app(FtsaResultsExportService::class);
        $table = $layout === 'long'
            ? $exporter->buildLongTableForBaseline($financial_baseline)
            : $exporter->buildWideTableForBaseline($financial_baseline);

        $email = app(DiagnosticAnswerSummaryService::class)->resolvedEmail($financial_baseline) ?? '';
        $slug = $email !== ''
            ? preg_replace('/[^a-zA-Z0-9._-]+/', '-', $email)
            : 'id-'.$financial_baseline->id;
        $stamp = $financial_baseline->formatDate('Ymd_His') ?: now()->format('Ymd_His');
        $baseName = "ftsa-{$slug}-{$stamp}";

        return $this->downloadTable($exporter, $table, $format, $baseName, $layout === 'long' ? 'Per Jawaban' : 'FTSA');
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
     * @param  array{headers: list<string>, rows: list<list<string|int>>}  $table
     */
    private function downloadTable(
        FtsaResultsExportService $exporter,
        array $table,
        string $format,
        string $baseName,
        string $sheetName,
    ): Response {
        if ($format === 'csv') {
            $csv = $exporter->toCsv($table['headers'], $table['rows']);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$baseName}.csv\"",
            ]);
        }

        $xlsx = $exporter->toXlsx($table['headers'], $table['rows'], $sheetName);

        return response($xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$baseName}.xlsx\"",
        ]);
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
