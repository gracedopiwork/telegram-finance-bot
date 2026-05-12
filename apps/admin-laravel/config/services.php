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
    ],

    'google' => [
        'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),
        'user_sheet_template_id' => env('GOOGLE_USER_SHEET_TEMPLATE_ID'),
    ],

    'telegram' => [
        'bot_url' => env('TELEGRAM_BOT_URL'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    'sync_dashboard' => [
        'python_binary' => env('SYNC_DASHBOARD_PYTHON', 'python'),
        'timeout_seconds' => (int) env('SYNC_DASHBOARD_TIMEOUT', 3600),
    ],

];
