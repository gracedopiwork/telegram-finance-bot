<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

class GoogleServiceAccountToken
{
    /**
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
}
