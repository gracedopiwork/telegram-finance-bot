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
        'unlock_product_codes' => array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            explode(',', (string) env('PORTAL_FTSA_UNLOCK_PRODUCT_CODES', 'yfd-ftsa-premium'))
        ))),
    ],
];
