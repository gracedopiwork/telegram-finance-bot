<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotClient
{
    public function configured(): bool
    {
        return $this->token() !== '';
    }

    public function sendMessage(int $chatId, string $text): bool
    {
        $token = $this->token();
        if ($token === '' || $chatId <= 0 || trim($text) === '') {
            return false;
        }

        try {
            $response = Http::timeout(15)->asForm()->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]
            );

            if (! $response->successful() || ! ($response->json('ok') ?? false)) {
                Log::warning('telegram.sendMessage failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('telegram.sendMessage exception: '.$e->getMessage(), [
                'chat_id' => $chatId,
            ]);

            return false;
        }
    }

    private function token(): string
    {
        return trim((string) config('services.telegram.bot_token', ''));
    }
}
