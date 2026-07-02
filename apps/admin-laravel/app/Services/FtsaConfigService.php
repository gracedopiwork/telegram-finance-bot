<?php

namespace App\Services;

use App\Models\FtsaQuestion;
use Illuminate\Support\Facades\Schema;

class FtsaConfigService
{
    /**
     * @return array<int, string>
     */
    public function questionMap(): array
    {
        $fromConfig = $this->normalizedConfigQuestions();
        $map = [];

        if ($this->usesDatabase()) {
            $rows = FtsaQuestion::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('question_num')
                ->get();

            foreach ($rows as $row) {
                $map[(int) $row->question_num] = (string) $row->text;
            }
        }

        for ($i = 1; $i <= 32; $i++) {
            if (! isset($map[$i]) || trim($map[$i]) === '') {
                if (isset($fromConfig[$i])) {
                    $map[$i] = $fromConfig[$i];
                }
            }
        }

        ksort($map);

        return $map !== [] ? $map : $fromConfig;
    }

    /**
     * @return array<int, string>
     */
    private function normalizedConfigQuestions(): array
    {
        $raw = (array) config('baseline_assessment.ftsa_questions', []);
        $map = [];
        foreach ($raw as $num => $text) {
            $n = (int) $num;
            if ($n >= 1 && $n <= 32 && is_string($text) && trim($text) !== '') {
                $map[$n] = trim($text);
            }
        }

        return $map;
    }

    /**
     * @return list<array{value: string, label: string, code: string}>
     */
    public function domainOptions(): array
    {
        $options = [];
        foreach ((array) config('baseline_assessment.ftsa_domains', []) as $key => $domain) {
            if (! is_array($domain)) {
                continue;
            }
            $options[] = [
                'value' => $key,
                'code' => (string) ($domain['code'] ?? strtoupper($key)),
                'label' => (string) ($domain['label'] ?? $key),
            ];
        }

        return $options;
    }

    public function usesDatabase(): bool
    {
        return Schema::hasTable('ftsa_questions');
    }

    public function syncFromConfig(): int
    {
        if (! $this->usesDatabase()) {
            return 0;
        }

        $questions = (array) config('baseline_assessment.ftsa_questions', []);
        $domainByNum = $this->domainNumbersFromConfig();
        $count = 0;

        foreach ($questions as $num => $text) {
            $num = (int) $num;
            if ($num < 1 || $num > 32 || ! is_string($text) || trim($text) === '') {
                continue;
            }

            FtsaQuestion::query()->updateOrCreate(
                ['question_num' => $num],
                [
                    'domain_key' => $domainByNum[$num] ?? 'chd',
                    'text' => trim($text),
                    'is_active' => true,
                    'sort_order' => $num,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * @return array<int, string>
     */
    private function domainNumbersFromConfig(): array
    {
        $map = [];
        foreach ((array) config('baseline_assessment.ftsa_domains', []) as $key => $domain) {
            if (! is_array($domain)) {
                continue;
            }
            foreach ($domain['questions'] ?? [] as $num) {
                $map[(int) $num] = $key;
            }
        }

        return $map;
    }
}
