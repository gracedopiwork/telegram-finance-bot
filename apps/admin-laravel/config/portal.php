<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FTSA Premium Access
    |--------------------------------------------------------------------------
    | If enabled, FTSA (question 1-32) is unlocked only for users that have
    | paid orders on specific product codes.
    */
    'ftsa' => [
        'requires_upgrade' => (bool) env('PORTAL_FTSA_REQUIRES_UPGRADE', true),
        'evaluation_months' => (int) env('PORTAL_FTSA_EVALUATION_MONTHS', 12),
        'unlock_product_codes' => array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            explode(',', (string) env('PORTAL_FTSA_UNLOCK_PRODUCT_CODES', 'yfd-ftsa-premium'))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot-only buyers (isi baseline lengkap di portal, bukan landing check-up)
    |--------------------------------------------------------------------------
    */
    'bot_only_product_codes' => array_values(array_filter(array_map(
        fn (string $v) => trim($v),
        explode(',', (string) env('PORTAL_BOT_ONLY_PRODUCT_CODES', 'yfd-bot-telegram'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | FTSA-only portal user IDs (tanpa aktivasi Telegram)
    |--------------------------------------------------------------------------
    | assigned_user_id = base + license_id — harus unsigned BIGINT (bukan negatif).
    */
    'synthetic_user_id_base' => (int) env('PORTAL_SYNTHETIC_USER_ID_BASE', 9_000_000_000_000),
];
