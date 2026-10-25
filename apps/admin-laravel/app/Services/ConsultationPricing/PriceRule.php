<?php

namespace App\Services\ConsultationPricing;

/**
 * Aturan tarif satu layanan konsultasi (service_type + session_role).
 */
interface PriceRule
{
    /**
     * Tarif untuk customer ini — yang dibayar dan disimpan di order.
     */
    public function quote(string $email): PriceQuote;

    /**
     * Tarif yang ditampilkan sebelum booking.
     */
    public function display(): PriceDisplay;

    public function requiresFhcu(): bool;
}
