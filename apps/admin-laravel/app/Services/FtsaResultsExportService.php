<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FtsaResultsExportService
{
    public function __construct(
        private readonly DiagnosticAnswerSummaryService $diagnosticAnswers,
        private readonly FtsaAnswerSummaryService $ftsaAnswers,
        private readonly SimpleXlsxBuilder $xlsx,
    ) {}

    /**
     * @return Builder<FinancialBaseline>
     */
    public function filteredQuery(Request $request): Builder
    {
        $query = FinancialBaseline::query()
            ->where(function ($q) {
                $q->whereNotIn('dominant_archetype', ['locked', 'guest', ''])
                    ->orWhere('ftsa_chd', '>', 0)
                    ->orWhere('ftsa_rvd', '>', 0)
                    ->orWhere('ftsa_ssd', '>', 0)
                    ->orWhere('ftsa_esd', '>', 0);
            })
            ->orderByDesc('assessed_at')
            ->orderByDesc('id');

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

        return $query;
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|int>>}
     */
    public function buildWideTable(Request $request): array
    {
        $headers = $this->wideHeaders();
        $rows = [];
        $this->filteredQuery($request)->cursor()->each(function (FinancialBaseline $baseline) use (&$rows) {
            $rows[] = $this->wideRow($baseline);
        });

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|int>>}
     */
    public function buildWideTableForBaseline(FinancialBaseline $baseline): array
    {
        return [
            'headers' => $this->wideHeaders(),
            'rows' => [$this->wideRow($baseline)],
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|int>>}
     */
    public function buildLongTable(Request $request): array
    {
        $headers = $this->longHeaders();
        $rows = [];
        $this->filteredQuery($request)->cursor()->each(function (FinancialBaseline $baseline) use (&$rows) {
            foreach ($this->longRows($baseline) as $row) {
                $rows[] = $row;
            }
        });

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|int>>}
     */
    public function buildLongTableForBaseline(FinancialBaseline $baseline): array
    {
        return [
            'headers' => $this->longHeaders(),
            'rows' => $this->longRows($baseline),
        ];
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

    public function toXlsx(array $headers, array $rows, string $sheetName = 'FTSA'): string
    {
        return $this->xlsx->build($headers, $rows, $sheetName);
    }

    /**
     * @return list<string>
     */
    private function wideHeaders(): array
    {
        $headers = [
            'id',
            'tanggal',
            'email',
            'telegram_user_id',
            'sumber',
            'ftsa_archetype',
            'ftsa_archetype_label',
            'ftsa_chd',
            'ftsa_rvd',
            'ftsa_ssd',
            'ftsa_esd',
            'ftsa_terisi',
            'ftsa_total',
        ];

        foreach (range(1, 32) as $num) {
            $headers[] = "ftsa_q{$num}_skor";
            $headers[] = "ftsa_q{$num}_label";
            $headers[] = "ftsa_q{$num}_domain";
            $headers[] = "ftsa_q{$num}_pertanyaan";
        }

        return $headers;
    }

    /**
     * @return list<string>
     */
    private function longHeaders(): array
    {
        return [
            'baseline_id',
            'tanggal',
            'email',
            'sumber',
            'ftsa_archetype_label',
            'ftsa_chd',
            'ftsa_rvd',
            'ftsa_ssd',
            'ftsa_esd',
            'nomor',
            'domain',
            'pertanyaan',
            'jawaban',
            'skor',
        ];
    }

    /**
     * @return list<string|int>
     */
    private function wideRow(FinancialBaseline $baseline): array
    {
        $email = $this->diagnosticAnswers->resolvedEmail($baseline) ?? '';
        $summary = $this->ftsaAnswers->scoreSummary($baseline);
        $byNum = [];
        foreach ($this->ftsaAnswers->summarizeAnswers($baseline) as $answer) {
            $byNum[$answer['num']] = $answer;
        }

        $row = [
            (int) $baseline->id,
            $baseline->formatDate('Y-m-d H:i:s'),
            $email,
            $baseline->telegram_user_id ? (int) $baseline->telegram_user_id : '',
            $baseline->telegram_user_id ? 'Portal' : 'Landing',
            (string) ($baseline->dominant_archetype ?? ''),
            (string) ($baseline->dominant_archetype_label ?? ''),
            (int) ($baseline->ftsa_chd ?? 0),
            (int) ($baseline->ftsa_rvd ?? 0),
            (int) ($baseline->ftsa_ssd ?? 0),
            (int) ($baseline->ftsa_esd ?? 0),
            (int) ($summary['filled'] ?? 0),
            (int) ($summary['total'] ?? 32),
        ];

        foreach (range(1, 32) as $num) {
            $answer = $byNum[$num] ?? null;
            $row[] = $answer['score'] ?? '';
            $row[] = $answer['score_label'] ?? '';
            $row[] = $answer['domain_code'] ?? '';
            $row[] = $answer['question'] ?? '';
        }

        return $row;
    }

    /**
     * @return list<list<string|int>>
     */
    private function longRows(FinancialBaseline $baseline): array
    {
        $email = $this->diagnosticAnswers->resolvedEmail($baseline) ?? '';
        $source = $baseline->telegram_user_id ? 'Portal' : 'Landing';
        $meta = [
            (int) $baseline->id,
            $baseline->formatDate('Y-m-d H:i:s'),
            $email,
            $source,
            (string) ($baseline->dominant_archetype_label ?: $baseline->dominant_archetype),
            (int) ($baseline->ftsa_chd ?? 0),
            (int) ($baseline->ftsa_rvd ?? 0),
            (int) ($baseline->ftsa_ssd ?? 0),
            (int) ($baseline->ftsa_esd ?? 0),
        ];

        $rows = [];
        foreach ($this->ftsaAnswers->summarizeAnswers($baseline) as $answer) {
            $rows[] = array_merge($meta, [
                (int) $answer['num'],
                (string) ($answer['domain_code'] ?? ''),
                (string) $answer['question'],
                (string) $answer['score_label'],
                (int) $answer['score'],
            ]);
        }

        return $rows;
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
