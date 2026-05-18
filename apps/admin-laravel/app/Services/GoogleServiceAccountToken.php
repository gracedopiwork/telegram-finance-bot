<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class GoogleServiceAccountToken
{
    /**
     * Token untuk Drive/Sheets API: OAuth user (refresh token) atau service account (+ impersonate).
     *
     * @param  list<string>  $scopes
     */
    public static function get(array $scopes = []): string
    {
        if ($scopes === []) {
            $scopes = [
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/spreadsheets',
            ];
        }

        if (self::useOAuthRefreshToken()) {
            return self::oauthAccessToken();
        }

        $path = (string) config('services.google.service_account_json', '');
        if ($path === '' || ! is_readable($path)) {
            throw new \RuntimeException('GOOGLE_SERVICE_ACCOUNT_JSON tidak terbaca.');
        }

        $subject = trim((string) config('services.google.drive_impersonate_user', ''));
        $creds = new ServiceAccountCredentials(
            $scopes,
            $path,
            $subject !== '' ? $subject : null
        );

        $token = $creds->fetchAuthToken();
        if (empty($token['access_token'])) {
            throw new \RuntimeException('Gagal ambil access token Google: '.json_encode($token));
        }

        return (string) $token['access_token'];
    }

    public static function useOAuthRefreshToken(): bool
    {
        return trim((string) config('services.google.oauth_refresh_token', '')) !== ''
            && trim((string) config('services.google.oauth_client_id', '')) !== ''
            && trim((string) config('services.google.oauth_client_secret', '')) !== '';
    }

    public static function oauthAccessToken(): string
    {
        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => (string) config('services.google.oauth_client_id'),
            'client_secret' => (string) config('services.google.oauth_client_secret'),
            'refresh_token' => (string) config('services.google.oauth_refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OAuth refresh token gagal: '.$response->body());
        }

        $access = (string) $response->json('access_token', '');
        if ($access === '') {
            throw new \RuntimeException('OAuth: access_token kosong.');
        }

        return $access;
    }
}
