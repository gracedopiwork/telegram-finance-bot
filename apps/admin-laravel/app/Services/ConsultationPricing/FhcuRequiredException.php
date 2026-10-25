<?php

namespace App\Services\ConsultationPricing;

use RuntimeException;

/**
 * Layanan bertarif FHCU, tapi email belum punya FHCU ($status 'missing')
 * atau FHCU sudah lewat masa berlaku ($status 'expired').
 */
final class FhcuRequiredException extends RuntimeException
{
    public function __construct(
        public readonly string $status,
        string $message,
    ) {
        parent::__construct($message);
    }
}
