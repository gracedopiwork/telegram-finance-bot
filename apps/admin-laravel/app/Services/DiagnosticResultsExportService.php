<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DiagnosticResultsExportService
{
    public function __construct(
        private readonly DiagnosticAnswerSummaryService $diagnosticAnswers,
        private readonly FtsaAnswerSummaryService $ftsaAnswers,
        private readonly DiagnosticConfigService $diagnosticConfig,
        private readonly SimpleXlsxBuilder $xlsx,
    ) {}

    /**
     * @return Builder<FinancialBaseline>
     */
    public function filteredQuery(Request $request): Builder
    {
        $query = FinancialBaseline::query()->orderByDesc('assessed_at')->orderByDesc('id');

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

        return $query;
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|int>>}
     */
    public function buildWideTable(Request $request): array
    {
        $fsKeys = $this->financialQuestionKeys();
        $ftsaNums = range(1, 32);

        $headers = [
            'id',
            'tanggal',
            'email',
            'telegram_user_id',
            'sumber',
            'tahap',
            'tahap_label',
            'skor',
            'skor_maks',
            'ftsa_archetype',
            'ftsa_archetype_label',
            'ftsa_chd',
            'ftsa_rvd',
            'ftsa_ssd',
            'ftsa_esd',
            'ftsa_terisi',
        ];

        foreach ($fsKeys as $key) {
            $headers[] = "fs_{$key}_jawaban";
            $headers[] = "fs_{$key}_skor";
            $headers[] = "fs_{$key}_pertanyaan";
        }

        foreach ($ftsaNums as $num) {
            $headers[] = "ftsa_q{$num}_skor";
            $headers[] = "ftsa_q{$num}_label";
            $headers[] = "ftsa_q{$num}_pertanyaan";
        }

        $rows = [];
        $this->filteredQuery($request)->cursor()->each(function (FinancialBaseline $baseline) use (&$rows, $fsKeys, $ftsaNums) {
            $rows[] = $this->rowForBaseline($baseline, $fsKeys, $ftsaNums);
        });

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Long format: satu baris per jawaban pertanyaan (lebih mudah pivot di Sheets).
     *
     * @return array{headers: list<string>, rows: list<list<string|int>>}
     */
    public function buildLongTable(Request $request): array
    {
        $headers = [
            'baseline_id',
            'tanggal',
            'email',
            'sumber',
            'tahap_label',
            'skor_total',
            'jenis',
            'nomor_atau_key',
            'pertanyaan',
            'jawaban',
            'skor',
            'domain',
        ];

        $rows = [];
        $this->filteredQuery($request)->cursor()->each(function (FinancialBaseline $baseline) use (&$rows) {
            $email = $this->diagnosticAnswers->resolvedEmail($baseline) ?? '';
            $source = $baseline->telegram_user_id ? 'Portal' : 'Landing';
            $meta = [
                (int) $baseline->id,
                $baseline->formatDate('Y-m-d H:i:s'),
                $email,
                $source,
                (string) ($baseline->stage_label ?: $baseline->financial_stage),
                (int) $baseline->financial_stage_score,
            ];

            foreach ($this->diagnosticAnswers->summarize($baseline) as $answer) {
                $rows[] = array_merge($meta, [
                    'diagnostik',
                    (string) $answer['question_key'],
                    (string) $answer['question'],
                    (string) $answer['answer_label'],
                    $answer['score'] ?? '',
                    '',
                ]);
            }

            foreach ($this->ftsaAnswers->summarizeAnswers($baseline) as $answer) {
                $rows[] = array_merge($meta, [
                    'ftsa',
                    (string) $answer['num'],
                    (string) $answer['question'],
                    (string) $answer['score_label'],
                    (int) $answer['score'],
                    (string) ($answer['domain_code'] ?? ''),
                ]);
            }
        });

        return ['headers' => $headers, 'rows' => $rows];
    }

    public function toCsv(array $headers, array $rows): string
    {
        $lines = [];
        $lines[] = $this->csvLine($headers);
        foreach ($rows as $row) {
            $lines[] = $this->csvLine($row);
        }

        return "\xEF\xBB\xBF".implode("\n", $lines)."\n";
    }

    public function toXlsx(array $headers, array $rows, string $sheetName = 'Diagnostik'): string
    {
        return $this->xlsx->build($headers, $rows, $sheetName);
    }

    /**
     * @param  list<string>  $fsKeys
     * @param  list<int>  $ftsaNums
     * @return list<string|int>
     */
    private function rowForBaseline(FinancialBaseline $baseline, array $fsKeys, array $ftsaNums): array
    {
        $email = $this->diagnosticAnswers->resolvedEmail($baseline) ?? '';
        $ftsaSummary = $this->ftsaAnswers->scoreSummary($baseline);

        $fsByKey = [];
        foreach ($this->diagnosticAnswers->summarize($baseline) as $answer) {
            $fsByKey[$answer['question_key']] = $answer;
        }

        $ftsaByNum = [];
        foreach ($this->ftsaAnswers->summarizeAnswers($baseline) as $answer) {
            $ftsaByNum[$answer['num']] = $answer;
        }

        $row = [
            (int) $baseline->id,
            $baseline->formatDate('Y-m-d H:i:s'),
            $email,
            $baseline->telegram_user_id ? (int) $baseline->telegram_user_id : '',
            $baseline->telegram_user_id ? 'Portal' : 'Landing',
            (string) ($baseline->financial_stage ?? ''),
            (string) ($baseline->stage_label ?? ''),
            (int) $baseline->financial_stage_score,
            39,
            (string) ($baseline->dominant_archetype ?? ''),
            (string) ($baseline->dominant_archetype_label ?? ''),
            (int) ($baseline->ftsa_chd ?? 0),
            (int) ($baseline->ftsa_rvd ?? 0),
            (int) ($baseline->ftsa_ssd ?? 0),
            (int) ($baseline->ftsa_esd ?? 0),
            (int) ($ftsaSummary['filled'] ?? 0),
        ];

        foreach ($fsKeys as $key) {
            $answer = $fsByKey[$key] ?? null;
            $row[] = $answer['answer_label'] ?? '';
            $row[] = $answer['score'] ?? '';
            $row[] = $answer['question'] ?? '';
        }

        foreach ($ftsaNums as $num) {
            $answer = $ftsaByNum[$num] ?? null;
            $row[] = $answer['score'] ?? '';
            $row[] = $answer['score_label'] ?? '';
            $row[] = $answer['question'] ?? '';
        }

        return $row;
    }

    /**
     * @return list<string>
     */
    private function financialQuestionKeys(): array
    {
        $map = [];
        if ($this->diagnosticConfig->usesDatabase()) {
            $keys = \App\Models\DiagnosticQuestion::query()
                ->orderBy('wizard_step')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('question_key')
                ->filter()
                ->values()
                ->all();
            if ($keys !== []) {
                return array_map('strval', $keys);
            }
        }

        foreach ((array) config('diagnostic_questions_canonical.questions', []) as $q) {
            if (! empty($q['question_key'])) {
                $map[] = (string) $q['question_key'];
            }
        }

        return $map;
    }

    /**
     * @param  list<string|int>  $cells
     */
    private function csvLine(array $cells): string
    {
        return implode(',', array_map(function ($value) {
            $text = str_replace(["\r\n", "\r", "\n"], ' ', (string) $value);
            if (str_contains($text, ',') || str_contains($text, '"') || str_contains($text, ';')) {
                return '"'.str_replace('"', '""', $text).'"';
            }

            return $text;
        }, $cells));
    }
}
