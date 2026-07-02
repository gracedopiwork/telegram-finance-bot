<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiJsonService
{
    public function isConfigured(): bool
    {
        return (bool) config('portal_ai.enabled', true)
            && trim((string) config('portal_ai.api_key', '')) !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function generate(string $prompt, ?float $temperature = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $apiKey = (string) config('portal_ai.api_key', '');
        $models = (array) config('portal_ai.models', ['gemini-2.0-flash']);
        $timeout = max(10, (int) config('portal_ai.timeout_seconds', 45));
        $temperature ??= (float) config('portal_ai.temperature', 0.3);

        foreach ($models as $model) {
            if (! is_string($model) || trim($model) === '') {
                continue;
            }

            try {
                $response = Http::timeout($timeout)
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/'.trim($model).':generateContent?key='.urlencode($apiKey),
                        [
                            'contents' => [
                                ['parts' => [['text' => $prompt]]],
                            ],
                            'generationConfig' => [
                                'temperature' => $temperature,
                                'responseMimeType' => 'application/json',
                            ],
                        ]
                    );
            } catch (\Throwable $e) {
                Log::warning('Gemini request failed', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (! is_string($text) || trim($text) === '') {
                continue;
            }

            $parsed = json_decode($text, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function normalizeLines(mixed $value, int $limit): array
    {
        if (! is_array($value)) {
            return [];
        }

        $lines = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }
            $trimmed = trim($item);
            if ($trimmed !== '') {
                $lines[] = $trimmed;
            }
        }

        return array_slice(array_values($lines), 0, max(1, $limit));
    }

    /**
     * @param  list<string>  $ruleKeys  Config keys under portal_ai (e.g. shared_rules, ftsa_rules)
     */
    public function rulesBlock(array $ruleKeys): string
    {
        $lines = [];
        foreach ($ruleKeys as $key) {
            foreach ((array) config('portal_ai.'.$key, []) as $rule) {
                if (is_string($rule) && trim($rule) !== '') {
                    $lines[] = trim($rule);
                }
            }
        }

        return implode("\n", array_map(
            fn (string $rule, int $i) => ($i + 1).'. '.$rule,
            $lines,
            array_keys($lines)
        ));
    }
}
