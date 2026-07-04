<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeJsonService
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
        $models = (array) config('portal_ai.models', ['claude-sonnet-4-20250514']);
        $timeout = max(10, (int) config('portal_ai.timeout_seconds', 45));
        $temperature ??= (float) config('portal_ai.temperature', 0.3);
        $maxTokens = max(256, (int) config('portal_ai.max_tokens', 2048));

        foreach ($models as $model) {
            if (! is_string($model) || trim($model) === '') {
                continue;
            }

            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => (string) config('portal_ai.api_version', '2023-06-01'),
                        'content-type' => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model' => trim($model),
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'system' => (string) config('portal_ai.system_prompt', 'Balas hanya dengan JSON valid tanpa markdown atau penjelasan tambahan.'),
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Claude request failed', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            if (! $response->successful()) {
                Log::warning('Claude API error', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                continue;
            }

            $text = $this->extractText($response->json());
            if ($text === null) {
                continue;
            }

            $parsed = $this->parseJsonResponse($text);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractText(?array $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $parts = $payload['content'] ?? null;
        if (! is_array($parts)) {
            return null;
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }
            if (($part['type'] ?? '') === 'text' && is_string($part['text'] ?? null)) {
                $chunks[] = trim($part['text']);
            }
        }

        $text = trim(implode("\n", array_filter($chunks)));

        return $text !== '' ? $text : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonResponse(string $text): ?array
    {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/u', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $parsed = json_decode($text, true);

        return is_array($parsed) ? $parsed : null;
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
