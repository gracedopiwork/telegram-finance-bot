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
    ],

    'google' => [
        'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),
        'user_sheet_template_id' => env('GOOGLE_USER_SHEET_TEMPLATE_ID'),
        /** Optional: Drive folder ID (disarankan Shared drive yang hanya berisi service account) */
        'copy_parent_folder_id' => env('GOOGLE_DRIVE_COPY_PARENT_ID'),
        'sheet_transaction_tab' => env('GOOGLE_SHEET_TRANSACTION_TAB', 'Transaksi'),
        'sheet_dashboard_tab' => env('GOOGLE_SHEET_DASHBOARD_TAB', 'Dashboard'),
        /**
         * Transfer ownership ke email checkout. Default false: transfer ke Gmail luar
         * sering menunggu persetujuan sehingga sheet tetap "Anda memerlukan akses".
         */
        'transfer_sheet_ownership' => env('GOOGLE_TRANSFER_SHEET_OWNERSHIP', false),
        /** Selalu izinkan siapa pun yang punya link (akun Google mana pun) */
        'sheet_anyone_with_link_reader' => env('GOOGLE_SHEET_ANYONE_WITH_LINK_READER', false),
        /** Jika share ke email checkout gagal, otomatis buka akses "siapa pun dengan link" */
        'sheet_fallback_link_reader' => env('GOOGLE_SHEET_FALLBACK_LINK_READER', true),
        /** Sembunyikan link sheet user di panel admin */
        'hide_user_sheet_from_admin' => env('GOOGLE_HIDE_USER_SHEET_FROM_ADMIN', true),
        /**
         * Salin file sebagai user Workspace ini (domain-wide delegation).
         * Mengatasi storageQuotaExceeded tanpa Shared drive. Contoh: yfinancialdoctor@gmail.com
         */
        'drive_impersonate_user' => env('GOOGLE_DRIVE_IMPERSONATE_USER'),
        /** Alternatif jika Domain-wide delegation tidak ada di Admin: OAuth refresh token akun Workspace Anda */
        'oauth_client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'oauth_refresh_token' => env('GOOGLE_OAUTH_REFRESH_TOKEN'),
    ],

    'telegram' => [
        'bot_url' => env('TELEGRAM_BOT_URL'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    'bot' => [
        /** Token untuk POST /api/bot/orders/{code}/ensure-sheet (sama dengan BOT_INTERNAL_API_TOKEN di bot-python/.env) */
        'internal_api_token' => env('BOT_INTERNAL_API_TOKEN', ''),
    ],

    'sync_dashboard' => [
        'python_binary' => env('SYNC_DASHBOARD_PYTHON', 'python'),
        'timeout_seconds' => (int) env('SYNC_DASHBOARD_TIMEOUT', 3600),
    ],

    'order_delivery' => [
        /** wa | email | both */
        'channel' => env('ORDER_DELIVERY_CHANNEL', 'wa'),
    ],

    'fonnte' => [
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
        'token' => env('FONNTE_TOKEN'),
        'timeout' => (int) env('FONNTE_TIMEOUT', 30),
    ],

];
