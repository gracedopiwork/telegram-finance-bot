<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'finish_url'    => env('MIDTRANS_FINISH_URL'),
        'use_finish_callback' => env('MIDTRANS_USE_FINISH_CALLBACK', true),
        // Snap enabled_payments — default QRIS saja (other_qris). Pisahkan dengan koma jika perlu tambah metode.
        'enabled_payments' => env('MIDTRANS_ENABLED_PAYMENTS', 'other_qris'),
    ],

    'telegram' => [
        'bot_url' => env('TELEGRAM_BOT_URL'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    'bot' => [
        /** Token untuk POST /api/bot/* (sama dengan BOT_INTERNAL_API_TOKEN di bot-python/.env) */
        'internal_api_token' => env('BOT_INTERNAL_API_TOKEN', ''),
    ],

    'order_delivery' => [
        /** wa | email | both */
        'channel' => env('ORDER_DELIVERY_CHANNEL', 'email'),
    ],

    'fonnte' => [
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
        'token' => env('FONNTE_TOKEN'),
        'timeout' => (int) env('FONNTE_TIMEOUT', 30),
    ],

    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    /*
    | Google Business Profile (owner OAuth) — sync semua ulasan ke homepage carousel.
    | Prasyarat: project disetujui akses GBP API + enable Account Management,
    | Business Information, dan My Business API. Scope: business.manage
    | Redirect URI harus cocok dengan OAuth client (web):
    |   {APP_URL}/admin/google-reviews/callback
    */
    'google_business' => [
        'client_id' => env('GOOGLE_BUSINESS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_BUSINESS_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_BUSINESS_REDIRECT_URI', rtrim((string) env('APP_URL', ''), '/').'/admin/google-reviews/callback'),
    ],

];
