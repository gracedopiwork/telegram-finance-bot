<?php

namespace App\Console\Commands;

/**
 * @deprecated Gunakan order:send-delivery
 */
class SendOrderDeliveryEmailCommand extends SendOrderDeliveryCommand
{
    protected $signature = 'order:send-delivery-email
                            {order_code : Kode order, contoh YFD-IVYZWN1WOQ}
                            {--force : Kirim ulang meskipun sudah pernah dikirim}';

    protected $description = '[deprecated] Alias untuk order:send-delivery';
}
