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
        if ($this->usesDatabase()) {
            $rows = FtsaQuestion::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('question_num')
                ->get();

            if ($rows->isNotEmpty()) {
                $map = [];
                foreach ($rows as $row) {
                    $map[(int) $row->question_num] = (string) $row->text;
                }

                return $map;
            }
        }

        return (array) config('baseline_assessment.ftsa_questions', []);
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
