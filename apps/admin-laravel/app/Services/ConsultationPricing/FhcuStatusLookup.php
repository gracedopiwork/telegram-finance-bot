<?php

namespace App\Services\ConsultationPricing;

use App\Models\FinancialBaseline;
use App\Support\ConsultationPricing;
use Illuminate\Validation\ValidationException;

/**
 * Status FHCU terbaru untuk satu email (dipakai aturan tarif yang mewajibkan FHCU).
 */
class FhcuStatusLookup
{
    /**
     * Email wajib punya FHCU yang masih berlaku. Status 'no_stage' tetap dikembalikan
     * (FHCU ada) — aturan berbasis tahap yang memutuskan.
     *
     * @return array{valid: bool, status: string, baseline: ?FinancialBaseline, stage_key: ?string, message: string}
     *
     * @throws FhcuRequiredException belum ada FHCU ('missing') atau sudah lewat masa berlaku ('expired')
     * @throws ValidationException email kosong
     */
    public function requireFor(string $email): array
    {
        $status = $this->forEmail($email);

        if (in_array($status['status'], ['missing', 'expired'], true)) {
            throw new FhcuRequiredException($status['status'], $status['message']);
        }

        if ($status['status'] === 'missing_email') {
            throw ValidationException::withMessages(['email' => $status['message']]);
        }

        return $status;
    }

    /**
     * @return array{valid: bool, status: string, baseline: ?FinancialBaseline, stage_key: ?string, message: string}
     */
    public function forEmail(?string $email): array
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return [
                'valid' => false,
                'status' => 'missing_email',
                'baseline' => null,
                'stage_key' => null,
                'message' => 'Email diperlukan untuk memverifikasi Financial Health Check-Up.',
            ];
        }

        $baseline = FinancialBaseline::latestForEmail($email);
        if ($baseline === null) {
            return [
                'valid' => false,
                'status' => 'missing',
                'baseline' => null,
                'stage_key' => null,
                'message' => 'Belum ada data FHCU. Selesaikan Financial Health Check-Up terlebih dahulu.',
            ];
        }

        $stageKey = ConsultationPricing::normalizeStageKey($baseline->financial_stage ?: $baseline->stage_label);

        if ($baseline->isReviewDue()) {
            return [
                'valid' => false,
                'status' => 'expired',
                'baseline' => $baseline,
                'stage_key' => $stageKey,
                'message' => 'Data FHCU sudah lebih dari 3 bulan. Silakan isi ulang Check-Up sebelum booking.',
            ];
        }

        if ($stageKey === null) {
            return [
                'valid' => false,
                'status' => 'no_stage',
                'baseline' => $baseline,
                'stage_key' => null,
                'message' => 'Tahap finansial dari FHCU belum terdeteksi. Hubungi admin YFD atau isi ulang Check-Up.',
            ];
        }

        return [
            'valid' => true,
            'status' => 'valid',
            'baseline' => $baseline,
            'stage_key' => $stageKey,
            'message' => 'FHCU valid. Tarif mengikuti tahap '.$stageKey.'.',
        ];
    }
}
